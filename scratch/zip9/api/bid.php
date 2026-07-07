<?php
// api/bid.php — Live Bid Submission API
require_once '../config.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$data = json_decode(file_get_contents('php://input'), true);
$auction_id = intval($data['auction_id'] ?? 0);
$amount     = floatval($data['amount'] ?? 0);

// Auth check
if (!isLoggedIn()) {
    echo json_encode(['success'=>false,'message'=>'يجب تسجيل الدخول أولاً','redirect'=>'/login.php']);
    exit;
}

$user_id = $_SESSION['user_id'];

if (!$db_connected) {
    // Offline simulation
    if (!isset($_SESSION['mock_bids'])) {
        $_SESSION['mock_bids'] = [];
    }
    
    // Check if bid is high enough based on session
    $currentMockPrice = 0;
    foreach ($_SESSION['mock_bids'] as $b) {
        if ($b['auction_id'] == $auction_id && $b['amount'] > $currentMockPrice) {
            $currentMockPrice = $b['amount'];
        }
    }
    
    if ($currentMockPrice == 0) {
        $mocks = getMockAuctions();
        foreach ($mocks as $m) {
            if ($m['id'] == $auction_id) $currentMockPrice = $m['current_price'];
        }
    }
    
    $increment = 500;
    if ($amount < $currentMockPrice + $increment) {
        echo json_encode(['success'=>false,'message'=>"الحد الأدنى للمزايدة " . number_format($currentMockPrice + $increment) . " ر.س"]);
        exit;
    }
    
    $_SESSION['mock_bids'][] = ['auction_id' => $auction_id, 'amount' => $amount];
    
    echo json_encode([
        'success'       => true,
        'message'       => 'تمت المزايدة بنجاح (وضع الاختبار)',
        'new_price'     => $amount,
        'bid_count'     => rand(10, 50),
        'simulation'    => true,
    ]);
    exit;
}

// Get auction
$stmt = $conn->prepare("SELECT * FROM auctions WHERE id = ? AND status IN ('active','live') FOR UPDATE");
$stmt->bind_param('i', $auction_id);
$stmt->execute();
$auction = $stmt->get_result()->fetch_assoc();

if (!$auction) {
    echo json_encode(['success'=>false,'message'=>'المزاد غير موجود أو انتهى']);
    exit;
}

$minBid = $auction['current_price'] + $auction['bid_increment'];
if ($amount < $minBid) {
    echo json_encode(['success'=>false,'message'=>"الحد الأدنى للمزايدة " . number_format($minBid) . " ر.س"]);
    exit;
}

// Check auction not expired
if ($auction['end_time'] && strtotime($auction['end_time']) < time()) {
    echo json_encode(['success'=>false,'message'=>'انتهى وقت المزاد']);
    exit;
}

// Insert bid
$conn->begin_transaction();
try {
    $stmt = $conn->prepare("INSERT INTO bids (auction_id, user_id, amount, ip_address) VALUES (?,?,?,?)");
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt->bind_param('iids', $auction_id, $user_id, $amount, $ip);
    $stmt->execute();

    // Update auction current price (Anti-Sniping: extend by 3 min if < 3 min left)
    $newEndTime = '';
    if ($auction['end_time']) {
        $remaining = strtotime($auction['end_time']) - time();
        if ($remaining < 180) {
            $newEndTime = date('Y-m-d H:i:s', time() + 180);
            $conn->query("UPDATE auctions SET current_price=$amount, end_time='$newEndTime' WHERE id=$auction_id");
        } else {
            $conn->query("UPDATE auctions SET current_price=$amount WHERE id=$auction_id");
        }
    } else {
        $conn->query("UPDATE auctions SET current_price=$amount WHERE id=$auction_id");
    }

    // Count bids
    $bidCount = $conn->query("SELECT COUNT(*) as c FROM bids WHERE auction_id=$auction_id")->fetch_assoc()['c'];

    // Notify outbid users
    $conn->query("INSERT INTO notifications (user_id, type, title, message, link)
                  SELECT DISTINCT b.user_id, 'outbid', 'تم تجاوز مزايدتك!',
                  CONCAT('قدم شخص آخر مزايدة بـ ', FORMAT($amount, 0), ' ر.س في مزاد {$auction['id']}'),
                  '/auction-live.php?id=$auction_id'
                  FROM bids b WHERE b.auction_id=$auction_id AND b.user_id != $user_id
                  AND b.amount = (SELECT MAX(amount) FROM bids WHERE auction_id=$auction_id AND user_id != $user_id)");

    $conn->commit();

    echo json_encode([
        'success'    => true,
        'message'    => 'تمت مزايدتك بنجاح!',
        'new_price'  => $amount,
        'bid_count'  => $bidCount,
        'new_end_time' => $newEndTime ?: ($auction['end_time'] ?? null),
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success'=>false,'message'=>'خطأ في المزايدة، حاول مجدداً']);
}
