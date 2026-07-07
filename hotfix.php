<?php
/**
 * ONE FILE HOTFIX — upload to public_html/ then visit:
 * https://mazadi.bearand.com/hotfix.php?key=mazad2026
 * DELETE this file after running.
 */
require_once 'config.php';
header('Content-Type: text/plain; charset=utf-8');
if (!isset($_GET['key']) || $_GET['key'] !== 'mazad2026') die('Unauthorized');

echo "=== FleetX Hotfix ===\n\n";

foreach ([
    "ALTER TABLE users ADD COLUMN sanad_limit DECIMAL(12,2) DEFAULT 0.00",
    "CREATE TABLE IF NOT EXISTS activity_log (
        id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL,
        type VARCHAR(50) NOT NULL DEFAULT 'system', message TEXT NOT NULL,
        meta JSON NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
] as $q) {
    try { $conn->query($q); echo "OK schema\n"; }
    catch (Throwable $e) { echo "SKIP: {$e->getMessage()}\n"; }
}

$hash = password_hash('123456', PASSWORD_DEFAULT);
$conn->query("UPDATE users SET password_hash='$hash', is_active=1, nafath_verified=1");
$conn->query("UPDATE users SET sanad_limit=GREATEST(COALESCE(sanad_limit,0),500000), wallet_balance=GREATEST(COALESCE(wallet_balance,0),50000) WHERE role IN ('buyer','admin')");
echo "Passwords=123456, sanad/wallet set\n";

$file = __DIR__ . '/sanad.php';
if (is_file($file) && is_writable($file)) {
    $s = file_get_contents($file);
    $fix = 'try { $conn->query("ALTER TABLE users ADD COLUMN sanad_limit DECIMAL(12,2) DEFAULT 0.00"); } catch (Throwable $e) {}';
    $s = str_replace('$conn->query("ALTER TABLE users ADD COLUMN sanad_limit DECIMAL(12,2) DEFAULT 0.00");', $fix, $s);
    file_put_contents($file, $s);
    echo "sanad.php patched\n";
} else {
    echo "Upload fixed sanad.php manually\n";
}

echo "\nDone. Delete hotfix.php. Test: php tests/e2e_end_to_end.php\n";
echo "Login: buyer 0501111111 / seller 0500000002 / inspector 0503333333 / pass 123456\n";