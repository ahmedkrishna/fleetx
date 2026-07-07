<?php
/**
 * FleetX Full End-to-End Test (live or local)
 * php tests/e2e_end_to_end.php
 * FLEETX_BASE_URL=https://mazadi.bearand.com php tests/e2e_end_to_end.php
 */
$base = rtrim(getenv('FLEETX_BASE_URL') ?: 'https://mazadi.bearand.com', '/');
$jar = tempnam(sys_get_temp_dir(), 'fx_e2e_');
$mobile = '05' . random_int(10000000, 99999999);
$email = 'e2e_' . time() . '_' . random_int(1000, 9999) . '@fleetx.test';
$pass = 'Test1234!';
$passed = 0;
$failed = 0;
$skipped = 0;

function req($url, $jar, $opts = []) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => !empty($opts['follow']),
        CURLOPT_TIMEOUT => 45,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
    ]);
    if (!empty($opts['post'])) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $opts['post']);
    }
    if (!empty($opts['json'])) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($opts['json']));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $body ?: ''];
}

function step($name, $ok, $detail = '', $skip = false) {
    global $passed, $failed, $skipped;
    if ($skip) {
        echo "[SKIP] $name" . ($detail ? " — $detail" : '') . "\n";
        $skipped++;
        return;
    }
    echo ($ok ? '[PASS]' : '[FAIL]') . " $name" . ($detail ? " — $detail" : '') . "\n";
    $ok ? $passed++ : $failed++;
}

echo "=== FleetX End-to-End ===\nBase: $base\nBuyer: $mobile / $email\n\n";

// ── Phase 1: Public pages ──────────────────────────────────
foreach ([
    'index' => '/',
    'login' => '/login.php',
    'register' => '/register.php',
    'auctions' => '/auctions.php',
    'companies' => '/companies.php',
] as $label => $path) {
    $r = req("$base$path", $jar);
    step("Public: $label", $r['code'] === 200, "HTTP {$r['code']}");
}

$r = req("$base/check_db.php", $jar);
step('Database connected', $r['code'] === 200 && str_contains($r['body'], 'Users:'), trim($r['body']));

// ── Phase 2: Register + Login ──────────────────────────────
$r = req("$base/register.php", $jar, ['post' => http_build_query([
    'register_submit' => '1', 'step' => '3', 'role' => 'buyer',
    'full_name' => 'E2E Buyer', 'mobile' => $mobile, 'password' => $pass,
    'national_id' => '1234567890', 'city' => 'الرياض', 'email' => $email,
])]);
step('Register buyer', str_contains($r['body'], 'success-screen') && str_contains($r['body'], 'تم إنشاء حسابك'));

$r = req("$base/login.php", $jar, [
    'post' => http_build_query(['login_type' => 'trader', 'mobile' => $mobile, 'password' => $pass]),
    'follow' => true,
]);
step('Login buyer', $r['code'] === 200 && !str_contains($r['body'], 'غير صحيحة'));

// ── Phase 3: Verification + wallet ─────────────────────────
$r = req("$base/nafath.php", $jar, ['post' => http_build_query(['verify_nafath' => '1']), 'follow' => true]);
step('Nafath verify', $r['code'] === 200 && !str_contains($r['body'], 'Fatal error'));

$r = req("$base/sanad.php", $jar, ['post' => http_build_query(['amount' => '2000000']), 'follow' => true]);
$sanad_ok = $r['code'] === 200 && !str_contains($r['body'], 'Fatal error') && !str_contains($r['body'], 'Duplicate column');
step('Sanad limit raise', $sanad_ok, $sanad_ok ? 'no fatal' : 'sanad.php needs deploy fix');

$r = req("$base/wallet-topup.php", $jar, ['post' => http_build_query(['amount' => '10000']), 'follow' => true]);
step('Wallet top-up', $r['code'] === 200);

$r = req("$base/api/notifications.php?action=list", $jar);
$nd = json_decode($r['body'], true);
step('Notifications API', ($nd['success'] ?? false) === true, 'unread=' . ($nd['unread_count'] ?? 0));

// ── Phase 4: Browse + find auction ─────────────────────────
$r = req("$base/companies.php", $jar);
step('Companies browse', $r['code'] === 200);

