<?php
require_once '../config.php';
requireLogin();
if (getUserRole() !== 'admin') {
    header('Location: ' . getDashboardUrl());
    exit;
}

$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $listing_id = intval($_POST['listing_id']);
    $action = $_POST['action'];

    if ($db_connected && $conn) {
        if ($action === 'approve') {
            $stmt = $conn->prepare("UPDATE listings SET status = 'active' WHERE id = ?");
            $stmt->bind_param('i', $listing_id);
            if ($stmt->execute()) {
                $success = 'تمت الموافقة على الإعلان بنجاح!';
            }
        } elseif ($action === 'reject') {
            $stmt = $conn->prepare("UPDATE listings SET status = 'cancelled' WHERE id = ?");
            $stmt->bind_param('i', $listing_id);
            if ($stmt->execute()) {
                $success = 'تم رفض الإعلان.';
            }
        }
    }
}

$pending_listings = [];
if ($db_connected && $conn) {
    try {
        $stmt = $conn->query("
            SELECT l.*, v.make, v.model, v.year, v.vin, u.full_name as seller_name
            FROM listings l
            JOIN garage_vehicles v ON l.vehicle_id = v.id
            JOIN users u ON l.seller_id = u.id
            WHERE l.status = 'pending'
            ORDER BY l.created_at ASC
        ");
        if ($stmt) {
            while ($row = $stmt->fetch_assoc()) {
                $pending_listings[] = $row;
            }
        }
    } catch (Throwable $e) {
        $pending_listings = [];
    }
}

$admin_page_title = 'موافقات الإعلانات | FleetX';
$admin_active = 'approvals';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<?php include __DIR__ . '/head.inc.php'; ?>
</head>
<body class="admin-body">

<?php include __DIR__ . '/sidebar.inc.php'; ?>

<main class="admin-content">
  <div class="admin-topbar">
    <div style="display:flex;align-items:center;gap:16px">
      <button id="sidebar-toggle" class="btn btn-secondary btn-sm" style="display:none" onclick="document.getElementById('admin-sidebar').classList.toggle('open')">
        <i class="fas fa-bars"></i>
      </button>
      <h2 class="admin-page-title">موافقات الإعلانات</h2>
    </div>
    <div class="admin-page-meta">
      معلّق: <strong><?= count($pending_listings) ?></strong>
    </div>
  </div>

  <p class="admin-page-meta" style="margin-bottom:20px;">مراجعة الإعلانات والمزادات المعلقة من قبل البائعين</p>

  <?php if ($success): ?>
    <div class="admin-alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <div class="admin-card">
    <h3 style="font-size:18px;font-weight:900;margin-bottom:18px;color:var(--text-dark);">
      الإعلانات المعلقة بانتظار الموافقة (<?= count($pending_listings) ?>)
    </h3>

    <?php if (empty($pending_listings)): ?>
      <div class="fx-empty-state" style="padding:40px 20px;">
        <i class="ph ph-check-circle" style="font-size:48px;color:var(--primary);"></i>
        <h3>لا توجد إعلانات معلقة</h3>
        <p>جميع الإعلانات تمت مراجعتها.</p>
      </div>
    <?php else: ?>
      <div style="overflow-x:auto;">
        <table class="admin-table" style="width:100%;">
          <thead>
            <tr>
              <th>البائع</th>
              <th>المركبة</th>
              <th>رقم الهيكل (VIN)</th>
              <th>نوع العرض</th>
              <th>السعر</th>
              <th>الإجراء</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pending_listings as $l): ?>
            <tr>
              <td><?= htmlspecialchars($l['seller_name'] ?? '') ?></td>
              <td><?= htmlspecialchars(($l['make'] ?? '') . ' ' . ($l['model'] ?? '') . ' (' . ($l['year'] ?? '') . ')') ?></td>
              <td style="color:var(--text-muted);font-family:var(--font-en);"><?= htmlspecialchars($l['vin'] ?? '') ?></td>
              <td><?= ($l['listing_type'] ?? '') === 'auction' ? 'مزاد' : 'بيع مباشر' ?></td>
              <td style="font-weight:800;color:var(--primary);"><?= number_format($l['starting_price'] ?? $l['direct_price'] ?? 0) ?> ر.س</td>
              <td>
                <form method="POST" style="display:inline-flex;gap:8px;flex-wrap:wrap;">
                  <input type="hidden" name="listing_id" value="<?= (int)$l['id'] ?>">
                  <button type="submit" name="action" value="approve" class="btn btn-primary btn-sm">موافقة ونشر</button>
                  <button type="submit" name="action" value="reject" class="btn btn-secondary btn-sm" style="color:var(--danger);border-color:var(--danger);">رفض</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</main>

<script src="https://unpkg.com/@phosphor-icons/web"></script>
</body>
</html>