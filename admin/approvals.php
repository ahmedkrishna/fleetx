<?php
require_once '../config.php';
requireLogin();
if (getUserRole() !== 'admin') {
    header('Location: ' . getDashboardUrl());
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db_connected) {
    $action = $_POST['action'] ?? '';
    $item_type = $_POST['item_type'] ?? '';

    if ($item_type === 'inspection' && $action === 'approve') {
        $vehicle_id = (int)($_POST['vehicle_id'] ?? 0);
        $inspection_id = (int)($_POST['inspection_id'] ?? 0);
        $conn->begin_transaction();
        try {
            $upd = $conn->prepare("UPDATE inspections SET admin_approved=1, status='pending' WHERE id=?");
            $upd->bind_param('i', $inspection_id);
            $upd->execute();
            $vupd = $conn->prepare("UPDATE vehicles SET status='inspection_scheduled' WHERE id=?");
            $vupd->bind_param('i', $vehicle_id);
            $vupd->execute();
            $conn->commit();
            $success = 'تمت الموافقة على طلب الفحص — بانتظار تعيين المفتش';
        } catch (Throwable $e) {
            $conn->rollback();
            $error = 'حدث خطأ أثناء الموافقة';
        }
    } elseif ($item_type === 'inspection' && $action === 'reject') {
        $vehicle_id = (int)($_POST['vehicle_id'] ?? 0);
        $inspection_id = (int)($_POST['inspection_id'] ?? 0);
        $stmt = $conn->prepare("UPDATE inspections SET status='rejected', admin_approved=0 WHERE id=?");
        $stmt->bind_param('i', $inspection_id);
        $stmt->execute();
        $vstmt = $conn->prepare("UPDATE vehicles SET status='withdrawn' WHERE id=?");
        $vstmt->bind_param('i', $vehicle_id);
        $vstmt->execute();
        $success = 'تم رفض طلب الفحص';
    } elseif ($item_type === 'auction' && $action === 'approve') {
        $auction_id = (int)($_POST['auction_id'] ?? 0);
        $astmt = $conn->prepare('SELECT vehicle_id FROM auctions WHERE id=?');
        $astmt->bind_param('i', $auction_id);
        $astmt->execute();
        $vid = (int)($astmt->get_result()->fetch_assoc()['vehicle_id'] ?? 0);
        $stmt = $conn->prepare("UPDATE auctions SET admin_approved=1, status='active' WHERE id=?");
        $stmt->bind_param('i', $auction_id);
        if ($stmt->execute()) {
            if ($vid) $conn->query("UPDATE vehicles SET status='in_auction' WHERE id=$vid");
            $success = 'تمت الموافقة على المزاد ونشره';
        }
    } elseif ($item_type === 'auction' && $action === 'reject') {
        $auction_id = (int)($_POST['auction_id'] ?? 0);
        $stmt = $conn->prepare("UPDATE auctions SET status='cancelled', admin_approved=0 WHERE id=?");
        $stmt->bind_param('i', $auction_id);
        if ($stmt->execute()) $success = 'تم رفض المزاد';
    } elseif ($item_type === 'document' && $action === 'approve') {
        $doc_id = (int)($_POST['doc_id'] ?? 0);
        $conn->query("UPDATE company_documents SET admin_approved=1 WHERE id=$doc_id");
        $success = 'تم اعتماد المستند';
    } elseif ($item_type === 'document' && $action === 'reject') {
        $doc_id = (int)($_POST['doc_id'] ?? 0);
        $conn->query("DELETE FROM company_documents WHERE id=$doc_id");
        $success = 'تم رفض المستند';
    } elseif ($item_type === 'buyer' && $action === 'approve') {
        $uid = (int)($_POST['user_id'] ?? 0);
        $stmt = $conn->prepare("UPDATE users SET is_active=1 WHERE id=? AND role='buyer'");
        $stmt->bind_param('i', $uid);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            createNotification($conn, $uid, 'system', 'تم تفعيل حسابك!', 'يمكنك الآن تسجيل الدخول والمزايدة على منصة FleetX.', '/login.php');
            $success = 'تمت الموافقة على تسجيل المشتري';
        }
    } elseif ($item_type === 'buyer' && $action === 'reject') {
        $uid = (int)($_POST['user_id'] ?? 0);
        $stmt = $conn->prepare("UPDATE users SET is_active=0 WHERE id=? AND role='buyer'");
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $success = 'تم رفض طلب التسجيل';
    }
}

$pending_inspections = [];
$pending_auctions = [];
$pending_documents = [];
$pending_buyers = [];

