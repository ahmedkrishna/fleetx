<?php
// setup.php - Create demo accounts and sample data
require_once 'config.php';

if (!$db_connected) {
    die('<p style="font-family:sans-serif; color:red; padding:20px;">خطأ: لا يمكن الاتصال بقاعدة البيانات</p>');
}

$messages = [];

// ── Create Demo Users ────────────────────────────────────────
$demo_users = [
    ['مدير النظام', '0500000000', 'admin@mazadi.sa', 'admin123', 'admin'],
    ['شركة الوطنية للتأجير', '0500000001', 'seller@mazadi.sa', 'seller123', 'seller'],
    ['أحمد محمد المشتري', '0500000002', 'buyer@mazadi.sa', 'buyer123', 'buyer'],
    ['مشترٍ ثانٍ', '0500000003', 'buyer2@mazadi.sa', 'buyer123', 'buyer'],
];

foreach ($demo_users as $u) {
    $mobile = $u[1];
    $check = $conn->prepare("SELECT id FROM users WHERE mobile=?");
    $check->bind_param('s', $mobile);
    $check->execute();
    
    if ($check->get_result()->num_rows === 0) {
        $hash = password_hash($u[3], PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO users (full_name, mobile, email, password_hash, role, city, is_active, wallet_balance) VALUES (?,?,?,?,?,'الرياض',1,10000)");
        $v1 = $u[0]; $v2 = $u[1]; $v3 = $u[2]; $v4 = $hash; $v5 = $u[4];
        $stmt->bind_param('sssss', $v1, $v2, $v3, $v4, $v5);
        if ($stmt->execute()) {
            $uid = $conn->insert_id;
            $messages[] = "✅ تم إنشاء حساب: {$u[0]} ({$u[4]}) - جوال: {$u[1]} - كلمة المرور: {$u[3]}";
            
            // Create seller company if seller
            if ($u[4] === 'seller') {
                $company = $u[0];
                $cr = '1010' . rand(100000, 999999);
                $stmt2 = $conn->prepare("INSERT INTO seller_companies (user_id, company_name, cr_number, is_verified, rating) VALUES (?,?,?,1,4.5)");
                $stmt2->bind_param('iss', $uid, $company, $cr);
                $stmt2->execute();
                $messages[] = "  → تم إنشاء شركة البيع: $company";
            }
        }
    } else {
        $messages[] = "⏭️ المستخدم موجود بالفعل: {$u[0]} ({$u[1]})";
    }
}

// ── Create Sample Vehicles and Auctions ─────────────────────
// Get seller company
$seller_co = $conn->query("SELECT sc.id FROM seller_companies sc JOIN users u ON sc.user_id=u.id WHERE u.role='seller' LIMIT 1")->fetch_assoc();
$seller_id = $seller_co['id'] ?? 0;

if ($seller_id) {
    $sample_auctions = [
        // Make, Model, Year, Mileage, Type, Price, Inc, FuelType, City, Featured
        ['Toyota',  'Camry',    2022, 35000, 'live',   75000, 1000, 'بنزين', 'الرياض', 1, 'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?w=800&q=80'],
        ['Hyundai', 'Sonata',   2021, 42000, 'live',   65000, 500,  'بنزين', 'جدة',    0, 'https://images.unsplash.com/photo-1568844293986-ca9c5c6f8b8a?w=800&q=80'],
        ['Kia',     'Sorento',  2022, 28000, 'live',   95000, 1000, 'بنزين', 'الدمام', 0, 'https://images.unsplash.com/photo-1606664515524-ed2f786a0bd6?w=800&q=80'],
        ['Nissan',  'Altima',   2021, 55000, 'instant',58000, 500,  'بنزين', 'الرياض', 0, 'https://images.unsplash.com/photo-1555215695-3004980ad54e?w=800&q=80'],
        ['BMW',     '520i',     2020, 48000, 'live',   145000,2000, 'بنزين', 'جدة',    1, 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=800&q=80'],
        ['Mercedes','C200',     2021, 38000, 'live',   165000,2000, 'بنزين', 'الرياض', 1, 'https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?w=800&q=80'],
        ['Honda',   'Accord',   2022, 25000, 'instant',70000, 500,  'بنزين', 'الدمام', 0, 'https://images.unsplash.com/photo-1580273916550-e323be2ae537?w=800&q=80'],
        ['Toyota',  'Fortuner', 2021, 60000, 'live',   120000,1000, 'ديزل',  'الرياض', 0, 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?w=800&q=80'],
        ['Chevrolet','Malibu',  2020, 70000, 'instant',45000, 500,  'بنزين', 'جدة',    0, 'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?w=800&q=80'],
        ['Hyundai', 'Tucson',   2022, 22000, 'sealed', 88000, 1000, 'بنزين', 'المدينة المنورة', 0, 'https://images.unsplash.com/photo-1571987502227-9231b837d92a?w=800&q=80'],
    ];
    
    foreach ($sample_auctions as $a) {
        [$make, $model, $year, $mileage, $atype, $price, $inc, $fuel, $city, $featured, $img] = $a;
        
        // Check if exists
        $check = $conn->query("SELECT id FROM vehicles WHERE make='$make' AND model='$model' AND year=$year AND seller_id=$seller_id LIMIT 1");
        if ($check->num_rows > 0) {
            $messages[] = "⏭️ سيارة موجودة: $make $model $year";
            continue;
        }
        
        // Vehicle
        $desc = "سيارة $make $model موديل $year بحالة ممتازة، استخدام شخصي بحت، تاريخ صيانة كامل ومتوفر. خالية من الحوادث.";
        $stmt = $conn->prepare("INSERT INTO vehicles (seller_id, make, model, year, mileage, color, fuel_type, transmission, city, condition_grade, description, image_url, status) VALUES (?,?,?,?,?,'أبيض لؤلؤي',?,'أوتوماتيك',?,'ممتازة',?,?,'in_auction')");
        $stmt->bind_param('issiissss', $seller_id, $make, $model, $year, $mileage, $fuel, $city, $desc, $img);
        $stmt->execute();
        $vid = $conn->insert_id;
        
        // Auction
        $title = "$make $model $year";
        $end_time = ($atype !== 'instant') ? date('Y-m-d H:i:s', strtotime('+' . rand(2, 10) . ' days')) : null;
        
        $stmt2 = $conn->prepare("INSERT INTO auctions (vehicle_id, seller_id, title, type, status, starting_price, current_price, bid_increment, start_time, end_time, is_featured) VALUES (?,?,?,?,'active',?,?,?,NOW(),?,?)");
        $stmt2->bind_param('iissdddssi', $vid, $seller_id, $title, $atype, $price, $price, $inc, $end_time, $featured);
        $stmt2->execute();
        $aid = $conn->insert_id;
        
        // Add some sample bids for live auctions
        if ($atype === 'live') {
            $buyers = $conn->query("SELECT id FROM users WHERE role='buyer' LIMIT 2");
            $buyer_ids = [];
            while ($b = $buyers->fetch_assoc()) $buyer_ids[] = $b['id'];
            
            if (!empty($buyer_ids)) {
                $current = $price;
                for ($i = 0; $i < rand(3, 8); $i++) {
                    $current += $inc;
                    $bidder = $buyer_ids[array_rand($buyer_ids)];
                    $stmt3 = $conn->prepare("INSERT INTO bids (auction_id, user_id, amount, created_at) VALUES (?,?,?,DATE_SUB(NOW(), INTERVAL ? HOUR))");
                    $hrs = rand(1, 48);
                    $stmt3->bind_param('iidi', $aid, $bidder, $current, $hrs);
                    $stmt3->execute();
                }
                // Update auction current price
                $conn->query("UPDATE auctions SET current_price=$current WHERE id=$aid");
            }
        }
        
        // Add to watchlist
        $buyers_res = $conn->query("SELECT id FROM users WHERE role='buyer' LIMIT 1");
        if ($b = $buyers_res->fetch_assoc()) {
            $conn->query("INSERT IGNORE INTO watchlist (user_id, auction_id) VALUES ({$b['id']}, $aid)");
        }
        
        $messages[] = "✅ تم إنشاء: $make $model $year ($atype) - #{$aid}";
    }
    
    // Create sample notifications
    $buyer_row = $conn->query("SELECT id FROM users WHERE role='buyer' LIMIT 1")->fetch_assoc();
    if ($buyer_row) {
        $bid = (int)$buyer_row['id'];
        $check = $conn->query("SELECT COUNT(*) as c FROM notifications WHERE user_id=$bid")->fetch_assoc();
        if ($check['c'] == 0) {
            $notifs = [
                [$bid, 'bid_placed', 'تمت مزايدتك', 'مزايدتك على تويوتا كامري 2022 تمت بنجاح', '/auctions.php'],
                [$bid, 'outbid', 'تم تجاوز مزايدتك!', 'قام أحد المشترين بتجاوز مزايدتك على BMW 520i', '/auctions.php'],
                [$bid, 'system', 'مرحباً بك في مزادي', 'حسابك جاهز للمزايدة. تصفح المزادات النشطة الآن!', '/auctions.php'],
            ];
            foreach ($notifs as $n) {
                createNotification($conn, $n[0], $n[1], $n[2], $n[3], $n[4]);
            }
            $messages[] = "✅ تم إنشاء إشعارات تجريبية";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>إعداد المنصة | مزادي</title>
  <link rel="stylesheet" href="/assets/css/mazadi.css">
</head>
<body>
<div class="container" style="max-width:700px; padding:60px 20px;">
  <h1 style="margin-bottom:8px; color:var(--primary);">⚙️ إعداد منصة مزادي</h1>
  <p style="color:var(--text-muted); margin-bottom:32px;">تم إعداد البيانات التجريبية بنجاح</p>
  
  <div class="card" style="padding:24px; margin-bottom:24px;">
    <h3 style="margin-bottom:16px;">حسابات تسجيل الدخول</h3>
    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:16px;">
      <?php foreach ([
        ['مدير', '0500000000', 'admin123', 'badge-live', '/admin/index.php'],
        ['بائع', '0500000001', 'seller123', 'badge-active', '/seller/dashboard.php'],
        ['مشترٍ', '0500000002', 'buyer123', 'badge-upcoming', '/buyer/dashboard.php'],
      ] as $acc): ?>
      <a href="<?= $acc[4] ?>" class="card" style="padding:16px; text-align:center; text-decoration:none; display:block;">
        <span class="badge <?= $acc[3] ?>" style="margin-bottom:8px; display:inline-flex;"><?= $acc[0] ?></span>
        <div style="font-weight:700; margin:4px 0;"><?= $acc[1] ?></div>
        <div style="font-size:0.8rem; color:var(--text-muted);">كلمة المرور: <?= $acc[2] ?></div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  
  <div class="card" style="padding:24px; margin-bottom:24px;">
    <h3 style="margin-bottom:16px;">سجل العمليات</h3>
    <div style="font-family:monospace; font-size:0.82rem; line-height:2;">
      <?php foreach ($messages as $msg): ?>
        <div style="color:<?= str_starts_with($msg, '✅') ? 'var(--success)' : (str_starts_with($msg, '⏭') ? 'var(--text-muted)' : 'var(--danger)') ?>; padding:4px 0;"><?= htmlspecialchars($msg) ?></div>
      <?php endforeach; ?>
    </div>
  </div>
  
  <div style="display:flex; gap:12px;">
    <a href="/index.php" class="btn btn-primary btn-lg">الذهاب للموقع</a>
    <a href="/login.php" class="btn btn-secondary btn-lg">صفحة تسجيل الدخول</a>
  </div>
  
  <div class="alert alert-warning" style="margin-top:24px;">
    <i class="ph-fill ph-warning"></i>
    <strong>تنبيه أمني:</strong> احذف هذا الملف (setup.php) فور الانتهاء من الاختبار
  </div>
</div>
<script src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>
<link rel="stylesheet" href="/assets/css/mazadi.css">
</body>
</html>
