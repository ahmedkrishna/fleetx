<?php
require_once 'config.php';

$vehicle_id = isset($_GET['id']) ? intval($_GET['id']) : 1;
$vehicle = null;

if ($db_connected) {
    $sql = "SELECT a.id as auction_id, a.type, a.current_price, a.starting_price, a.end_time, a.status,
                   v.*, ir.exterior_score, ir.interior_score, ir.overall_score
            FROM auctions a
            JOIN vehicles v ON a.vehicle_id = v.id
            LEFT JOIN inspections ir ON ir.vehicle_id = v.id
            WHERE a.id = $vehicle_id OR v.id = $vehicle_id LIMIT 1";
    $res = $conn->query($sql);
    if ($res && $res->num_rows > 0) {
        $vehicle = $res->fetch_assoc();
    }
}

if (!$vehicle) {
    $mocks = getMockAuctions(30);
    foreach ($mocks as $m) {
        if ($m['id'] == $vehicle_id) {
            $vehicle = $m;
            break;
        }
    }
    if (!$vehicle) $vehicle = $mocks[0];
}

$title_car = $vehicle['title'] ?? ($vehicle['make'].' '.$vehicle['model'].' '.$vehicle['year']);
$img = (!empty($vehicle['image_url']) && strlen($vehicle['image_url']) > 4) ? $vehicle['image_url'] : getCarImage($vehicle['make']);
$price = number_format($vehicle['current_price'] ?? $vehicle['starting_price']);

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= sanitize($title_car) ?> | FleetX</title>
  <link rel="stylesheet" href="/assets/css/fleetx.css">
  <style>
    .vd-gallery {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 16px;
      margin-bottom: 24px;
    }
    .vd-main-img {
      width: 100%;
      height: 400px;
      object-fit: cover;
      border-radius: var(--radius-lg);
    }
    .vd-thumbs {
      display: flex;
      flex-direction: column;
      gap: 16px;
    }
    .vd-thumb {
      width: 100%;
      height: 192px;
      object-fit: cover;
      border-radius: var(--radius-md);
      cursor: pointer;
      transition: var(--transition);
    }
    .vd-thumb:hover { opacity: 0.8; }
    
    @media (max-width: 768px) {
      .vd-gallery { grid-template-columns: 1fr; }
      .vd-thumbs { flex-direction: row; }
      .vd-thumb { height: 100px; }
      .vd-main-img { height: 250px; }
    }

    .vd-specs-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
      gap: 16px;
      margin-top: 24px;
    }
    .vd-spec-item {
      background: var(--bg-light);
      padding: 16px;
      border-radius: var(--radius-md);
      text-align: center;
      border: 1px solid var(--border-light);
    }
    .vd-spec-item i { font-size: 24px; color: var(--primary); margin-bottom: 8px; }
    .vd-spec-value { font-weight: 800; color: var(--text-dark); margin-top: 4px; }
    .vd-spec-label { font-size: 12px; color: var(--text-muted); }
  </style>
</head>
<body class="page-inner">

<?php include 'includes/navbar.php'; ?>

