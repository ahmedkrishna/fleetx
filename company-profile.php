<?php
require_once 'config.php';

$company_id = intval($_GET['id'] ?? 0);
if (!$company_id) { header('Location: /companies.php'); exit; }

$company = null;
if ($db_connected) {
    $stmt = $conn->prepare("SELECT * FROM seller_companies WHERE id = ?");
    $stmt->bind_param('i', $company_id);
    $stmt->execute();
    $company = $stmt->get_result()->fetch_assoc();
}
if (!$company) {
    $company = [
        'id' => $company_id,
        'company_name' => 'الوطنية للتأجير',
        'city' => 'الرياض',
        'rating' => 4.9,
        'total_auctions' => 420,
        'is_verified' => 1,
        'phone' => '920012345',
        'email' => 'info@wnt.sa',
        'bank_name' => 'بنك الرياض',
        'iban' => 'SA03 8000 0000 6080 1016 7519',
    ];
}

$vehicles = [];
if ($db_connected) {
    $stmt = $conn->prepare("
        SELECT a.*, v.id as vid,
               v.make, v.model, v.year, v.mileage, v.city as vcity, v.image_url,
               a.current_price, a.starting_price, a.status as auction_status, a.end_time, a.type as auction_type
        FROM auctions a
        JOIN vehicles v ON a.vehicle_id = v.id
        WHERE a.seller_id = ? AND a.status IN ('active','live','upcoming')
        ORDER BY a.end_time ASC
        LIMIT 24
    ");
    $stmt->bind_param('i', $company_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) { $vehicles[] = $r; }
}

if (empty($vehicles)) {
    $vehicles = [
        ['vid'=>1,'make'=>'Toyota','model'=>'Camry','year'=>2022,'mileage'=>65000,'vcity'=>'الرياض','image_url'=>'','current_price'=>87000,'starting_price'=>80000,'auction_status'=>'active','end_time'=>date('Y-m-d H:i:s', time()+7200),'auction_type'=>'live','id'=>101],
        ['vid'=>2,'make'=>'Hyundai','model'=>'Elantra','year'=>2023,'mileage'=>42000,'vcity'=>'الرياض','image_url'=>'','current_price'=>70000,'starting_price'=>65000,'auction_status'=>'active','end_time'=>date('Y-m-d H:i:s', time()+86400),'auction_type'=>'live','id'=>102],
        ['vid'=>3,'make'=>'Ford','model'=>'Fusion','year'=>2021,'mileage'=>78000,'vcity'=>'جدة','image_url'=>'','current_price'=>57000,'starting_price'=>52000,'auction_status'=>'upcoming','end_time'=>date('Y-m-d H:i:s', time()+172800),'auction_type'=>'live','id'=>103],
        ['vid'=>4,'make'=>'Honda','model'=>'CR-V','year'=>2022,'mileage'=>50000,'vcity'=>'الرياض','image_url'=>'','current_price'=>108000,'starting_price'=>108000,'auction_status'=>'active','end_time'=>date('Y-m-d H:i:s', time()+259200),'auction_type'=>'instant','id'=>104],
    ];
}

$live_count = count(array_filter($vehicles, fn($v) => in_array($v['auction_status'], ['live', 'active'], true)));
$active_count = $live_count;
$upcoming_count = count(array_filter($vehicles, fn($v) => ($v['auction_status'] ?? '') === 'upcoming'));
$instant_count = count(array_filter($vehicles, fn($v) => ($v['auction_type'] ?? '') === 'instant'));

$hero_title_html = htmlspecialchars($company['company_name']);
if (!empty($company['is_verified'])) {
    $hero_title_html .= ' <i class="ph-fill ph-seal-check fx-text-primary" style="font-size:22px;vertical-align:middle;"></i>';
}
$hero_bg = 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=1600&q=80';
$hero_back_href = '/companies.php';
$hero_back_label = '← العودة لدليل الشركات';
$hero_desc = 'تصفح مزادات ومركبات الشركة المعتمدة على منصة FleetX';
$hero_modifier = 'light';
$hero_meta_html = '
  <span class="fx-page-hero__chip"><i class="ph ph-map-pin"></i> ' . htmlspecialchars($company['city'] ?? 'المملكة') . '</span>
  <span class="fx-page-hero__chip"><i class="ph-fill ph-star"></i> ' . number_format(floatval($company['rating'] ?? 4.5), 1) . ' تقييم</span>';
if (!empty($company['phone'])) {
    $hero_meta_html .= '<span class="fx-page-hero__chip"><i class="ph ph-phone"></i> ' . htmlspecialchars($company['phone']) . '</span>';
}
$hero_bottom_html = '
  <div class="fx-page-hero__toolbar fx-company-hero-stats">
    <div class="fx-company-stat-pill fx-company-stat-pill--live"><strong>' . $live_count . '</strong><span>مباشر الآن</span></div>
    <div class="fx-company-stat-pill fx-company-stat-pill--active"><strong>' . $active_count . '</strong><span>نشط</span></div>
    <div class="fx-company-stat-pill"><strong>' . $upcoming_count . '</strong><span>قادم</span></div>
    <div class="fx-company-stat-pill"><strong>' . number_format(intval($company['total_auctions'] ?? count($vehicles))) . '</strong><span>إجمالي المزادات</span></div>
  </div>';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($company['company_name']) ?> | FleetX</title>
  <meta name="description" content="تصفح مزادات وسيارات <?= htmlspecialchars($company['company_name']) ?> على FleetX">
  <link rel="stylesheet" href="/assets/css/fleetx.css">
</head>
<body class="fx-home fx-page-shell fx-company-profile-shell">
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/page-hero.inc.php'; ?>

<div class="container fx-page-body fx-page-body--overlap-lg">

  <div class="fx-bank-strip">
    <span><i class="ph ph-bank"></i> البنك: <strong><?= htmlspecialchars($company['bank_name'] ?? 'بنك الرياض') ?></strong></span>
    <span><i class="ph ph-credit-card"></i> IBAN: <strong dir="ltr"><?= htmlspecialchars($company['iban'] ?? 'SA03 8000 XXXX XXXX XXXX XXXX') ?></strong></span>
    <span><i class="ph ph-info"></i> للتحويل المباشر بعد إتمام الشراء</span>
  </div>

  <div class="fx-tabs-bar">
    <button type="button" class="fx-tab-btn active" onclick="filterTab(this,'all')">الكل (<?= count($vehicles) ?>)</button>
    <button type="button" class="fx-tab-btn" onclick="filterTab(this,'live')">مباشر (<?= $live_count ?>)</button>
    <button type="button" class="fx-tab-btn" onclick="filterTab(this,'upcoming')">قادم (<?= $upcoming_count ?>)</button>
    <button type="button" class="fx-tab-btn" onclick="filterTab(this,'instant')">شراء فوري (<?= $instant_count ?>)</button>
  </div>

  <?php if (empty($vehicles)): ?>
  <div class="fx-panel-first empty-state fx-empty-state-panel">
    <div class="empty-icon">🚗</div>
    <h3>لا توجد مزادات متاحة حالياً</h3>
    <p>تفقّد لاحقاً أو تصفح شركات أخرى</p>
    <a href="/companies.php" class="btn btn-primary fx-empty-state-panel__cta">عرض شركات أخرى</a>
  </div>
  <?php else: ?>
  <div class="fx-vehicles-grid" id="vehiclesGrid">
    <?php foreach ($vehicles as $v):
      $title = trim(($v['make'] ?? '') . ' ' . ($v['model'] ?? '') . ' ' . ($v['year'] ?? ''));
      $is_instant = ($v['auction_type'] ?? '') === 'instant';
      $status = $v['auction_status'] ?? 'active';
      if ($status === 'live') $status = 'active';
      $auction_id = intval($v['id'] ?? 0);
      $href = $is_instant ? '/vehicle-details.php?id=' . $auction_id : '/auction-live.php?id=' . $auction_id;
      $fx_card = [
        'id' => $auction_id,
        'href' => $href,
        'title' => $title,
        'image' => getCarImage($v['make'] ?? '', $v['image_url'] ?? ''),
        'type' => $v['auction_type'] ?? 'live',
        'status' => $status,
        'city' => $v['vcity'] ?? 'الرياض',
        'mileage' => intval($v['mileage'] ?? 0),
        'year' => $v['year'] ?? '2023',
        'price' => intval($v['current_price'] ?? $v['starting_price'] ?? 0),
        'price_label' => $is_instant ? 'سعر الشراء' : 'السعر الحالي',
        'end_time' => $v['end_time'] ?? null,
        'timer_data' => !empty($v['end_time']) ? timeLeft($v['end_time']) : null,
        'show_installment' => $is_instant && ($auction_id % 2 !== 0),
      ];
    ?>
    <div class="fx-card-wrap" data-status="<?= htmlspecialchars($status) ?>" data-type="<?= htmlspecialchars($v['auction_type'] ?? 'live') ?>">
      <?php include 'includes/fx-auction-card.inc.php'; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
<script>
function filterTab(btn, status) {
  document.querySelectorAll('.fx-tab-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('#vehiclesGrid .fx-card-wrap').forEach(wrap => {
    const cs = wrap.dataset.status;
    const ct = wrap.dataset.type;
    let show = status === 'all'
      || (status === 'live' && (cs === 'active' || cs === 'live'))
      || (status === 'upcoming' && cs === 'upcoming')
      || (status === 'instant' && ct === 'instant');
    wrap.style.display = show ? '' : 'none';
  });
}
document.querySelectorAll('.card-fav').forEach(btn => {
  btn.addEventListener('click', e => { e.stopPropagation(); e.preventDefault(); btn.classList.toggle('active'); });
});
</script>
</body>
</html>