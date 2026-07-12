<?php
/**
 * SMS live test — Taqnyat integration
 * GET /api/test-sms.php?key=mazad2026&mobile=0501111111
 * Optional: token=...&sender=...&save_token=1 (one-off override, not saved)
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
$message = trim($_GET['message'] ?? $_POST['message'] ?? 'FleetX اختبار SMS — ' . date('Y-m-d H:i:s'));
$save_token = !empty($_GET['save_token']) || !empty($_POST['save_token']);

$overrides = [];
if (!empty($_GET['token']) || !empty($_POST['token'])) {
    $overrides['token'] = trim($_GET['token'] ?? $_POST['token'] ?? '');
}
if (!empty($_GET['sender']) || !empty($_POST['sender'])) {
    $overrides['sender'] = trim($_GET['sender'] ?? $_POST['sender'] ?? '');
}
if (!empty($_GET['url']) || !empty($_POST['url'])) {
    $overrides['url'] = trim($_GET['url'] ?? $_POST['url'] ?? '');
}

if ($save_token && $db_connected && !empty($overrides['token']) && fleetx_table_exists($conn, 'platform_settings')) {
    $pairs = [
        'sms_api_token' => $overrides['token'],
        'sms_sender_name' => $overrides['sender'] ?? fleetx_get_setting($conn, 'sms_sender_name', ''),
    ];
    if (!empty($overrides['url'])) {
        $pairs['sms_api_url'] = $overrides['url'];
    }
    foreach ($pairs as $k => $v) {
        if ($v === '') continue;
        $stmt = $conn->prepare('INSERT INTO platform_settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
        $stmt->bind_param('ss', $k, $v);
        $stmt->execute();
    }
}

$config = fleetx_sms_config($conn, $overrides);
$result = sendSmsNotification($mobile, $message, $conn, $overrides);

if (!is_array($result)) {
    $result = ['ok' => (bool)$result, 'mode' => $config['configured'] ? 'live' : 'log_only'];
}

echo json_encode([
    'ok' => $result['ok'] ?? false,
    'mode' => $result['mode'] ?? ($config['configured'] ? 'live' : 'log_only'),
    'configured' => $config['configured'],
    'mobile' => fleetx_normalize_mobile_api($mobile),
    'sender' => $config['sender'] ?: null,
    'http' => $result['http'] ?? 0,
    'response' => $result['response'] ?? '',
    'saved_token' => $save_token && !empty($overrides['token']),
    'message_preview' => mb_substr($message, 0, 80),
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);