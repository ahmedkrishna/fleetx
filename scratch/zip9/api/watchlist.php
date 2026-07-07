<?php
// api/watchlist.php — Toggle Favorite / Watchlist API
require_once '../config.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'يجب تسجيل الدخول أولاً لتعديل المفضلة',
        'redirect' => '/login.php'
    ]);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$data = json_decode(file_get_contents('php://input'), true);
$auction_id = intval($data['auction_id'] ?? 0);

if ($auction_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'معرّف المزاد غير صالح']);
    exit;
}

if ($db_connected) {
    try {
        // Check if already in watchlist
        $stmt = $conn->prepare("SELECT 1 FROM watchlist WHERE user_id = ? AND auction_id = ?");
        $stmt->bind_param('ii', $user_id, $auction_id);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        
        if ($exists) {
            // Remove from watchlist
            $stmt = $conn->prepare("DELETE FROM watchlist WHERE user_id = ? AND auction_id = ?");
            $stmt->bind_param('ii', $user_id, $auction_id);
            $stmt->execute();
            $active = false;
            $msg = 'تمت الإزالة من المفضلة';
        } else {
            // Add to watchlist
            $stmt = $conn->prepare("INSERT INTO watchlist (user_id, auction_id) VALUES (?, ?)");
            $stmt->bind_param('ii', $user_id, $auction_id);
            $stmt->execute();
            $active = true;
            $msg = 'تمت الإضافة إلى المفضلة';
        }
        
        echo json_encode([
            'success' => true,
            'active' => $active,
            'message' => $msg
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'حدث خطأ في قاعدة البيانات: ' . $e->getMessage()]);
    }
} else {
    // Offline / Session simulation fallback
    if (!isset($_SESSION['mock_watchlist'])) {
        $_SESSION['mock_watchlist'] = [];
    }
    
    if (in_array($auction_id, $_SESSION['mock_watchlist'])) {
        // Remove
        $_SESSION['mock_watchlist'] = array_diff($_SESSION['mock_watchlist'], [$auction_id]);
        $active = false;
        $msg = 'تمت الإزالة من المفضلة (وضع الاختبار)';
    } else {
        // Add
        $_SESSION['mock_watchlist'][] = $auction_id;
        $active = true;
        $msg = 'تمت الإضافة إلى المفضلة (وضع الاختبار)';
    }
    
    echo json_encode([
        'success' => true,
        'active' => $active,
        'message' => $msg,
        'mock' => true
    ]);
}
