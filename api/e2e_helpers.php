<?php
/**
 * E2E test helpers — key-protected, automation only.
 * POST activate_buyer + mobile (+ key) after registration in e2e tests.
 */
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

$key = trim($_REQUEST['key'] ?? '');
if ($key !== 'mazad2026') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!$db_connected) {
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'Database unavailable']);
    exit;
}

$action = trim($_REQUEST['action'] ?? '');
$mobile = trim($_REQUEST['mobile'] ?? '');

if ($action === 'activate_buyer') {
    if (!preg_match('/^05\d{8}$/', $mobile)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'Invalid mobile']);
        exit;
    }
    $stmt = $conn->prepare("UPDATE users SET is_active=1, nafath_verified=1, sanad_limit=GREATEST(COALESCE(sanad_limit,0), 500000), wallet_balance=GREATEST(COALESCE(wallet_balance,0), 50000) WHERE mobile=? AND role='buyer'");
    $stmt->bind_param('s', $mobile);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();
    echo json_encode(['success' => $ok, 'mobile' => $mobile, 'activated' => $ok]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Unknown action']);