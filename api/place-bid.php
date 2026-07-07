<?php
/**
 * api/place-bid.php — FormData-compatible bid endpoint (delegates to bid.php logic)
 */
require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'رفض الطلب.']);
    exit;
}

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول أولاً', 'redirect' => '/login.php']);
    exit;
}

$auction_id = intval($_POST['auction_id'] ?? 0);
$amount     = floatval($_POST['amount'] ?? 0);

if ($auction_id <= 0 || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'بيانات غير صالحة.']);
    exit;
}

if (!$db_connected) {
    echo json_encode(['success' => false, 'message' => 'قاعدة البيانات غير متصلة']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Nafath + Sanad checks
$ustmt = $conn->prepare("SELECT nafath_verified, sanad_limit FROM users WHERE id = ?");
$ustmt->bind_param('i', $user_id);
$ustmt->execute();
$ures = $ustmt->get_result();
if ($urow = $ures->fetch_assoc()) {
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
    $cntStmt = $conn->prepare("SELECT COUNT(*) as c FROM bids WHERE auction_id=?");
    $cntStmt->bind_param('i', $auction_id);
    $cntStmt->execute();
    $bidCount = (int)($cntStmt->get_result()->fetch_assoc()['c'] ?? 0);

    echo json_encode([
        'success'   => true,
        'message'   => 'تم تسجيل مزايدتك بنجاح!',
        'new_price' => number_format($result['new_price']),
        'bid_count' => $bidCount,
    ]);
} else {
    echo json_encode(['success' => false, 'message' => $result['error'] ?? 'حدث خطأ أثناء تسجيل المزايدة.']);
}