if ($db_connected && $conn) {
    $sql_insp = "
        SELECT i.id as inspection_id, i.created_at, v.id as vehicle_id, v.make, v.model, v.year, v.vin, v.mileage,
               sc.company_name as seller_name, u.full_name as seller_contact
        FROM inspections i
        JOIN vehicles v ON i.vehicle_id = v.id
        JOIN seller_companies sc ON v.seller_id = sc.id
        JOIN users u ON sc.user_id = u.id
        WHERE i.status = 'awaiting_admin' OR (i.admin_approved = 0 AND i.status IN ('awaiting_admin','pending') AND v.status = 'awaiting_admin')
        ORDER BY i.created_at ASC
    ";
    $res = $conn->query($sql_insp);
    if ($res) while ($row = $res->fetch_assoc()) $pending_inspections[] = $row;

    $sql_auc = "
        SELECT a.*, v.make, v.model, v.year, sc.company_name as seller_name
        FROM auctions a
        JOIN vehicles v ON a.vehicle_id = v.id
        JOIN seller_companies sc ON a.seller_id = sc.id
        WHERE a.admin_approved = 0 AND a.status IN ('draft','active')
        ORDER BY a.created_at ASC
    ";
    $res2 = @$conn->query($sql_auc);
    if ($res2) while ($row = $res2->fetch_assoc()) $pending_auctions[] = $row;

    if (fleetx_table_exists($conn, 'company_documents')) {
        $dsql = "SELECT d.*, sc.company_name FROM company_documents d JOIN seller_companies sc ON d.seller_id=sc.id WHERE d.admin_approved=0 ORDER BY d.uploaded_at ASC";
        $dres = $conn->query($dsql);
        if ($dres) while ($row = $dres->fetch_assoc()) $pending_documents[] = $row;
    }

    $bsql = "SELECT id, full_name, mobile, email, city, created_at FROM users WHERE role='buyer' AND is_active=0 ORDER BY created_at ASC";
    $bres = $conn->query($bsql);
    if ($bres) while ($row = $bres->fetch_assoc()) $pending_buyers[] = $row;
}

