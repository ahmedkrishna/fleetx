<?php
require_once '../config.php';

// Verify admin role
if (getUserRole() !== 'admin') {
    if (!$db_connected && !isset($_SESSION['user_id'])) {
        // If DB is offline, start a mock admin session
        $_SESSION['user_id'] = 3;
        $_SESSION['user_name'] = 'م. أحمد السعدي (محاكي)';
        $_SESSION['role'] = 'admin';
    } else {
        header('Location: ../login.php');
        exit;
    }
}

// Determine seller_id: try to get from vehicle owner or default to first seller
$default_seller_id = 1;
if ($db_connected) {
    $res_seller = $conn->query("SELECT id FROM users WHERE role = 'seller' ORDER BY id ASC LIMIT 1");
    if ($res_seller && $row_s = $res_seller->fetch_assoc()) {
        $default_seller_id = intval($row_s['id']);
    }
}

$success_msg = '';
$error_msg = '';

// Handle DB Actions
if ($db_connected) {
    // 1. Create Auction
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_auction'])) {
        $vehicle_id = intval($_POST['vehicle_id']);
        $type = sanitize($_POST['type']);
        $start_price = floatval($_POST['start_price']);
        $reserve_price = floatval($_POST['reserve_price']);
        $min_increment = floatval($_POST['min_increment']);
        $start_time = sanitize($_POST['start_time']);
        $duration_hours = intval($_POST['duration_hours']);
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        
        $end_time = date('Y-m-d H:i:s', strtotime($start_time . " + $duration_hours hours"));
        
        // Fetch vehicle title
        $title = "سيارة";
        $stmt_veh = $conn->prepare("SELECT make, model, year FROM vehicles WHERE id = ?");
        $stmt_veh->bind_param("i", $vehicle_id);
        $stmt_veh->execute();
        $res_veh = $stmt_veh->get_result();
        if ($v = $res_veh->fetch_assoc()) {
            $title = $v['make'] . ' ' . $v['model'] . ' ' . $v['year'];
        }
        $stmt_veh->close();
        
        $status = (strtotime($start_time) > time()) ? 'upcoming' : 'active';
        
        $stmt_auc = $conn->prepare("INSERT INTO auctions (vehicle_id, seller_id, title, type, start_price, reserve_price, current_price, min_increment, start_time, end_time, status, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_auc->bind_param("iissddddsssi", $vehicle_id, $default_seller_id, $title, $type, $start_price, $reserve_price, $start_price, $min_increment, $start_time, $end_time, $status, $is_featured);
        
        if ($stmt_auc->execute()) {
            $success_msg = 'تمت جدولة وإنشاء المزاد بنجاح!';
        } else {
            $error_msg = 'حدث خطأ أثناء حفظ المزاد: ' . $conn->error;
        }
        $stmt_auc->close();
    }
    
    // 2. Delete Action
    if (isset($_GET['action']) && $_GET['action'] === 'delete') {
        $del_id = intval($_GET['id']);
        $stmt_del = $conn->prepare("DELETE FROM auctions WHERE id = ?");
        $stmt_del->bind_param("i", $del_id);
        if ($stmt_del->execute()) {
            $success_msg = 'تم حذف المزاد بنجاح!';
        } else {
            $error_msg = 'حدث خطأ أثناء الحذف.';
        }
        $stmt_del->close();
    }
    
    // 3. Stop Action (Force End)
    if (isset($_GET['action']) && $_GET['action'] === 'stop') {
        $stop_id = intval($_GET['id']);
        $stmt_stop = $conn->prepare("UPDATE auctions SET status = 'ended', end_time = NOW() WHERE id = ?");
        $stmt_stop->bind_param("i", $stop_id);
        if ($stmt_stop->execute()) {
            $success_msg = 'تم إنهاء المزاد وإيقافه بنجاح!';
        } else {
            $error_msg = 'حدث خطأ أثناء تحديث حالة المزاد.';
        }
        $stmt_stop->close();
    }
}

// Fetch KPIs dynamically
$kpi_live = 12;
$kpi_upcoming = 8;
$kpi_ended = 5;
$kpi_liquidity = 1850000;

