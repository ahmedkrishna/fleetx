<?php
require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

$auction_id = isset($_GET['auction_id']) ? intval($_GET['auction_id']) : 0;

if ($auction_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'معرف المزاد غير صالح.']);
    exit;
}

$bids = [];
$current_price = 0;
$status = 'active';

if ($db_connected) {
    // 1. Fetch current info
    $stmt = $conn->prepare("SELECT current_price, status FROM auctions WHERE id = ?");
    $stmt->bind_param("i", $auction_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        $auction = $res->fetch_assoc();
        $current_price = $auction['current_price'];
        $status = $auction['status'];
    }
    
    // Simulate Realistic Live Bidding in DB (20% chance every 3 seconds)
    if (rand(1, 5) === 1 && $status === 'active') {
        $increments = [500, 1000, 2000, 500, 500]; // Weighted towards 500
        $random_increment = $increments[array_rand($increments)];
        $new_simulated_bid = $current_price + $random_increment;
        $bot_user_id = rand(10, 99);
        $ip = '127.0.0.1';
        
        $stmt_insert = $conn->prepare("INSERT INTO bids (auction_id, user_id, amount, ip_address) VALUES (?, ?, ?, ?)");
        $stmt_insert->bind_param("iids", $auction_id, $bot_user_id, $new_simulated_bid, $ip);
        if ($stmt_insert->execute()) {
            $stmt_update = $conn->prepare("UPDATE auctions SET current_price = ? WHERE id = ?");
            $stmt_update->bind_param("di", $new_simulated_bid, $auction_id);
            $stmt_update->execute();
            $current_price = $new_simulated_bid;
        }
    }
    
    // Fetch Bids
    $stmt_bids = $conn->prepare("SELECT b.*, u.full_name as user_name FROM bids b LEFT JOIN users u ON b.user_id = u.id WHERE auction_id = ? ORDER BY created_at DESC LIMIT 50");
    $stmt_bids->bind_param("i", $auction_id);
    $stmt_bids->execute();
    $res_bids = $stmt_bids->get_result();
    
    $currentUser = $_SESSION['user_id'] ?? 1;
    
    while ($row = $res_bids->fetch_assoc()) {
        $isUser = ($row['user_id'] == $currentUser);
        $bids[] = [
            'amount' => $row['amount'],
            'time' => date('H:i:s', strtotime($row['created_at'])),
            'user' => $isUser ? 'أنت' : ($row['user_name'] ?? 'مزايد M' . substr(md5($row['user_id']), 0, 4)),
            'isUser' => $isUser
        ];
    }
} else {
    echo json_encode(['success' => false, 'message' => 'قاعدة البيانات غير متصلة']);
    exit;
}

echo json_encode([
    'success' => true,
    'current_price' => $current_price,
    'status' => $status,
    'bids' => $bids
]);