$total_pending = count($pending_inspections) + count($pending_auctions) + count($pending_documents) + count($pending_buyers);
$admin_page_title = 'موافقات الإدارة | FleetX';
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
      <button type="button" id="sidebar-toggle" class="btn btn-secondary btn-sm admin-sidebar-toggle" aria-label="فتح القائمة">
        <i class="fas fa-bars"></i>
      </button>
      <h2 class="admin-page-title">موافقات الإدارة</h2>
    </div>
    <div class="admin-page-meta">
      معلّق: <strong><?= $total_pending ?></strong>
    </div>
  </div>

  <p class="admin-page-meta" style="margin-bottom:20px;">مراجعة طلبات الفحص والمزادات قبل النشر</p>

  <?php if ($success): ?>
    <div class="admin-alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="admin-alert-success" style="background:#fef2f2;color:#b91c1c;"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="admin-card" style="margin-bottom:24px;">
    <h3 style="font-size:18px;font-weight:900;margin-bottom:18px;">طلبات الفحص (<?= count($pending_inspections) ?>)</h3>
    <?php if (empty($pending_inspections)): ?>
      <p style="color:var(--text-muted);">لا توجد طلبات فحص معلقة.</p>
    <?php else: ?>
      <div style="overflow-x:auto;">
        <table class="admin-table" style="width:100%;">
          <thead>
            <tr>
              <th>البائع</th>
              <th>المركبة</th>
              <th>VIN</th>
              <th>الممشى</th>
              <th>التاريخ</th>
              <th>الإجراء</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pending_inspections as $item): ?>
            <tr>
              <td><?= htmlspecialchars($item['seller_name'] ?? '') ?></td>
              <td><?= htmlspecialchars(($item['make'] ?? '') . ' ' . ($item['model'] ?? '') . ' (' . ($item['year'] ?? '') . ')') ?></td>
              <td style="font-family:var(--font-en);color:var(--text-muted);"><?= htmlspecialchars($item['vin'] ?? '—') ?></td>
              <td><?= number_format($item['mileage'] ?? 0) ?> كم</td>
              <td><?= date('Y-m-d', strtotime($item['created_at'])) ?></td>
              <td>
                <form method="POST" style="display:inline-flex;gap:8px;">
                  <input type="hidden" name="item_type" value="inspection">
                  <input type="hidden" name="vehicle_id" value="<?= (int)$item['vehicle_id'] ?>">
                  <input type="hidden" name="inspection_id" value="<?= (int)$item['inspection_id'] ?>">
                  <button type="submit" name="action" value="approve" class="btn btn-primary btn-sm">موافقة</button>
                  <button type="submit" name="action" value="reject" class="btn btn-secondary btn-sm" style="color:var(--danger);">رفض</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <div class="admin-card">
    <h3 style="font-size:18px;font-weight:900;margin-bottom:18px;">مزادات بانتظار الموافقة (<?= count($pending_auctions) ?>)</h3>
    <?php if (empty($pending_auctions)): ?>
      <p style="color:var(--text-muted);">لا توجد مزادات معلقة.</p>
    <?php else: ?>
      <div style="overflow-x:auto;">
        <table class="admin-table" style="width:100%;">
          <thead>
            <tr>
              <th>البائع</th>
              <th>المركبة</th>
              <th>النوع</th>
              <th>السعر الابتدائي</th>
              <th>الإجراء</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pending_auctions as $a): ?>
            <tr>
              <td><?= htmlspecialchars($a['seller_name'] ?? '') ?></td>
              <td><?= htmlspecialchars(($a['make'] ?? '') . ' ' . ($a['model'] ?? '') . ' (' . ($a['year'] ?? '') . ')') ?></td>
              <td><?= htmlspecialchars($a['type'] ?? '') ?></td>
              <td style="font-weight:800;color:var(--primary);"><?= number_format($a['starting_price'] ?? 0) ?> ر.س</td>
              <td>
                <form method="POST" style="display:inline-flex;gap:8px;">
                  <input type="hidden" name="item_type" value="auction">
                  <input type="hidden" name="auction_id" value="<?= (int)$a['id'] ?>">
                  <button type="submit" name="action" value="approve" class="btn btn-primary btn-sm">موافقة ونشر</button>
                  <button type="submit" name="action" value="reject" class="btn btn-secondary btn-sm" style="color:var(--danger);">رفض</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <div class="admin-card" style="margin-top:24px;">
    <h3 style="font-size:18px;font-weight:900;margin-bottom:18px;">تسجيل مشترين جدد (<?= count($pending_buyers) ?>)</h3>
    <?php if (empty($pending_buyers)): ?>
      <p style="color:var(--text-muted);">لا توجد طلبات تسجيل معلقة.</p>
    <?php else: ?>
      <table class="admin-table" style="width:100%;">
        <thead><tr><th>الاسم</th><th>الجوال</th><th>البريد</th><th>المدينة</th><th>التاريخ</th><th>الإجراء</th></tr></thead>
        <tbody>
          <?php foreach ($pending_buyers as $b): ?>
          <tr>
            <td><?= htmlspecialchars($b['full_name'] ?? '') ?></td>
            <td style="font-family:var(--font-en);"><?= htmlspecialchars($b['mobile'] ?? '') ?></td>
            <td><?= htmlspecialchars($b['email'] ?? '—') ?></td>
            <td><?= htmlspecialchars($b['city'] ?? '—') ?></td>
            <td><?= date('Y-m-d', strtotime($b['created_at'])) ?></td>
            <td>
              <form method="POST" style="display:inline-flex;gap:8px;">
                <input type="hidden" name="item_type" value="buyer">
                <input type="hidden" name="user_id" value="<?= (int)$b['id'] ?>">
                <button type="submit" name="action" value="approve" class="btn btn-primary btn-sm">موافقة وتفعيل</button>
                <button type="submit" name="action" value="reject" class="btn btn-secondary btn-sm" style="color:var(--danger);">رفض</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="admin-card" style="margin-top:24px;">
    <h3 style="font-size:18px;font-weight:900;margin-bottom:18px;">مستندات قانونية (<?= count($pending_documents) ?>)</h3>
    <?php if (empty($pending_documents)): ?>
      <p style="color:var(--text-muted);">لا توجد مستندات معلقة.</p>
    <?php else: ?>
      <table class="admin-table" style="width:100%;">
        <thead><tr><th>الشركة</th><th>النوع</th><th>الملف</th><th>الإجراء</th></tr></thead>
        <tbody>
          <?php foreach ($pending_documents as $doc): ?>
          <tr>
            <td><?= htmlspecialchars($doc['company_name'] ?? '') ?></td>
            <td><?= htmlspecialchars($doc['doc_type'] ?? '') ?></td>
            <td><a href="<?= htmlspecialchars($doc['file_url']) ?>" target="_blank">عرض</a></td>
            <td>
              <form method="POST" style="display:inline-flex;gap:8px;">
                <input type="hidden" name="item_type" value="document">
                <input type="hidden" name="doc_id" value="<?= (int)$doc['id'] ?>">
                <button type="submit" name="action" value="approve" class="btn btn-primary btn-sm">اعتماد</button>
                <button type="submit" name="action" value="reject" class="btn btn-secondary btn-sm">رفض</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</main>

<script src="https://unpkg.com/@phosphor-icons/web"></script>
</body>
</html>