if ($db_connected) {
    $res_live = $conn->query("SELECT COUNT(*) FROM auctions WHERE status = 'active'");
    if ($res_live) $kpi_live = intval($res_live->fetch_row()[0]);
    
    $res_upc = $conn->query("SELECT COUNT(*) FROM auctions WHERE status = 'upcoming'");
    if ($res_upc) $kpi_upcoming = intval($res_upc->fetch_row()[0]);
    
    $res_end = $conn->query("SELECT COUNT(*) FROM auctions WHERE status = 'ended'");
    if ($res_end) $kpi_ended = intval($res_end->fetch_row()[0]);
    
    $res_liq = $conn->query("SELECT SUM(current_price) FROM auctions WHERE status = 'ended'");
    if ($res_liq) {
        $val = $res_liq->fetch_row()[0];
        if ($val) $kpi_liquidity += floatval($val);
    }
}

// Fetch Vehicles for Creation dropdown
$vehicles_list = [];
if ($db_connected) {
    $res_veh = $conn->query("SELECT id, make, model, year, city FROM vehicles ORDER BY id DESC");
    if ($res_veh && $res_veh->num_rows > 0) {
        while ($row = $res_veh->fetch_assoc()) {
            $vehicles_list[] = $row;
        }
    }
}

// Fetch all auctions
$auctions_list = [];
if ($db_connected) {
    $sql_all = "SELECT a.*, v.make, v.model, v.year, v.mileage, v.city, v.image_url, u.full_name AS seller_name,
                (SELECT COUNT(*) FROM bids b WHERE b.auction_id = a.id) as bid_count
                FROM auctions a
                JOIN vehicles v ON a.vehicle_id = v.id
                JOIN users u ON a.seller_id = u.id
                ORDER BY a.status ASC, a.created_at DESC";
    $res_all = $conn->query($sql_all);
    if ($res_all && $res_all->num_rows > 0) {
        while ($row = $res_all->fetch_assoc()) {
            $auctions_list[] = $row;
        }
    }
}


