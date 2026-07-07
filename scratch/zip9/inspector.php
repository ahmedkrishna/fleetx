<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header("Location: /login.php");
    exit;
}

$role = getUserRole();
if ($role !== 'inspector') {
    header("Location: /");
    exit;
}

$inspections = [];
$user_id = intval($_SESSION['user_id']);
if ($db_connected) {
    $res = $conn->query("SELECT i.id as inspection_id, i.status, v.id as vehicle_id, v.make, v.model, v.year, sc.company_name as seller 
                         FROM inspections i 
                         JOIN vehicles v ON i.vehicle_id = v.id 
                         JOIN seller_companies sc ON v.seller_id = sc.id 
                         WHERE i.inspector_id = $user_id AND i.status != 'completed'");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $inspections[] = $row;
        }
    }
} else {
    $inspections = [
        ['inspection_id'=>1042, 'make'=>'تويوتا', 'model'=>'كامري', 'year'=>2022, 'seller'=>'الوطنية للتأجير', 'status'=>'pending'],
        ['inspection_id'=>1043, 'make'=>'هيونداي', 'model'=>'توسان', 'year'=>2023, 'seller'=>'الوطنية للتأجير', 'status'=>'pending']
    ];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>لوحة الفحص | FleetX</title>
  <link rel="stylesheet" href="/assets/css/fleetx.css">
  <style>
    body { background: var(--gray-50); }
    .header { background: var(--navy); color: white; padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; }
    .card { background: white; padding: 20px; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); margin-bottom: 20px; }
  </style>
</head>
<body>

<div class="header">
    <div style="font-size:20px;font-weight:800">FleetX <span style="color:var(--green);font-size:14px">الفاحصين</span></div>
    <div style="display:flex;gap:16px;align-items:center">
        <span style="font-size:14px">مرحباً م. <?= sanitize($_SESSION['user_name']) ?></span>
        <a href="/logout.php" style="color:var(--danger);font-size:14px;text-decoration:none">خروج</a>
    </div>
</div>

<div class="container section-sm">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
        <h1 style="font-size:24px;font-weight:800">طلبات الفحص المجدولة (اليوم)</h1>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
        
        <?php if(empty($inspections)): ?>
          <div style="grid-column:1/-1; text-align:center; padding:40px; color:var(--text-muted); font-size:14px;">لا توجد طلبات فحص مجدولة لك حالياً.</div>
        <?php else: ?>
          <?php foreach($inspections as $req): ?>
          <div class="card">
              <div style="display:flex;justify-content:space-between;margin-bottom:16px;border-bottom:1px solid var(--gray-100);padding-bottom:16px">
                  <div>
                      <div style="font-weight:800;font-size:18px;margin-bottom:4px"><?= $req['make'].' '.$req['model'].' '.$req['year'] ?></div>
                      <div style="font-size:13px;color:var(--gray-600)">البائع: <?= $req['seller'] ?> | رقم الطلب: FX-<?= $req['inspection_id'] ?></div>
                  </div>
                  <div style="text-align:left">
                      <div style="font-weight:700;color:var(--gray-900);margin-bottom:4px">موعد الفحص</div>
                      <span style="background:var(--gray-100);color:var(--gray-600);padding:4px 8px;border-radius:4px;font-size:11px">
                          <?= $req['status'] === 'pending' ? 'قيد الانتظار' : 'جاري الفحص' ?>
                      </span>
                  </div>
              </div>
              
              <div style="display:flex;gap:12px">
                  <button class="btn btn-primary" style="flex:1; display:inline-flex; align-items:center; justify-content:center; gap:8px;" onclick="alert('فتح نموذج 100 نقطة فحص (قريباً)')">بدء الفحص <i class="ph ph-clipboard-text"></i></button>
                  <button class="btn btn-outline" style="flex:1; display:inline-flex; align-items:center; justify-content:center; gap:8px;" onclick="alert('فتح الخريطة لموقع المركبة')">موقع السيارة <i class="ph ph-map-pin"></i></button>
              </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>

    </div>
</div>

</body>
</html>
