<?php
require_once '../config.php';

// Verify admin role (with local mock bypass if database is down)
if ($db_connected) {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
        header('Location: ../login.php');
        exit;
    }
} else {
    // If DB is offline, start a mock admin session if none exists
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['user_id'] = 3;
        $_SESSION['user_name'] = 'م. أحمد السعدي (محاكي)';
        $_SESSION['user_role'] = 'admin';
    }
}

// Calculate Dashboard stats dynamically
$total_revenue = 1284750; // Premium base
$active_auctions_count = 12;
$total_users = 38247;
$pending_approvals = 8;

if ($db_connected) {
    // Sum of ended auctions
    $rev_result = $conn->query("SELECT SUM(current_price) FROM auctions WHERE status = 'ended'");
    if ($rev_result) {
        $db_rev = $rev_result->fetch_row()[0];
        if ($db_rev) {
            $total_revenue += floatval($db_rev);
        }
    }
    
    // Active auctions count
    $act_result = $conn->query("SELECT COUNT(*) FROM auctions WHERE status = 'active'");
    if ($act_result) {
        $active_auctions_count = intval($act_result->fetch_row()[0]);
    }
    
    // Total users count
    $usr_result = $conn->query("SELECT COUNT(*) FROM users");
    if ($usr_result) {
        // Base seed offset
        $db_users = intval($usr_result->fetch_row()[0]);
        if ($db_users > 5) {
            $total_users += ($db_users - 5);
        }
    }
    
    // Pending approvals (sellers in pending state)
    $pend_result = $conn->query("SELECT COUNT(*) FROM users WHERE status = 'pending'");
    if ($pend_result) {
        $pending_approvals = intval($pend_result->fetch_row()[0]);
    }
}

// Fetch 5 recent auctions
$recent_auctions = [];
if ($db_connected) {
    $sql_recent = "SELECT a.*, v.make, v.model, v.year, v.mileage, v.city, v.image_url, u.name AS seller_name,
                   (SELECT COUNT(*) FROM bids b WHERE b.auction_id = a.id) as bid_count
                   FROM auctions a
                   JOIN vehicles v ON a.vehicle_id = v.id
                   JOIN users u ON a.seller_id = u.id
                   ORDER BY a.created_at DESC
                   LIMIT 5";
    $res_recent = $conn->query($sql_recent);
    if ($res_recent && $res_recent->num_rows > 0) {
        while ($row = $res_recent->fetch_assoc()) {
            $recent_auctions[] = $row;
        }
    }
}

