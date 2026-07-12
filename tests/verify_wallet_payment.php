<?php
/**
 * Live wallet top-up + notification flow
 * php tests/verify_wallet_payment.php
 */
$base = rtrim(getenv('FLEETX_BASE_URL') ?: 'https://mazadi.bearand.com', '/');
$jar = tempnam(sys_get_temp_dir(), 'fx_wal_');
$pass = 0;
$fail = 0;

function wal_req($url, $jar, $opts = []) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_FOLLOWLOCATION => !empty($opts['follow']),
        CURLOPT_HEADER => !empty($opts['header']),
    ]);
    if (!empty($opts['post'])) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $opts['post']);
    }
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $body ?: '', 'headers' => !empty($opts['header']) ? ($body ?: '') : ''];
}

function wal_check($ok, $label, $detail = '') {
    global $pass, $fail;
    echo ($ok ? '[PASS]' : '[FAIL]') . " $label" . ($detail ? " — $detail" : '') . "\n";
    $ok ? $pass++ : $fail++;
}

function wal_loc($headers, $base) {
    if (preg_match('/^Location:\s*(\S+)/mi', $headers, $m)) {
        $loc = trim($m[1]);
        return str_starts_with($loc, '/') ? rtrim($base, '/') . $loc : $loc;
    }
    return '';
}

echo "=== FleetX Wallet + Notifications Verify ===\nBase: $base\n\n";

$login = wal_req("$base/login.php", $jar, [
    'post' => http_build_query(['login_type' => 'trader', 'mobile' => '0501111111', 'password' => '123456']),
    'follow' => true,
]);
wal_check($login['code'] === 200, 'Buyer login');

$page = wal_req("$base/wallet-topup.php", $jar, ['follow' => true]);
wal_check($page['code'] === 200, 'Wallet topup page loads');
wal_check(str_contains($page['body'], 'payment_method') || str_contains($page['body'], 'متابعة للدفع'), 'Wallet shows payment method form');

$init = wal_req("$base/wallet-topup.php", $jar, [
    'post' => http_build_query(['amount' => 5000, 'payment_method' => 'mada']),
    'header' => true,
]);
$loc = wal_loc($init['headers'], $base);
wal_check($init['code'] === 302 && $loc !== '', 'Wallet POST redirects to gateway', $loc);
wal_check(str_contains($loc, 'payment-gateway.php') && str_contains($loc, 'ref=FXW-'), 'Wallet uses FXW- payment reference', $loc);

if ($loc) {
    $gw = wal_req($loc, $jar, ['follow' => true]);
    wal_check($gw['code'] === 200, 'Wallet gateway page loads');
    wal_check(str_contains($gw['body'], 'شحن المحفظة') || str_contains($gw['body'], 'بوابة الدفع'), 'Gateway shows wallet context');
    preg_match('/name="ref"\s+value="([^"]+)"/', $gw['body'], $rm);
    $ref = $rm[1] ?? '';
    wal_check($ref !== '', 'Wallet ref extracted', $ref);

    $notif_before = wal_req("$base/api/notifications.php", $jar);
    $nb = json_decode($notif_before['body'], true);
    $unread_before = (int)($nb['unread'] ?? 0);

    if ($ref) {
        $done = wal_req("$base/payment-return.php", $jar, [
            'post' => http_build_query(['ref' => $ref, 'confirm' => '1']),
            'header' => true,
        ]);
        $done_loc = wal_loc($done['headers'], $base);
        wal_check(($done['code'] === 302) && str_contains($done_loc, 'wallet'), 'Wallet payment completes', $done_loc);

        $notif_after = wal_req("$base/api/notifications.php", $jar);
        $na = json_decode($notif_after['body'], true);
        $unread_after = (int)($na['unread'] ?? 0);
        wal_check($unread_after >= $unread_before, 'In-app notification created after wallet topup', "unread $unread_before -> $unread_after");
    }
}

@unlink($jar);
echo "\nResult: $pass passed, $fail failed\n";
echo "Note: WhatsApp/SMS log to server logs/notifications.log; set WHATSAPP_API_URL for live push.\n";
exit($fail > 0 ? 1 : 0);