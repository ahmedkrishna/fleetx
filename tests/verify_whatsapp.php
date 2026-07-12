<?php
/**
 * Live WhatsApp integration verify
 * php tests/verify_whatsapp.php
 * Env: WHATSAPP_API_TOKEN, WHATSAPP_TEMPLATE_NAME (optional one-off test)
 */
$base = rtrim(getenv('FLEETX_BASE_URL') ?: 'https://mazadi.bearand.com', '/');
$pass = 0;
$fail = 0;

function wa_check($ok, $label, $detail = '') {
    global $pass, $fail;
    echo ($ok ? '[PASS]' : '[FAIL]') . " $label" . ($detail ? " — $detail" : '') . "\n";
    $ok ? $pass++ : $fail++;
}

function wa_get($url) {
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

echo "=== FleetX WhatsApp Live Verify ===\nBase: $base\n\n";

$r = wa_get("$base/api/test-whatsapp.php?key=mazad2026&mobile=0501111111");
wa_check($r['code'] === 200, 'Test endpoint reachable', "HTTP {$r['code']}");

$data = json_decode($r['body'], true);
wa_check(is_array($data), 'Test endpoint returns JSON');
wa_check(isset($data['mode']), 'Response includes mode', $data['mode'] ?? '');

$configured = !empty($data['configured']);
$token = getenv('WHATSAPP_API_TOKEN') ?: '';
$template = getenv('WHATSAPP_TEMPLATE_NAME') ?: '';

if ($token !== '') {
    $qs = http_build_query([
        'key' => 'mazad2026',
        'mobile' => getenv('WHATSAPP_TEST_MOBILE') ?: '0501111111',
        'token' => $token,
        'template' => $template,
        'message' => 'FleetX verify ' . date('H:i:s'),
    ]);
    if (getenv('WHATSAPP_SESSION_MODE')) $qs .= '&session=1';
    $live = wa_get("$base/api/test-whatsapp.php?$qs");
    $live_data = json_decode($live['body'], true);
    wa_check(($live_data['mode'] ?? '') === 'live', 'Live mode with env token');
    wa_check(!empty($live_data['ok']), 'API send accepted', 'HTTP ' . ($live_data['http'] ?? 0) . ' ' . substr($live_data['response'] ?? '', 0, 80));
} elseif ($configured) {
    $live = wa_get("$base/api/test-whatsapp.php?key=mazad2026&mobile=0501111111&message=" . urlencode('FleetX verify ' . date('H:i:s')));
    $live_data = json_decode($live['body'], true);
    wa_check(($live_data['mode'] ?? '') === 'live', 'Server has stored token');
    wa_check(!empty($live_data['ok']), 'API send accepted', 'HTTP ' . ($live_data['http'] ?? 0));
} else {
    wa_check(($data['mode'] ?? '') === 'log_only', 'Log-only mode when no token');
    echo "[INFO] Set WHATSAPP_API_TOKEN + WHATSAPP_TEMPLATE_NAME env or save token in Admin → Settings for live send.\n";
    echo "[INFO] Or: api/test-whatsapp.php?key=mazad2026&token=...&template=...&save_token=1\n";
}

echo "\n=== Results: $pass passed, $fail failed ===\n";
exit($fail > 0 ? 1 : 0);