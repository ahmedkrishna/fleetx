<?php
/**
 * Full E2E flow — register → verify → bid → notify
 * php tests/e2e_flow.php
 */
$base = rtrim(getenv('FLEETX_BASE_URL') ?: 'https://mazadi.bearand.com', '/');
$jar = tempnam(sys_get_temp_dir(), 'fx_flow_');
$mobile = '05' . random_int(10000000, 99999999);
$pass = 'Test1234!';
$passed = 0;
$failed = 0;

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

function step($name, $ok, $detail = '') {
    global $passed, $failed;
    echo ($ok ? '[PASS]' : '[FAIL]') . " $name" . ($detail ? " — $detail" : '') . "\n";
    $ok ? $passed++ : $failed++;
}

echo "=== FleetX Full E2E Flow ===\nBase: $base\nTest mobile: $mobile\n\n";

// 1. Register new buyer
$r = req("$base/register.php", $jar, ['post' => http_build_query([
    'register_submit' => '1', 'step' => '3', 'role' => 'buyer',
    'full_name' => 'E2E Test User', 'mobile' => $mobile, 'password' => $pass,
    'national_id' => '1234567890', 'city' => 'الرياض', 'email' => 'e2e@test.com',
])]);
step('Register buyer', str_contains($r['body'], 'تم إنشاء حسابك'));

// 2. Login
$r = req("$base/login.php", $jar, ['post' => http_build_query([
    'login_type' => 'trader', 'mobile' => $mobile, 'password' => $pass,
])]);
step('Login buyer', $r['code'] === 200 && !str_contains($r['body'], 'غير صحيحة'));

// 3. Nafath
$r = req("$base/nafath.php", $jar, ['post' => http_build_query(['verify_nafath' => '1'])]);
step('Nafath verify', $r['code'] === 200);

// 4. Sanad limit
$r = req("$base/sanad.php", $jar, ['post' => http_build_query(['amount' => '500000'])]);
step('Sanad limit', $r['code'] === 200);

// 5. Companies
$r = req("$base/companies.php", $jar);
step('Companies page', $r['code'] === 200);

// 6. Find auction via event
$r = req("$base/auctions.php", $jar);
preg_match('/event\.php\?id=(\d+)/', $r['body'], $em);
$event_id = (int)($em[1] ?? 0);
step('Find auction event', $event_id > 0, "event_id=$event_id");

$auction_id = 0;
if ($event_id) {
    $r = req("$base/event.php?id=$event_id", $jar);
    preg_match('/auction-room\.php\?id=(\d+)/', $r['body'], $am);
    if (!$am) preg_match('/vehicle-details\.php\?id=(\d+)/', $r['body'], $am);
    $auction_id = (int)($am[1] ?? 0);
}
if (!$auction_id) {
    preg_match('/vehicle-details\.php\?id=(\d+)/', $r['body'] ?? '', $vm);
    $auction_id = (int)($vm[1] ?? 0);
}
step('Resolve auction id', $auction_id > 0, "auction_id=$auction_id");

// 7. Auction room
if ($auction_id) {
    $r = req("$base/auction-room.php?id=$auction_id", $jar);
    step('Auction room', $r['code'] === 200);

    preg_match('/currentPrice\s*=\s*(\d+)/', $r['body'], $pm);
    if (!$pm) preg_match('/"current_price"\s*:\s*(\d+)/', $r['body'], $pm);
    $price = (int)($pm[1] ?? 75000);
    $bid = $price + 1500;

    $r = req("$base/api/bid.php", $jar, ['json' => ['auction_id' => $auction_id, 'amount' => $bid]]);
    $data = json_decode($r['body'], true);
    step('Place bid', ($data['success'] ?? false) === true, $data['message'] ?? $r['body']);
}

// 8. Notifications
$r = req("$base/api/notifications.php?action=list", $jar);
$nd = json_decode($r['body'], true);
step('Notifications authed', ($nd['success'] ?? false) === true, 'unread=' . ($nd['unread_count'] ?? 0));

// 9. Wallet topup
$r = req("$base/wallet-topup.php", $jar, ['post' => http_build_query(['amount' => '5000']), 'follow' => true]);
step('Wallet top-up', $r['code'] === 200);

// 10. Buyer dashboard
$r = req("$base/buyer.php?section=dashboard", $jar);
step('Buyer dashboard', $r['code'] === 200 && str_contains($r['body'], 'لوحة'));

@unlink($jar);
echo "\n=== RESULT: $passed passed, $failed failed ===\n";
exit($failed > 0 ? 1 : 0);