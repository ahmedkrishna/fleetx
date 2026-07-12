<?php
/**
 * Live SMS integration verify
 * php tests/verify_sms.php
 * Env: SMS_API_TOKEN, SMS_SENDER_NAME (optional one-off test)
 */
$base = rtrim(getenv('FLEETX_BASE_URL') ?: 'https://mazadi.bearand.com', '/');
$pass = 0;
$fail = 0;

function sms_check($ok, $label, $detail = '') {
    global $pass, $fail;
    echo ($ok ? '[PASS]' : '[FAIL]') . " $label" . ($detail ? " — $detail" : '') . "\n";
    $ok ? $pass++ : $fail++;
}

function sms_get($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $body ?: ''];
}

echo "=== FleetX SMS Live Verify ===\nBase: $base\n\n";

$r = sms_get("$base/api/test-sms.php?key=mazad2026&mobile=0501111111");
sms_check($r['code'] === 200, 'Test endpoint reachable', "HTTP {$r['code']}");

$data = json_decode($r['body'], true);
sms_check(is_array($data), 'Test endpoint returns JSON');
sms_check(isset($data['mode']), 'Response includes mode', $data['mode'] ?? '');

$configured = !empty($data['configured']);
$token = getenv('SMS_API_TOKEN') ?: getenv('SMS_API_KEY') ?: '';
$sender = getenv('SMS_SENDER_NAME') ?: '';

if ($token !== '' && $sender !== '') {
    $qs = http_build_query([
        'key' => 'mazad2026',
        'mobile' => getenv('SMS_TEST_MOBILE') ?: '0501111111',
        'token' => $token,
        'sender' => $sender,
        'message' => 'FleetX SMS verify ' . date('H:i:s'),
    ]);
    $live = sms_get("$base/api/test-sms.php?$qs");
    $live_data = json_decode($live['body'], true);
    sms_check(($live_data['mode'] ?? '') === 'live', 'Live mode with env token');
    sms_check(!empty($live_data['ok']), 'API send accepted', 'HTTP ' . ($live_data['http'] ?? 0) . ' ' . substr($live_data['response'] ?? '', 0, 80));
} elseif ($configured) {
    $live = sms_get("$base/api/test-sms.php?key=mazad2026&mobile=0501111111&message=" . urlencode('FleetX SMS verify ' . date('H:i:s')));
    $live_data = json_decode($live['body'], true);
    sms_check(($live_data['mode'] ?? '') === 'live', 'Server has stored token + sender');
    sms_check(!empty($live_data['ok']), 'API send accepted', 'HTTP ' . ($live_data['http'] ?? 0));
} else {
    sms_check(($data['mode'] ?? '') === 'log_only', 'Log-only mode when no token/sender');
    echo "[INFO] Set SMS_API_TOKEN + SMS_SENDER_NAME env or save in Admin → Settings for live send.\n";
    echo "[INFO] Or: api/test-sms.php?key=mazad2026&token=...&sender=...&save_token=1\n";
}

echo "\n=== Results: $pass passed, $fail failed ===\n";
exit($fail > 0 ? 1 : 0);