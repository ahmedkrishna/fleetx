<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

$role = getUserRole();
if ($role !== 'inspector' && $role !== 'admin') {
    header('Location: /');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$inspections = [];
$completed_count = 0;
$pending_count = 0;

if ($db_connected) {
    $where = ($role === 'admin')
        ? "i.status != 'completed'"
        : "i.inspector_id = $user_id AND i.status != 'completed'";

    $res = $conn->query("
        SELECT i.id as inspection_id, i.status, i.inspection_date,
               v.id as vehicle_id, v.make, v.model, v.year, v.city, v.mileage,
               sc.company_name as seller
        FROM inspections i
        JOIN vehicles v ON i.vehicle_id = v.id
        JOIN seller_companies sc ON v.seller_id = sc.id
        WHERE $where
        ORDER BY i.created_at DESC
    ");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $inspections[] = $row;
        }
    }

    $cw = ($role === 'admin') ? "status='completed'" : "inspector_id=$user_id AND status='completed'";
    $cres = $conn->query("SELECT COUNT(*) FROM inspections WHERE $cw");
    if ($cres) $completed_count = (int)$cres->fetch_row()[0];

    $pw = ($role === 'admin') ? "status IN ('pending','in_progress')" : "inspector_id=$user_id AND status IN ('pending','in_progress')";
    $pres = $conn->query("SELECT COUNT(*) FROM inspections WHERE $pw");
    if ($pres) $pending_count = (int)$pres->fetch_row()[0];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>لوحة الفحص | FleetX</title>
  <link rel="stylesheet" href="<?= fleetx_css_href() ?>">
</head>
<body class="fx-home fx-page-shell fx-page-shell--inspector">

<?php include 'includes/navbar.php'; ?>

<?php
$hero_title = 'لوحة الفاحصين';
$hero_desc = 'مرحباً ' . sanitize($_SESSION['user_name']) . ' — إدارة طلبات الفحص والتقارير';
$hero_bg = 'https://images.unsplash.com/photo-1486262715619-67b85e0b08d3?w=1600&q=80';
$hero_eyebrow = 'الفحص الفني';
$hero_meta_html = '<span class="fx-page-hero__chip"><i class="ph-fill ph-hourglass"></i> ' . (int)$pending_count . ' بانتظار الفحص</span>'
    . '<span class="fx-page-hero__chip fx-page-hero__chip--accent"><i class="ph-fill ph-check-circle"></i> ' . (int)$completed_count . ' مكتمل</span>';
$hero_actions_html = ($role === 'admin')
    ? '<a href="/admin/inspections.php" class="btn btn-outline"><i class="ph ph-shield-check ph-space-left"></i> لوحة الإدارة</a>'
    : '';
$hero_modifier = 'dashboard';
$hero_extra_class = 'fx-page-hero--inspector';
include 'includes/page-hero.inc.php';
?>

<div class="container fx-page-body fx-page-body--overlap fx-inspector-page">
  <?php if (isset($_GET['msg']) && $_GET['msg'] === 'success'): ?>
  <div class="fx-alert-success">تم اعتماد التقرير وإشعار البائع بنجاح!</div>
  <?php endif; ?>

  <div class="fx-insp-stats">
    <div class="fx-insp-stat"><div class="num"><?= $pending_count ?></div><div class="lbl">بانتظار الفحص</div></div>
    <div class="fx-insp-stat"><div class="num"><?= count($inspections) ?></div><div class="lbl">قائمة العمل</div></div>
    <div class="fx-insp-stat"><div class="num"><?= $completed_count ?></div><div class="lbl">تقارير مكتملة</div></div>
  </div>

  <h2 class="fx-inspector-section-title">طلبات الفحص النشطة</h2>

  <?php if (empty($inspections)): ?>
  <div class="fx-empty-state">
    <i class="ph ph-clipboard-text"></i>
    <h3>لا توجد طلبات فحص</h3>
    <p>لا توجد طلبات فحص مجدولة حالياً.</p>
  </div>
  <?php else: ?>
  <div class="fx-insp-grid">
    <?php foreach ($inspections as $req): ?>
    <div class="fx-insp-card">
      <div class="fx-insp-card-head">
        <div>
          <div style="font-weight:900; font-size:17px;"><?= sanitize($req['make'].' '.$req['model'].' '.$req['year']) ?></div>
          <div style="font-size:13px; color:var(--text-muted); margin-top:4px;">
            <?= sanitize($req['seller']) ?> · FX-<?= (int)$req['inspection_id'] ?>
          </div>
          <div style="font-size:12px; color:var(--text-muted); margin-top:4px;">
            <i class="ph ph-map-pin"></i> <?= sanitize($req['city'] ?? '—') ?> · <?= number_format($req['mileage'] ?? 0) ?> كم
          </div>
        </div>
        <span class="fx-insp-badge">
          <?= $req['status'] === 'in_progress' ? 'جاري الفحص' : 'قيد الانتظار' ?>
        </span>
      </div>
      <div class="fx-insp-actions">
        <a href="/inspector-report.php?id=<?= (int)$req['inspection_id'] ?>" class="btn btn-primary">
          <i class="ph ph-clipboard-text"></i> إعداد التقرير
        </a>
        <a href="/map.php?city=<?= urlencode($req['city'] ?? '') ?>" class="btn btn-outline">
          <i class="ph ph-map-pin"></i> الموقع
        </a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>