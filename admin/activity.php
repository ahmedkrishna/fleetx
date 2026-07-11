<?php
require_once '../config.php';
requireLogin();
if (getUserRole() !== 'admin') {
    header('Location: ' . getDashboardUrl());
    exit;
}

$logs = [];
if ($db_connected && fleetx_table_exists($conn, 'activity_log')) {
    $res = $conn->query("SELECT a.*, u.full_name FROM activity_log a JOIN users u ON a.user_id=u.id ORDER BY a.created_at DESC LIMIT 200");
    if ($res) while ($r = $res->fetch_assoc()) $logs[] = $r;
}

$admin_page_title = 'سجل النشاط | FleetX';
$admin_active = 'activity';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head><?php include __DIR__ . '/head.inc.php'; ?></head>
<body class="admin-body">
<?php include __DIR__ . '/sidebar.inc.php'; ?>
<main class="admin-content">
  <div class="admin-topbar"><h2 class="admin-page-title">سجل النشاط والتدقيق</h2></div>
  <div class="admin-card">
    <table class="admin-table" style="width:100%;">
      <thead><tr><th>المستخدم</th><th>النوع</th><th>الرسالة</th><th>التاريخ</th></tr></thead>
      <tbody>
        <?php foreach ($logs as $log): ?>
        <tr>
          <td><?= sanitize($log['full_name'] ?? '') ?></td>
          <td><?= sanitize($log['type']) ?></td>
          <td><?= sanitize($log['message']) ?></td>
          <td style="font-family:var(--font-en);font-size:12px;"><?= $log['created_at'] ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($logs)): ?><tr><td colspan="4">لا توجد سجلات</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</main>
</body>
</html>