<?php
/**
 * Verify live API endpoints — php tests/verify_live_apis.php
 */
$base = rtrim(getenv('FLEETX_BASE_URL') ?: 'https://mazadi.bearand.com', '/');
$jar = tempnam(sys_get_temp_dir(), 'fx_api_');
$pass = 0;
$fail = 0;

function api_req($url, $jar, $opts = []) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_FOLLOWLOCATION => !empty($opts['follow']),
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
    return ['code' => $code, 'body' => $body ?: '', 'json' => json_decode($body ?: '{}', true)];
}

function check($ok, $label, $detail = '') {
    global $pass, $fail;
    echo ($ok ? '[PASS]' : '[FAIL]') . " $label" . ($detail ? " — $detail" : '') . "\n";
    $ok ? $pass++ : $fail++;
}

echo "=== FleetX Live API Verification ===\nBase: $base\n\n";

// ── Public / unauthenticated ───────────────────────────────
$r = api_req("$base/api/get-bids.php", $jar);
check($r['code'] === 200 && ($r['json']['success'] ?? null) === false, 'get-bids.php missing id rejects', $r['json']['message'] ?? '');

$r = api_req("$base/api/get-bids.php?auction_id=1", $jar);
$gb = $r['json'];
check($r['code'] === 200 && ($gb['success'] ?? false) === true, 'get-bids.php auction_id=1', 'price=' . ($gb['current_price'] ?? '?') . ' bids=' . count($gb['bids'] ?? []));
check(!str_contains($r['body'], 'bot_user_id') && !str_contains($r['body'], '127.0.0.1'), 'get-bids.php no bot simulation in source response');

$r = api_req("$base/api/bid.php", $jar, ['json' => ['auction_id' => 1, 'amount' => 999999]]);
check(($r['json']['success'] ?? null) === false && str_contains($r['json']['message'] ?? '', 'تسجيل'), 'bid.php requires login', $r['json']['message'] ?? '');

$r = api_req("$base/api/place-bid.php", $jar, ['post' => http_build_query(['auction_id' => 1, 'amount' => 50000])]);
check(($r['json']['success'] ?? null) === false, 'place-bid.php requires login', $r['json']['message'] ?? '');

$r = api_req("$base/api/toggle_favorite.php", $jar, ['json' => ['id' => 1]]);
check(($r['json']['success'] ?? null) === false, 'toggle_favorite.php requires login', $r['json']['message'] ?? '');

$r = api_req("$base/api/notifications.php?action=list", $jar);
check(($r['json']['success'] ?? null) === false || isset($r['json']['notifications']), 'notifications.php responds JSON', 'keys=' . implode(',', array_keys($r['json'] ?? [])));

// ── Authenticated buyer ────────────────────────────────────
api_req("$base/login.php", $jar, [
    'post' => http_build_query(['login_type' => 'trader', 'mobile' => '0501111111', 'password' => '123456']),
    'follow' => true,
]);

$r = api_req("$base/api/notifications.php?action=list", $jar);
$nd = $r['json'];
check(($nd['success'] ?? false) === true, 'notifications.php authenticated', 'unread=' . ($nd['unread_count'] ?? 0));

$r = api_req("$base/api/toggle_favorite.php", $jar, ['json' => ['id' => 1]]);
$tf = $r['json'];
check(($tf['success'] ?? false) === true, 'toggle_favorite.php toggle', 'fav=' . (($tf['is_favorite'] ?? false) ? '1' : '0'));

// Restore favorite state
api_req("$base/api/toggle_favorite.php", $jar, ['json' => ['id' => 1]]);

// Find live auction
$r = api_req("$base/auctions.php?type=live", $jar);
preg_match('/auction-live\.php\?id=(\d+)/', $r['body'], $m);
if (!$m) preg_match('/vehicle-details\.php\?id=(\d+)/', $r['body'], $m);
$auction_id = (int)($m[1] ?? 1);

$r = api_req("$base/api/get-bids.php?auction_id=$auction_id", $jar);
$gb2 = $r['json'];
$price_before = (float)($gb2['current_price'] ?? 0);
check($price_before > 0, 'get-bids.php live auction price', "id=$auction_id price=$price_before");

$r = api_req("$base/api/bid.php", $jar, ['json' => ['auction_id' => $auction_id, 'amount' => $price_before + 5000]]);
$bd = $r['json'];
check(($bd['success'] ?? false) === true, 'bid.php place bid', $bd['message'] ?? substr($r['body'], 0, 80));
check(isset($bd['new_price']) && isset($bd['bid_count']), 'bid.php returns new_price + bid_count', 'price=' . ($bd['new_price'] ?? '?'));

sleep(1);
$r = api_req("$base/api/get-bids.php?auction_id=$auction_id", $jar);
$price_after = (float)($r['json']['current_price'] ?? 0);
check($price_after >= $price_before, 'get-bids.php price updated after bid', "$price_before -> $price_after");

$r = api_req("$base/api/place-bid.php", $jar, ['post' => http_build_query(['auction_id' => $auction_id, 'amount' => $price_after + 5000])]);
$pbd = $r['json'];
check(($pbd['success'] ?? false) === true, 'place-bid.php FormData bid', $pbd['message'] ?? '');

@unlink($jar);
echo "\nResult: $pass passed, $fail failed\n";
exit($fail > 0 ? 1 : 0);