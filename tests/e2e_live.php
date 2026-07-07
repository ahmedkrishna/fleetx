<?php
/**
 * Live E2E — register → verify → bid flow
 * php tests/e2e_live.php
 */
$base = 'https://mazadi.bearand.com';
$jar = tempnam(sys_get_temp_dir(), 'live_');
$pass = 'Test1234!';
$mobile = '05' . random_int(10000000, 99999999);
$email = 'live_' . time() . '@fleetx.test';
$passed = 0; $failed = 0;

function rq($url, $jar, $opts = []) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => !empty($opts['follow']),
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_TIMEOUT => 40,
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

function ok($n, $cond, $d = '') {
    global $passed, $failed;
    echo ($cond ? '[PASS]' : '[FAIL]') . " $n" . ($d ? " — $d" : '') . "\n";
    $cond ? $passed++ : $failed++;
}

echo "=== Live E2E ===\n";

$r = rq("$base/register.php", $jar, ['post' => http_build_query([
    'register_submit'=>'1','step'=>'3','role'=>'buyer','full_name'=>'E2E Bot',
    'mobile'=>$mobile,'password'=>$pass,'national_id'=>'1234567890','city'=>'الرياض','email'=>$email,
])]);
ok('1. Register', str_contains($r['body'], 'success-screen'), "mobile=$mobile");

$r = rq("$base/login.php", $jar, ['post' => http_build_query(['login_type'=>'trader','mobile'=>$mobile,'password'=>$pass]), 'follow'=>true]);
ok('2. Login', !str_contains($r['body'], 'غير صحيحة'));

rq("$base/nafath.php", $jar, ['post' => http_build_query(['verify_nafath'=>'1']), 'follow'=>true]);
$r = rq("$base/sanad.php", $jar, ['post' => http_build_query(['amount'=>'2000000']), 'follow'=>true]);
$sanad_ok = !str_contains($r['body'], 'Fatal error');
ok('3. Sanad (no fatal)', $sanad_ok);

$r = rq("$base/api/notifications.php?action=list", $jar);
$nd = json_decode($r['body'], true);
ok('4. Auth session', ($nd['success'] ?? false));

$r = rq("$base/event.php?id=1", $jar);
preg_match('/auction-live\.php\?id=(\d+)/', $r['body'], $m);
$auction_id = (int)($m[1] ?? 0);
if (!$auction_id) {
    preg_match('/vehicle-details\.php\?id=(\d+)/', $r['body'], $m2);
    $auction_id = (int)($m2[1] ?? 0);
}
ok('5. Find auction', $auction_id > 0, "id=$auction_id");

if ($auction_id) {
    $r = rq("$base/auction-live.php?id=$auction_id", $jar);
    ok('6. Auction live page', $r['code'] === 200);

    preg_match('/currentPrice\s*=\s*(\d+)/', $r['body'], $pm);
    $price = (int)($pm[1] ?? 80000);
    $bid = $price + 500;

    $r = rq("$base/api/bid.php", $jar, ['json' => ['auction_id' => $auction_id, 'amount' => $bid]]);
    $bd = json_decode($r['body'], true);
    ok('7. Place bid', ($bd['success'] ?? false), $bd['message'] ?? substr($r['body'], 0, 100));

    $r = rq("$base/api/get-bids.php?auction_id=$auction_id", $jar);
    ok('8. Bid history API', $r['code'] === 200);
}

$r = rq("$base/buyer.php?section=dashboard", $jar);
ok('9. Buyer dashboard', $r['code'] === 200 && str_contains($r['body'], 'لوحة'));

@unlink($jar);
echo "\n=== $passed passed, $failed failed ===\n";
exit($failed ? 1 : 0);