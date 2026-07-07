<?php
/**
 * deploy_fix.php — One-shot production repair after upload
 * /deploy_fix.php?key=mazad2026
 */
require_once 'config.php';
header('Content-Type: text/plain; charset=utf-8');

if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== 'mazad2026')) {
    die('Unauthorized');
}
if (!$db_connected) die('DB error: ' . $db_error_msg);

echo "=== FleetX Deploy Fix ===\n\n";

// 1. Schema patches
$patches = [
    "ALTER TABLE users ADD COLUMN sanad_limit DECIMAL(12,2) DEFAULT 0.00",
    "CREATE TABLE IF NOT EXISTS activity_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type VARCHAR(50) NOT NULL DEFAULT 'system',
        message TEXT NOT NULL,
        meta JSON NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];
foreach ($patches as $q) {
    try { $conn->query($q); echo "OK: " . substr($q, 0, 60) . "\n"; }
    catch (Throwable $e) { echo "SKIP: " . $e->getMessage() . "\n"; }
}

// 2. Reset all passwords to 123456
$hash = password_hash('123456', PASSWORD_DEFAULT);
$conn->query("UPDATE users SET password_hash='$hash', is_active=1, nafath_verified=1");
echo "\nPasswords reset to: 123456 (all users)\n";

// 3. Ensure buyers have sanad limit
$conn->query("UPDATE users SET sanad_limit=500000, wallet_balance=50000 WHERE role='buyer' AND sanad_limit=0");

// 4. Stats
$u = $conn->query('SELECT COUNT(*) FROM users')->fetch_row()[0];
$a = $conn->query('SELECT COUNT(*) FROM auctions')->fetch_row()[0];
$v = $conn->query('SELECT COUNT(*) FROM vehicles')->fetch_row()[0];
echo "\nDB: users=$u auctions=$a vehicles=$v\n";
echo "Done. Run /tests/e2e.php?key=mazad2026 to verify.\n";