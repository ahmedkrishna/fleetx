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

// Handle Actions (Assign Inspector, Approve Report)
if ($db_connected) {
    // 1. Post request from Assign Inspector modal
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_inspector') {
        $report_id = intval($_POST['report_id']);
        $inspector_id = intval($_POST['inspector_id']);
        
        $stmt = $conn->prepare("UPDATE inspection_reports SET inspector_id = ?, status = 'assigned' WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("ii", $inspector_id, $report_id);
            if ($stmt->execute()) {
                $success_msg = 'تم تعيين الفاحص بنجاح!';
            } else {
                $error_msg = 'حدث خطأ أثناء تعيين الفاحص.';
            }
            $stmt->close();
        }
    }
    
    // 2. Direct action links (GET parameters)
    if (isset($_GET['action'])) {
        $act_id = intval($_GET['id']);
        if ($_GET['action'] === 'approve') {
            $stmt = $conn->prepare("UPDATE inspection_reports SET status = 'approved' WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $act_id);
                if ($stmt->execute()) {
                    $success_msg = 'تم اعتماد التقرير بنجاح!';
                } else {
                    $error_msg = 'حدث خطأ أثناء اعتماد التقرير.';
                }
                $stmt->close();
            }
        }
    }
}

// Fetch all inspection reports from database
$inspections_list = [];
if ($db_connected) {
    $sql = "SELECT ir.*, v.make, v.model, v.year, u.name AS inspector_name 
            FROM inspection_reports ir 
            JOIN vehicles v ON ir.vehicle_id = v.id 
            LEFT JOIN users u ON ir.inspector_id = u.id 
            ORDER BY ir.created_at DESC";
    $res = $conn->query($sql);
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $inspections_list[] = $row;
        }
    }
}

// Fetch inspectors for the assign modal
$inspectors = [];
if ($db_connected) {
    $res = $conn->query("SELECT id, name FROM users WHERE role IN ('admin', 'inspector') OR name LIKE '%فاحص%'");
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $inspectors[] = $row;
        }
    }
}

// Fallback Mock Data if database offline or empty
if (empty($inspections_list)) {
    $inspections_list = [
        [
            'id' => 101,
            'make' => 'تويوتا',
            'model' => 'كامري',
            'year' => 2022,
            'inspector_name' => 'محمد الفاحص',
            'status' => 'approved',
            'overall_score' => 95,
            'created_at' => '2023-10-01 09:30:00'
        ],
        [
            'id' => 102,
            'make' => 'هيونداي',
            'model' => 'إلنترا',
            'year' => 2021,
            'inspector_name' => null,
            'status' => 'pending',
            'overall_score' => null,
            'created_at' => '2023-10-02 11:15:00'
        ],
        [
            'id' => 103,
            'make' => 'فورد',
            'model' => 'تورس',
            'year' => 2020,
            'inspector_name' => 'عبدالله الفاحص',
            'status' => 'assigned',
            'overall_score' => null,
            'created_at' => '2023-10-03 14:00:00'
        ],
        [
            'id' => 104,
            'make' => 'نيسان',
            'model' => 'باترول',
            'year' => 2023,
            'inspector_name' => 'محمد الفاحص',
            'status' => 'completed',
            'overall_score' => 88,
            'created_at' => '2023-10-04 16:45:00'
        ]
    ];
}

if (empty($inspectors)) {
    $inspectors = [
        ['id' => 10, 'name' => 'محمد الفاحص'],
        ['id' => 11, 'name' => 'عبدالله الفاحص'],
        ['id' => 12, 'name' => 'خالد المهندس']
    ];
}

