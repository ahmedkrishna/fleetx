<?php
/**
 * migrate_requirements.php — Requirements gap schema (run once)
 * CLI: php migrate_requirements.php
 * Web: https://site.com/migrate_requirements.php?key=mazad2026
 */
require_once __DIR__ . '/config.php';

if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== 'mazad2026')) {
    die('Unauthorized');
}

if (!$db_connected) {
    die('DB connection failed: ' . $db_error_msg);
}

function fx_migrate_run(mysqli $conn, string $sql, string $label = ''): void {
    if ($conn->query($sql)) {
        echo "OK: " . ($label ?: substr($sql, 0, 70)) . "\n";
    } else {
        echo "SKIP: " . $conn->error . " — " . ($label ?: substr($sql, 0, 50)) . "\n";
    }
}

function fx_column_exists(mysqli $conn, string $table, string $column): bool {
    $t = $conn->real_escape_string($table);
    $c = $conn->real_escape_string($column);
    $res = $conn->query("SHOW COLUMNS FROM `$t` LIKE '$c'");
    return $res && $res->num_rows > 0;
}

$queries = [
    "ALTER TABLE vehicles MODIFY `status` ENUM('pending','awaiting_admin','inspection_scheduled','awaiting_seller_approval','approved','in_auction','sold','withdrawn','suspended') NOT NULL DEFAULT 'pending'" => 'vehicles.status enum',
    "ALTER TABLE inspections MODIFY `inspector_id` INT NULL" => 'inspections.inspector_id nullable',
    "ALTER TABLE inspections MODIFY `status` ENUM('awaiting_admin','pending','in_progress','completed','rejected') NOT NULL DEFAULT 'awaiting_admin'" => 'inspections.status enum',
    "ALTER TABLE inspections ADD COLUMN `admin_approved` TINYINT(1) NOT NULL DEFAULT 0" => 'inspections.admin_approved',
    "ALTER TABLE inspections ADD COLUMN `seller_approved` TINYINT(1) DEFAULT NULL" => 'inspections.seller_approved',
    "ALTER TABLE inspections ADD COLUMN `tire_condition` VARCHAR(40) DEFAULT NULL" => 'inspections.tire_condition',
    "ALTER TABLE inspections ADD COLUMN `transmission_notes` TEXT DEFAULT NULL" => 'inspections.transmission_notes',
    "ALTER TABLE auctions ADD COLUMN `seller_decision` ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending'" => 'auctions.seller_decision',
    "ALTER TABLE auctions ADD COLUMN `admin_approved` TINYINT(1) NOT NULL DEFAULT 1" => 'auctions.admin_approved',
    "ALTER TABLE transactions ADD COLUMN `inspection_fee` DECIMAL(10,2) DEFAULT 0.00" => 'transactions.inspection_fee',
    "CREATE TABLE IF NOT EXISTS `saved_searches` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `name` VARCHAR(120) NOT NULL DEFAULT 'بحث محفوظ',
        `filters` JSON NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        INDEX `idx_user` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci" => 'saved_searches table',
    "CREATE TABLE IF NOT EXISTS `buyer_subscriptions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `plan` ENUM('free','pro','enterprise') NOT NULL DEFAULT 'free',
        `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `start_date` DATE NOT NULL,
        `end_date` DATE NOT NULL,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        INDEX `idx_user_active` (`user_id`, `is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci" => 'buyer_subscriptions table',
    "CREATE TABLE IF NOT EXISTS `platform_settings` (
        `setting_key` VARCHAR(60) NOT NULL PRIMARY KEY,
        `setting_value` TEXT,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci" => 'platform_settings table',
    "CREATE TABLE IF NOT EXISTS `company_documents` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `seller_id` INT NOT NULL,
        `doc_type` ENUM('cr','vat','istimara','insurance','other') NOT NULL DEFAULT 'other',
        `file_url` VARCHAR(500) NOT NULL,
        `admin_approved` TINYINT(1) NOT NULL DEFAULT 0,
        `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`seller_id`) REFERENCES `seller_companies`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci" => 'company_documents table',
    "ALTER TABLE transactions ADD COLUMN `extra_services` JSON DEFAULT NULL" => 'transactions.extra_services',
    "ALTER TABLE transactions ADD COLUMN `vat_amount` DECIMAL(12,2) DEFAULT 0.00" => 'transactions.vat_amount',
    "ALTER TABLE transactions ADD COLUMN `invoice_number` VARCHAR(40) DEFAULT NULL" => 'transactions.invoice_number',
    "ALTER TABLE inspections ADD COLUMN `engine_notes` TEXT DEFAULT NULL" => 'inspections.engine_notes',
    "ALTER TABLE inspections ADD COLUMN `mileage_verified` TINYINT(1) DEFAULT 1" => 'inspections.mileage_verified',
    "CREATE TABLE IF NOT EXISTS `invoices` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `transaction_id` INT NOT NULL,
        `invoice_number` VARCHAR(40) NOT NULL UNIQUE,
        `buyer_id` INT NOT NULL,
        `seller_id` INT NOT NULL,
        `subtotal` DECIMAL(12,2) NOT NULL,
        `vat_amount` DECIMAL(12,2) NOT NULL,
        `total` DECIMAL(12,2) NOT NULL,
        `zatca_qr` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`transaction_id`) REFERENCES `transactions`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci" => 'invoices table',
    "CREATE TABLE IF NOT EXISTS `payment_intents` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `reference` VARCHAR(32) NOT NULL UNIQUE,
        `auction_id` INT NOT NULL,
        `buyer_id` INT NOT NULL,
        `amount` DECIMAL(12,2) NOT NULL,
        `method` VARCHAR(20) NOT NULL DEFAULT 'card',
        `extra_services` JSON DEFAULT NULL,
        `inspection_fee` DECIMAL(10,2) DEFAULT 0.00,
        `status` ENUM('pending','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `completed_at` TIMESTAMP NULL DEFAULT NULL,
        INDEX `idx_buyer` (`buyer_id`),
        INDEX `idx_auction` (`auction_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci" => 'payment_intents table',
    "ALTER TABLE `payment_intents` ADD COLUMN `purpose` VARCHAR(32) NOT NULL DEFAULT 'purchase' AFTER `method`" => 'payment_intents.purpose',
];

foreach ($queries as $sql => $label) {
    if (str_contains($sql, 'ADD COLUMN')) {
        preg_match('/ADD COLUMN `([^`]+)`/', $sql, $m);
        preg_match('/ALTER TABLE (\w+)/', $sql, $t);
        if (!empty($m[1]) && !empty($t[1]) && fx_column_exists($conn, $t[1], $m[1])) {
            echo "SKIP (exists): {$t[1]}.{$m[1]}\n";
            continue;
        }
    }
    fx_migrate_run($conn, $sql, $label);
}

fx_migrate_run($conn, "INSERT IGNORE INTO platform_settings (setting_key, setting_value) VALUES
    ('inspection_fee', '100'),
    ('buyer_pro_price', '299'),
    ('platform_fee_percent', '5'),
    ('whatsapp_enabled', '1'),
    ('whatsapp_template_lang', 'ar')", 'platform_settings seed');

fx_migrate_run($conn, "INSERT IGNORE INTO buyer_subscriptions (user_id, plan, price, start_date, end_date, is_active)
    SELECT id, 'pro', 299, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 1 FROM users WHERE role='buyer' AND id IN (4,5)", 'buyer_subscriptions seed');

echo "Requirements migration complete.\n";