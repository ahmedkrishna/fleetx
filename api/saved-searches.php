<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'يجب تسجيل الدخول']);
    exit;
}

if (!$db_connected || !fleetx_table_exists($conn, 'saved_searches')) {
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'الخدمة غير متاحة']);
    exit;
}

$user_id = (int)getUserId();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $conn->prepare('SELECT id, name, filters, created_at FROM saved_searches WHERE user_id=? ORDER BY created_at DESC');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $items = [];
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $row['filters'] = json_decode($row['filters'] ?? '{}', true);
        $items[] = $row;
    }
    echo json_encode(['success' => true, 'items' => $items]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

if ($method === 'POST') {
    $name = trim($input['name'] ?? 'بحث محفوظ');
    $filters = $input['filters'] ?? [];
    if (!is_array($filters)) $filters = [];
    $filters_json = json_encode($filters, JSON_UNESCAPED_UNICODE);
    $stmt = $conn->prepare('INSERT INTO saved_searches (user_id, name, filters) VALUES (?,?,?)');
    $stmt->bind_param('iss', $user_id, $name, $filters_json);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $conn->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'فشل الحفظ']);
    }
    exit;
}

if ($method === 'DELETE') {
    $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
    $stmt = $conn->prepare('DELETE FROM saved_searches WHERE id=? AND user_id=?');
    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();
    echo json_encode(['success' => true, 'deleted' => $stmt->affected_rows > 0]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);