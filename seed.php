<?php
require_once 'config.php';

// Only allow execution from command line or with a secret key to prevent abuse
if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== 'mazad2026')) {
    die("Unauthorized access. Use ?key=mazad2026");
}

if (!$db_connected) {
    die("Database connection failed: " . $db_error_msg);
}

echo "<pre>Starting Database Seed...\n";

// Disable foreign key checks for truncation
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

$tables = ['transactions', 'bids', 'watchlist', 'notifications', 'activity_log', 'inspections', 'auctions', 'auction_events', 'vehicles', 'seller_companies', 'users'];
foreach ($tables as $table) {
    $exists = $conn->query("SHOW TABLES LIKE '$table'");
    if ($exists && $exists->num_rows > 0) {
        $conn->query("TRUNCATE TABLE $table");
        echo "Truncated $table\n";
    } else {
        echo "Skipped $table (not found)\n";
    }
}

$conn->query("SET FOREIGN_KEY_CHECKS = 1");

// 1. Seed Users
$pass = password_hash('123456', PASSWORD_DEFAULT);
$users_sql = "INSERT INTO users (id, full_name, mobile, email, password_hash, role, city, wallet_balance, is_active) VALUES 
(1, 'أحمد السعدي', '0501111111', 'admin@mazadi.com', '$pass', 'admin', 'الرياض', 0, 1),
(2, 'المشتري الأول', '0502222222', 'buyer@mazadi.com', '$pass', 'buyer', 'الرياض', 50000, 1),
(3, 'الوطنية للتأجير', '0503333333', 'watania@seller.com', '$pass', 'seller', 'الرياض', 0, 1),
(4, 'بدجت السعودية', '0504444444', 'budget@seller.com', '$pass', 'seller', 'جدة', 0, 1),
(5, 'يلو لتأجير السيارات', '0505555555', 'yelo@seller.com', '$pass', 'seller', 'الدمام', 0, 1)";
$conn->query($users_sql);
echo "Users seeded.\n";

// 2. Seed Sellers
$sellers_sql = "INSERT INTO seller_companies (id, user_id, company_name, cr_number, fleet_size, subscription, rating, is_verified, total_auctions) VALUES 
(1, 3, 'الوطنية لتأجير السيارات', '1010123456', 500, 'premium', 4.8, 1, 150),
(2, 4, 'بدجت السعودية', '1010234567', 1200, 'enterprise', 4.9, 1, 320),
(3, 5, 'يلو (الوفاق)', '1010345678', 3000, 'enterprise', 4.7, 1, 500)";
$conn->query($sellers_sql);
echo "Seller Companies seeded.\n";

// 3. Seed Vehicles
$v_imgs1 = json_encode(['https://images.unsplash.com/photo-1550314421-2a1e6704b2b8?w=800&q=80', 'https://images.unsplash.com/photo-1502877338535-766e1452684a?w=800&q=80']);
$v_imgs2 = json_encode(['https://images.unsplash.com/photo-1606664515524-ed2f786a0bd6?w=800&q=80', 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?w=800&q=80']);
$v_imgs3 = json_encode(['https://images.unsplash.com/photo-1542282088-fe8426682b8f?w=800&q=80']);

$vehicles_sql = "INSERT INTO vehicles (id, seller_id, vin, make, model, year, mileage, color, city, image_url, images, status) VALUES 
(1, 1, 'VIN1234567890123', 'Toyota', 'Camry', 2023, 45000, 'أبيض', 'الرياض', 'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?w=800&q=80', '$v_imgs1', 'approved'),
(2, 1, 'VIN2234567890123', 'Hyundai', 'Elantra', 2022, 60000, 'فضي', 'الرياض', 'https://images.unsplash.com/photo-1568844293986-ca9c5c6f8b8a?w=800&q=80', '$v_imgs1', 'approved'),
(3, 2, 'VIN3234567890123', 'Mercedes', 'S-Class', 2021, 35000, 'أسود', 'جدة', 'https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?w=800&q=80', '$v_imgs2', 'approved'),
(4, 2, 'VIN4234567890123', 'BMW', 'X5', 2022, 25000, 'أزرق', 'جدة', 'https://images.unsplash.com/photo-1580273916550-e323be2ae537?w=800&q=80', '$v_imgs2', 'approved'),
(5, 3, 'VIN5234567890123', 'Kia', 'Optima', 2023, 15000, 'أبيض', 'الدمام', 'https://images.unsplash.com/photo-1606664515524-ed2f786a0bd6?w=800&q=80', '$v_imgs3', 'approved'),
(6, 3, 'VIN6234567890123', 'Chevrolet', 'Tahoe', 2020, 95000, 'أسود', 'الدمام', 'https://images.unsplash.com/photo-1502877338535-766e1452684a?w=800&q=80', '$v_imgs3', 'approved')";
$conn->query($vehicles_sql);
echo "Vehicles seeded.\n";

// 4. Seed Events
$now = date('Y-m-d H:i:s');
$future_1d = date('Y-m-d H:i:s', time() + 86400);
$future_5d = date('Y-m-d H:i:s', time() + 86400 * 5);
$past_1d = date('Y-m-d H:i:s', time() - 86400);

$events_sql = "INSERT INTO auction_events (id, seller_id, title, status, start_time, end_time) VALUES 
(1, 1, 'مزاد الوطنية للسيارات الفاخرة', 'active', '$past_1d', '$future_5d'),
(2, 2, 'مزاد تصفية أسطول بدجت', 'active', '$now', '$future_1d')";
$conn->query($events_sql);
echo "Events seeded.\n";

// 5. Seed Auctions
$auctions_sql = "INSERT INTO auctions (id, event_id, vehicle_id, seller_id, title, type, status, starting_price, current_price, start_time, end_time) VALUES 
(1, 1, 1, 1, 'تويوتا كامري 2023 - بحالة الوكالة', 'live', 'active', 75000, 75000, '$past_1d', '$future_5d'),
(2, 1, 2, 1, 'هيونداي النترا 2022 - فحص جديد', 'live', 'active', 55000, 56000, '$past_1d', '$future_5d'),
(3, 2, 3, 2, 'مرسيدس S-Class 2021', 'instant', 'active', 320000, 320000, '$now', '$future_5d'),
(4, 2, 4, 2, 'BMW X5 2022 للبيع الفوري', 'instant', 'active', 280000, 280000, '$now', '$future_5d'),
(5, null, 5, 3, 'كيا اوبتيما 2023 (مزاد مغلق)', 'sealed', 'active', 65000, 65000, '$now', '$future_1d'),
(6, null, 6, 3, 'شيفروليه تاهو 2020 (شراء فوري)', 'instant', 'active', 150000, 150000, '$now', '$future_5d')";
$conn->query($auctions_sql);
echo "Auctions seeded.\n";

// 6. Seed Bids
$bids_sql = "INSERT INTO bids (auction_id, user_id, amount) VALUES 
(2, 2, 56000)";
$conn->query($bids_sql);
echo "Bids seeded.\n";

echo "\nDatabase seeding completed successfully!</pre>";
