<?php
require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id = isset($data['id']) ? intval($data['id']) : 0;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول', 'redirect' => '/login.php']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$is_favorite = false;

if ($db_connected) {
    if (isInWatchlist($conn, $id, $user_id)) {
        $stmt = $conn->prepare('DELETE FROM watchlist WHERE user_id=? AND auction_id=?');
        $stmt->bind_param('ii', $user_id, $id);
        $stmt->execute();
        $is_favorite = false;
    } else {
        $stmt = $conn->prepare('INSERT IGNORE INTO watchlist (user_id, auction_id) VALUES (?,?)');
        $stmt->bind_param('ii', $user_id, $id);
        $stmt->execute();
        $is_favorite = true;
    }
    $cnt = $conn->prepare('SELECT COUNT(*) FROM watchlist WHERE user_id=?');
    $cnt->bind_param('i', $user_id);
    $cnt->execute();
    $total = (int)$cnt->get_result()->fetch_row()[0];
} else {
    if (!isset($_SESSION['favorites']) || !is_array($_SESSION['favorites'])) {
        $_SESSION['favorites'] = [];
    }
    $index = array_search($id, $_SESSION['favorites']);
    if ($index !== false) {
        unset($_SESSION['favorites'][$index]);
        $_SESSION['favorites'] = array_values($_SESSION['favorites']);
    } else {
        $_SESSION['favorites'][] = $id;
        $is_favorite = true;
    }
    $total = count($_SESSION['favorites']);
}

echo json_encode([
    'success'     => true,
    'is_favorite' => $is_favorite,
    'total'       => $total ?? 0,
]);