<?php
/**
 * Google Sign-In — verifies GIS credential and logs in / registers user.
 */
require_once dirname(__DIR__) . '/config.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$credential = trim($input['credential'] ?? '');
if ($credential === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing credential']);
    exit;
}

$verify = @file_get_contents('https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($credential));
$payload = $verify ? json_decode($verify, true) : null;
if (!$payload || empty($payload['email'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'رمز Google غير صالح']);
    exit;
}

$client_id = defined('GOOGLE_CLIENT_ID') ? GOOGLE_CLIENT_ID : '';
if ($client_id !== '' && ($payload['aud'] ?? '') !== $client_id) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'تطبيق Google غير مطابق']);
    exit;
}

$email = strtolower(trim($payload['email']));
$name = trim($payload['name'] ?? $payload['given_name'] ?? 'مستخدم Google');
$google_id = $payload['sub'] ?? '';

if (!$db_connected) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'خطأ في قاعدة البيانات']);
    exit;
}

$has_google_col = false;
$col_chk = $conn->query("SHOW COLUMNS FROM users LIKE 'google_id'");
if ($col_chk && $col_chk->num_rows > 0) $has_google_col = true;

$user = null;
if ($has_google_col) {
    $stmt = $conn->prepare('SELECT * FROM users WHERE email = ? OR google_id = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('ss', $email, $google_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
} else {
    $stmt = $conn->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

if (!$user) {
    $mobile = '05' . substr(preg_replace('/\D/', '', md5($google_id)), 0, 8);
    $check = $conn->prepare('SELECT id FROM users WHERE mobile = ? LIMIT 1');
    $check->bind_param('s', $mobile);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $mobile = '05' . substr(md5($google_id . time()), 0, 8);
    }
    $check->close();

    $hash = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
    $role = 'buyer';
    if ($has_google_col) {
        $ins = $conn->prepare('INSERT INTO users (full_name, mobile, email, password_hash, role, google_id, nafath_verified, wallet_balance, is_active) VALUES (?,?,?,?,?,?,1,0,1)');
        if ($ins) {
            $ins->bind_param('ssssss', $name, $mobile, $email, $hash, $role, $google_id);
            $ins->execute();
            $ins->close();
        }
    } else {
        $ins = $conn->prepare('INSERT INTO users (full_name, mobile, email, password_hash, role, nafath_verified, wallet_balance, is_active) VALUES (?,?,?,?,?,1,0,1)');
        if ($ins) {
            $ins->bind_param('sssss', $name, $mobile, $email, $hash, $role);
            $ins->execute();
            $ins->close();
        }
    }
    $uid = (int)$conn->insert_id;
    if ($uid) {
        $user = ['id' => $uid, 'full_name' => $name, 'mobile' => $mobile, 'email' => $email, 'role' => $role, 'wallet_balance' => 0, 'nafath_verified' => 1, 'sanad_limit' => 0, 'city' => ''];
        notifyUser($conn, $uid, 'auth', 'مرحباً بك في FleetX', 'تم إنشاء حسابك عبر Google بنجاح.', '/buyer.php', ['in_app', 'whatsapp']);
    }
}

if (!$user) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'تعذّر تسجيل الدخول']);
    exit;
}

if (!fleetx_user_is_active($user)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'حسابك بانتظار موافقة الإدارة']);
    exit;
}

if ($has_google_col && empty($user['google_id']) && $google_id) {
    $upd = $conn->prepare('UPDATE users SET google_id = ?, email = COALESCE(NULLIF(email,""), ?) WHERE id = ?');
    if ($upd) {
        $upd->bind_param('ssi', $google_id, $email, $user['id']);
        $upd->execute();
        $upd->close();
    }
}

$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['user_name'] = $user['full_name'];
$_SESSION['role'] = $user['role'];
$_SESSION['user_role'] = $user['role'];
$_SESSION['wallet_balance'] = floatval($user['wallet_balance'] ?? 0);
$_SESSION['nafath_verified'] = (int)($user['nafath_verified'] ?? 0);
$_SESSION['sanad_limit'] = floatval($user['sanad_limit'] ?? 0);
$_SESSION['user_phone'] = $user['mobile'] ?? '';
$_SESSION['user_city'] = $user['city'] ?? '';

$redirect = fleetx_safe_redirect($input['redirect'] ?? null, getBuyerLandingUrl());
notifyUser($conn, (int)$user['id'], 'auth', 'تسجيل دخول ناجح', 'تم تسجيل دخولك عبر Google.', $redirect, ['in_app']);

echo json_encode(['ok' => true, 'redirect' => $redirect]);