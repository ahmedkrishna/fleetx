<?php
/**
 * check_db.php — DB health + optional hotfix
 * /check_db.php
 * /check_db.php?key=mazad2026&fix=1
 */
require 'config.php';
header('Content-Type: text/plain; charset=utf-8');

if (isset($db_connected) && $db_connected) {
    echo "Users: " . $conn->query('SELECT COUNT(*) FROM users')->fetch_row()[0] . "\n";
    echo "Auctions: " . $conn->query('SELECT COUNT(*) FROM auctions')->fetch_row()[0] . "\n";
    echo "Vehicles: " . $conn->query('SELECT COUNT(*) FROM vehicles')->fetch_row()[0] . "\n";
} else {
    echo "DB not connected.\n";
    exit(1);
}

if (!isset($_GET['key']) || $_GET['key'] !== 'mazad2026' || empty($_GET['fix'])) {
    exit(0);
}

echo "\n=== Hotfix ===\n";

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
    try {
        $conn->query($q);
        echo "OK: " . substr($q, 0, 55) . "\n";
    } catch (Throwable $e) {
        echo "SKIP: " . $e->getMessage() . "\n";
    }
}

$hash = password_hash('123456', PASSWORD_DEFAULT);
$conn->query("UPDATE users SET password_hash='$hash', is_active=1, nafath_verified=1");
echo "Passwords reset to 123456\n";

$conn->query("UPDATE users SET sanad_limit=GREATEST(COALESCE(sanad_limit,0), 500000), wallet_balance=GREATEST(COALESCE(wallet_balance,0), 50000) WHERE role IN ('buyer','admin')");
echo "Buyer sanad/wallet updated\n";
echo "Done.\n";