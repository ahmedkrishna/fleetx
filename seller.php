<?php
require_once 'config.php';
requireLogin();
if (!in_array(getUserRole(), ['seller', 'admin'], true)) {
    header('Location: ' . getDashboardUrl());
    exit;
}

$section = isset($_GET['section']) ? sanitize($_GET['section']) : 'dashboard';
$user_name = $_SESSION['user_name'] ?? 'مستخدم';
$user_id = $_SESSION['user_id'] ?? 0;
$role = getUserRole();

// Get seller company info
$company = null;
if ($db_connected) {
    $stmt = $conn->prepare('SELECT * FROM seller_companies WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $company = $stmt->get_result()->fetch_assoc();
}
if (!$company && $db_connected && getUserRole() === 'seller') {
    $cname = $user_name . ' للتأجير';
    $cr = 'CR-' . rand(100000, 999999);
    $ins = $conn->prepare('INSERT INTO seller_companies (user_id, company_name, cr_number, fleet_size, is_verified) VALUES (?, ?, ?, 10, 1)');
    $ins->bind_param('iss', $user_id, $cname, $cr);
    if ($ins->execute()) {
        $company = [
            'id' => $conn->insert_id,
            'company_name' => $cname,
            'cr_number' => $cr,
            'subscription' => 'standard',
            'rating' => 0,
            'total_auctions' => 0,
            'is_verified' => 1,
        ];
    }
}
if (!$company && $db_connected && getUserRole() !== 'admin') {
    header('Location: /register.php?type=company&complete=1');
    exit;
}
if (!$company) {
    $company = [
        'id' => 0,
        'company_name' => 'شركة ' . $user_name,
        'cr_number' => '',
        'subscription' => 'standard',
        'rating' => 0,
        'total_auctions' => 0,
        'is_verified' => 0,
    ];
}
$company_id = (int)($company['id'] ?? 0);

// Handle settings POST
$settings_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $section === 'settings') {
    $company_name = sanitize($_POST['company_name'] ?? '');
    $cr_number = sanitize($_POST['cr_number'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $personal_name = sanitize($_POST['personal_name'] ?? '');

    if ($db_connected) {
        $stmt = $conn->prepare('UPDATE seller_companies SET company_name=?, cr_number=?, city=?, phone=?, email=? WHERE user_id=?');
        $stmt->bind_param('sssssi', $company_name, $cr_number, $city, $phone, $email, $user_id);
        if ($stmt->execute()) {
            $settings_msg = 'success';
            $company['company_name'] = $company_name;
            $company['cr_number'] = $cr_number;
            $company['city'] = $city;
            $company['phone'] = $phone;
            $company['email'] = $email;
        } else {
            $settings_msg = 'error';
        }
        if ($personal_name) {
            $stmt2 = $conn->prepare('UPDATE users SET full_name=? WHERE id=?');
            $stmt2->bind_param('si', $personal_name, $user_id);
            $stmt2->execute();
            $_SESSION['user_name'] = $personal_name;
            $user_name = $personal_name;
        }
    } else {
        // Mock save
        $settings_msg = 'success';
        $company['company_name'] = $company_name ?: $company['company_name'];
        $company['cr_number'] = $cr_number ?: $company['cr_number'];
        $company['city'] = $city ?: $company['city'];
        $company['phone'] = $phone ?: $company['phone'];
        $company['email'] = $email ?: $company['email'];
        if ($personal_name) {
            $_SESSION['user_name'] = $personal_name;
            $user_name = $personal_name;
        }
    }
}

// Handle Push to Inspection
if (isset($_GET['push_inspection'])) {
    $vehicle_id = intval($_GET['push_inspection']);
    if ($db_connected && $vehicle_id > 0 && !empty($company_id)) {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE vehicles SET status='pending' WHERE id=? AND seller_id=?");
            $stmt->bind_param('ii', $vehicle_id, $company_id);
            $stmt->execute();

            $inspector_id = getDefaultInspectorId($conn);
            $stmt2 = $conn->prepare("INSERT INTO inspections (vehicle_id, inspector_id, status, inspection_date) VALUES (?, ?, 'pending', CURDATE()) ON DUPLICATE KEY UPDATE status='pending'");
            $stmt2->bind_param('ii', $vehicle_id, $inspector_id);
            $stmt2->execute();

            $conn->commit();

            notifyUser($conn, 1, 'system', 'طلب فحص جديد', 'قامت شركة ' . $company['company_name'] . ' بطلب فحص مركبة جديدة', '/admin/inspections.php');
            notifyUser($conn, $inspector_id, 'system', 'طلب فحص جديد', 'تم تعيين فحص مركبة جديدة لك', '/inspector.php', ['in_app', 'sms']);

            header('Location: ?section=fleet&msg=pushed');
            exit;
        } catch (Exception $e) {
            $conn->rollback();
        }
    }
}

// Publish approved vehicle to auction
if (isset($_GET['publish_auction'])) {
    $vehicle_id = intval($_GET['publish_auction']);
    if ($db_connected && $vehicle_id > 0 && !empty($company_id)) {
        $vstmt = $conn->prepare("SELECT * FROM vehicles WHERE id=? AND seller_id=? AND status='approved' LIMIT 1");
        $vstmt->bind_param('ii', $vehicle_id, $company_id);
        $vstmt->execute();
        $veh = $vstmt->get_result()->fetch_assoc();
        if ($veh) {
            $exists = $conn->prepare("SELECT id FROM auctions WHERE vehicle_id=? AND status IN ('active','live','draft') LIMIT 1");
            $exists->bind_param('i', $vehicle_id);
            $exists->execute();
            if ($exists->get_result()->num_rows === 0) {
                $title = $veh['make'] . ' ' . $veh['model'] . ' ' . $veh['year'];
                $start = date('Y-m-d H:i:s');
                $end = date('Y-m-d H:i:s', strtotime('+48 hours'));
                $price = floatval($veh['autodata_price_min'] ?? 50000);
                $ins = $conn->prepare("INSERT INTO auctions (vehicle_id, seller_id, title, type, status, starting_price, current_price, bid_increment, start_time, end_time) VALUES (?,?,?,'live','active',?,?,500,?,?)");
                $ins->bind_param('iissdss', $vehicle_id, $company_id, $title, $price, $price, $start, $end);
                $ins->execute();
                $conn->query("UPDATE vehicles SET status='in_auction' WHERE id=$vehicle_id");
            }
            header('Location: ?section=fleet&msg=published');
            exit;
        }
    }
}

// Handle Delete Vehicle
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    if ($db_connected && $del_id > 0 && !empty($company_id)) {
        $stmt = $conn->prepare("DELETE FROM vehicles WHERE id=? AND seller_id=?");
        $stmt->bind_param('ii', $del_id, $company_id);
        $stmt->execute();
        header('Location: ?section=fleet&msg=deleted');
        exit;
    }
}

