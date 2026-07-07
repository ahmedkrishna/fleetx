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
    echo json_encode(['success'=>false,'message'=>'قاعدة البيانات غير متصلة']);
    exit;
}

// Check Nafath and Sanad Limit
$ustmt = $conn->prepare("SELECT nafath_verified, sanad_limit FROM users WHERE id = ?");
$ustmt->bind_param('i', $user_id);
$ustmt->execute();
$ures = $ustmt->get_result();
if ($urow = $ures->fetch_assoc()) {
    if (!$urow['nafath_verified']) {
        echo json_encode(['success'=>false,'message'=>'يجب توثيق هويتك عبر نفاذ أولاً','redirect'=>'/nafath.php']);
        exit;
    }
    $sanad_limit = floatval($urow['sanad_limit']);
    if ($sanad_limit <= 0) {
        $sanad_limit = floatval($_SESSION['sanad_limit'] ?? 0);
    }
    if ($amount > $sanad_limit) {
        echo json_encode(['success'=>false,'message'=>'مبلغ المزايدة يتجاوز الحد المالي لسند لأمر الخاص بك. يرجى رفع الحد','redirect'=>'/sanad.php']);
        exit;
    }
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
            $upd = $conn->prepare("UPDATE auctions SET current_price=?, end_time=? WHERE id=?");
            $upd->bind_param('dsi', $amount, $newEndTime, $auction_id);
            $upd->execute();
        } else {
            $upd = $conn->prepare("UPDATE auctions SET current_price=? WHERE id=?");
            $upd->bind_param('di', $amount, $auction_id);
            $upd->execute();
        }
    } else {
        $upd = $conn->prepare("UPDATE auctions SET current_price=? WHERE id=?");
        $upd->bind_param('di', $amount, $auction_id);
        $upd->execute();
    }

    // Count bids
    $cntStmt = $conn->prepare("SELECT COUNT(*) as c FROM bids WHERE auction_id=?");
    $cntStmt->bind_param('i', $auction_id);
    $cntStmt->execute();
    $bidCount = $cntStmt->get_result()->fetch_assoc()['c'];

    // Notify outbid users
    $notifMsg = 'قدم شخص آخر مزايدة بـ ' . number_format($amount) . ' ر.س';
    $notifLink = '/auction-room.php?id=' . $auction_id;
    $outbidStmt = $conn->prepare("
        SELECT DISTINCT b.user_id FROM bids b
        WHERE b.auction_id=? AND b.user_id != ?
        AND b.amount < ?
    ");
    $outbidStmt->bind_param('iid', $auction_id, $user_id, $amount);
    $outbidStmt->execute();
    $outbidRes = $outbidStmt->get_result();
    while ($ob = $outbidRes->fetch_assoc()) {
        notifyUser($conn, (int)$ob['user_id'], 'outbid', 'تم تجاوز مزايدتك!', $notifMsg, $notifLink, ['in_app', 'sms']);
    }

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
