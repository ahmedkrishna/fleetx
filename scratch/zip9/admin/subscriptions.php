<?php
require_once '../config.php';

// Verify admin role (with local mock bypass if database is down)
if ($db_connected) {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
        header('Location: ../login.php');
        exit;
    }
} else {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['user_id'] = 3;
        $_SESSION['user_name'] = 'م. أحمد السعدي (محاكي)';
        $_SESSION['user_role'] = 'admin';
    }
}

$success_msg = '';
$error_msg = '';

// Handle Actions (Activate / Cancel)
if ($db_connected) {
    // Direct action links (GET parameters)
    if (isset($_GET['action']) && isset($_GET['id'])) {
        $act_id = intval($_GET['id']);
        if ($_GET['action'] === 'activate') {
            $stmt = $conn->prepare("UPDATE subscriptions SET status = 'active' WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $act_id);
                if ($stmt->execute()) {
                    $success_msg = 'تم تنشيط الاشتراك بنجاح!';
                }
                $stmt->close();
            }
        } elseif ($_GET['action'] === 'cancel') {
            $stmt = $conn->prepare("UPDATE subscriptions SET status = 'cancelled' WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $act_id);
                if ($stmt->execute()) {
                    $success_msg = 'تم إلغاء الاشتراك بنجاح!';
                }
                $stmt->close();
            }
        }
    }
}

// Fetch total stats count
$total_subs_count = 1245;
if ($db_connected) {
    // Only check if table exists before querying
    $res_check = $conn->query("SHOW TABLES LIKE 'subscriptions'");
    if ($res_check && $res_check->num_rows > 0) {
        $res_cnt = $conn->query("SELECT COUNT(*) FROM subscriptions");
        if ($res_cnt) {
            $total_subs_count = intval($res_cnt->fetch_row()[0]);
        }
    }
}

// Fetch all subscriptions list from database
$subs_list = [];
if ($db_connected) {
    $res_check = $conn->query("SHOW TABLES LIKE 'subscriptions'");
    if ($res_check && $res_check->num_rows > 0) {
        $sql_subs = "SELECT s.*, u.name as buyer_name, u.email as buyer_email, u.phone as buyer_phone 
                     FROM subscriptions s 
                     JOIN users u ON s.user_id = u.id 
                     ORDER BY s.id DESC";
        $res_subs = $conn->query($sql_subs);
        if ($res_subs && $res_subs->num_rows > 0) {
            while ($row = $res_subs->fetch_assoc()) {
                $subs_list[] = $row;
            }
        }
    }
}

// Fallback Mock Subscriptions Data if database offline or empty
if (empty($subs_list)) {
    $subs_list = [
        [
            'id' => 101,
            'buyer_name' => 'خالد العمري',
            'buyer_email' => 'buyer@mazadi.sa',
            'buyer_phone' => '0503348812',
            'plan_name' => 'الباقة الذهبية',
            'start_date' => '2024-01-01',
            'end_date' => '2025-01-01',
            'status' => 'active'
        ],
        [
            'id' => 102,
            'buyer_name' => 'شركة الوطنية للتأجير',
            'buyer_email' => 'national@rent.sa',
            'buyer_phone' => '0551120021',
            'plan_name' => 'باقة الأعمال',
            'start_date' => '2023-05-10',
            'end_date' => '2024-05-10',
            'status' => 'expired'
        ],
        [
            'id' => 103,
            'buyer_name' => 'شركة الأفضل للسيارات',
            'buyer_email' => 'alafdal@car.sa',
            'buyer_phone' => '0544289901',
            'plan_name' => 'باقة الأعمال بلس',
            'start_date' => '2024-03-15',
            'end_date' => '2025-03-15',
            'status' => 'cancelled'
        ],
        [
            'id' => 104,
            'buyer_name' => 'فيصل المطيري',
            'buyer_email' => 'banned@mazadi.sa',
            'buyer_phone' => '0567781123',
            'plan_name' => 'الباقة الأساسية',
            'start_date' => '2024-05-01',
            'end_date' => '2024-11-01',
            'status' => 'active'
        ]
    ];
    if (!$db_connected || (isset($res_check) && $res_check->num_rows == 0)) {
        $total_subs_count = 1245;
    }
}

