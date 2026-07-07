<?php
/**
 * cron/end-auctions.php
 * FleetX — Automatic Auction Closer
 * 
 * Run every minute via cron:
 * * * * * * php /path/to/cron/end-auctions.php
 * 
 * This script:
 * 1. Finds auctions where end_time has passed and status is still 'active'
 * 2. Sets them to 'ended'
 * 3. Determines the winner (highest bidder)
 * 4. Creates a transaction record
 * 5. Notifies the winner and the seller
 * 6. Updates vehicle status to 'sold'
 */
require_once __DIR__ . '/../config.php';

if (!$db_connected) {
    echo "DB not connected. Exiting.\n";
    exit(1);
}

echo "[" . date('Y-m-d H:i:s') . "] Running auction closer...\n";

// 1. Find expired active auctions
$expired = $conn->query("
    SELECT a.id, a.title, a.vehicle_id, a.seller_id, a.current_price, a.starting_price, 
           a.reserve_price, a.type, v.make, v.model, v.year
    FROM auctions a
    JOIN vehicles v ON a.vehicle_id = v.id
    WHERE a.status IN ('active','live')
      AND a.end_time IS NOT NULL
      AND a.end_time <= NOW()
      AND a.type != 'instant'
");

if (!$expired || $expired->num_rows === 0) {
    echo "No expired auctions found.\n";
    exit(0);
}

$ended = 0;
$winners_set = 0;

while ($auction = $expired->fetch_assoc()) {
    $auction_id = $auction['id'];
    $car_name = ($auction['title'] ?: $auction['make'] . ' ' . $auction['model'] . ' ' . $auction['year']);
    
    echo "  Processing auction #{$auction_id}: {$car_name}\n";
    
    $conn->begin_transaction();
    try {
        // 2. Find highest bidder
        $bid_stmt = $conn->prepare("
            SELECT user_id, amount 
            FROM bids 
            WHERE auction_id = ? 
            ORDER BY amount DESC 
            LIMIT 1
        ");
        $bid_stmt->bind_param('i', $auction_id);
        $bid_stmt->execute();
        $top_bid = $bid_stmt->get_result()->fetch_assoc();
        
        if ($top_bid) {
            $winner_id  = (int)$top_bid['user_id'];
            $sale_price = (float)$top_bid['amount'];
            
            // Check reserve price
            $reserve = (float)($auction['reserve_price'] ?? 0);
            $met_reserve = ($reserve <= 0 || $sale_price >= $reserve);
            
            if ($met_reserve) {
                // 3. Mark auction as ended with winner
                $upd = $conn->prepare("UPDATE auctions SET status='ended', winner_id=?, sale_price=? WHERE id=?");
                $upd->bind_param('idi', $winner_id, $sale_price, $auction_id);
                $upd->execute();
                
                // 4. Create transaction
                $fee    = $sale_price * (PLATFORM_FEE_PERCENT / 100);
                $payout = $sale_price - $fee;
                
                $tx = $conn->prepare("
                    INSERT INTO transactions (auction_id, buyer_id, seller_id, sale_price, platform_fee, seller_payout, payment_status)
                    VALUES (?, ?, ?, ?, ?, ?, 'pending')
                ");
                $tx->bind_param('iiiddd', $auction_id, $winner_id, $auction['seller_id'], $sale_price, $fee, $payout);
                $tx->execute();
                
                // 5. Update vehicle status
                $conn->query("UPDATE vehicles SET status='sold' WHERE id=" . (int)$auction['vehicle_id']);
                
                // 6. Notify winner
                notifyUser($conn, $winner_id, 'auction_won',
                    'تهانينا! فزت بالمزاد',
                    "فزت بمزاد {$car_name} بمبلغ " . formatPrice($sale_price) . ". أكمل عملية الدفع الآن.",
                    "/checkout.php?id={$auction_id}",
                    ['in_app', 'sms', 'whatsapp']
                );

                // 7. Notify seller
                $seller_user_stmt = $conn->prepare("SELECT user_id FROM seller_companies WHERE id=?");
                $seller_user_stmt->bind_param('i', $auction['seller_id']);
                $seller_user_stmt->execute();
                $seller_user = $seller_user_stmt->get_result()->fetch_assoc();
                if ($seller_user) {
                    notifyUser($conn, (int)$seller_user['user_id'], 'auction_end',
                        'تم بيع سيارتك!',
                        "تم بيع {$car_name} بمبلغ " . formatPrice($sale_price) . ". سيتم تحويل المستحقات خلال 3 أيام عمل.",
                        "/seller.php?section=payouts",
                        ['in_app', 'sms']
                    );
                }
                
                // 8. Notify all other bidders
                $losers = $conn->prepare("
                    SELECT DISTINCT user_id FROM bids 
                    WHERE auction_id=? AND user_id != ?
                ");
                $losers->bind_param('ii', $auction_id, $winner_id);
                $losers->execute();
                $loser_res = $losers->get_result();
                while ($loser = $loser_res->fetch_assoc()) {
                    createNotification($conn, $loser['user_id'], 'auction_end',
                        'انتهى المزاد',
                        "انتهى مزاد {$car_name}. تم البيع بمبلغ " . formatPrice($sale_price) . ". تصفح مزادات أخرى!",
                        "/auctions.php"
                    );
                }
                
                $winners_set++;
                echo "    → Winner: user #{$winner_id}, price: " . formatPrice($sale_price) . "\n";
            } else {
                // Reserve not met — end without winner
                $upd = $conn->prepare("UPDATE auctions SET status='ended' WHERE id=?");
                $upd->bind_param('i', $auction_id);
                $upd->execute();
                echo "    → Reserve price not met. Ended without winner.\n";
            }
        } else {
            // No bids — just end
            $upd = $conn->prepare("UPDATE auctions SET status='ended' WHERE id=?");
            $upd->bind_param('i', $auction_id);
            $upd->execute();
            echo "    → No bids. Ended.\n";
        }
        
        $conn->commit();
        $ended++;
        
    } catch (Exception $e) {
        $conn->rollback();
        echo "    → ERROR: " . $e->getMessage() . "\n";
    }
}

echo "\nDone. Ended: {$ended} auctions, Winners set: {$winners_set}\n";
