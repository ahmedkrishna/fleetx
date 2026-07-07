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



$total_reports = count($inspections_list);
$admin_page_title = 'إدارة الفحوصات | FleetX';
$admin_active = 'inspections';
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
