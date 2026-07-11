<?php
// api/bid.php — Live bid API (delegates to placeBid)
require_once '../config.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$auction_id = intval($data['auction_id'] ?? 0);
$amount     = floatval($data['amount'] ?? 0);

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول أولاً', 'redirect' => '/login.php']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

if (!$db_connected) {
    echo json_encode(['success' => false, 'message' => 'قاعدة البيانات غير متصلة']);
    exit;
}

if ($auction_id <= 0 || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'بيانات غير صالحة']);
    exit;
}

$ustmt = $conn->prepare('SELECT nafath_verified, sanad_limit FROM users WHERE id = ?');
$ustmt->bind_param('i', $user_id);
$ustmt->execute();
if ($urow = $ustmt->get_result()->fetch_assoc()) {
    if (!$urow['nafath_verified']) {
        echo json_encode(['success' => false, 'message' => 'يجب توثيق هويتك عبر نفاذ أولاً', 'redirect' => '/nafath.php']);
        exit;
    }
    $sanad_limit = floatval($urow['sanad_limit'] ?? 0);
    if ($sanad_limit <= 0) {
        $sanad_limit = floatval($_SESSION['sanad_limit'] ?? 0);
    }
    if ($amount > $sanad_limit) {
        echo json_encode(['success' => false, 'message' => 'مبلغ المزايدة يتجاوز الحد المالي لسند لأمر الخاص بك', 'redirect' => '/sanad.php']);
        exit;
    }
}

$result = placeBid($conn, $auction_id, $user_id, $amount);

if ($result['success']) {
    $cntStmt = $conn->prepare('SELECT COUNT(*) as c FROM bids WHERE auction_id=?');
    $cntStmt->bind_param('i', $auction_id);
    $cntStmt->execute();
    $bidCount = (int)($cntStmt->get_result()->fetch_assoc()['c'] ?? 0);

    echo json_encode([
        'success'      => true,
        'message'      => 'تمت مزايدتك بنجاح!',
        'new_price'    => $result['new_price'],
        'bid_count'    => $bidCount,
        'new_end_time' => $result['new_end_time'] ?? null,
    ]);
} else {
    echo json_encode(['success' => false, 'message' => $result['error'] ?? 'خطأ في المزايدة، حاول مجدداً']);
}