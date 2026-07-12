<?php
/**
 * WhatsApp live test — Taqnyat integration
 * GET /api/test-whatsapp.php?key=mazad2026&mobile=0501111111
 * Optional: token=...&template=...&session=1 (one-off override, not saved)
 */
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

$key = $_GET['key'] ?? $_POST['key'] ?? '';
if ($key !== 'mazad2026') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

$mobile = trim($_GET['mobile'] ?? $_POST['mobile'] ?? '0501111111');
$message = trim($_GET['message'] ?? $_POST['message'] ?? 'FleetX اختبار مباشر — ' . date('Y-m-d H:i:s'));
$save_token = !empty($_GET['save_token']) || !empty($_POST['save_token']);

$overrides = [];
if (!empty($_GET['token']) || !empty($_POST['token'])) {
    $overrides['token'] = trim($_GET['token'] ?? $_POST['token'] ?? '');
}
if (!empty($_GET['template']) || !empty($_POST['template'])) {
    $overrides['template'] = trim($_GET['template'] ?? $_POST['template'] ?? '');
}
if (!empty($_GET['lang']) || !empty($_POST['lang'])) {
    $overrides['lang'] = trim($_GET['lang'] ?? $_POST['lang'] ?? '');
}
if (!empty($_GET['session']) || !empty($_POST['session'])) {
    $overrides['session'] = true;
}

if ($save_token && $db_connected && !empty($overrides['token']) && fleetx_table_exists($conn, 'platform_settings')) {
    $pairs = [
        'whatsapp_api_token' => $overrides['token'],
        'whatsapp_template_name' => $overrides['template'] ?? fleetx_get_setting($conn, 'whatsapp_template_name', ''),
        'whatsapp_template_lang' => $overrides['lang'] ?? fleetx_get_setting($conn, 'whatsapp_template_lang', 'ar'),
    ];
    if (!empty($_GET['url']) || !empty($_POST['url'])) {
        $pairs['whatsapp_api_url'] = trim($_GET['url'] ?? $_POST['url'] ?? '');
    }
    foreach ($pairs as $k => $v) {
        if ($v === '') continue;
        $stmt = $conn->prepare('INSERT INTO platform_settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
        $stmt->bind_param('ss', $k, $v);
        $stmt->execute();
    }
}

$config = fleetx_whatsapp_config($conn, $overrides);
$result = sendWhatsAppNotification($mobile, $message, $conn, $overrides);

if (!is_array($result)) {
    $result = ['ok' => (bool)$result, 'mode' => $config['configured'] ? 'live' : 'log_only'];
}

echo json_encode([
    'ok' => $result['ok'] ?? false,
    'mode' => $result['mode'] ?? ($config['configured'] ? 'live' : 'log_only'),
    'configured' => $config['configured'],
    'mobile' => fleetx_normalize_mobile_api($mobile),
    'template' => $config['template'] ?: null,
    'lang' => $config['lang'],
    'http' => $result['http'] ?? 0,
    'response' => $result['response'] ?? '',
    'saved_token' => $save_token && !empty($overrides['token']),
    'message_preview' => mb_substr($message, 0, 80),
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);