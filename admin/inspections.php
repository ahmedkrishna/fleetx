<?php
require_once '../config.php';
requireLogin();
if (getUserRole() !== 'admin') {
    header('Location: ' . getDashboardUrl());
    exit;
}

$success_msg = '';
$error_msg = '';

if ($db_connected && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'assign_inspector') {
    $inspection_id = (int)($_POST['inspection_id'] ?? 0);
    $inspector_id = (int)($_POST['inspector_id'] ?? 0);
    if ($inspection_id && $inspector_id) {
        $stmt = $conn->prepare("UPDATE inspections SET inspector_id=?, status='pending', admin_approved=1 WHERE id=?");
        $stmt->bind_param('ii', $inspector_id, $inspection_id);
        if ($stmt->execute()) {
            $vstmt = $conn->prepare("SELECT vehicle_id FROM inspections WHERE id=?");
            $vstmt->bind_param('i', $inspection_id);
            $vstmt->execute();
            $vid = (int)($vstmt->get_result()->fetch_assoc()['vehicle_id'] ?? 0);
            if ($vid) {
                $conn->query("UPDATE vehicles SET status='inspection_scheduled' WHERE id=$vid");
            }
            notifyUser($conn, $inspector_id, 'system', 'طلب فحص جديد', 'تم تعيين فحص مركبة جديدة لك', '/inspector.php', ['in_app', 'sms']);
            $success_msg = 'تم تعيين الفاحص بنجاح!';
        } else {
            $error_msg = 'حدث خطأ أثناء تعيين الفاحص.';
        }
    }
}

$inspections_list = [];
if ($db_connected) {
    $sql = "SELECT i.*, v.make, v.model, v.year, v.status as vehicle_status,
                   u.full_name AS inspector_name, sc.company_name as seller_name
            FROM inspections i
            JOIN vehicles v ON i.vehicle_id = v.id
            JOIN seller_companies sc ON v.seller_id = sc.id
            LEFT JOIN users u ON i.inspector_id = u.id
            ORDER BY i.created_at DESC";
    $res = $conn->query($sql);
    if ($res) while ($row = $res->fetch_assoc()) $inspections_list[] = $row;
}

$inspectors = [];
if ($db_connected) {
    $res = $conn->query("SELECT id, full_name FROM users WHERE role='inspector' AND is_active=1 ORDER BY full_name");
    if ($res) while ($row = $res->fetch_assoc()) $inspectors[] = $row;
}

$total_reports = count($inspections_list);
$admin_page_title = 'إدارة الفحوصات | FleetX';
$admin_active = 'inspections';