<div class="container" style="padding-top: 120px; padding-bottom: 80px;">
  <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px;">
    <div>
      <a href="/auctions.php" style="color:var(--text-muted); font-size:14px; margin-bottom:12px; display:inline-block">العودة للرئيسية</a>
      <h1 style="font-size:32px; font-weight:900; color:var(--text-dark); margin:0"><?= sanitize($title_car) ?></h1>
    </div>
    <div style="display:flex; gap:12px">
      <button class="btn btn-outline" onclick="toggleFavorite(<?= $vehicle['id'] ?>, this)"><i class="ph ph-heart"></i> إضافة للمفضلة</button>
      <button class="btn btn-outline"><i class="ph ph-share-network"></i> مشاركة</button>
    </div>
  </div>

  <div class="vd-gallery">
    <img src="<?= $img ?>" class="vd-main-img" alt="Main image" id="mainImage">
    <div class="vd-thumbs">
      <img src="https://images.unsplash.com/photo-1555215695-3004980ad54e?w=600&q=80" class="vd-thumb" onclick="document.getElementById('mainImage').src=this.src">
      <img src="https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=600&q=80" class="vd-thumb" onclick="document.getElementById('mainImage').src=this.src">
    </div>
  </div>

  <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 32px;">
    <!-- Details -->
    <div>
      <div class="panel-content" style="border-radius:var(--radius-lg)">
        <h3 style="font-size: 20px; font-weight:800; border-bottom:1px solid var(--border-light); padding-bottom:16px; margin-bottom:24px;">المواصفات الفنية</h3>
        <p style="color:var(--text-muted); line-height:1.8">هذه المركبة معتمدة ومفحوصة بالكامل. تناسب الاستخدام التجاري والشخصي وتتميز بكفاءة استهلاك الوقود والموثوقية.</p>
        
        <div class="vd-specs-grid">
          <div class="vd-spec-item">
            <i class="ph ph-calendar-blank"></i>
            <div class="vd-spec-label">سنة الصنع</div>
            <div class="vd-spec-value"><?= $vehicle['year'] ?? '2023' ?></div>
          </div>
          <div class="vd-spec-item">
            <i class="ph ph-gauge"></i>
            <div class="vd-spec-label">الممشى</div>
            <div class="vd-spec-value"><?= number_format($vehicle['mileage'] ?? 0) ?> كم</div>
          </div>
          <div class="vd-spec-item">
            <i class="ph ph-gas-pump"></i>
            <div class="vd-spec-label">الوقود</div>
            <div class="vd-spec-value"><?= sanitize($vehicle['fuel_type'] ?? 'بنزين') ?></div>
          </div>
          <div class="vd-spec-item">
            <i class="ph ph-gear"></i>
            <div class="vd-spec-label">ناقل الحركة</div>
            <div class="vd-spec-value"><?= sanitize($vehicle['transmission'] ?? 'أوتوماتيك') ?></div>
          </div>
          <div class="vd-spec-item">
            <i class="ph ph-palette"></i>
            <div class="vd-spec-label">اللون</div>
            <div class="vd-spec-value"><?= sanitize($vehicle['color'] ?? 'أبيض') ?></div>
          </div>
          <div class="vd-spec-item">
            <i class="ph ph-map-pin"></i>
            <div class="vd-spec-label">المدينة</div>
            <div class="vd-spec-value"><?= sanitize($vehicle['city'] ?? 'الرياض') ?></div>
          </div>
        </div>
      </div>

      <div class="panel-content" style="border-radius:var(--radius-lg); margin-top:24px;">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border-light); padding-bottom:16px; margin-bottom:24px;">
          <h3 style="font-size: 20px; font-weight:800; margin:0;">تقرير الفحص המعتمد</h3>
          <div style="color:var(--primary); font-weight:800; font-size:24px"><?= $vehicle['overall_score'] ?? 88 ?>%</div>
        </div>
        <p style="color:var(--text-muted); line-height:1.8">السيارة مفحوصة عبر 100+ نقطة بواسطة خبراء معتمدين.</p>
        <a href="/vehicle.php?id=<?= $vehicle['id'] ?? 1 ?>" class="btn btn-outline" style="margin-top:16px;"><i class="ph ph-file-text"></i> عرض تقرير الفحص الكامل</a>
      </div>
    </div>

    <!-- Purchase Sidebar -->
    <div>
      <div class="panel-content" style="border-radius:var(--radius-lg); position:sticky; top:120px;">
        <div style="text-align:center; padding-bottom:24px; border-bottom:1px solid var(--border-light); margin-bottom:24px;">
          <div style="color:var(--text-muted); font-size:14px; margin-bottom:8px">السعر الفوري الشامل</div>
          <div style="font-size:36px; font-weight:900; color:var(--primary); font-family:var(--font-en)"><?= $price ?> <span style="font-size:16px; font-family:var(--font-ar)">ر.س</span></div>
          <div style="color:var(--success); font-size:12px; margin-top:8px; background:rgba(34, 197, 94, 0.1); padding:4px 12px; border-radius:12px; display:inline-block"><i class="ph ph-shield-check"></i> السعر نهائي شامل الرسوم</div>
        </div>

        <button class="btn btn-primary" onclick="alert('تم تحويلك إلى بوابة الدفع الآمنة...')" style="width:100%; justify-content:center; padding:16px; font-size:18px; margin-bottom:16px; background:var(--primary-gradient);">إتمام الشراء الآن <i class="ph ph-credit-card"></i></button>
        
        <div style="display:flex; align-items:center; gap:12px; font-size:13px; color:var(--text-muted)">
          <i class="ph ph-lock-key" style="font-size:24px; color:#94a3b8"></i>
          <div>معاملتك آمنة 100% ومحمية بواسطة أنظمة الدفع الوطنية (مدى، فيزا، ماستركارد).</div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
<script src="/assets/js/fleetx.js"></script>
</body>
</html>
