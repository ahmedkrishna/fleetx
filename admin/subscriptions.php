<?php
require_once '../config.php';
requireLogin();
if (getUserRole() !== 'admin') {
    header('Location: ' . getDashboardUrl());
    exit;
}

$success_msg = '';
$tab = $_GET['tab'] ?? 'seller';

if ($db_connected && isset($_GET['action'], $_GET['id'], $_GET['tab'])) {
    $id = (int)$_GET['id'];
    $t = $_GET['tab'];
    if ($t === 'buyer' && fleetx_table_exists($conn, 'buyer_subscriptions')) {
        if ($_GET['action'] === 'activate') {
            $conn->query("UPDATE buyer_subscriptions SET is_active=1 WHERE id=$id");
            $success_msg = 'تم تفعيل اشتراك المشتري';
        } elseif ($_GET['action'] === 'cancel') {
            $conn->query("UPDATE buyer_subscriptions SET is_active=0 WHERE id=$id");
            $success_msg = 'تم إلغاء اشتراك المشتري';
        }
    } elseif ($t === 'seller') {
        if ($_GET['action'] === 'activate') {
            $conn->query("UPDATE subscriptions SET is_active=1 WHERE id=$id");
            $success_msg = 'تم تفعيل اشتراك البائع';
        } elseif ($_GET['action'] === 'cancel') {
            $conn->query("UPDATE subscriptions SET is_active=0 WHERE id=$id");
            $success_msg = 'تم إلغاء اشتراك البائع';
        }
    }
}

$seller_subs = [];
$buyer_subs = [];
if ($db_connected) {
    $sql = "SELECT s.*, sc.company_name as entity_name, u.full_name as contact_name, u.email, u.mobile
            FROM subscriptions s
            JOIN seller_companies sc ON s.seller_id = sc.id
            JOIN users u ON sc.user_id = u.id
            ORDER BY s.id DESC LIMIT 100";
    $res = @$conn->query($sql);
    if ($res) while ($r = $res->fetch_assoc()) {
        $r['computed_status'] = !$r['is_active'] ? 'cancelled' : (strtotime($r['end_date']) < time() ? 'expired' : 'active');
        $seller_subs[] = $r;
    }
    if (fleetx_table_exists($conn, 'buyer_subscriptions')) {
        $bsql = "SELECT bs.*, u.full_name as entity_name, u.email, u.mobile
                 FROM buyer_subscriptions bs JOIN users u ON bs.user_id=u.id ORDER BY bs.id DESC LIMIT 100";
        $bres = $conn->query($bsql);
        if ($bres) while ($r = $bres->fetch_assoc()) {
            $r['computed_status'] = !$r['is_active'] ? 'cancelled' : (strtotime($r['end_date']) < time() ? 'expired' : 'active');
            $buyer_subs[] = $r;
        }
    }
}

$list = $tab === 'buyer' ? $buyer_subs : $seller_subs;
$admin_page_title = 'إدارة الاشتراكات | FleetX';
$admin_active = 'subscriptions';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head><?php include __DIR__ . '/head.inc.php'; ?></head>
<body class="admin-body">
<?php include __DIR__ . '/sidebar.inc.php'; ?>
<main class="admin-content">
  <div class="admin-topbar">
    <h2 style="font-size:var(--font-size-xl);">إدارة الاشتراكات</h2>
    <div>
      <a href="?tab=seller" class="btn btn-sm <?= $tab==='seller'?'btn-primary':'btn-secondary' ?>">بائعون</a>
      <a href="?tab=buyer" class="btn btn-sm <?= $tab==='buyer'?'btn-primary':'btn-secondary' ?>">مشترون</a>
    </div>
  </div>
  <?php if ($success_msg): ?><div class="admin-alert-success"><?= $success_msg ?></div><?php endif; ?>
  <div class="admin-table-wrapper">
    <table class="admin-table">
      <thead>
        <tr>
          <th>#</th>
          <th><?= $tab==='seller'?'الشركة':'المشتري' ?></th>
          <th>الباقة</th>
          <th>السعر</th>
          <th>من</th>
          <th>إلى</th>
          <th>الحالة</th>
          <th>إجراء</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($list as $sub): ?>
        <tr>
          <td>#<?= (int)$sub['id'] ?></td>
          <td>
            <strong><?= sanitize($sub['entity_name'] ?? '') ?></strong>
            <div style="font-size:11px;color:#64748b;"><?= sanitize($sub['email'] ?? $sub['mobile'] ?? '') ?></div>
          </td>
          <td><?= sanitize($sub['plan'] ?? '') ?></td>
          <td><?= number_format($sub['price'] ?? 0) ?> ر.س</td>
          <td><?= sanitize($sub['start_date'] ?? '') ?></td>
          <td><?= sanitize($sub['end_date'] ?? '') ?></td>
          <td><?= sanitize($sub['computed_status'] ?? '') ?></td>
          <td>
            <?php if (($sub['computed_status'] ?? '') !== 'active'): ?>
            <a href="?tab=<?= $tab ?>&action=activate&id=<?= (int)$sub['id'] ?>" class="btn btn-success btn-sm">تفعيل</a>
            <?php endif; ?>
            <?php if (($sub['computed_status'] ?? '') !== 'cancelled'): ?>
            <a href="?tab=<?= $tab ?>&action=cancel&id=<?= (int)$sub['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('إلغاء؟')">إلغاء</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($list)): ?><tr><td colspan="8">لا توجد اشتراكات</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</main>
</body>
</html>