$r = req("$base/event.php?id=1", $jar);
preg_match('/auction-live\.php\?id=(\d+)/', $r['body'], $m);
$auction_id = (int)($m[1] ?? 0);
if (!$auction_id) {
    preg_match('/vehicle-details\.php\?id=(\d+)/', $r['body'], $m2);
    $auction_id = (int)($m2[1] ?? 0);
}
step('Find auction', $auction_id > 0, "id=$auction_id");

if ($auction_id) {
    $r = req("$base/auction-live.php?id=$auction_id", $jar);
    step('Auction live room', $r['code'] === 200);

    preg_match('/currentPrice\s*=\s*(\d+)/', $r['body'], $pm);
    $price = (int)($pm[1] ?? 80000);
    $bid = $price + max(500, (int)($price * 0.01));

    $r = req("$base/api/bid.php", $jar, ['json' => ['auction_id' => $auction_id, 'amount' => $bid]]);
    $bd = json_decode($r['body'], true);
    $bid_ok = ($bd['success'] ?? false) === true;
    step('Place bid', $bid_ok, $bd['message'] ?? substr($r['body'], 0, 120));

    $r = req("$base/api/get-bids.php?auction_id=$auction_id", $jar);
    step('Bid history API', $r['code'] === 200);
}

// ── Phase 5: Buyer dashboard ─────────────────────────────────
$r = req("$base/buyer.php?section=dashboard", $jar);
step('Buyer dashboard', $r['code'] === 200 && str_contains($r['body'], 'لوحة'));

$r = req("$base/buyer.php?section=favorites", $jar);
step('Buyer favorites', $r['code'] === 200);

// ── Phase 6: Role dashboards ─────────────────────────────────
@unlink($jar);
$jar3 = tempnam(sys_get_temp_dir(), 'fx_dash_');
foreach ([
    'buyer' => ['0501111111', 'trader', '/buyer.php?section=dashboard', 'لوحة'],
    'seller' => ['0500000002', 'company', '/seller.php?section=dashboard', 'لوحة التحكم'],
    'inspector' => ['0503333333', 'trader', '/inspector.php', 'فحص'],
] as $role => [$mob, $type, $path, $needle]) {
    req("$base/login.php", $jar3, ['post' => http_build_query(['login_type' => $type, 'mobile' => $mob, 'password' => '123456']), 'follow' => true]);
    $r = req("$base$path", $jar3);
    step("Dashboard: $role", $r['code'] === 200 && str_contains($r['body'], $needle), "HTTP {$r['code']}");
    @unlink($jar3);
    $jar3 = tempnam(sys_get_temp_dir(), 'fx_dash_');
}
@unlink($jar3);
$jar = tempnam(sys_get_temp_dir(), 'fx_e2e_');

// ── Phase 7: Demo accounts (optional) ────────────────────────
@unlink($jar);
$jar2 = tempnam(sys_get_temp_dir(), 'fx_demo_');
foreach ([
    'buyer' => ['0501111111', 'trader'],
    'seller' => ['0500000002', 'company'],
    'inspector' => ['0503333333', 'trader'],
] as $role => [$mob, $type]) {
    $r = req("$base/login.php", $jar2, [
        'post' => http_build_query(['login_type' => $type, 'mobile' => $mob, 'password' => '123456']),
        'follow' => true,
    ]);
    $ok = $r['code'] === 200 && !str_contains($r['body'], 'غير صحيحة');
    step("Demo login: $role", $ok, $ok ? $mob : 'run update_db_sanad.php?key=mazad2026 after upload');
}
@unlink($jar2);

// ── Phase 8: New pages deploy check ──────────────────────────
foreach (['add-auction.php', 'inspector-report.php', 'deploy_fix.php'] as $page) {
    $r = req("$base/$page", $jar);
    $deployed = in_array($r['code'], [200, 302], true) && !str_contains($r['body'], 'Page Does Not Exist');
    step("Deployed: $page", $deployed, "HTTP {$r['code']}");
}

@unlink($jar);

echo "\n=== RESULT: $passed passed, $failed failed, $skipped skipped ===\n";
if (!$sanad_ok) {
    echo "\nBLOCKER: Upload fixed sanad.php + run update_db_sanad.php?key=mazad2026\n";
}
exit($failed > 0 ? 1 : 0);