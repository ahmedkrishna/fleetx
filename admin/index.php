<?php
require_once '../config.php';
requireLogin();
if (getUserRole() !== 'admin') {
    header('Location: ' . getDashboardUrl());
    exit;
}

// Calculate Dashboard stats dynamically
$total_revenue = 0;
$active_auctions_count = 0;
$total_users = 0;
$pending_approvals = 0;

if ($db_connected) {
    // Sum of ended auctions
    $rev_result = $conn->query("SELECT COALESCE(SUM(current_price), 0) FROM auctions WHERE status = 'ended'");
    if ($rev_result) {
        $total_revenue = floatval($rev_result->fetch_row()[0]);
    }
    
    // Active auctions count
    $act_result = $conn->query("SELECT COUNT(*) FROM auctions WHERE status = 'active'");
    if ($act_result) {
        $active_auctions_count = intval($act_result->fetch_row()[0]);
    }
    
    // Total users count
    $usr_result = $conn->query("SELECT COUNT(*) FROM users");
    if ($usr_result) {
        $total_users = intval($usr_result->fetch_row()[0]);
    }
    
    // Pending approvals (users in pending state, if applicable, else default to 0)
    // Checking if status column exists in users table first or assuming 0
    // Actually we can just query it. If it fails, default to 0.
    $pend_result = $conn->query("SELECT COUNT(*) FROM users WHERE is_active = 0");
    if ($pend_result) {
        $pending_approvals = intval($pend_result->fetch_row()[0]);
    }
}