$total_reports = count($inspections_list);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>إدارة الفحوصات | لوحة الإدارة | مزادي Mazadi</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800;900&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
  <style>
    .admin-body { background: #F4F6F9; }
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
    .status-badge {
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 12px;
      font-weight: bold;
    }
    .status-pending { background: #fff3cd; color: #856404; }
    .status-assigned { background: #cce5ff; color: #004085; }
    .status-completed { background: #d4edda; color: #155724; }
    .status-approved { background: #d1ecf1; color: #0c5460; }
    
    /* Modal Styles */
    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.5);
      z-index: 1000;
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.3s;
    }
    .modal-overlay.open {
      opacity: 1;
      pointer-events: auto;
    }
    .modal-box {
      background: #fff;
      border-radius: 8px;
      width: 100%;
      max-width: 400px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.2);
      transform: translateY(-20px);
      transition: transform 0.3s;
    }
    .modal-overlay.open .modal-box {
      transform: translateY(0);
    }
    .modal-header {
      padding: 15px 20px;
      border-bottom: 1px solid #ddd;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .modal-header h3 {
      margin: 0;
      font-size: 1.1rem;
    }
    .modal-close {
      background: none;
      border: none;
      font-size: 1.2rem;
      cursor: pointer;
      color: #666;
    }
    .modal-body {
      padding: 20px;
    }
    .modal-footer {
      padding: 15px 20px;
      border-top: 1px solid #ddd;
      display: flex;
      justify-content: flex-end;
      gap: 10px;
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
    <a href="inspections.php" class="admin-nav-link active" id="nav-inspections">
      <i class="fas fa-clipboard-check"></i> إدارة الفحوصات
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
      <h2 style="font-size:var(--font-size-xl);color:#1E293B">إدارة الفحوصات</h2>
    </div>
    <div style="font-size:var(--font-size-sm);color:var(--gray-500)">
      إجمالي التقارير: <span style="font-weight:800;color:#0F75BC"><?php echo number_format($total_reports); ?> تقرير</span>
    </div>
  </div>

  <?php if (!empty($success_msg)): ?>
    <div class="card card-body" style="background:var(--success-pale);border-color:var(--success);color:var(--success);margin-bottom:var(--space-5);padding:var(--space-3) var(--space-4)">
      <i class="fas fa-check-circle" style="margin-left:8px"></i> <?php echo $success_msg; ?>
    </div>
  <?php endif; ?>
  
  <?php if (!empty($error_msg)): ?>
    <div class="card card-body" style="background:#f8d7da;border-color:#f5c6cb;color:#721c24;margin-bottom:var(--space-5);padding:var(--space-3) var(--space-4)">
      <i class="fas fa-exclamation-circle" style="margin-left:8px"></i> <?php echo $error_msg; ?>
    </div>
  <?php endif; ?>

  <!-- Table -->
  <div class="admin-table-wrapper" style="background:#FFFFFF;border:1px solid var(--navy-mid)">
    <table class="admin-table" role="table">
      <thead>
        <tr>
          <th>رقم التقرير (ID)</th>
          <th>المركبة</th>
          <th>اسم الفاحص</th>
          <th>النتيجة الكلية</th>
          <th>الحالة</th>
          <th>الإجراءات</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($inspections_list as $report): ?>
          <tr>
            <td style="font-family:var(--font-en);font-weight:bold;color:#0F75BC">#<?php echo $report['id']; ?></td>
            <td style="color:#1E293B;font-weight:600">
                <?php echo sanitize($report['make'] . ' ' . $report['model'] . ' ' . $report['year']); ?>
            </td>
            <td style="color:#1E293B">
                <?php echo $report['inspector_name'] ? sanitize($report['inspector_name']) : '<span style="color:#999">لم يتم التعيين</span>'; ?>
            </td>
            <td style="font-family:var(--font-en);font-weight:bold">
                <?php 
                    if ($report['overall_score'] !== null) {
                        echo $report['overall_score'] . '/100';
                    } else {
                        echo '-';
                    }
                ?>
            </td>
            <td>
              <?php 
                switch($report['status']) {
                    case 'pending': echo '<span class="status-badge status-pending">قيد الانتظار</span>'; break;
                    case 'assigned': echo '<span class="status-badge status-assigned">تم التعيين</span>'; break;
                    case 'completed': echo '<span class="status-badge status-completed">مكتمل</span>'; break;
                    case 'approved': echo '<span class="status-badge status-approved">معتمد</span>'; break;
                    default: echo '<span class="status-badge status-pending">غير معروف</span>';
                }
              ?>
            </td>
            <td>
              <div style="display:flex;gap:var(--space-2)">
                <?php if ($report['status'] === 'pending' || $report['status'] === 'assigned'): ?>
                  <button type="button" class="btn btn-secondary btn-sm" onclick="openAssignModal(<?php echo $report['id']; ?>)">
                    <i class="fas fa-user-plus"></i> تعيين فاحص
                  </button>
                <?php endif; ?>
                
                <?php if ($report['status'] === 'completed'): ?>
                  <a href="inspections.php?action=approve&id=<?php echo $report['id']; ?>" class="btn btn-success btn-sm" onclick="return confirm('هل أنت متأكد من اعتماد هذا التقرير؟')">
                    <i class="fas fa-check"></i> اعتماد التقرير
                  </a>
                <?php endif; ?>
                
                <button type="button" class="btn btn-primary btn-sm btn-icon" title="عرض التفاصيل"><i class="fas fa-eye"></i></button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>

<!-- ASSIGN INSPECTOR MODAL -->
<div class="modal-overlay" id="assignModal">
  <div class="modal-box">
    <div class="modal-header">
      <h3>تعيين فاحص</h3>
      <button class="modal-close" onclick="closeAssignModal()">&times;</button>
    </div>
    <form method="POST" action="inspections.php">
      <div class="modal-body">
        <input type="hidden" name="action" value="assign_inspector">
        <input type="hidden" name="report_id" id="modal_report_id" value="">
        
        <div class="form-group">
          <label class="form-label" for="inspector_id">اختر الفاحص:</label>
          <select name="inspector_id" id="inspector_id" class="form-control" required>
            <option value="">-- اختر فاحص --</option>
            <?php foreach($inspectors as $inspector): ?>
              <option value="<?php echo $inspector['id']; ?>"><?php echo sanitize($inspector['name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeAssignModal()">إلغاء</button>
        <button type="submit" class="btn btn-primary">حفظ التعيين</button>
      </div>
    </form>
  </div>
</div>

<script>
  function openAssignModal(reportId) {
    document.getElementById('modal_report_id').value = reportId;
    document.getElementById('assignModal').classList.add('open');
  }

  function closeAssignModal() {
    document.getElementById('assignModal').classList.remove('open');
  }
</script>
</body>
</html>
