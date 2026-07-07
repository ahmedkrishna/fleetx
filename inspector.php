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
  <link rel="stylesheet" href="/assets/css/fleetx.css">
</head>
<body class="page-inner">

<?php include 'includes/navbar.php'; ?>

<section class="fx-insp-hero">
  <div class="container" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
    <div>
      <h1 style="font-size:26px; font-weight:900; margin:0;">لوحة الفاحصين</h1>
      <p style="opacity:0.75; font-size:14px; margin-top:8px;">مرحباً <?= sanitize($_SESSION['user_name']) ?> — إدارة طلبات الفحص والتقارير</p>
    </div>
    <?php if ($role === 'admin'): ?>
    <a href="/admin/inspections.php" class="btn btn-outline" style="border-color:rgba(255,255,255,0.3); color:#fff;">لوحة الإدارة</a>
    <?php endif; ?>
  </div>
</section>

<div class="container" style="padding-bottom: 64px;">
  <?php if (isset($_GET['msg']) && $_GET['msg'] === 'success'): ?>
  <div class="fx-alert-success">تم اعتماد التقرير وإشعار البائع بنجاح!</div>
  <?php endif; ?>

  <div class="fx-insp-stats">
    <div class="fx-insp-stat"><div class="num"><?= $pending_count ?></div><div class="lbl">بانتظار الفحص</div></div>
    <div class="fx-insp-stat"><div class="num"><?= count($inspections) ?></div><div class="lbl">قائمة العمل</div></div>
    <div class="fx-insp-stat"><div class="num"><?= $completed_count ?></div><div class="lbl">تقارير مكتملة</div></div>
  </div>

  <h2 style="font-size:20px; font-weight:900; margin-bottom:18px;">طلبات الفحص النشطة</h2>

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