$admin_page_title = 'إدارة المزادات | FleetX';
$admin_active = 'auctions';
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
      <h2 style="font-size:var(--font-size-xl);color:#1E293B">إدارة المزادات</h2>
    </div>
    <div style="display:flex;align-items:center;gap:var(--space-4)">
      <button class="btn btn-primary btn-sm" onclick="openCreateModal()">
        <i class="fas fa-plus"></i> جدولة وإنشاء مزاد
      </button>
    </div>
  </div>

  <?php if (!empty($success_msg)): ?>
    <div class="card card-body" style="background:var(--success-pale);border-color:var(--success);color:var(--success);margin-bottom:var(--space-5);padding:var(--space-3) var(--space-4)">
      <i class="fas fa-check-circle" style="margin-left:8px"></i> <?php echo $success_msg; ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($error_msg)): ?>
    <div class="card card-body" style="background:var(--danger-pale);border-color:var(--danger);color:var(--danger);margin-bottom:var(--space-5);padding:var(--space-3) var(--space-4)">
      <i class="fas fa-exclamation-triangle" style="margin-left:8px"></i> <?php echo $error_msg; ?>
    </div>
  <?php endif; ?>

  <!-- KPI Row -->
  <div class="kpi-row">
    <div class="kpi-mini-card" style="border:1px solid var(--navy-mid)">
      <div class="kpi-mini-icon" style="background:rgba(15, 117, 188, 0.1);color:#0F75BC"><i class="fas fa-circle-dot"></i></div>
      <div>
        <div style="font-size:var(--font-size-xs);color:var(--gray-500)">المزادات النشطة</div>
        <div style="font-size:var(--font-size-xl);font-weight:800;color:#1E293B"><?php echo $kpi_live; ?> مزاد</div>
      </div>
    </div>
    <div class="kpi-mini-card" style="border:1px solid var(--navy-mid)">
      <div class="kpi-mini-icon" style="background:rgba(212, 168, 67, 0.1);color:var(--gold)"><i class="fas fa-clock"></i></div>
      <div>
        <div style="font-size:var(--font-size-xs);color:var(--gray-500)">المزادات القادمة</div>
        <div style="font-size:var(--font-size-xl);font-weight:800;color:#1E293B"><?php echo $kpi_upcoming; ?> مزادات</div>
      </div>
    </div>
    <div class="kpi-mini-card" style="border:1px solid var(--navy-mid)">
      <div class="kpi-mini-icon" style="background:rgba(16, 185, 129, 0.1);color:var(--success)"><i class="fas fa-flag-checkered"></i></div>
      <div>
        <div style="font-size:var(--font-size-xs);color:var(--gray-500)">المنتهية</div>
        <div style="font-size:var(--font-size-xl);font-weight:800;color:#1E293B"><?php echo $kpi_ended; ?> مزادات</div>
      </div>
    </div>
    <div class="kpi-mini-card" style="border:1px solid var(--navy-mid)">
      <div class="kpi-mini-icon" style="background:rgba(15, 117, 188, 0.1);color:#0F75BC"><i class="fas fa-handshake"></i></div>
      <div>
        <div style="font-size:var(--font-size-xs);color:var(--gray-500)">قيمة التسييل (إجمالي)</div>
        <div style="font-size:var(--font-size-xl);font-weight:800;color:#1E293B"><?php echo number_format($kpi_liquidity); ?> ر.س</div>
      </div>
    </div>
  </div>

  <!-- Table Filters & Search -->
  <div class="admin-table-wrapper" style="margin-bottom:var(--space-6);background:#FFFFFF;border:1px solid var(--navy-mid)">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:var(--space-5) var(--space-6);border-bottom:1px solid var(--navy-mid);flex-wrap:wrap;gap:var(--space-4)">
      <div style="display:flex;align-items:center;gap:var(--space-3)">
        <select class="form-control" style="width:auto;padding:var(--space-2) var(--space-4)" id="status-filter" onchange="filterAuctions()">
          <option value="all">كل الحالات</option>
          <option value="live">نشط (مباشر)</option>
          <option value="upcoming">قادم</option>
          <option value="ended">منتهي</option>
        </select>
      </div>
      <div class="admin-search-bar" style="max-width:300px;background:#F8F9FA;border:1px solid var(--navy-mid)">
        <i class="fas fa-search" style="color:var(--gray-500)"></i>
        <input type="text" placeholder="بحث باسم السيارة أو المعرف..." id="auction-search" oninput="filterAuctions()">
      </div>
    </div>

    <!-- Table -->
    <table class="admin-table" role="table">
      <thead>
        <tr>
          <th>المعرف</th>
          <th>السيارة</th>
          <th>البائع</th>
          <th>النوع</th>
          <th>بداية السعر</th>
          <th>السعر الحالي</th>
          <th>المزايدات</th>
          <th>الحالة</th>
          <th>الإجراءات</th>
        </tr>
      </thead>
      <tbody id="auctions-table-body">
        <?php foreach ($auctions_list as $auc): ?>
          <tr data-status="<?php echo ($auc['status'] == 'active') ? 'live' : $auc['status']; ?>" data-type="<?php echo $auc['type']; ?>">
            <td style="font-family:var(--font-en);color:#0F75BC;font-weight:700">#AUC-<?php echo sprintf("%03d", $auc['id']); ?></td>
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
            <td>
              <?php if ($auc['type'] == 'instant'): ?>
                <span class="badge badge-upcoming" style="color:var(--info);border-color:var(--info);background:none">شراء فوري</span>
              <?php elseif ($auc['type'] == 'sealed'): ?>
                <span class="badge badge-gold" style="background:rgba(16,185,129,0.1);color:#10B981;border-color:#10B981">ظرف مغلق</span>
              <?php else: ?>
                <span class="badge badge-gold">مزاد حي</span>
              <?php endif; ?>
            </td>
            <td style="font-family:var(--font-en);color:var(--gray-500)"><?php echo number_format($auc['start_price'] ?? $auc['current_price'] * 0.8); ?> ر.س</td>
            <td style="color:#0F75BC;font-weight:700;font-family:var(--font-en)"><?php echo number_format($auc['current_price']); ?> ر.س</td>
            <td style="text-align:center;color:#1E293B"><?php echo $auc['bid_count']; ?></td>
            <td>
              <?php if ($auc['status'] == 'active'): ?>
                <span class="status-dot live">مباشر</span>
              <?php elseif ($auc['status'] == 'upcoming'): ?>
                <span class="status-dot pending">قادم</span>
              <?php else: ?>
                <span class="status-dot ended">منتهي</span>
              <?php endif; ?>
            </td>
            <td>
              <div style="display:flex;gap:var(--space-2)">
                <a href="../auction-room.php?id=<?php echo $auc['id']; ?>" target="_blank" class="btn btn-secondary btn-sm btn-icon"><i class="fas fa-eye"></i></a>
                <?php if ($auc['status'] == 'active'): ?>
                  <a href="auctions.php?action=stop&id=<?php echo $auc['id']; ?>" class="btn btn-danger btn-sm btn-icon" title="إنهاء الآن" onclick="return confirm('هل أنت متأكد من رغبتك في إيقاف وإنهاء هذا المزاد؟')"><i class="fas fa-stop"></i></a>
                <?php endif; ?>
                <a href="auctions.php?action=delete&id=<?php echo $auc['id']; ?>" class="btn btn-danger btn-sm btn-icon" title="حذف" onclick="return confirm('هل أنت متأكد من حذف هذا المزاد نهائياً؟')"><i class="fas fa-trash"></i></a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>

