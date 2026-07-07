<?php
// api/buy-now.php
require_once '../config.php';

if (!isLoggedIn() || getUserRole() !== 'buyer') {
    header('Location: /login.php');
    exit;
}

$auction_id = (int)($_POST['auction_id'] ?? 0);
if (!$auction_id || !$db_connected) {
    header('Location: /auctions.php');
    exit;
}

$auction = getAuctionById($conn, $auction_id);
if (!$auction || $auction['type'] !== 'instant' || $auction['status'] !== 'active') {
    header('Location: /vehicle-details.php?id=' . $auction_id . '&error=unavailable');
    exit;
}

$user_id = getUserId();
$price = $auction['current_price'];

// Create transaction record
$conn->begin_transaction();
try {
    $fee = $price * (PLATFORM_FEE_PERCENT / 100);
    $payout = $price - $fee;
    
    $stmt = $conn->prepare("
        INSERT INTO transactions (auction_id, buyer_id, seller_id, sale_price, platform_fee, seller_payout, payment_status)
        VALUES (?, ?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->bind_param('iiiddd', $auction_id, $user_id, $auction['seller_id'], $price, $fee, $payout);
    $stmt->execute();
    
    // Mark auction as ended with winner
    $stmt2 = $conn->prepare("UPDATE auctions SET status='ended', winner_id=?, sale_price=? WHERE id=?");
    $stmt2->bind_param('idi', $user_id, $price, $auction_id);
    $stmt2->execute();
    
    // Notify seller
    createNotification($conn, $auction['seller_id'] ?? 1, 'auction_won',
        'تم بيع سيارتك!', 
        "تم شراء {$auction['make']} {$auction['model']} بسعر " . formatPrice($price),
        "/seller/dashboard.php"
    );
    
    $conn->commit();
    header('Location: /checkout.php?id=' . $auction_id . '&bought=1');
    exit;
} catch (Exception $e) {
    $conn->rollback();
    header('Location: /vehicle-details.php?id=' . $auction_id . '&error=failed');
    exit;
}
?>