if (!function_exists('sanitize')) {
    function sanitize($data) {
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>إدارة الاشتراكات | لوحة الإدارة | مزادي Mazadi</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800;900&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
  <style>
    .admin-body { background: #F4F6F9; }
    .tabs-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid var(--navy-mid);
      padding: var(--space-4) var(--space-6);
      background: #FFFFFF;
      border-top-left-radius: var(--radius-xl);
      border-top-right-radius: var(--radius-xl);
      flex-wrap: wrap;
      gap: var(--space-4);
    }
    .user-tab-btn {
      padding: var(--space-2) var(--space-5);
      border-radius: var(--radius-full);
      font-size: var(--font-size-sm);
      font-weight: 700;
      color: var(--gray-500);
      cursor: pointer;
      background: none;
      border: none;
      transition: all var(--transition-fast);
    }
    .user-tab-btn:hover,
    .user-tab-btn.active {
      background: rgba(15, 117, 188, 0.08);
      color: #0F75BC;
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
    <a href="index.php" class="admin-nav-link" id="nav-dashboard">
      <i class="fas fa-chart-line"></i> لوحة التحكم
    </a>
    <a href="auctions.php" class="admin-nav-link" id="nav-auctions">
      <i class="fas fa-gavel"></i> إدارة المزادات
    </a>
    <a href="users.php" class="admin-nav-link" id="nav-users">
      <i class="fas fa-users"></i> إدارة المستخدمين
    </a>
    <a href="subscriptions.php" class="admin-nav-link active" id="nav-subscriptions">
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
      <h2 style="font-size:var(--font-size-xl);color:#1E293B">إدارة الاشتراكات</h2>
    </div>
    <div style="font-size:var(--font-size-sm);color:var(--gray-500)">
      إجمالي الاشتراكات: <span style="font-weight:800;color:#0F75BC"><?php echo number_format($total_subs_count); ?> اشتراك</span>
    </div>
  </div>

  <?php if (!empty($success_msg)): ?>
    <div class="card card-body" style="background:var(--success-pale);border-color:var(--success);color:var(--success);margin-bottom:var(--space-5);padding:var(--space-3) var(--space-4)">
      <i class="fas fa-check-circle" style="margin-left:8px"></i> <?php echo $success_msg; ?>
    </div>
  <?php endif; ?>

  <!-- Filters and Search Table -->
  <div class="admin-table-wrapper" style="margin-bottom:var(--space-6);background:#FFFFFF;border:1px solid var(--navy-mid)">
    <div class="tabs-row">
      <div style="display:flex;gap:var(--space-2)">
        <button class="user-tab-btn active" onclick="switchTab('all', this)">الكل</button>
        <button class="user-tab-btn" onclick="switchTab('active', this)">نشط</button>
        <button class="user-tab-btn" onclick="switchTab('expired', this)">منتهي</button>
        <button class="user-tab-btn" onclick="switchTab('cancelled', this)">ملغى</button>
      </div>
      <div class="admin-search-bar" style="max-width:300px;background:#F8F9FA;border:1px solid var(--navy-mid)">
        <i class="fas fa-search" style="color:var(--gray-500)"></i>
        <input type="text" placeholder="بحث باسم المشتري أو الباقة..." id="sub-search" oninput="filterSubs()">
      </div>
    </div>

    <!-- Table -->
    <table class="admin-table" role="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>اسم المشتري</th>
          <th>اسم الباقة</th>
          <th>تاريخ البداية</th>
          <th>تاريخ النهاية</th>
          <th>الحالة</th>
          <th>الإجراءات</th>
        </tr>
      </thead>
      <tbody id="subs-table-body">
        <?php foreach ($subs_list as $sub): ?>
          <tr data-status="<?php echo $sub['status']; ?>">
            <td style="font-family:var(--font-en);color:var(--gray-500)">#<?php echo $sub['id']; ?></td>
            <td>
              <div style="font-weight:600;font-size:var(--font-size-sm);color:#1E293B"><?php echo sanitize($sub['buyer_name']); ?></div>
              <div style="font-size:11px;color:var(--gray-500);font-family:var(--font-en)"><?php echo sanitize($sub['buyer_email']); ?></div>
            </td>
            <td style="color:#1E293B;font-weight:500;">
              <?php echo sanitize($sub['plan_name']); ?>
            </td>
            <td style="font-family:var(--font-en);font-size:var(--font-size-xs);color:#1E293B">
              <?php echo date('Y-m-d', strtotime($sub['start_date'])); ?>
            </td>
            <td style="font-family:var(--font-en);font-size:var(--font-size-xs);color:#1E293B">
              <?php echo date('Y-m-d', strtotime($sub['end_date'])); ?>
            </td>
            <td>
              <?php if ($sub['status'] === 'active'): ?>
                <span class="status-dot active">نشط</span>
              <?php elseif ($sub['status'] === 'expired'): ?>
                <span class="status-dot pending" style="background:#FFF3CD;color:#856404;">منتهي</span>
              <?php elseif ($sub['status'] === 'cancelled'): ?>
                <span class="status-dot rejected">ملغى</span>
              <?php endif; ?>
            </td>
            <td>
              <div style="display:flex;gap:var(--space-2)">
                <?php if ($sub['status'] !== 'active'): ?>
                  <a href="subscriptions.php?action=activate&id=<?php echo $sub['id']; ?>" class="btn btn-success btn-sm btn-icon" title="تنشيط"><i class="fas fa-check-circle"></i></a>
                <?php endif; ?>
                <?php if ($sub['status'] !== 'cancelled'): ?>
                  <a href="subscriptions.php?action=cancel&id=<?php echo $sub['id']; ?>" class="btn btn-danger btn-sm btn-icon" title="إلغاء" onclick="return confirm('هل أنت متأكد من إلغاء هذا الاشتراك؟')"><i class="fas fa-ban"></i></a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>

<script>
  let activeTabStatus = 'all';

  function switchTab(status, element) {
    document.querySelectorAll('.user-tab-btn').forEach(btn => btn.classList.remove('active'));
    element.classList.add('active');
    activeTabStatus = status;
    filterSubs();
  }

  function filterSubs() {
    const search = document.getElementById('sub-search').value.toLowerCase();

    document.querySelectorAll('#subs-table-body tr').forEach(row => {
      const rowStatus = row.getAttribute('data-status');
      const buyerText = row.querySelector('td:nth-child(2)').innerText.toLowerCase();
      const planText = row.querySelector('td:nth-child(3)').innerText.toLowerCase();

      let matchesTab = false;
      if (activeTabStatus === 'all') {
        matchesTab = true;
      } else if (activeTabStatus === rowStatus) {
        matchesTab = true;
      }

      const matchesSearch = (search === '' || buyerText.includes(search) || planText.includes(search));

      if (matchesTab && matchesSearch) {
        row.style.display = '';
      } else {
        row.style.display = 'none';
      }
    });
  }
</script>
</body>
</html>
