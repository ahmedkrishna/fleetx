<?php
require_once 'config.php';

if (!$db_connected) {
    die("Database connection failed: " . $db_error_msg);
}

echo "Updating database schema...\n";

$queries = [
    // 1. Update vehicles table
    "ALTER TABLE vehicles ADD COLUMN inspection_status ENUM('draft', 'pending_inspection', 'inspected', 'approved', 'rejected') DEFAULT 'draft'",
    "ALTER TABLE vehicles ADD COLUMN estimated_price DECIMAL(10,2) NULL",
    "ALTER TABLE vehicles ADD COLUMN expected_profit_margin DECIMAL(5,2) NULL",
    
    // 2. Create services table
    "CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        service_type ENUM('transfer', 'delivery', 'golden') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    // 3. Create transaction_services table
    "CREATE TABLE IF NOT EXISTS transaction_services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        auction_id INT NOT NULL,
        service_id INT NOT NULL,
        status ENUM('pending', 'paid', 'completed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (auction_id) REFERENCES auctions(id),
        FOREIGN KEY (service_id) REFERENCES services(id)
    )",
    
    // 4. Update inspections table if needed (assuming it exists, we might need a report_url column)
    "ALTER TABLE inspections ADD COLUMN report_url VARCHAR(255) NULL",
    "ALTER TABLE inspections ADD COLUMN mechanic_notes TEXT NULL",
    
    // Seed default services
    "INSERT IGNORE INTO services (id, name, description, price, service_type) VALUES 
    (1, 'نقل الملكية والتأمين', 'تخليص إجراءات نقل الملكية وإصدار تأمين ضد الغير', 1500.00, 'transfer'),
    (2, 'توصيل السيارة', 'شحن وتوصيل السيارة إلى مدينة المشتري عبر سطحة مغلقة', 800.00, 'delivery'),
    (3, 'الباقة الذهبية', 'تشمل نقل الملكية، التأمين الشامل، الغسيل والتلميع، والتوصيل لباب المنزل', 4500.00, 'golden')"
];

foreach ($queries as $query) {
    try {
        if ($conn->query($query)) {
            echo "Success: " . substr($query, 0, 50) . "...\n";
        } else {
            echo "Error or already exists: " . $conn->error . " -> " . substr($query, 0, 50) . "...\n";
        }
    } catch (Throwable $e) {
        echo "Skipped (already applied): " . substr($query, 0, 50) . "... — " . $e->getMessage() . "\n";
    }
}

echo "Schema update completed.\n";