// Fetch 5 recent auctions
$recent_auctions = [];
if ($db_connected) {
    $sql_recent = "SELECT a.*, v.make, v.model, v.year, v.mileage, v.city, v.image_url, u.full_name AS seller_name,
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


$admin_page_title = 'لوحة الإدارة | FleetX';
$admin_active = 'dashboard';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<?php include __DIR__ . '/head.inc.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="admin-body">

<?php include __DIR__ . '/sidebar.inc.php'; ?>

<!-- MAIN CONTENT -->
<main class="admin-content">

  <!-- Top Bar -->
  <div class="admin-topbar">
    <div style="display:flex;align-items:center;gap:16px">
      <button id="sidebar-toggle" class="btn btn-secondary btn-sm" style="display:none" onclick="document.getElementById('admin-sidebar').classList.toggle('open')">
        <i class="fas fa-bars"></i>
      </button>
      <div class="admin-search-bar" style="max-width:400px">
        <i class="fas fa-search" style="color:var(--text-muted)"></i>
        <input type="text" placeholder="بحث في المزادات والمستخدمين..." id="admin-search-input">
      </div>
    </div>
    <div style="display:flex;align-items:center;gap:16px">
      <div style="display:flex;align-items:center;gap:12px;padding:8px 16px;background:#f8fafc;border:1px solid var(--border-light);border-radius:999px">
        <div style="width:36px;height:36px;background:var(--primary-gradient);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;color:#000;font-size:14px">م</div>
        <div>
          <div style="font-size:14px;font-weight:700;color:var(--text-dark)"><?php echo sanitize($_SESSION['user_name']); ?></div>
          <div style="font-size:11px;color:var(--primary);font-weight:700">مدير FleetX</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Welcome -->
  <div class="welcome-bar reveal">
    <div>
      <h2 style="font-size:var(--font-size-2xl);margin-bottom:var(--space-1);color:#1E293B">مرحباً بك مجدداً <i class="ph ph-hand-waving" style="font-size: 24px; vertical-align: middle;"></i></h2>
      <p style="color:var(--text-muted);font-size:14px">إدارة العمليات والمزادات وإحصائيات المنصة — FleetX</p>
    </div>
    <div style="display:flex;gap:var(--space-3)">
      <a href="auctions.php" class="btn btn-primary" id="create-auction-btn">
        <i class="fas fa-plus"></i> جدولة مزاد جديد
      </a>
    </div>
  </div>

  <!-- KPI Cards -->
  <div class="kpi-grid" id="kpi-section">
    <div class="kpi-card reveal">
      <div class="kpi-icon"><i class="fas fa-sack-dollar"></i></div>
      <div class="kpi-value"><?php echo number_format($total_revenue); ?></div>
      <div class="kpi-label">إجمالي الإيرادات المبيعة (ر.س)</div>
    </div>
    <div class="kpi-card reveal animate-delay-1">
      <div class="kpi-icon"><i class="fas fa-gavel"></i></div>
      <div class="kpi-value"><?php echo $active_auctions_count; ?></div>
      <div class="kpi-label">المزادات النشطة حالياً</div>
    </div>
    <div class="kpi-card reveal animate-delay-2">
      <div class="kpi-icon"><i class="fas fa-users"></i></div>
      <div class="kpi-value"><?php echo number_format($total_users); ?></div>
      <div class="kpi-label">المستخدمين المسجلين</div>
    </div>
    <div class="kpi-card reveal animate-delay-3">
      <div class="kpi-icon" style="background:rgba(245,158,11,0.1)!important;color:#f59e0b!important"><i class="fas fa-clock"></i></div>
      <div class="kpi-value"><?php echo $pending_approvals; ?></div>
      <div class="kpi-label">طلبات التسجيل المعلقة</div>
    </div>
  </div>

  <!-- Charts & Mini List Grid -->
  <div class="admin-chart-grid">
    <div class="admin-card reveal" style="height:400px">
      <h3 style="font-size:16px;margin-bottom:16px;color:var(--text-dark);font-weight:900"><i class="fas fa-chart-column text-fx-accent"></i> تحليل المبيعات الشهرية</h3>
      <div style="position:relative;height:320px">
        <canvas id="revenue-chart"></canvas>
      </div>
    </div>

    <!-- Live Sidebar info -->
    <div class="admin-card reveal">
      <h3 style="font-size:16px;margin-bottom:16px;color:var(--text-dark);font-weight:900"><i class="fas fa-circle-dot text-fx-accent" style="font-size:10px"></i> توزع ماركات السيارات</h3>
      <div style="position:relative;height:250px">
        <canvas id="brands-chart"></canvas>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-2);margin-top:var(--space-4);font-size:var(--font-size-xs)">
        <span style="color:var(--primary)">■ تويوتا (34%)</span>
        <span style="color:#0ea5e9">■ هيونداي (22%)</span>
        <span style="color:#10b981">■ كيا (18%)</span>
        <span style="color:#f59e0b">■ نيسان (14%)</span>
      </div>
    </div>
  </div>

  <!-- Recent Auctions Table -->
  <div class="admin-card reveal" style="margin-top:24px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px">
      <h3 style="font-size:16px;color:var(--text-dark);font-weight:900"><i class="fas fa-table-list text-fx-accent"></i> المزادات المضافة حديثاً</h3>
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
            <td style="color:var(--primary);font-weight:800;font-family:var(--font-en)"><?php echo number_format($auc['current_price']); ?> ر.س</td>
            <td style="text-align:center"><span class="badge badge-upcoming" style="background:rgba(27,201,118,0.1);color:var(--primary)"><?php echo $auc['bid_count']; ?></span></td>
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
                <a href="../auction-room.php?id=<?php echo $auc['id']; ?>" target="_blank" class="btn btn-secondary btn-sm btn-icon"><i class="fas fa-eye"></i></a>
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
              backgroundColor: 'rgba(27, 201, 118, 0.75)',
              borderColor: '#1bc976',
              borderWidth: 1,
              borderRadius: 6,
            },
            {
              label: 'المبيعات',
              data: [142000, 178000, 165000, 210000, 248000, 312000],
              backgroundColor: 'rgba(14, 165, 233, 0.55)',
              borderColor: '#0ea5e9',
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
            backgroundColor: ['#1bc976', '#0ea5e9', '#10b981', '#f59e0b', '#94a3b8'],
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
