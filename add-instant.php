<?php
require_once 'config.php';

requireLogin();
if (!in_array(getUserRole(), ['seller', 'admin'], true)) {
    header('Location: /register.php?type=company');
    exit;
}

// Fetch/Assign seller ID
$seller_id = null;
if ($db_connected) {
    $uid = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id FROM seller_companies WHERE user_id = ? LIMIT 1");
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $seller_id = $row['id'];
    } else {
        // Create a mock seller company if none exists
        $cname = $_SESSION['user_name'] . ' للتأجير';
        $cr = 'CR-' . rand(100000, 999999);
        $ins = $conn->prepare("INSERT INTO seller_companies (user_id, company_name, cr_number, fleet_size, is_verified) VALUES (?, ?, ?, 10, 1)");
        $ins->bind_param('iss', $uid, $cname, $cr);
        $ins->execute();
        $seller_id = $conn->insert_id;
    }
}

$success = false;
$msg = '';

$seller_events = [];
if ($db_connected && $seller_id) {
    $res = $conn->query("SELECT id, title FROM auction_events WHERE seller_id = $seller_id AND status = 'active'");
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $seller_events[] = $row;
        }
    } else {
        $res2 = $conn->query("SELECT id, title FROM auction_events WHERE status = 'active'");
        if ($res2) {
            while ($row = $res2->fetch_assoc()) {
                $seller_events[] = $row;
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = intval($_POST['event_id'] ?? 0);
    $make = $_POST['make'] ?? '';
    $model = $_POST['model'] ?? '';
    $year = intval($_POST['year'] ?? 2022);
    $mileage = intval($_POST['mileage'] ?? 0);
    $city = $_POST['city'] ?? 'الرياض';
    $fuel_type = $_POST['fuel_type'] ?? 'بنزين';
    $transmission = $_POST['transmission'] ?? 'أوتوماتيك';
    $price = floatval($_POST['price'] ?? 0);
    $desc = $_POST['description'] ?? '';
    $image_url = $_POST['image_url'] ?? '';

    // Multiple File upload logic
    $uploaded_urls = [];
    if (isset($_FILES['image_files'])) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        foreach ($_FILES['image_files']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['image_files']['error'][$key] === UPLOAD_ERR_OK) {
                $file_name = time() . '_' . $key . '_' . basename($_FILES['image_files']['name'][$key]);
                $file_path = $upload_dir . $file_name;
                if (move_uploaded_file($tmp_name, $file_path)) {
                    $uploaded_urls[] = '/' . $file_path;
                }
            }
        }
    }
    
    // Multiple Links logic
    $text_urls = $_POST['image_urls'] ?? '';
    if (!empty($text_urls)) {
        $lines = explode("\n", str_replace("\r", "", $text_urls));
        foreach ($lines as $l) {
            $l = trim($l);
            if (!empty($l)) $uploaded_urls[] = $l;
        }
    }
    
    $image_url = !empty($uploaded_urls) ? implode(',', $uploaded_urls) : '';

    if (empty($image_url)) {
        // Assign default image based on make
        $make_lower = strtolower($make);
        if (strpos($make_lower, 'toyota') !== false || strpos($make_lower, 'تويوتا') !== false) {
            $image_url = 'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?w=600&q=80';
        } elseif (strpos($make_lower, 'hyundai') !== false || strpos($make_lower, 'هيونداي') !== false) {
            $image_url = 'https://images.unsplash.com/photo-1568844293986-ca9c5c6f8b8a?w=600&q=80';
        } elseif (strpos($make_lower, 'mercedes') !== false || strpos($make_lower, 'مرسيدس') !== false) {
            $image_url = 'https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?w=600&q=80';
        } else {
            $image_url = 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=600&q=80';
        }
    }

    if ($price <= 0) {
        $msg = 'الرجاء إدخال سعر بيع فوري صحيح';
    } else {
        if ($db_connected && $seller_id) {
            // Insert into vehicles table
            $status = 'approved';
            $ins_veh = $conn->prepare("INSERT INTO vehicles (seller_id, make, model, year, mileage, city, fuel_type, transmission, description, image_url, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $ins_veh->bind_param('issiissssss', $seller_id, $make, $model, $year, $mileage, $city, $fuel_type, $transmission, $desc, $image_url, $status);
            
            if ($ins_veh->execute()) {
                $vehicle_id = $conn->insert_id;
                
                // Insert into auctions table as instant buy
                $title = $make . ' ' . $model . ' ' . $year;
                $type = 'instant';
                $auc_status = 'active';
                $now = date('Y-m-d H:i:s');
                // Instant buy auctions do not expire quickly or don't have standard countdowns
                $end_time = date('Y-m-d H:i:s', strtotime('+30 days')); 
                
                $ins_auc = $conn->prepare("INSERT INTO auctions (vehicle_id, seller_id, event_id, title, type, status, starting_price, current_price, buy_now_price, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $ins_auc->bind_param('iiisssdddss', $vehicle_id, $seller_id, $event_id, $title, $type, $auc_status, $price, $price, $price, $now, $end_time);
                
                if ($ins_auc->execute()) {
                    $success = true;
                    $msg = 'تم إدراج السيارة للبيع الفوري بنجاح!';
                } else {
                    $msg = 'حدث خطأ أثناء إدراج المزاد: ' . $conn->error;
                }
            } else {
                $msg = 'حدث خطأ أثناء إدراج المركبة: ' . $conn->error;
            }
        } else {
            // Mock submission success (for offline demo)
            $success = true;
            $msg = 'تمت محاكاة إدراج السيارة للبيع الفوري بنجاح في وضع عدم الاتصال!';
            
            $mock_auction = [
                'id' => rand(1000, 9999),
                'event_id' => $event_id,
                'title' => $make . ' ' . $model . ' ' . $year,
                'make' => $make,
                'model' => $model,
                'year' => $year,
                'mileage' => $mileage,
                'fuel_type' => $fuel_type,
                'transmission' => $transmission,
                'city' => $city,
                'current_price' => $price,
                'starting_price' => $price,
                'bid_count' => 0,
                'end_time' => null,
                'type' => 'instant',
                'image_url' => $image_url,
                'seller' => $_SESSION['user_name'] ?? 'الوطنية للتأجير'
            ];
            if (!isset($_SESSION['mock_auctions']) || !is_array($_SESSION['mock_auctions'])) {
                $_SESSION['mock_auctions'] = [];
            }
            $_SESSION['mock_auctions'][] = $mock_auction;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>إضافة سيارة للبيع الفوري | FleetX</title>
  <link rel="stylesheet" href="/assets/css/fleetx.css">
</head>
<body class="page-inner">

<!-- Navbar -->
<?php include 'includes/navbar.php'; ?>

<!-- Page Header -->
<header class="page-header">
  <div class="page-header-bg" style="background-image:url('https://images.unsplash.com/photo-1552519507-da3b142c6e3d?w=1600&q=80')"></div>
  <div class="container">
    <div class="fx-seller-kicker">بوابة البائعين</div>
    <h1 style="margin:0">إضافة سيارة للبيع الفوري المباشر</h1>
    <p style="color:var(--text-light-muted); font-size:16px; margin-top:6px; max-width:600px">اعرض سيارة أسطولك بسعر محدد مسبقاً للشراء الفوري السريع دون مزايدات.</p>
  </div>
</header>

<div class="container fx-seller-form-wrap">
  
  <div class="fx-seller-form-box">
    
    <?php if ($msg): ?>
      <div class="fx-seller-alert <?= $success ? 'success' : 'error' ?>">
        <?= $success ? '<i class="ph ph-check-circle"></i>' : '<i class="ph ph-warning-circle"></i>' ?> <?= sanitize($msg) ?>
      </div>
      <?php if ($success): ?>
        <script>
          setTimeout(() => { window.location.href = '/auctions.php?type=instant'; }, 2000);
        </script>
      <?php endif; ?>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data">
      <div class="fx-seller-form-grid">
        
        <div class="form-group span-full">
          <label class="form-label">فعالية المزاد (الحدث)</label>
          <select name="event_id" class="form-input" required>
            <?php foreach ($seller_events as $ev): ?>
              <option value="<?= $ev['id'] ?>"><?= sanitize($ev['title']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">الشركة المصنعة (الماركة)</label>
          <select name="make" class="form-input" required>
            <option value="تويوتا">تويوتا</option>
            <option value="هيونداي">هيونداي</option>
            <option value="كيا">كيا</option>
            <option value="نيسان">نيسان</option>
            <option value="مرسيدس">مرسيدس</option>
            <option value="BMW">BMW</option>
            <option value="فورد">فورد</option>
            <option value="هوندا">هوندا</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">الموديل (الطراز)</label>
          <input type="text" name="model" class="form-input" placeholder="مثال: كامري، توسان، سوناتا" required>
        </div>

        <div class="form-group">
          <label class="form-label">السنة</label>
          <select name="year" class="form-input" required>
            <?php for($y=2026; $y>=2015; $y--): ?>
              <option value="<?= $y ?>" <?= $y==2023?'selected':'' ?>><?= $y ?></option>
            <?php endfor; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">الممشى (المسافة المقطوعة بالكم)</label>
          <input type="number" name="mileage" class="form-input font-en" placeholder="مثال: 45000" required>
        </div>

        <div class="form-group">
          <label class="form-label">المدينة</label>
          <select name="city" class="form-input" required>
            <option value="الرياض">الرياض</option>
            <option value="جدة">جدة</option>
            <option value="الدمام">الدمام</option>
            <option value="مكة المكرمة">مكة المكرمة</option>
            <option value="المدينة المنورة">المدينة المنورة</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">نوع الوقود</label>
          <select name="fuel_type" class="form-input">
            <option value="بنزين">بنزين</option>
            <option value="ديزل">ديزل</option>
            <option value="هجين">هجين (هايبرد)</option>
            <option value="كهربائي">كهربائي بالكامل</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">ناقل الحركة</label>
          <select name="transmission" class="form-input">
            <option value="أوتوماتيك">أوتوماتيك</option>
            <option value="يدوي">يدوي (عادي)</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">سعر البيع الفوري المطلوب (ريال)</label>
          <input type="number" name="price" class="form-input font-en" placeholder="مثال: 85000" required>
        </div>

      </div>

      <div class="form-group" style="margin-top:24px">
        <label class="form-label">صورة السيارة</label>
        <div class="fx-image-type-row">
          <label><input type="radio" name="image_type" value="link" checked onchange="toggleImageTypeInstant(this.value)"> استخدام رابط</label>
          <label><input type="radio" name="image_type" value="upload" onchange="toggleImageTypeInstant(this.value)"> رفع من الجهاز</label>
        </div>
        
        <div id="image_link_input_instant">
          <textarea name="image_urls" class="form-input font-en" rows="3" placeholder="أدخل روابط الصور (كل رابط في سطر منفصل)... (اترك فارغاً لتعيين صورة تلقائية)"></textarea>
        </div>
        
        <div id="image_upload_input_instant" style="display:none;">
          <input type="file" name="image_files[]" class="form-input" accept="image/*" multiple><div style="font-size:12px; color:var(--text-muted); margin-top:8px;">يمكنك تحديد أكثر من صورة بالضغط على زر Ctrl (في ويندوز) أو Command (في ماك).</div>
        </div>
        
        <script>
        function toggleImageTypeInstant(type) {
          if (type === 'link') {
            document.getElementById('image_link_input_instant').style.display = 'block';
            document.getElementById('image_upload_input_instant').style.display = 'none';
          } else {
            document.getElementById('image_link_input_instant').style.display = 'none';
            document.getElementById('image_upload_input_instant').style.display = 'block';
          }
        }
        </script>
      </div>

      <div class="form-group" style="margin-top:24px">
        <label class="form-label">وصف وتفاصيل إضافية</label>
        <textarea name="description" class="form-input" rows="4" placeholder="اكتب أي معلومات أخرى تفيد المشتري بخصوص حالة السيارة..."></textarea>
      </div>

      <div class="fx-seller-form-actions">
        <a href="/seller.php" class="btn btn-outline-dark">إلغاء</a>
        <button type="submit" class="btn btn-primary" style="padding:14px 40px">إدراج للبيع الفوري الآن</button>
      </div>

    </form>

  </div>

</div>

<!-- Footer -->
<?php include 'includes/footer.php'; ?>

</body>
</html>
