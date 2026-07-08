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



if (!function_exists('sanitize')) {
    function sanitize($data) {
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
}
$admin_page_title = 'إدارة الاشتراكات | FleetX';
$admin_active = 'subscriptions';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<?php include __DIR__ . '/head.inc.php'; ?>
</head>
<body class="admin-body">

<?php include __DIR__ . '/sidebar.inc.php'; ?>

<!-- MAIN CONTENT -->
<main class="admin-content">
  
  <!-- Top Bar -->
  <div class="admin-topbar" style="background:#FFFFFF;border-bottom:1px solid var(--navy-mid)">
    <div style="display:flex;align-items:center;gap:var(--space-4)">
      <button type="button" id="sidebar-toggle" class="btn btn-secondary btn-sm admin-sidebar-toggle" aria-label="فتح القائمة">
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