<!-- CREATE AUCTION MODAL -->
<div class="modal-overlay" id="create-auction-modal">
  <div class="modal-container">
    <div class="modal-header">
      <h3 style="font-size:var(--font-size-lg);color:#1E293B"><i class="fas fa-plus-circle text-gold"></i> جدولة وإنشاء مزاد جديد</h3>
      <button onclick="closeCreateModal()" style="font-size:1.2rem;color:var(--gray-500);background:none;border:none;cursor:pointer"><i class="fas fa-times"></i></button>
    </div>
    <form action="auctions.php" method="POST">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label" for="pending-car">اختر مركبة مفحوصة (جاهزة للمزاد)</label>
          <select class="form-control" name="vehicle_id" id="pending-car" required>
            <?php if (empty($vehicles_list)): ?>
              <option value="" disabled>لا توجد مركبات في قاعدة البيانات حالياً</option>
            <?php else: ?>
              <?php foreach ($vehicles_list as $veh): ?>
                <option value="<?php echo $veh['id']; ?>"><?php echo sanitize($veh['make'] . ' ' . $veh['model'] . ' ' . $veh['year'] . ' — ' . $veh['city']); ?></option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>
        <div class="admin-form-grid-2">
          <div class="form-group">
            <label class="form-label" for="m-type">نوع المزاد</label>
            <select class="form-control" name="type" id="m-type" required>
              <option value="live">مزاد مباشر (مفتوح)</option>
              <option value="instant">شراء فوري بسعر محدد</option>
              <option value="sealed">ظرف مغلق</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="m-start-price">السعر الابتدائي (ر.س)</label>
            <input type="number" class="form-control" name="start_price" id="m-start-price" value="50000" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="m-start-time">تاريخ ووقت البدء</label>
            <input type="datetime-local" class="form-control" name="start_time" id="m-start-time" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="m-duration">مدة المزاد (بالساعات)</label>
            <input type="number" class="form-control" name="duration_hours" id="m-duration" value="24" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="m-reserve">السعر الاحتياطي للبيع (ر.س)</label>
            <input type="number" class="form-control" name="reserve_price" id="m-reserve" value="65000" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="m-increment">الحد الأدنى للزيادة (ر.س)</label>
            <input type="number" class="form-control" name="min_increment" id="m-increment" value="500" required>
          </div>
        </div>
        <div class="form-group" style="display:flex;align-items:center;gap:var(--space-3);margin-top:var(--space-2)">
          <input type="checkbox" name="is_featured" id="m-featured" style="width:18px;height:18px">
          <label class="form-label" for="m-featured" style="margin-bottom:0">عرض السيارة في واجهة المنصة كـ "مزاد مميز"</label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" onclick="closeCreateModal()">إلغاء</button>
        <button type="submit" name="create_auction" class="btn btn-primary btn-sm">حفظ وجدولة المزاد</button>
      </div>
    </form>
  </div>
</div>

<script>
  function openCreateModal() {
    const now = new Date();
    now.setHours(now.getHours() + 1);
    now.setMinutes(0);
    const formatted = now.toISOString().slice(0, 16);
    document.getElementById('m-start-time').value = formatted;
    document.getElementById('create-auction-modal').classList.add('open');
  }

  function closeCreateModal() {
    document.getElementById('create-auction-modal').classList.remove('open');
  }

  function filterAuctions() {
    const status = document.getElementById('status-filter').value;
    const search = document.getElementById('auction-search').value.toLowerCase();

    document.querySelectorAll('#auctions-table-body tr').forEach(row => {
      const rowStatus = row.getAttribute('data-status');
      const carText = row.querySelector('td:nth-child(2)').innerText.toLowerCase();
      const idText = row.querySelector('td:nth-child(1)').innerText.toLowerCase();

      const matchesStatus = (status === 'all' || rowStatus === status);
      const matchesSearch = (search === '' || carText.includes(search) || idText.includes(search));

      if (matchesStatus && matchesSearch) {
        row.style.display = '';
      } else {
        row.style.display = 'none';
      }
    });
  }
</script>
</body>
</html>
