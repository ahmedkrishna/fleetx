<?php
require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

$auction_id = isset($_GET['auction_id']) ? intval($_GET['auction_id']) : 0;

if ($auction_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'معرف المزاد غير صالح.']);
    exit;
}

if (!$db_connected) {
    echo json_encode(['success' => false, 'message' => 'قاعدة البيانات غير متصلة']);
    exit;
}

$bids = [];
$current_price = 0;
$status = 'active';

$stmt = $conn->prepare('SELECT current_price, status FROM auctions WHERE id = ?');
$stmt->bind_param('i', $auction_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $auction = $res->fetch_assoc();
    $current_price = $auction['current_price'];
    $status = $auction['status'];
}

$stmt_bids = $conn->prepare('
    SELECT b.*, u.full_name as user_name
    FROM bids b
    LEFT JOIN users u ON b.user_id = u.id
    WHERE auction_id = ?
    ORDER BY created_at DESC
    LIMIT 50
');
$stmt_bids->bind_param('i', $auction_id);
$stmt_bids->execute();
$res_bids = $stmt_bids->get_result();

$currentUser = $_SESSION['user_id'] ?? 0;

while ($row = $res_bids->fetch_assoc()) {
    $isUser = ($row['user_id'] == $currentUser);
    $bids[] = [
        'amount' => $row['amount'],
        'time' => date('H:i:s', strtotime($row['created_at'])),
        'user' => $isUser ? 'أنت' : ($row['user_name'] ?? 'مزايد M' . substr(md5($row['user_id']), 0, 4)),
        'isUser' => $isUser,
    ];
}

echo json_encode([
    'success' => true,
    'current_price' => $current_price,
    'status' => $status,
    'bids' => $bids,
]);