function fx_insp_status_badge(string $status, ?int $seller_approved): string {
    if ($status === 'completed' && $seller_approved === 1) return '<span class="status-badge status-approved">معتمد من البائع</span>';
    if ($status === 'completed' && $seller_approved === 0) return '<span class="status-badge status-pending">مرفوض من البائع</span>';
    if ($status === 'completed') return '<span class="status-badge status-completed">بانتظار البائع</span>';
    $map = [
        'awaiting_admin' => '<span class="status-badge status-pending">بانتظار الإدارة</span>',
        'pending' => '<span class="status-badge status-assigned">جاهز للفحص</span>',
        'in_progress' => '<span class="status-badge status-assigned">قيد الفحص</span>',
        'rejected' => '<span class="status-badge status-pending">مرفوض</span>',
    ];
    return $map[$status] ?? '<span class="status-badge status-pending">' . htmlspecialchars($status) . '</span>';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<?php include __DIR__ . '/head.inc.php'; ?>
</head>
<body class="admin-body">

<?php include __DIR__ . '/sidebar.inc.php'; ?>

<main class="admin-content">
<?php include __DIR__ . '/mobile-chrome.inc.php'; ?>
  <div class="admin-topbar" style="background:#FFFFFF;border-bottom:1px solid var(--navy-mid)">
    <div style="display:flex;align-items:center;gap:var(--space-4)">
      <button type="button" id="sidebar-toggle" class="btn btn-secondary btn-sm admin-sidebar-toggle" aria-label="فتح القائمة">
        <i class="fas fa-bars"></i>
      </button>
      <h2 style="font-size:var(--font-size-xl);color:#1E293B">إدارة الفحوصات</h2>
    </div>
    <div style="font-size:var(--font-size-sm);color:var(--gray-500)">
      إجمالي التقارير: <span style="font-weight:800;color:#0F75BC"><?= number_format($total_reports) ?> تقرير</span>
    </div>
  </div>

  <?php if ($success_msg): ?>
    <div class="card card-body" style="background:var(--success-pale);border-color:var(--success);color:var(--success);margin-bottom:var(--space-5);padding:var(--space-3) var(--space-4)">
      <i class="fas fa-check-circle" style="margin-left:8px"></i> <?= $success_msg ?>
    </div>
  <?php endif; ?>
  <?php if ($error_msg): ?>
    <div class="card card-body" style="background:#f8d7da;border-color:#f5c6cb;color:#721c24;margin-bottom:var(--space-5);padding:var(--space-3) var(--space-4)">
      <i class="fas fa-exclamation-circle" style="margin-left:8px"></i> <?= $error_msg ?>
    </div>
  <?php endif; ?>

  <div class="admin-table-wrapper" style="background:#FFFFFF;border:1px solid var(--navy-mid)">
    <table class="admin-table" role="table">
      <thead>
        <tr>
          <th>رقم التقرير</th>
          <th>المركبة</th>
          <th>البائع</th>
          <th>الفاحص</th>
          <th>النتيجة</th>
          <th>الحالة</th>
          <th>الإجراءات</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($inspections_list as $report): ?>
          <tr>
            <td style="font-family:var(--font-en);font-weight:bold;color:#0F75BC">#<?= (int)$report['id'] ?></td>
            <td style="color:#1E293B;font-weight:600"><?= sanitize($report['make'] . ' ' . $report['model'] . ' ' . $report['year']) ?></td>
            <td><?= sanitize($report['seller_name'] ?? '') ?></td>
            <td><?= $report['inspector_name'] ? sanitize($report['inspector_name']) : '<span style="color:#999">لم يُعيَّن</span>' ?></td>
            <td style="font-family:var(--font-en);font-weight:bold">
              <?= $report['overall_score'] !== null ? (int)$report['overall_score'] . '/100' : '—' ?>
            </td>
            <td><?= fx_insp_status_badge($report['status'], $report['seller_approved'] ?? null) ?></td>
            <td>
              <div style="display:flex;gap:var(--space-2);flex-wrap:wrap;">
                <?php if (in_array($report['status'], ['pending', 'awaiting_admin'], true) && empty($report['inspector_id'])): ?>
                  <button type="button" class="btn btn-secondary btn-sm" onclick="openAssignModal(<?= (int)$report['id'] ?>)">
                    <i class="fas fa-user-plus"></i> تعيين فاحص
                  </button>
                <?php endif; ?>
                <?php if ($report['status'] === 'completed' && $report['report_pdf']): ?>
                  <a href="<?= sanitize($report['report_pdf']) ?>" target="_blank" class="btn btn-primary btn-sm"><i class="fas fa-file-pdf"></i> التقرير</a>
                <?php endif; ?>
                <?php if ($report['status'] === 'in_progress' || ($report['status'] === 'pending' && !empty($report['inspector_id']))): ?>
                  <a href="/inspector-report.php?id=<?= (int)$report['id'] ?>" class="btn btn-primary btn-sm btn-icon" title="عرض"><i class="fas fa-eye"></i></a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>

<div class="modal-overlay" id="assignModal">
  <div class="modal-box">
    <div class="modal-header">
      <h3>تعيين فاحص</h3>
      <button class="modal-close" onclick="closeAssignModal()">&times;</button>
    </div>
    <form method="POST" action="inspections.php">
      <div class="modal-body">
        <input type="hidden" name="action" value="assign_inspector">
        <input type="hidden" name="inspection_id" id="modal_report_id" value="">
        <div class="form-group">
          <label class="form-label" for="inspector_id">اختر الفاحص:</label>
          <select name="inspector_id" id="inspector_id" class="form-control" required>
            <option value="">-- اختر فاحص --</option>
            <?php foreach ($inspectors as $inspector): ?>
              <option value="<?= (int)$inspector['id'] ?>"><?= sanitize($inspector['full_name']) ?></option>
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