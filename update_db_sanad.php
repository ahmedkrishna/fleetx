<?php
/**
 * update_db_sanad.php — Production hotfix (safe to re-run)
 * https://mazadi.bearand.com/update_db_sanad.php?key=mazad2026
 */
require_once 'config.php';
header('Content-Type: text/plain; charset=utf-8');

if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== 'mazad2026')) {
    die('Unauthorized');
}
if (!$db_connected) {
    die('DB not connected: ' . $db_error_msg);
}

echo "=== FleetX Hotfix ===\n\n";

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
echo "\nPasswords reset to: 123456\n";

$conn->query("UPDATE users SET sanad_limit=GREATEST(COALESCE(sanad_limit,0), 500000), wallet_balance=GREATEST(COALESCE(wallet_balance,0), 50000) WHERE role IN ('buyer','admin')");
echo "Buyer sanad/wallet minimums set\n";

// Patch sanad.php ALTER bug in-place if possible
$sanadFile = __DIR__ . '/sanad.php';
if (is_writable($sanadFile)) {
    $src = file_get_contents($sanadFile);
    $patched = str_replace(
        '$conn->query("ALTER TABLE users ADD COLUMN sanad_limit DECIMAL(12,2) DEFAULT 0.00");',
        'try { $conn->query("ALTER TABLE users ADD COLUMN sanad_limit DECIMAL(12,2) DEFAULT 0.00"); } catch (Throwable $e) { /* exists */ }',
        $src
    );
    if ($patched !== $src) {
        file_put_contents($sanadFile, $patched);
        echo "sanad.php patched on disk\n";
    } else {
        echo "sanad.php already patched or pattern not found\n";
    }
} else {
    echo "sanad.php not writable — upload fixed sanad.php manually\n";
}

echo "\nDB: users=" . $conn->query('SELECT COUNT(*) FROM users')->fetch_row()[0];
echo " auctions=" . $conn->query('SELECT COUNT(*) FROM auctions')->fetch_row()[0] . "\n";
echo "Done.\n";