<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$auction_id = (int)($input['auction_id'] ?? 0);
$max_amount = (float)($input['max_amount'] ?? 0);
$user_id = (int)getUserId();
$action = $input['action'] ?? 'set';

if (!$db_connected || !$auction_id) {
    echo json_encode(['success' => false, 'error' => 'بيانات غير صالحة']);
    exit;
}

if ($action === 'cancel') {
    $stmt = $conn->prepare('UPDATE auto_bids SET is_active=0 WHERE auction_id=? AND user_id=?');
    $stmt->bind_param('ii', $auction_id, $user_id);
    $stmt->execute();
    echo json_encode(['success' => true, 'message' => 'تم إيقاف المزايدة التلقائية']);
    exit;
}

if ($max_amount <= 0) {
    echo json_encode(['success' => false, 'error' => 'حد أقصى غير صالح']);
    exit;
}

$bid_check = buyerCanBid($conn, $user_id);
if (!$bid_check['allowed']) {
    echo json_encode(['success' => false, 'error' => $bid_check['reason']]);
    exit;
}

$off = $conn->prepare('UPDATE auto_bids SET is_active=0 WHERE auction_id=? AND user_id=?');
$off->bind_param('ii', $auction_id, $user_id);
$off->execute();
$stmt = $conn->prepare('INSERT INTO auto_bids (auction_id, user_id, max_amount, is_active) VALUES (?,?,?,1)');
$stmt->bind_param('iid', $auction_id, $user_id, $max_amount);
$stmt->execute();

// Try immediate proxy bid
$astmt = $conn->prepare('SELECT current_price, bid_increment FROM auctions WHERE id=? AND status IN ("active","live")');
$astmt->bind_param('i', $auction_id);
$astmt->execute();
$auc = $astmt->get_result()->fetch_assoc();
$placed = null;
if ($auc) {
    $next = (float)$auc['current_price'] + (float)$auc['bid_increment'];
    if ($next <= $max_amount) {
        $placed = placeBid($conn, $auction_id, $user_id, $next);
    }
}

echo json_encode([
    'success' => true,
    'max_amount' => $max_amount,
    'placed_bid' => $placed,
]);