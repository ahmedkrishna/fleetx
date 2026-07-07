<?php
// admin/toggle-user.php
require_once '../config.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/index.php?tab=users');
    exit;
}

$user_id = (int)($_POST['user_id'] ?? 0);
if ($user_id && $db_connected) {
    $current = $conn->query("SELECT is_active FROM users WHERE id=$user_id")->fetch_assoc();
    $new_status = $current['is_active'] ? 0 : 1;
    $conn->query("UPDATE users SET is_active=$new_status WHERE id=$user_id");
}

header('Location: /admin/index.php?tab=users');
exit;
?>
