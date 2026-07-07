<?php
/**
 * fix_passwords.php — Set demo passwords for all users (non-destructive)
 * Run once: /fix_passwords.php?key=mazad2026
 */
require_once 'config.php';

if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== 'mazad2026')) {
    die('Unauthorized. Use ?key=mazad2026');
}

if (!$db_connected) {
    die('DB not connected: ' . $db_error_msg);
}

$password = $_GET['pass'] ?? '123456';
$hash = password_hash($password, PASSWORD_DEFAULT);
$updated = 0;

$stmt = $conn->prepare("UPDATE users SET password_hash = ?, is_active = 1 WHERE id = ?");
$res = $conn->query("SELECT id, mobile, full_name, role FROM users ORDER BY id");
echo "<pre>FleetX Password Reset\nPassword: $password\n\n";

while ($row = $res->fetch_assoc()) {
    $id = (int)$row['id'];
    $stmt->bind_param('si', $hash, $id);
    if ($stmt->execute()) {
        $updated++;
        echo "OK #{$id} {$row['role']} {$row['mobile']} — {$row['full_name']}\n";
    }
}

echo "\nUpdated $updated users.\n";
echo "Test login: buyer 0501111111 / seller 0500000002 / inspector 0503333333 / admin 0500000001\n";
echo "</pre>";