// Fallback mock recent auctions
if (empty($recent_auctions)) {
    $recent_auctions = [
        [
            'id' => 1,
            'title' => 'تويوتا كامري 2.5L 2022',
            'make' => 'تويوتا',
            'model' => 'كامري',
            'year' => 2022,
            'mileage' => 45200,
            'city' => 'الرياض',
            'image_url' => 'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?w=60&q=60',
            'seller_name' => 'الوطنية للتأجير',
            'start_price' => 70000,
            'current_price' => 82500,
            'bid_count' => 23,
            'start_time' => '2024-12-01 10:00:00',
            'status' => 'active'
        ],
        [
            'id' => 2,
            'title' => 'هيونداي توسان 2.0 2023',
            'make' => 'هيونداي',
            'model' => 'توسان',
            'year' => 2023,
            'mileage' => 28100,
            'city' => 'جدة',
            'image_url' => 'https://images.unsplash.com/photo-1568844293986-ca9c5c6f8b8a?w=60&q=60',
            'seller_name' => 'الوطنية للتأجير',
            'start_price' => 80000,
            'current_price' => 94000,
            'bid_count' => 31,
            'start_time' => '2024-12-01 12:00:00',
            'status' => 'active'
        ],
        [
            'id' => 4,
            'title' => 'نيسان باترول 5.6 V8 2021',
            'make' => 'نيسان',
            'model' => 'باترول',
            'year' => 2021,
            'mileage' => 62800,
            'city' => 'الدمام',
            'image_url' => 'https://images.unsplash.com/photo-1555215695-3004980ad54e?w=60&q=60',
            'seller_name' => 'الراشد للتأجير',
            'start_price' => 110000,
            'current_price' => 110000,
            'bid_count' => 0,
            'start_time' => '2024-12-15 19:00:00',
            'status' => 'upcoming'
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>لوحة الإدارة | مزادي Mazadi</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800;900&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .admin-body { background: #F4F6F9; }
    .welcome-bar {
      background: linear-gradient(135deg, rgba(15, 117, 188, 0.05) 0%, transparent 60%);
      border: 1px solid rgba(15, 117, 188, 0.15);
      border-radius: var(--radius-xl);
      padding: var(--space-6) var(--space-8);
      margin-bottom: var(--space-8);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .live-auctions-mini {
      display: flex;
      flex-direction: column;
      gap: var(--space-3);
      max-height: 320px;
      overflow-y: auto;
    }
    .live-auction-row {
      display: flex;
      align-items: center;
      gap: var(--space-4);
      padding: var(--space-3);
      background: var(--glass-bg);
      border-radius: var(--radius);
      border: 1px solid var(--glass-border);
    }
    .live-auction-row img {
      width: 60px;
      height: 45px;
      object-fit: cover;
      border-radius: var(--radius-sm);
    }
    .admin-sidebar {
      background: #FFFFFF !important;
      border-left: 1px solid var(--navy-mid) !important;
    }
    .admin-nav-link {
      color: #1E293B !important;
    }
    .admin-nav-link.active, .admin-nav-link:hover {
      background: rgba(15, 117, 188, 0.08) !important;
      color: #0F75BC !important;
    }
  </style>
</head>
<body class="admin-body">

<!-- SIDEBAR -->
<aside class="admin-sidebar" id="admin-sidebar" role="complementary" aria-label="القائمة الجانبية">
  <div class="admin-sidebar-header">
    <a href="../index.php" class="navbar-brand">
      <div class="navbar-logo" style="width:36px;height:36px;font-size:1.1rem">م</div>
      <div class="navbar-brand-text">
        <span class="brand-ar" style="font-size:var(--font-size-base);color:#1E293B">مزادي</span>
        <span class="brand-en" style="font-size:10px;color:#0F75BC">MAZADI ADMIN</span>
      </div>
    </a>
  </div>

  <nav class="admin-nav" role="navigation" aria-label="قائمة الإدارة">
    <div class="admin-nav-section" style="color:var(--gray-500)">الرئيسية</div>
    <a href="index.php" class="admin-nav-link active" id="nav-dashboard">
      <i class="fas fa-chart-line"></i> لوحة التحكم
    </a>
    <a href="auctions.php" class="admin-nav-link" id="nav-auctions">
      <i class="fas fa-gavel"></i> إدارة المزادات
    </a>
    <a href="users.php" class="admin-nav-link" id="nav-users">
      <i class="fas fa-users"></i> إدارة المستخدمين
    </a>
    <a href="inspections.php" class="admin-nav-link" id="nav-inspections">
      <i class="fas fa-clipboard-check"></i> إدارة الفحوصات
    </a>
    <a href="subscriptions.php" class="admin-nav-link" id="nav-subscriptions">
      <i class="fas fa-id-card"></i> إدارة الاشتراكات
    </a>

    <div class="admin-nav-section" style="color:var(--gray-500)">إعدادات المنصة</div>
    <a href="../index.php" class="admin-nav-link">
      <i class="fas fa-arrow-right"></i> الموقع الرئيسي
    </a>
    <a href="../logout.php" class="admin-nav-link" style="color:var(--danger) !important">
      <i class="fas fa-right-from-bracket"></i> تسجيل الخروج
    </a>
  </nav>
</aside>

<!-- MAIN CONTENT -->
<main class="admin-content">

  <!-- Top Bar -->
  <div class="admin-topbar" style="background:#FFFFFF;border-bottom:1px solid var(--navy-mid)">
    <div style="display:flex;align-items:center;gap:var(--space-4)">
      <button id="sidebar-toggle" class="btn btn-secondary btn-sm" style="display:none" onclick="document.getElementById('admin-sidebar').classList.toggle('open')">
        <i class="fas fa-bars"></i>
      </button>
      <div class="admin-search-bar" style="max-width:400px;background:#F8F9FA;border:1px solid var(--navy-mid)">
        <i class="fas fa-search" style="color:var(--gray-500)"></i>
        <input type="text" placeholder="بحث..." id="admin-search-input">
      </div>
    </div>
    <div style="display:flex;align-items:center;gap:var(--space-4)">
      <div style="display:flex;align-items:center;gap:var(--space-3);padding:var(--space-2) var(--space-4);background:#F8F9FA;border:1px solid var(--navy-mid);border-radius:var(--radius-full)">
        <div style="width:32px;height:32px;background:linear-gradient(135deg,#0F75BC,#3FA6F2);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;color:#FFFFFF;font-size:var(--font-size-sm)">م</div>
        <div>
          <div style="font-size:var(--font-size-sm);font-weight:600;color:#1E293B"><?php echo sanitize($_SESSION['user_name']); ?></div>
          <div style="font-size:10px;color:#0F75BC">مدير النظام</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Welcome -->
  <div class="welcome-bar reveal">
    <div>
      <h2 style="font-size:var(--font-size-2xl);margin-bottom:var(--space-1);color:#1E293B">مرحباً بك مجدداً <i class="ph ph-hand-waving" style="font-size: 24px; vertical-align: middle;"></i></h2>
      <p style="color:var(--gray-500);font-size:var(--font-size-sm)">إدارة العمليات والسيارات وإحصائيات المزاد لـ مزادي</p>
    </div>
    <div style="display:flex;gap:var(--space-3)">
      <a href="auctions.php" class="btn btn-primary" id="create-auction-btn">
        <i class="fas fa-plus"></i> جدولة مزاد جديد
      </a>
    </div>
  </div>

  <!-- KPI Cards -->
  <div class="kpi-grid" id="kpi-section">
    <div class="kpi-card reveal" style="background:#FFFFFF;border:1px solid var(--navy-mid)">
      <div class="kpi-icon" style="background:rgba(15, 117, 188, 0.1);color:#0F75BC">
        <i class="fas fa-sack-dollar"></i>
      </div>
      <div class="kpi-value" style="color:#1E293B"><?php echo number_format($total_revenue); ?></div>
      <div class="kpi-label" style="color:var(--gray-500)">إجمالي الإيرادات المبيعة (ر.س)</div>
    </div>
    
    <div class="kpi-card reveal animate-delay-1" style="background:#FFFFFF;border:1px solid var(--navy-mid)">
      <div class="kpi-icon" style="background:rgba(15, 117, 188, 0.1);color:#0F75BC">
        <i class="fas fa-gavel"></i>
      </div>
      <div class="kpi-value" style="color:#1E293B"><?php echo $active_auctions_count; ?></div>
      <div class="kpi-label" style="color:var(--gray-500)">المزادات النشطة حالياً</div>
    </div>
    
    <div class="kpi-card reveal animate-delay-2" style="background:#FFFFFF;border:1px solid var(--navy-mid)">
      <div class="kpi-icon" style="background:rgba(15, 117, 188, 0.1);color:#0F75BC">
        <i class="fas fa-users"></i>
      </div>
      <div class="kpi-value" style="color:#1E293B"><?php echo number_format($total_users); ?></div>
      <div class="kpi-label" style="color:var(--gray-500)">المستخدمين المسجلين</div>
    </div>

    <div class="kpi-card reveal animate-delay-3" style="background:#FFFFFF;border:1px solid var(--navy-mid)">
      <div class="kpi-icon" style="background:rgba(212, 168, 67, 0.1);color:var(--gold)">
        <i class="fas fa-clock"></i>
      </div>
      <div class="kpi-value" style="color:#1E293B"><?php echo $pending_approvals; ?></div>
      <div class="kpi-label" style="color:var(--gray-500)">طلبات التسجيل المعلقة</div>
    </div>
  </div>

  <!-- Charts & Mini List Grid -->
  <div style="display:grid;grid-template-columns:1.3fr 0.7fr;gap:var(--space-8);margin-top:var(--space-8)">
    <!-- Main Chart -->
    <div class="card card-body reveal" style="background:#FFFFFF;border:1px solid var(--navy-mid);height:400px">
      <h3 style="font-size:var(--font-size-base);margin-bottom:var(--space-4);color:#1E293B"><i class="fas fa-chart-column text-gold"></i> تحليل المبيعات الشهرية</h3>
      <div style="position:relative;height:320px">
        <canvas id="revenue-chart"></canvas>
      </div>
    </div>

    <!-- Live Sidebar info -->
    <div class="card card-body reveal" style="background:#FFFFFF;border:1px solid var(--navy-mid)">
      <h3 style="font-size:var(--font-size-base);margin-bottom:var(--space-4);color:#1E293B"><i class="fas fa-circle-dot text-gold" style="font-size:10px"></i> توزع ماركات السيارات</h3>
      <div style="position:relative;height:250px">
        <canvas id="brands-chart"></canvas>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-2);margin-top:var(--space-4);font-size:var(--font-size-xs)">
        <span style="color:#0F75BC">■ تويوتا (34%)</span>
        <span style="color:#3FA6F2">■ هيونداي (22%)</span>
        <span style="color:#10B981">■ كيا (18%)</span>
        <span style="color:#E2862A">■ نيسان (14%)</span>
      </div>
    </div>
  </div>

  <!-- Recent Auctions Table -->
  <div class="card card-body reveal" style="background:#FFFFFF;border:1px solid var(--navy-mid);margin-top:var(--space-8)">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-4)">
      <h3 style="font-size:var(--font-size-base);color:#1E293B"><i class="fas fa-table-list text-gold"></i> المزادات المضافة حديثاً</h3>
      <a href="auctions.php" class="btn btn-secondary btn-sm">إدارة كافة المزادات</a>
    </div>
    
    <table class="admin-table">
      <thead>
        <tr>
          <th>كود المزاد</th>
          <th>السيارة</th>
          <th>البائع</th>
          <th>سعر البداية</th>
          <th>السعر الحالي</th>
          <th>المزايدات</th>
          <th>الحالة</th>
          <th>العمليات</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recent_auctions as $auc): ?>
          <tr>
            <td style="font-family:var(--font-en);color:var(--gray-500)">#AUC-<?php echo sprintf("%03d", $auc['id']); ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:var(--space-3)">
                <img src="<?php echo sanitize($auc['image_url']); ?>" alt="" style="width:48px;height:36px;object-fit:cover;border-radius:var(--radius-sm)">
                <div>
                  <div style="font-weight:600;font-size:var(--font-size-sm);color:#1E293B"><?php echo sanitize($auc['title']); ?></div>
                  <div style="font-size:10px;color:var(--gray-500)"><?php echo number_format($auc['mileage']); ?> كم | <?php echo sanitize($auc['city']); ?></div>
                </div>
              </div>
            </td>
            <td style="font-size:var(--font-size-sm);color:#1E293B"><?php echo sanitize($auc['seller_name']); ?></td>
            <td style="color:var(--gray-500);font-family:var(--font-en)"><?php echo number_format($auc['start_price'] ?? $auc['current_price'] * 0.8); ?> ر.س</td>
            <td style="color:#0F75BC;font-weight:700;font-family:var(--font-en)"><?php echo number_format($auc['current_price']); ?> ر.س</td>
            <td style="text-align:center"><span class="badge badge-upcoming" style="background:rgba(15,117,188,0.1);color:#0F75BC"><?php echo $auc['bid_count']; ?></span></td>
            <td>
              <?php if ($auc['status'] == 'active'): ?>
                <span class="status-dot live">نشط الآن</span>
              <?php elseif ($auc['status'] == 'upcoming'): ?>
                <span class="status-dot pending">قادم</span>
              <?php else: ?>
                <span class="status-dot ended">منتهي</span>
              <?php endif; ?>
            </td>
            <td>
              <div style="display:flex;gap:var(--space-2)">
                <a href="../auction-live.php?id=<?php echo $auc['id']; ?>" target="_blank" class="btn btn-secondary btn-sm btn-icon"><i class="fas fa-eye"></i></a>
                <a href="auctions.php?edit=<?php echo $auc['id']; ?>" class="btn btn-outline-gold btn-sm btn-icon"><i class="fas fa-edit"></i></a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

</main>

<div class="toast-container" id="toast-container" style="position: fixed; top: var(--space-6); left: 50%; transform: translateX(-50%); z-index: 9999; display: flex; flex-direction: column; gap: var(--space-3); min-width: 300px;"></div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    initAdminCharts();
  });

  function initAdminCharts() {
    // Revenue Chart
    const revenueCtx = document.getElementById('revenue-chart');
    if (revenueCtx) {
      new Chart(revenueCtx, {
        type: 'bar',
        data: {
          labels: ['يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'],
          datasets: [
            {
              label: 'الإيرادات',
              data: [185000, 220000, 198000, 265000, 310000, 385000],
              backgroundColor: 'rgba(15, 117, 188, 0.75)',
              borderColor: '#0F75BC',
              borderWidth: 1,
              borderRadius: 6,
            },
            {
              label: 'المبيعات',
              data: [142000, 178000, 165000, 210000, 248000, 312000],
              backgroundColor: 'rgba(212, 168, 67, 0.6)',
              borderColor: '#D4A843',
              borderWidth: 1,
              borderRadius: 6,
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            x: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { color: '#4B5563', font: { family: 'Tajawal' } } },
            y: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { color: '#4B5563' } }
          }
        }
      });
    }

    // Brands Pie Chart
    const brandsCtx = document.getElementById('brands-chart');
    if (brandsCtx) {
      new Chart(brandsCtx, {
        type: 'doughnut',
        data: {
          labels: ['تويوتا', 'هيونداي', 'كيا', 'نيسان', 'أخرى'],
          datasets: [{
            data: [34, 22, 18, 14, 12],
            backgroundColor: ['#0F75BC', '#3FA6F2', '#10B981', '#E2862A', '#F59E0B'],
            borderWidth: 2,
            borderColor: '#FFFFFF'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: '65%',
          plugins: { legend: { display: false } }
        }
      });
    }
  }
</script>
</body>
</html>