// Get fleet data for fleet + dashboard sections (all vehicles, with optional auction)
$fleet_auctions = [];
$total_sales = 0;
$fleet_count = 0;
if ($db_connected && !empty($company_id)) {
    $stmt = $conn->prepare("
        SELECT v.id as vehicle_id, v.make, v.model, v.year, v.city, v.mileage, v.image_url,
               v.status as v_status, v.autodata_price_min, v.autodata_price_max,
               a.id, a.title, a.current_price, a.starting_price, a.status, a.type,
               (SELECT COUNT(*) FROM bids WHERE auction_id=a.id) as bid_count,
               (SELECT MAX(amount) FROM bids WHERE auction_id=a.id) as top_bid
        FROM vehicles v
        LEFT JOIN auctions a ON a.vehicle_id = v.id AND a.status NOT IN ('ended','cancelled')
        WHERE v.seller_id = ?
        ORDER BY v.created_at DESC
    ");
    $stmt->bind_param('i', $company_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $fleet_auctions[] = $row;
    }
    $fleet_count = count($fleet_auctions);
    // Get total sales
    $stmt2 = $conn->prepare('SELECT COALESCE(SUM(sale_price), 0) FROM transactions WHERE seller_id=? AND payment_status="paid"');
    $stmt2->bind_param('i', $company_id);
    $stmt2->execute();
    $total_sales = $stmt2->get_result()->fetch_row()[0];
    // Get pending dues
    $stmt3 = $conn->prepare('SELECT COALESCE(SUM(seller_payout), 0) FROM transactions WHERE seller_id=? AND payment_status="pending"');
    if ($stmt3) {
        $stmt3->bind_param('i', $company_id);
        $stmt3->execute();
        $pending_dues = $stmt3->get_result()->fetch_row()[0];
    } else {
        $pending_dues = 0;
    }
} else {
    $pending_dues = 0;
}

// Seller dashboard analytics
$seller_chart_labels = [];
$seller_chart_sales = [];
$seller_activities = [];
$active_auctions_count = 0;
$pending_inspections = 0;

if ($db_connected && !empty($company_id)) {
    for ($m = 5; $m >= 0; $m--) {
        $month_start = date('Y-m-01', strtotime("-$m months"));
        $month_end = date('Y-m-t', strtotime("-$m months"));
        $seller_chart_labels[] = date('M Y', strtotime($month_start));
        $cstmt = $conn->prepare("SELECT COALESCE(SUM(sale_price),0) FROM transactions WHERE seller_id=? AND payment_status='paid' AND paid_at BETWEEN ? AND ?");
        $month_end_sql = $month_end . ' 23:59:59';
        $cstmt->bind_param('iss', $company_id, $month_start, $month_end_sql);
        $cstmt->execute();
        $seller_chart_sales[] = (float)($cstmt->get_result()->fetch_row()[0] ?? 0);
    }

    $ac = $conn->prepare("SELECT COUNT(*) FROM auctions WHERE seller_id=? AND status IN ('active','live')");
    $ac->bind_param('i', $company_id);
    $ac->execute();
    $active_auctions_count = (int)$ac->get_result()->fetch_row()[0];

    $pi = $conn->prepare("SELECT COUNT(*) FROM vehicles WHERE seller_id=? AND status='pending'");
    $pi->bind_param('i', $company_id);
    $pi->execute();
    $pending_inspections = (int)$pi->get_result()->fetch_row()[0];

    $act = $conn->prepare("
        SELECT 'bid' as src, b.amount as val, b.created_at, CONCAT('مزايدة جديدة على ', COALESCE(a.title, v.make)) as msg
        FROM bids b
        JOIN auctions a ON b.auction_id = a.id
        JOIN vehicles v ON a.vehicle_id = v.id
        WHERE a.seller_id = ?
        UNION ALL
        SELECT 'inspection', i.overall_score, i.created_at, CONCAT('طلب فحص ', v.make, ' ', v.model) as msg
        FROM inspections i JOIN vehicles v ON i.vehicle_id = v.id WHERE v.seller_id = ?
        ORDER BY created_at DESC LIMIT 8
    ");
    if ($act) {
        $act->bind_param('ii', $company_id, $company_id);
        $act->execute();
        $ares = $act->get_result();
        while ($ar = $ares->fetch_assoc()) $seller_activities[] = $ar;
    }
}

// Also fetch draft/pending vehicles (not yet in auctions) for fleet management
$draft_vehicles = [];
if ($db_connected) {
    $dstmt = $conn->prepare("SELECT v.* FROM vehicles v WHERE v.seller_id=? AND v.id NOT IN (SELECT vehicle_id FROM auctions WHERE seller_id=?) ORDER BY v.created_at DESC");
    if ($dstmt) {
        $dstmt->bind_param('ii', $company_id, $company_id);
        $dstmt->execute();
        $dres = $dstmt->get_result();
        while ($dr = $dres->fetch_assoc()) {
            $draft_vehicles[] = $dr;
        }
    }
}

// Subscription data
$plans = [
    'standard' => [
        'name' => 'الباقة الأساسية',
        'price' => 'مجاناً',
        'price_num' => 0,
        'color' => '#64748b',
        'icon' => 'ph-package',
        'features' => ['حتى 5 مركبات شهرياً', 'تقارير أساسية', 'دعم بالبريد الإلكتروني', 'عمولة 3% على المبيعات']
    ],
    'premium' => [
        'name' => 'الباقة المتقدمة',
        'price' => '999 ر.س/شهر',
        'price_num' => 999,
        'color' => '#0ea5e9',
        'icon' => 'ph-crown',
        'features' => ['حتى 50 مركبة شهرياً', 'تقارير تفصيلية وتحليلات', 'دعم أولوية 24/7', 'عمولة 1.5% على المبيعات', 'شارة بائع متميز', 'ترويج في الصفحة الرئيسية']
    ],
    'enterprise' => [
        'name' => 'باقة المؤسسات',
        'price' => 'تواصل معنا',
        'price_num' => -1,
        'color' => '#1bc976',
        'icon' => 'ph-buildings',
        'features' => ['مركبات غير محدودة', 'مدير حساب مخصص', 'API ربط مباشر', 'عمولة مخفضة 0.5%', 'تقارير مخصصة وبيانات حية', 'أولوية القوائم والعرض', 'تدريب فريق العمل']
    ]
];
$current_plan = $company['subscription'] ?? 'premium';
// Inspection reports
$reports = [];
if ($db_connected) {
    $stmt = $conn->prepare("SELECT i.*, v.make, v.model, v.year, v.vin, u.full_name AS inspector_name FROM inspections i JOIN vehicles v ON i.vehicle_id = v.id LEFT JOIN users u ON i.inspector_id = u.id WHERE v.seller_id = ? ORDER BY i.inspection_date DESC, i.id DESC LIMIT 20");
    if ($stmt) {
        $stmt->bind_param('i', $company_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $reports[] = [
                'id' => $r['id'],
                'vehicle' => $r['make'].' '.$r['model'].' '.$r['year'],
                'vin' => $r['vin'],
                'date' => isset($r['inspection_date']) ? date('Y-m-d', strtotime($r['inspection_date'])) : date('Y-m-d'),
                'inspector' => $r['inspector_name'] ?? 'مفتش المنصة',
                'exterior' => $r['exterior_score'] ?? 90,
                'interior' => $r['interior_score'] ?? 90,
                'mechanical' => $r['mechanical_score'] ?? 90,
                'overall' => round((($r['exterior_score'] ?? 90) + ($r['interior_score'] ?? 90) + ($r['mechanical_score'] ?? 90) + ($r['electronics_score'] ?? 90)) / 4),
                'status' => $r['status'] ?? 'passed'
            ];
        }
    }
}

// Payout transactions
$payouts = [];
if ($db_connected) {
    $stmt_payouts = $conn->prepare("SELECT t.*, v.make, v.model, v.year FROM transactions t JOIN auctions a ON t.auction_id = a.id JOIN vehicles v ON a.vehicle_id = v.id WHERE t.seller_id = ? ORDER BY t.created_at DESC LIMIT 20");
    if ($stmt_payouts) {
        $stmt_payouts->bind_param('i', $company_id);
        $stmt_payouts->execute();
        $res = $stmt_payouts->get_result();
        while ($r = $res->fetch_assoc()) {
            $payouts[] = [
                'id' => 'TXN-' . date('Ymd', strtotime($r['created_at'])) . '-' . str_pad($r['id'], 3, '0', STR_PAD_LEFT),
                'date' => date('Y-m-d', strtotime($r['created_at'])),
                'vehicle' => $r['make'].' '.$r['model'].' '.$r['year'],
                'amount' => $r['sale_price'],
                'commission' => $r['platform_fee'],
                'net' => $r['seller_payout'],
                'status' => ($r['payment_status'] === 'paid' ? 'completed' : 'pending')
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>لوحة البائع | FleetX</title>
  <link rel="stylesheet" href="/assets/css/fleetx.css">
  </head>
<body class="fx-home fx-page-shell fx-page-shell--seller">
<?php include 'includes/navbar.php'; ?>

<?php
$hero_title_html = sanitize($company['company_name']);
if (!empty($company['is_verified'])) {
    $hero_title_html .= ' <span class="verified-badge"><i class="ph-fill ph-seal-check"></i> بائع موثق</span>';
}
$hero_desc = 'إدارة كاملة لأسطولك ومزاداتك ومستحقاتك المالية';
$hero_bg = 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=1600&q=80';
$hero_modifier = 'light';
$hero_eyebrow = 'لوحة البائع';
$hero_meta_html = '<span class="fx-page-hero__chip"><i class="ph-fill ph-car"></i> ' . (int)$fleet_count . ' مركبة معروضة</span>'
    . '<span class="fx-page-hero__chip"><i class="ph-fill ph-currency-circle-dollar"></i> ' . number_format((float)$total_sales) . ' ر.س مبيعات</span>'
    . '<span class="fx-page-hero__chip fx-page-hero__chip--accent"><i class="ph-fill ph-gavel"></i> ' . (int)($active_auctions_count ?? 0) . ' مزاد نشط</span>';
$hero_actions_html = '<a href="/add-auction.php" class="btn btn-primary"><i class="ph ph-gavel ph-space-left"></i> مزاد مباشر</a>'
    . '<a href="/add-auction.php?type=instant" class="btn btn-outline"><i class="ph ph-lightning ph-space-left"></i> بيع فوري</a>';
include 'includes/page-hero.inc.php';
?>

<div class="container fx-page-body fx-page-body--overlap fx-seller-page">
  <div class="fx-seller-layout">

  <!-- ── SIDEBAR ── -->
  <aside class="fx-profile-sidebar fx-profile-sidebar--home fx-seller-sidebar">
    <div class="fx-seller-profile">
      <div class="fx-seller-avatar"><i class="ph-fill ph-buildings"></i></div>
      <div class="fx-seller-company-name"><?= sanitize($company['company_name']) ?></div>
      <span class="subscription-badge sub-<?= $current_plan ?>">
        <i class="ph-fill ph-crown"></i>
        <?= $plans[$current_plan]['name'] ?? 'الباقة المتقدمة' ?>
      </span>
      <?php if ($company['is_verified']): ?>
      <div class="fx-seller-verified">
        <i class="ph-fill ph-seal-check"></i> حساب موثق ومعتمد
      </div>
      <?php endif; ?>
    </div>
    <ul class="fx-profile-nav fx-seller-nav">
      <li><a href="?section=dashboard" class="<?= $section==='dashboard'?'active':'' ?>"><i class="ph ph-chart-bar"></i> لوحة التحكم</a></li>
      <li><a href="?section=fleet" class="<?= $section==='fleet'?'active':'' ?>"><i class="ph ph-car"></i> أسطولي المعروض</a></li>
      <li class="fx-seller-nav-label">إضافة إعلان</li>
      <li><a href="/add-auction.php" class="fx-seller-nav-sub <?= $section==='add_auction'?'active':'' ?>"><i class="ph ph-gavel fx-icon-primary"></i> جدولة مزاد مباشر</a></li>
      <li><a href="/add-auction.php?type=instant" class="fx-seller-nav-sub"><i class="ph ph-lightning fx-icon-warning"></i> بيع فوري</a></li>
      <li><a href="/bulk-upload.php" class="fx-seller-nav-sub"><i class="ph ph-upload-simple fx-icon-purple"></i> رفع مجمّع Excel</a></li>
      <li><a href="?section=payouts" class="<?= $section==='payouts'?'active':'' ?>"><i class="ph ph-money"></i> المستحقات المالية</a></li>
      <li><a href="?section=reports" class="<?= $section==='reports'?'active':'' ?>"><i class="ph ph-clipboard-text"></i> تقارير الفحص</a></li>
      <li><a href="?section=subscription" class="<?= $section==='subscription'?'active':'' ?>"><i class="ph ph-crown"></i> الباقة والاشتراك</a></li>
      <li><a href="?section=settings" class="<?= $section==='settings'?'active':'' ?>"><i class="ph ph-gear"></i> إعدادات الحساب</a></li>
      <li><a href="/logout.php" class="danger"><i class="ph ph-sign-out"></i> تسجيل خروج</a></li>
    </ul>
  </aside>

  <!-- ── MAIN CONTENT ── -->
  <main class="fx-seller-main">
    <div class="fx-dash-mobile-nav">
      <select onchange="if(this.value) window.location.href=this.value" aria-label="قائمة لوحة البائع">
        <option value="">انتقل إلى قسم...</option>
        <option value="?section=dashboard" <?= $section==='dashboard'?'selected':'' ?>>لوحة التحكم</option>
        <option value="?section=fleet" <?= $section==='fleet'?'selected':'' ?>>أسطولي المعروض</option>
        <option value="/add-auction.php">جدولة مزاد مباشر</option>
        <option value="/add-auction.php?type=instant">بيع فوري</option>
        <option value="/bulk-upload.php">رفع مجمّع Excel</option>
        <option value="?section=payouts" <?= $section==='payouts'?'selected':'' ?>>المستحقات المالية</option>
        <option value="?section=reports" <?= $section==='reports'?'selected':'' ?>>تقارير الفحص</option>
        <option value="?section=subscription" <?= $section==='subscription'?'selected':'' ?>>الباقة والاشتراك</option>
        <option value="?section=settings" <?= $section==='settings'?'selected':'' ?>>إعدادات الحساب</option>
      </select>
    </div>

    <?php
    // Nafath Check for Seller
    $nafath_verified = $_SESSION['nafath_verified'] ?? 0;
    if ($db_connected && !$nafath_verified) {
        $nst = $conn->prepare("SELECT nafath_verified FROM users WHERE id = ?");
        if($nst) {
            $nst->bind_param('i', $user_id); $nst->execute();
            if ($row = $nst->get_result()->fetch_assoc()) {
                $nafath_verified = $row['nafath_verified'];
                $_SESSION['nafath_verified'] = $nafath_verified;
            }
        }
    }
    ?>
    <?php if (!$nafath_verified): ?>
      <div class="fx-dash-alert fx-dash-alert--danger">
        <div class="fx-dash-alert__body">
          <i class="ph-fill ph-warning-circle"></i>
          <span>حسابك كبائع غير موثق في نفاذ. نرجو التوثيق لتتمكن من إضافة المزادات وعرض سياراتك للبيع.</span>
        </div>
        <a href="/nafath.php" class="btn btn-primary fx-dash-alert__btn">توثيق الآن</a>
      </div>
    <?php else: ?>
      <div class="fx-dash-alert fx-dash-alert--success">
        <i class="ph-fill ph-check-circle"></i>
        <span>الشركة موثقة عبر النفاذ الوطني</span>
      </div>
    <?php endif; ?>    <!-- ══════════════════════════════════════════════ -->
    <!-- SECTION: DASHBOARD                            -->
    <!-- ══════════════════════════════════════════════ -->
    <?php if ($section === 'dashboard'): ?>

    <div class="seller-header-bar fx-seller-card">
      <h1 class="seller-section-title"><i class="ph-fill ph-chart-bar fx-icon-primary"></i> لوحة التحكم</h1>
      <div class="fx-seller-actions-top">
        <a href="/add-auction.php" class="btn-action-top btn-action-top--live">
          <i class="ph ph-gavel"></i> مزاد مباشر
        </a>
        <a href="/add-auction.php?type=instant" class="btn-action-top btn-action-top--instant">
          <i class="ph ph-lightning"></i> بيع فوري
        </a>
        <a href="/bulk-upload.php" class="btn-action-top btn-action-top--bulk">
          <i class="ph ph-upload-simple"></i> رفع Excel
        </a>
      </div>
    </div>

    <div class="stats-grid fx-seller-stats">
      <div class="stat-card primary">
        <div class="stat-card-icon"><i class="ph-fill ph-car"></i></div>
        <div class="stat-card-label">مركبات معروضة</div>
        <div class="stat-card-value"><?= $fleet_count ?></div>
      </div>
      <div class="stat-card blue">
        <div class="stat-card-icon"><i class="ph-fill ph-currency-circle-dollar"></i></div>
        <div class="stat-card-label">إجمالي المبيعات</div>
        <div class="stat-card-value"><?= number_format($total_sales) ?> <span class="unit">ر.س</span></div>
      </div>
      <div class="stat-card warning">
        <div class="stat-card-icon"><i class="ph-fill ph-hourglass-medium"></i></div>
        <div class="stat-card-label">مستحقات معلقة</div>
        <div class="stat-card-value"><?= number_format($pending_dues) ?> <span class="unit">ر.س</span></div>
      </div>
      <div class="stat-card purple">
        <div class="stat-card-icon"><i class="ph-fill ph-gavel"></i></div>
        <div class="stat-card-label">مزادات نشطة</div>
        <div class="stat-card-value"><?= $active_auctions_count ?? 0 ?> <span class="unit">مزاد</span></div>
      </div>
    </div>

    <div class="activity-card fx-seller-card fx-seller-card--chart">
      <h3 class="activity-title"><i class="ph-fill ph-chart-line-up fx-icon-primary"></i> المبيعات الشهرية (ر.س)</h3>
      <canvas id="sellerSalesChart" height="80"></canvas>
    </div>

    <div class="activity-card fx-seller-card">
      <h3 class="activity-title"><i class="ph-fill ph-clock-counter-clockwise fx-icon-primary"></i> آخر النشاطات</h3>
      <ul class="activity-list">
        <?php if (empty($seller_activities)): ?>
        <li class="activity-item activity-item--empty">لا يوجد نشاط بعد — أضف مركباتك وابدأ المزادات</li>
        <?php else: foreach ($seller_activities as $act):
          $is_bid = ($act['src'] ?? '') === 'bid';
          $icon = $is_bid ? 'ph-gavel' : 'ph-clipboard-text';
          $tone = $is_bid ? 'bid' : 'inspection';
        ?>
        <li class="activity-item">
          <div class="activity-icon activity-icon--<?= $tone ?>"><i class="ph-fill <?= $icon ?>"></i></div>
          <div class="activity-info">
            <h4><?= sanitize($act['msg'] ?? 'نشاط') ?></h4>
            <p><?= sanitize($act['created_at'] ?? '') ?></p>
          </div>
          <?php if ($is_bid): ?>
          <div class="activity-amount activity-amount--bid"><?= number_format($act['val'] ?? 0) ?> <span>ر.س</span></div>
          <?php endif; ?>
        </li>
        <?php endforeach; endif; ?>
      </ul>
      <?php if (($pending_inspections ?? 0) > 0): ?>
      <a href="?section=fleet" class="fx-seller-fleet-link">
        <?= $pending_inspections ?> مركبة بانتظار الفحص — عرض الأسطول
      </a>
      <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
    (function(){
      const el = document.getElementById('sellerSalesChart');
      if (!el || typeof Chart === 'undefined') return;
      new Chart(el, {
        type: 'bar',
        data: {
          labels: <?= json_encode($seller_chart_labels ?? [], JSON_UNESCAPED_UNICODE) ?>,
          datasets: [{ label: 'المبيعات', data: <?= json_encode($seller_chart_sales ?? []) ?>, backgroundColor: 'rgba(27,201,118,0.7)', borderRadius: 8 }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
      });
    })();
    </script>

    <!-- ══════════════════════════════════════════════ -->
    <!-- SECTION: FLEET                                -->
    <!-- ══════════════════════════════════════════════ -->
    <?php elseif ($section === 'fleet'): ?>

    <div class="seller-header-bar fx-seller-card">
      <h1 class="seller-section-title"><i class="ph-fill ph-car fx-icon-primary"></i> أسطولي المعروض</h1>
      <a href="/add-auction.php" class="btn-action-top"><i class="ph ph-plus"></i> إضافة مركبة</a>
    </div>

    <?php if (empty($fleet_auctions)): ?>
    <div class="seller-empty fx-seller-card">
      <div class="fx-seller-empty-icon">
        <i class="ph-fill ph-car"></i>
      </div>
      <h3>لا توجد مركبات معروضة حالياً</h3>
      <p>ابدأ بإضافة مركباتك لعرضها في المزادات وجذب المشترين.</p>
      <a href="/add-auction.php" class="btn btn-primary" style="margin-top:24px; border-radius:30px; padding:12px 30px; font-weight:800;">أضف أول مركبة</a>
    </div>
    <?php else: ?>
    <div class="fleet-grid">
      <?php
      $statuses = ['active' => 'نشط', 'pending' => 'قيد المراجعة', 'ended' => 'منتهي', 'sold' => 'مباع'];
      $status_classes = ['active' => 'status-active', 'pending' => 'status-pending', 'ended' => 'status-ended', 'sold' => 'status-sold'];
      foreach (array_slice($fleet_auctions, 0, 12) as $idx => $car):
        $title = $car['title'] ?? ($car['make'] . ' ' . $car['model'] . ' ' . $car['year']);
        $img = getCarImage($car['make'] ?? '', $car['image_url'] ?? null);
        $bids = $car['bid_count'] ?? 0;
        $price = $car['current_price'] ?? $car['starting_price'] ?? 0;
        $views = 0; // Live data
        $vst = $car['v_status'] ?? 'pending';
        $vstatus_labels = ['pending' => 'بانتظار الفحص', 'approved' => 'معتمدة', 'in_auction' => 'في المزاد', 'sold' => 'مباعة', 'withdrawn' => 'مسحوبة'];
        $st = !empty($car['id']) ? ($car['status'] ?? 'active') : $vst;
        $st_class = $status_classes[$st] ?? ($vst === 'approved' ? 'status-active' : 'status-pending');
        $st_label = $statuses[$st] ?? ($vstatus_labels[$vst] ?? 'قيد المراجعة');
      ?>
      <div class="fleet-card">
        <div class="fleet-card-img">
          <img src="<?= sanitize($img) ?>" alt="<?= sanitize($title) ?>" loading="lazy">
          <span class="fleet-card-status <?= $st_class ?>"><?= $st_label ?></span>
        </div>
        <div class="fleet-card-body">
          <div class="fleet-card-title"><?= sanitize($title) ?></div>
          <div class="fleet-card-meta">
            <span><i class="ph ph-map-pin" style="font-size:14px;"></i> <?= sanitize($car['city'] ?? 'الرياض') ?></span>
            <span><i class="ph ph-gauge" style="font-size:14px;"></i> <?= number_format($car['mileage'] ?? 0) ?> كم</span>
            <span><i class="ph ph-calendar" style="font-size:14px;"></i> <?= $car['year'] ?? '2023' ?></span>
          </div>
          <?php if (!empty($car['autodata_price_min']) && !empty($car['autodata_price_max'])): ?>
          <div class="fleet-card-meta" style="color:#0ea5e9; font-weight:700;">
            <span><i class="ph ph-chart-line-up" style="font-size:14px;"></i> تقييم AutoData: <?= number_format($car['autodata_price_min']) ?> - <?= number_format($car['autodata_price_max']) ?> ر.س</span>
          </div>
          <?php endif; ?>
          <div class="fleet-card-stats">
            <div class="fleet-card-price"><?= number_format($price) ?> <span class="cur">ر.س</span></div>
            <div class="fleet-card-bids">
              <i class="ph ph-gavel" style="font-size:14px; color:var(--text-muted);"></i> <?= $bids ?> مزايدة
              <span style="margin:0 6px; color:var(--border-light);">|</span>
              <i class="ph ph-eye" style="font-size:14px; color:var(--text-muted);"></i> <?= $views ?>
            </div>
          </div>
          <div class="fleet-card-actions" style="flex-wrap: wrap;">
            <?php if (in_array($car['v_status'] ?? '', ['pending', 'withdrawn'], true)): ?>
            <a href="?section=fleet&push_inspection=<?= (int)$car['vehicle_id'] ?>" class="fleet-btn" style="background:#f59e0b; color:#fff; width:100%; margin-bottom:8px;"><i class="ph ph-magnifying-glass" style="font-size:14px;"></i> إرسال للفحص</a>
            <?php endif; ?>
            <?php if (($car['v_status'] ?? '') === 'approved' && empty($car['id'])): ?>
            <a href="?section=fleet&publish_auction=<?= (int)$car['vehicle_id'] ?>" class="fleet-btn" style="background:var(--primary); color:#000; width:100%; margin-bottom:8px;"><i class="ph ph-gavel" style="font-size:14px;"></i> نشر في المزاد</a>
            <?php endif; ?>
            <?php if (!empty($car['id'])): ?>
            <a href="/auction-room.php?id=<?= (int)$car['id'] ?>" class="fleet-btn fleet-btn-view"><i class="ph ph-eye" style="font-size:14px; color:inherit;"></i> عرض</a>
            <?php endif; ?>
            <a href="/add-auction.php?vehicle_id=<?= (int)$car['vehicle_id'] ?>" class="fleet-btn fleet-btn-edit"><i class="ph ph-pencil-simple" style="font-size:14px; color:inherit;"></i> تعديل</a>
            <button class="fleet-btn fleet-btn-delete" onclick="if(confirm('هل أنت متأكد من حذف هذه المركبة؟')) window.location.href='?section=fleet&delete=<?= (int)$car['vehicle_id'] ?>'"><i class="ph ph-trash" style="font-size:16px; color:inherit;"></i></button>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ══════════════════════════════════════════════ -->
    <!-- SECTION: INTEGRATION                          -->
    <!-- ══════════════════════════════════════════════ -->
    <?php elseif ($section === 'integration'): ?>

    <div class="seller-header-bar fx-seller-card">
      <h1 class="seller-section-title"><i class="ph-fill ph-plugs fx-icon-primary"></i> الربط الخارجي وجلب البيانات</h1>
    </div>

    <div class="stat-card fx-seller-card fx-seller-integration-card">
        <h3 style="margin-bottom: 15px;">استدعاء بيانات المركبة آلياً</h3>
        <p style="color: var(--text-muted); margin-bottom: 20px;">
            أدخل رقم الشاسيه (VIN) أو رقم اللوحة لجلب بيانات المركبة كاملة من مركز المعلومات الوطني (موجز/علم) لتسهيل عملية إضافة المركبة.
        </p>
        
        <div class="fx-integration-row">
            <div class="fx-integration-field">
                <label class="fx-integration-label">نوع البحث</label>
                <select id="integrationType" class="form-control fx-integration-input">
                    <option value="vin">رقم الشاسيه (VIN)</option>
                    <option value="plate">رقم اللوحة</option>
                </select>
            </div>
            <div class="fx-integration-field fx-integration-field--wide">
                <label class="fx-integration-label">القيمة (رقم الشاسيه أو اللوحة)</label>
                <div class="fx-integration-actions">
                    <input type="text" id="integrationValue" class="form-control fx-integration-input" placeholder="مثال: 1HGCM82633A..." />
                    <button type="button" class="btn btn-primary fx-integration-btn" onclick="fetchVehicleData()">
                        <i class="ph ph-magnifying-glass"></i> جلب البيانات
                    </button>
                </div>
            </div>
        </div>

        <div id="integrationResult" style="display: none; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: var(--radius-lg); padding: 20px; margin-top: 20px;">
            <h4 style="margin-bottom: 15px; color: #0ea5e9; display: flex; align-items: center; gap: 8px;">
                <i class="ph-fill ph-check-circle"></i> تم العثور على المركبة
            </h4>
            <div class="stats-grid fx-seller-stats fx-seller-stats--3">
                <div style="background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                    <span style="display:block; font-size:12px; color:gray; margin-bottom:4px;">الشركة والموديل</span>
                    <strong id="fetchedMakeModel" style="font-size:16px;">--</strong>
                </div>
                <div style="background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                    <span style="display:block; font-size:12px; color:gray; margin-bottom:4px;">سنة الصنع</span>
                    <strong id="fetchedYear" style="font-size:16px;">--</strong>
                </div>
                <div style="background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                    <span style="display:block; font-size:12px; color:gray; margin-bottom:4px;">اللون</span>
                    <strong id="fetchedColor" style="font-size:16px;">--</strong>
                </div>
            </div>
            
            <a href="/add-auction.php?autofill=1" class="btn btn-primary" style="width: 100%; text-align: center; justify-content: center; padding: 14px; border-radius: var(--radius-md); font-weight: bold;">
                <i class="ph ph-plus-circle"></i> إرسال البيانات وإضافة الإعلان
            </a>
        </div>
    </div>
    
    <script>
    function fetchVehicleData() {
        const val = document.getElementById('integrationValue').value;
        if (!val) {
            alert('الرجاء إدخال رقم الشاسيه أو اللوحة أولاً');
            return;
        }
        
        // Simulate API Loading
        const btn = event.currentTarget;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> جاري الجلب...';
        btn.disabled = true;
        
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            
            // Mock fetched data
            document.getElementById('fetchedMakeModel').innerText = 'تويوتا - كامري';
            document.getElementById('fetchedYear').innerText = '2023';
            document.getElementById('fetchedColor').innerText = 'أبيض لؤلؤي';
            
            document.getElementById('integrationResult').style.display = 'block';
            
            // Save in localStorage for the next page to pick up
            localStorage.setItem('fleetx_autofill', JSON.stringify({
                make: 'تويوتا',
                model: 'كامري',
                year: '2023',
                color: 'أبيض',
                vin: val,
                mileage: '24000',
                specs: 'سعودي'
            }));
            
        }, 1500);
    }
    </script>

    <!-- ══════════════════════════════════════════════ -->
    <!-- SECTION: PAYOUTS                              -->
    <!-- ══════════════════════════════════════════════ -->
    <?php elseif ($section === 'payouts'): 
      $payout_available = 0;
      $payout_total_paid = 0;
      $payout_pending = 0;
      $payout_fees = 0;

      if ($db_connected && isset($company_id)) {
          // Available Balance (paid payouts)
          $stmt_av = $conn->prepare('SELECT COALESCE(SUM(seller_payout), 0) FROM transactions WHERE seller_id=? AND payment_status="paid"');
          if ($stmt_av) {
              $stmt_av->bind_param('i', $company_id);
              $stmt_av->execute();
              $payout_available = $stmt_av->get_result()->fetch_row()[0];
              $payout_total_paid = $payout_available;
          }
          // Pending Balance
          $stmt_pe = $conn->prepare('SELECT COALESCE(SUM(seller_payout), 0) FROM transactions WHERE seller_id=? AND payment_status="pending"');
          if ($stmt_pe) {
              $stmt_pe->bind_param('i', $company_id);
              $stmt_pe->execute();
              $payout_pending = $stmt_pe->get_result()->fetch_row()[0];
          }
          // Fees Withheld
          $stmt_fe = $conn->prepare('SELECT COALESCE(SUM(platform_fee), 0) FROM transactions WHERE seller_id=? AND (payment_status="paid" OR payment_status="pending")');
          if ($stmt_fe) {
              $stmt_fe->bind_param('i', $company_id);
              $stmt_fe->execute();
              $payout_fees = $stmt_fe->get_result()->fetch_row()[0];
          }
      }
    ?>

    <div class="seller-header-bar fx-seller-card">
      <h1 class="seller-section-title"><i class="ph-fill ph-money fx-icon-primary"></i> سجل ودفعات المبيعات</h1>
    </div>

    <div class="payout-hero fx-seller-payout-hero">
      <i class="ph-fill ph-wallet payout-hero-bg"></i>
      <div style="position:relative; z-index:2;">
        <div class="payout-hero-label">رصيد المبيعات المتاح للسحب</div>
        <div class="payout-hero-amount"><?= number_format($payout_available) ?> <span class="cur">ر.س</span></div>
        <div class="payout-hero-note">تاريخ الصرف القادم: 8 يونيو 2026 • بنك الرياض الحساب ****4521</div>
      </div>
      <button class="payout-btn-transfer" style="position:relative; z-index:2;">
        <i class="ph ph-bank" style="font-size:20px; color:#fff;"></i> طلب تحويل الرصيد
      </button>
    </div>

    <!-- Summary Cards -->
    <div class="stats-grid fx-seller-stats fx-seller-stats--3">
      <div class="stat-card primary">
        <div class="stat-card-icon"><i class="ph-fill ph-check-circle"></i></div>
        <div class="stat-card-label">إجمالي المدفوعات المستلمة</div>
        <div class="stat-card-value"><?= number_format($payout_total_paid) ?> <span class="unit">ر.س</span></div>
      </div>
      <div class="stat-card warning">
        <div class="stat-card-icon"><i class="ph-fill ph-hourglass-medium"></i></div>
        <div class="stat-card-label">دفعات معلقة قيد التحقق</div>
        <div class="stat-card-value"><?= number_format($payout_pending) ?> <span class="unit">ر.س</span></div>
      </div>
      <div class="stat-card blue">
        <div class="stat-card-icon"><i class="ph-fill ph-percent"></i></div>
        <div class="stat-card-label">رسوم المنصة والضرائب المستقطعة</div>
        <div class="stat-card-value"><?= number_format($payout_fees) ?> <span class="unit">ر.س</span></div>
      </div>
    </div>

    <div class="payout-table-wrap fx-seller-card">
      <div class="payout-table-header" style="display:flex; justify-content:space-between; align-items:center;">
          <span>سجل التحويلات والمعاملات</span>
          <button class="btn btn-outline" style="font-size:13px; padding:6px 12px; background:#fff; border-color:var(--border-light);" onclick="alert('جاري التصدير...')"><i class="ph ph-download-simple"></i> تصدير CSV</button>
      </div>
      <table class="payout-table">
        <thead>
          <tr>
            <th>رقم العملية</th>
            <th>التاريخ</th>
            <th>المركبة</th>
            <th>مبلغ البيع</th>
            <th>العمولة</th>
            <th>صافي المبلغ</th>
            <th>الحالة</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($payouts as $tx): ?>
          <tr>
            <td style="font-family:var(--font-en); font-weight:700; color:var(--text-muted); font-size:12px;"><?= $tx['id'] ?></td>
            <td><?= $tx['date'] ?></td>
            <td style="font-weight:700;"><?= $tx['vehicle'] ?></td>
            <td style="font-family:var(--font-en); font-weight:800;"><?= number_format($tx['amount']) ?> <span style="font-family:var(--font-ar); font-size:11px;">ر.س</span></td>
            <td style="font-family:var(--font-en); color:#f43f5e; font-weight:700;">-<?= number_format($tx['commission']) ?></td>
            <td style="font-family:var(--font-en); font-weight:900; color:var(--primary);"><?= number_format($tx['net']) ?></td>
            <td><span class="payout-status <?= $tx['status'] ?>"><?= $tx['status'] === 'completed' ? 'مكتمل' : 'قيد المعالجة' ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- ══════════════════════════════════════════════ -->
    <!-- SECTION: REPORTS                              -->
    <!-- ══════════════════════════════════════════════ -->
    <?php elseif ($section === 'reports'): ?>

    <div class="seller-header-bar fx-seller-card">
      <h1 class="seller-section-title"><i class="ph-fill ph-clipboard-text fx-icon-primary"></i> تقارير الفحص</h1>
    </div>

    <?php foreach ($reports as $report):
      $scoreClass = function($s) {
        if ($s >= 90) return 'score-excellent';
        if ($s >= 80) return 'score-good';
        if ($s >= 70) return 'score-fair';
        return 'score-poor';
      };
    ?>
    <div class="report-card fx-seller-card">
      <div class="report-card-header">
        <div>
          <div class="report-vehicle-name"><?= sanitize($report['vehicle']) ?></div>
          <div class="report-vin">VIN: <?= $report['vin'] ?></div>
        </div>
        <span class="report-status-badge report-<?= $report['status'] ?>">
          <?= $report['status'] === 'passed' ? 'اجتاز الفحص' : 'قيد المراجعة' ?>
        </span>
      </div>
      <div class="report-scores">
        <div class="report-score-item">
          <div class="report-score-label">الهيكل الخارجي</div>
          <div class="report-score-circle <?= $scoreClass($report['exterior']) ?>"><?= $report['exterior'] ?></div>
        </div>
        <div class="report-score-item">
          <div class="report-score-label">المقصورة الداخلية</div>
          <div class="report-score-circle <?= $scoreClass($report['interior']) ?>"><?= $report['interior'] ?></div>
        </div>
        <div class="report-score-item">
          <div class="report-score-label">الحالة الميكانيكية</div>
          <div class="report-score-circle <?= $scoreClass($report['mechanical']) ?>"><?= $report['mechanical'] ?></div>
        </div>
        <div class="report-score-item">
          <div class="report-score-label">التقييم الإجمالي</div>
          <div class="report-score-circle <?= $scoreClass($report['overall']) ?>"><?= $report['overall'] ?></div>
        </div>
      </div>
      <div class="report-meta">
        <span><i class="ph ph-user" style="font-size:16px; color:var(--text-muted);"></i> <?= sanitize($report['inspector']) ?></span>
        <span><i class="ph ph-calendar" style="font-size:16px; color:var(--text-muted);"></i> <?= $report['date'] ?></span>
        <a href="#" style="color:var(--primary); font-weight:800; margin-right:auto; display:inline-flex; align-items:center; gap:5px;">
          <i class="ph ph-file-pdf" style="font-size:16px; color:var(--primary);"></i> تحميل التقرير
        </a>
      </div>
    </div>
    <?php endforeach; ?>

    <!-- ══════════════════════════════════════════════ -->
    <!-- SECTION: SUBSCRIPTION                         -->
    <!-- ══════════════════════════════════════════════ -->
    <?php elseif ($section === 'subscription'): ?>

    <div class="seller-header-bar fx-seller-card">
      <h1 class="seller-section-title"><i class="ph-fill ph-crown fx-icon-primary"></i> الباقة والاشتراك</h1>
    </div>

    <div class="plans-grid">
      <?php foreach ($plans as $key => $plan):
        $is_current = ($key === $current_plan);
      ?>
      <div class="plan-card <?= $is_current ? 'current-plan' : '' ?>">
        <div class="plan-icon" style="background: <?= $plan['color'] ?>15; color: <?= $plan['color'] ?>;">
          <i class="ph-fill <?= $plan['icon'] ?>"></i>
        </div>
        <div class="plan-name"><?= $plan['name'] ?></div>
        <div class="plan-price" style="color: <?= $plan['color'] ?>;">
          <?php if ($plan['price_num'] === 0): ?>
            مجاناً
          <?php elseif ($plan['price_num'] === -1): ?>
            <span style="font-size:20px; font-family:var(--font-ar);">تواصل معنا</span>
          <?php else: ?>
            <?= number_format($plan['price_num']) ?> <span class="monthly">ر.س/شهر</span>
          <?php endif; ?>
        </div>
        <ul class="plan-features">
          <?php foreach ($plan['features'] as $feat): ?>
          <li><i class="ph-fill ph-check-circle" style="color:<?= $plan['color'] ?>; flex-shrink:0;"></i> <?= $feat ?></li>
          <?php endforeach; ?>
        </ul>
        <?php if ($is_current): ?>
          <button class="plan-btn plan-btn-current"><i class="ph-fill ph-check" style="color:var(--primary); font-size:18px;"></i> باقتك الحالية</button>
        <?php elseif ($plan['price_num'] === -1): ?>
          <button class="plan-btn plan-btn-contact"><i class="ph ph-phone" style="color:#fff; font-size:18px;"></i> تواصل مع المبيعات</button>
        <?php else: ?>
          <button class="plan-btn plan-btn-upgrade"><i class="ph ph-rocket-launch" style="color:#fff; font-size:18px;"></i> ترقية الآن</button>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- ══════════════════════════════════════════════ -->
    <!-- SECTION: SETTINGS                             -->
    <!-- ══════════════════════════════════════════════ -->
    <?php elseif ($section === 'settings'): ?>

    <div class="seller-header-bar fx-seller-card">
      <h1 class="seller-section-title"><i class="ph-fill ph-gear fx-icon-primary"></i> إعدادات الحساب</h1>
    </div>

    <?php if ($settings_msg === 'success'): ?>
    <div class="alert-msg alert-success">
      <i class="ph-fill ph-check-circle" style="font-size:20px; color:#10b981;"></i> تم حفظ التعديلات بنجاح
    </div>
    <?php elseif ($settings_msg === 'error'): ?>
    <div class="alert-msg alert-error">
      <i class="ph-fill ph-warning" style="font-size:20px; color:#f43f5e;"></i> حدث خطأ أثناء الحفظ، يرجى المحاولة مرة أخرى
    </div>
    <?php endif; ?>

    <form method="POST" action="?section=settings">
      <!-- Company Info -->
      <div class="settings-section">
        <h3 class="settings-title"><i class="ph-fill ph-buildings" style="color:var(--primary); font-size:22px;"></i> معلومات الشركة</h3>
        <div class="form-grid">
          <div class="form-group">
            <label>اسم الشركة</label>
            <input type="text" name="company_name" value="<?= sanitize($company['company_name']) ?>" placeholder="أدخل اسم الشركة">
          </div>
          <div class="form-group">
            <label>رقم السجل التجاري</label>
            <input type="text" name="cr_number" value="<?= sanitize($company['cr_number']) ?>" placeholder="1010XXXXXX" style="font-family:var(--font-en);">
          </div>
          <div class="form-group">
            <label>المدينة</label>
            <select name="city">
              <option value="">اختر المدينة</option>
              <?php
              $cities = ['الرياض', 'جدة', 'الدمام', 'مكة المكرمة', 'المدينة المنورة', 'الخبر', 'بريدة', 'تبوك', 'أبها', 'حائل'];
              foreach ($cities as $c):
              ?>
              <option value="<?= $c ?>" <?= ($company['city'] ?? '') === $c ? 'selected' : '' ?>><?= $c ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>رقم الهاتف</label>
            <input type="tel" name="phone" value="<?= sanitize($company['phone'] ?? '') ?>" placeholder="05XXXXXXXX" style="font-family:var(--font-en); direction:ltr; text-align:right;">
          </div>
          <div class="form-group full">
            <label>البريد الإلكتروني</label>
            <input type="email" name="email" value="<?= sanitize($company['email'] ?? '') ?>" placeholder="email@company.com" style="font-family:var(--font-en); direction:ltr; text-align:right;">
          </div>
        </div>
      </div>

      <!-- Personal Info -->
      <div class="settings-section">
        <h3 class="settings-title"><i class="ph-fill ph-user" style="color:var(--primary); font-size:22px;"></i> المعلومات الشخصية</h3>
        <div class="form-grid">
          <div class="form-group">
            <label>الاسم الكامل</label>
            <input type="text" name="personal_name" value="<?= sanitize($user_name) ?>" placeholder="أدخل اسمك الكامل">
          </div>
          <div class="form-group">
            <label>البريد الإلكتروني للحساب</label>
            <input type="email" value="<?= sanitize($_SESSION['email'] ?? $company['email'] ?? '') ?>" disabled style="font-family:var(--font-en); direction:ltr; text-align:right; opacity:0.6; cursor:not-allowed;">
          </div>
          <div class="form-group">
            <label>كلمة المرور الجديدة</label>
            <input type="password" name="new_password" placeholder="اتركه فارغاً إذا لم تريد التغيير">
          </div>
          <div class="form-group">
            <label>تأكيد كلمة المرور</label>
            <input type="password" name="confirm_password" placeholder="أعد إدخال كلمة المرور الجديدة">
          </div>
        </div>
      </div>

      <!-- Bank Info -->
      <div class="settings-section">
        <h3 class="settings-title"><i class="ph-fill ph-bank" style="color:var(--primary); font-size:22px;"></i> معلومات الحساب البنكي</h3>
        <div class="form-grid">
          <div class="form-group">
            <label>اسم البنك</label>
            <select name="bank_name">
              <option value="">اختر البنك</option>
              <option value="alrajhi" selected>مصرف الراجحي</option>
              <option value="alinma">بنك الإنماء</option>
              <option value="snb">البنك الأهلي السعودي</option>
              <option value="riyad">بنك الرياض</option>
              <option value="sab">بنك ساب</option>
              <option value="albilad">بنك البلاد</option>
            </select>
          </div>
          <div class="form-group">
            <label>رقم الآيبان (IBAN)</label>
            <input type="text" name="iban" value="SA44 2000 0001 2345 6789 1234" placeholder="SA..." style="font-family:var(--font-en); direction:ltr; text-align:right;">
          </div>
        </div>
      </div>

      <button type="submit" class="settings-submit">
        <i class="ph ph-floppy-disk" style="font-size:20px; color:#fff;"></i> حفظ التعديلات
      </button>
    </form>

    <?php endif; ?>

  </main>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
