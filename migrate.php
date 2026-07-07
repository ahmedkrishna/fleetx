<?php
/**
 * migrate.php — Run once on production to apply schema updates
 * Access: php migrate.php  OR  https://site.com/migrate.php?key=mazad2026
 */
require_once 'config.php';

if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== 'mazad2026')) {
    die('Unauthorized');
}

if (!$db_connected) {
    die('DB connection failed: ' . $db_error_msg);
}

$queries = [
    "ALTER TABLE users ADD COLUMN sanad_limit DECIMAL(12,2) DEFAULT 0.00",
    "ALTER TABLE users ADD COLUMN admin_notes TEXT NULL",
    "ALTER TABLE seller_companies ADD COLUMN city VARCHAR(60) NULL",
    "ALTER TABLE seller_companies ADD COLUMN phone VARCHAR(20) NULL",
    "ALTER TABLE seller_companies ADD COLUMN email VARCHAR(150) NULL",
    "ALTER TABLE seller_companies ADD COLUMN bank_name VARCHAR(100) NULL",
    "ALTER TABLE seller_companies ADD COLUMN iban VARCHAR(34) NULL",
    "CREATE TABLE IF NOT EXISTS activity_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type VARCHAR(50) NOT NULL DEFAULT 'system',
        message TEXT NOT NULL,
        meta JSON NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_created (user_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];

foreach ($queries as $q) {
    if ($conn->query($q)) {
        echo "OK: " . substr($q, 0, 60) . "...\n";
    } else {
        echo "SKIP/ERR: " . $conn->error . " — " . substr($q, 0, 50) . "...\n";
    }
}

echo "Migration complete.\n";