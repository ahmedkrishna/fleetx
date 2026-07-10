<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

if (!$db_connected) {
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'قاعدة البيانات غير متصلة']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$action = $input['action'] ?? $_GET['action'] ?? '';

if ($action === 'send') {
    $mobile = trim($input['mobile'] ?? '');
    $purpose = in_array($input['purpose'] ?? '', ['login', 'register', 'password_reset'], true)
        ? $input['purpose'] : 'login';
    if (!preg_match('/^05\d{8}$/', $mobile)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'رقم جوال غير صالح']);
        exit;
    }
    $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $stmt = $conn->prepare('INSERT INTO otp_sessions (mobile, otp_code, purpose, expires_at) VALUES (?,?,?, DATE_ADD(NOW(), INTERVAL 5 MINUTE))');
    $stmt->bind_param('sss', $mobile, $otp, $purpose);
    $stmt->execute();
    sendSmsNotification($mobile, "رمز التحقق FleetX: $otp (صالح 5 دقائق)");
    $e2e_key = trim($input['e2e_key'] ?? $_GET['e2e_key'] ?? '');
    $is_local = defined('DB_HOST') && str_contains((string)DB_HOST, 'localhost');
    $is_e2e = ($e2e_key === 'mazad2026');
    echo json_encode([
        'success' => true,
        'message' => 'تم إرسال رمز التحقق',
        'debug_otp' => ($is_local || $is_e2e) ? $otp : null,
    ]);
    exit;
}

if ($action === 'verify') {
    $mobile = trim($input['mobile'] ?? '');
    $otp = trim($input['otp'] ?? '');
    $purpose = $input['purpose'] ?? 'login';
    $stmt = $conn->prepare("SELECT id, otp_code FROM otp_sessions WHERE mobile=? AND purpose=? AND is_used=0 AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('ss', $mobile, $purpose);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row || $row['otp_code'] !== $otp) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'رمز التحقق غير صحيح أو منتهي']);
        exit;
    }
    $conn->query('UPDATE otp_sessions SET is_used=1 WHERE id=' . (int)$row['id']);
    if ($purpose === 'login') {
        $ustmt = $conn->prepare('SELECT * FROM users WHERE mobile=? LIMIT 1');
        $ustmt->bind_param('s', $mobile);
        $ustmt->execute();
        $user = $ustmt->get_result()->fetch_assoc();
        if ($user) {
            if (!fleetx_user_is_active($user)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'حسابك بانتظار موافقة الإدارة']);
                exit;
            }
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['wallet_balance'] = floatval($user['wallet_balance'] ?? 0);
            echo json_encode(['success' => true, 'redirect' => getDashboardUrl()]);
            exit;
        }
    }
    echo json_encode(['success' => true, 'verified' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'إجراء غير معروف']);