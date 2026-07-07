<?php
require_once 'config.php';

$auction_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$auction = null;

if ($db_connected && $auction_id > 0) {
    $auction = getAuctionById($conn, $auction_id);
}

if (!$auction) {
    header('Location: /auctions.php');
    exit;
}

$is_instant = ($auction['type'] ?? '') === 'instant';
if (!$is_instant && in_array($auction['status'] ?? '', ['active', 'live'], true)) {
    header('Location: /auction-room.php?id=' . $auction_id);
    exit;
}

$title_car = $auction['title'] ?? ($auction['make'].' '.$auction['model'].' '.$auction['year']);
$img = (!empty($auction['image_url']) && strlen($auction['image_url']) > 4) ? $auction['image_url'] : getCarImage($auction['make']);

$gallery_images = isset($auction['gallery_images']) && is_array($auction['gallery_images']) ? $auction['gallery_images'] : [
    $img,
    'https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=1200&q=80',
    'https://images.unsplash.com/photo-1600712242805-5f78671b24da?w=1200&q=80',
    'https://images.unsplash.com/photo-1511919884226-fd3cad34687c?w=1200&q=80',
    'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?w=1200&q=80',
    'https://images.unsplash.com/photo-1493238792000-8113da705763?w=1200&q=80',
    'https://images.unsplash.com/photo-1542282088-fe8426682b8f?w=1200&q=80',
    'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=1200&q=80',
    'https://images.unsplash.com/photo-1568605117036-5fe5e7bab0b7?w=1200&q=80',
    'https://images.unsplash.com/photo-1502877338535-766e1452684a?w=1200&q=80'
];

$current_price = $auction['current_price'] ?? $auction['starting_price'];
$min_increment = $auction['bid_increment'] ?? 500;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $is_instant ? 'الشراء الفوري' : 'غرفة المزايدة' ?>: <?= sanitize($title_car) ?> | FleetX</title>
  <link rel="stylesheet" href="/assets/css/fleetx.css">
</head>
<body class="page-inner fx-page-shell fx-page-shell--detail">

<?php include 'includes/navbar.php'; ?>

<?php
$defaultCover = 'https://images.unsplash.com/photo-1508962914676-134849a727f0?w=1600&q=80';
$coverImage = (!empty($auction['cover_image']) && strlen($auction['cover_image']) > 5) ? $auction['cover_image'] : $defaultCover;
$hero_title = $title_car;
$hero_bg = $coverImage;
$hero_back_href = $is_instant ? '/auctions.php?type=instant' : '/auctions.php';
$hero_back_label = '← العودة إلى ' . ($is_instant ? 'الشراء الفوري' : 'المزادات');
$hero_modifier = 'overlap';
$hero_eyebrow = $is_instant ? 'شراء فوري' : 'تفاصيل المزاد';
$hero_meta_html = '<span class="fx-page-hero__chip"><i class="ph ph-map-pin"></i> ' . sanitize($auction['vehicle_city'] ?? $auction['city'] ?? 'الرياض') . '</span>';
$hero_actions_html = '<button class="btn btn-outline" onclick="toggleFavorite(' . (int)$auction['id'] . ', this)"><i class="ph ph-heart"></i> أضف للمفضلة</button>';
include 'includes/page-hero.inc.php';
?>

<div class="container fx-page-body fx-page-body--overlap-lg">

  <div class="fx-detail-layout">
    
    <!-- Left Column: Details -->
    <div>
        <!-- Viewers Box -->
        <?php
        $viewers = $db_connected
            ? max(15, (int)($conn->query('SELECT COUNT(*) FROM bids WHERE auction_id=' . intval($auction_id))->fetch_row()[0] ?? 0) * 2 + rand(10, 40))
            : rand(30, 150);
        ?>
      <div class="fx-live-card fx-panel-first--flush">
        <div class="fx-live-card-header">
          <div class="fx-live-card-title"><span class="fx-live-dot"></span> تفاصيل المركبة</div>
          <div class="fx-viewers-badge">
            المشاهدين:
            <span class="font-en"><i class="ph-fill ph-eye" style="color:var(--primary)"></i> <?= number_format($viewers) ?></span>
          </div>
        </div>
        <div class="mazad-premium-gallery">
          <div class="mpg-main-view">
            <div class="fx-instant-badge"><i class="ph-fill ph-lightning"></i> متاح للشراء الفوري</div>
            <img id="main-gallery-img" src="<?= $gallery_images[0] ?>" alt="صورة رئيسية">
            <div class="mpg-overlay">
              <div class="mpg-badge"><i class="ph-bold ph-camera"></i> <?= count($gallery_images) ?> صور</div>
            </div>
          </div>
          <div class="mpg-thumbs-strip">
            <?php foreach ($gallery_images as $index => $gImg): ?>
            <div class="mpg-thumb <?= $index === 0 ? 'active' : '' ?>" onclick="changePremiumImage(this, '<?= $gImg ?>')">
              <img src="<?= $gImg ?>" alt="صورة <?= $index + 1 ?>">
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="fx-detail-specs">
        <div class="fx-detail-spec">
          <div class="fx-detail-spec__icon"><i class="ph ph-speedometer"></i></div>
          <div>
            <div class="fx-detail-spec__label">الممشى</div>
            <div class="fx-detail-spec__val font-en"><?= number_format($auction['mileage'] ?? 0) ?> <span style="font-family:var(--font-ar);font-size:12px;">كم</span></div>
          </div>
        </div>
        <div class="fx-detail-spec">
          <div class="fx-detail-spec__icon fx-detail-spec__icon--fuel"><i class="ph ph-gas-pump"></i></div>
          <div>
            <div class="fx-detail-spec__label">الوقود</div>
            <div class="fx-detail-spec__val"><?= $auction['fuel_type'] ?? 'بنزين' ?></div>
          </div>
        </div>
        <div class="fx-detail-spec">
          <div class="fx-detail-spec__icon fx-detail-spec__icon--trans"><i class="ph ph-steering-wheel"></i></div>
          <div>
            <div class="fx-detail-spec__label">ناقل الحركة</div>
            <div class="fx-detail-spec__val"><?= $auction['transmission'] ?? 'أوتوماتيك' ?></div>
          </div>
        </div>
      </div>

      <div class="fx-detail-block">
        <h3>نظرة عامة على المركبة</h3>
        <p class="fx-live-desc">
          هذه المركبة معتمدة وخضعت لفحص شامل يتضمن أكثر من 100 نقطة فحص ميكانيكية وكهربائية وللهيكل الداخلي والخارجي. السيارة بحالة ممتازة وخالية من الحوادث الجوهرية.
        </p>
        <div class="fx-detail-kv">
          <div class="fx-detail-kv-row"><span>سنة الصنع</span><span><?= $auction['year'] ?? '2023' ?></span></div>
          <div class="fx-detail-kv-row"><span>اللون الخارجي</span><span><?= $auction['color'] ?? 'أبيض' ?></span></div>
          <div class="fx-detail-kv-row"><span>حالة البودي</span><span style="color:#1bc976">سليم (وكالة)</span></div>
          <div class="fx-detail-kv-row"><span>المدينة</span><span><?= sanitize($auction['city'] ?? 'الرياض') ?></span></div>
        </div>
        
        <?php
        // Fetch AutoData pricing for this vehicle
        $v_id = $auction['vehicle_id'] ?? 0;
        $autodata_min = 0; $autodata_max = 0;
        if ($db_connected && $v_id > 0) {
            $stmt = $conn->prepare("SELECT autodata_price_min, autodata_price_max FROM vehicles WHERE id=?");
            $stmt->bind_param('i', $v_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $autodata_min = $row['autodata_price_min'];
                $autodata_max = $row['autodata_price_max'];
            }
        }
        if ($autodata_min > 0 && $autodata_max > 0):
        ?>
        <div class="fx-autodata-box">
            <h4><i class="ph-fill ph-chart-line-up"></i> تقييم AutoData للأسعار</h4>
            <div class="fx-detail-kv-row"><span>السعر التقديري في السوق</span><span class="font-en"><?= number_format($autodata_min) ?> - <?= number_format($autodata_max) ?> ر.س</span></div>
            <div class="fx-detail-kv-row"><span>هامش الربح المتوقع</span><span style="color:#1bc976">~ <?= number_format($autodata_max - $current_price) ?> ر.س</span></div>
        </div>
        <?php endif; ?>

        <button class="btn btn-outline btn-ac-full"><i class="ph ph-download-simple"></i> تحميل تقرير الفحص (PDF)</button>
      </div>

    </div>

    <div class="fx-pricing-panel">
      <div class="pricing-card fx-panel-first">
        <div class="fx-detail-spec__label">السعر الإجمالي</div>
        <div class="fx-price-display"><?= number_format($current_price) ?> <span class="unit">ر.س</span></div>
        <p class="fx-live-desc" style="margin-top:8px;font-size:12px;">شامل ضريبة القيمة المضافة ورسوم النقل</p>

        <div class="finance-badge">
          <i class="ph-fill ph-calculator"></i> تقسيط من <?= number_format(ceil($current_price / 60)) ?> ر.س / شهر
        </div>

        <div class="fx-detail-actions">
          <button class="btn btn-primary btn-ac-full btn-ac-full--gradient" onclick="window.location.href='/checkout.php?id=<?= $auction['id'] ?>'">
            شراء ودفع الآن <i class="ph-fill ph-check-circle ph-space-left"></i>
          </button>
          <button class="btn btn-outline btn-ac-full">
            <i class="ph-fill ph-calendar-check ph-space-left"></i> حجز موعد للفحص
          </button>
        </div>

        <div class="seller-info-card">
          <div class="fx-detail-spec__icon" style="width:40px;height:40px;border-radius:50%;background:var(--bg-dark);color:#fff;"><i class="ph-fill ph-buildings"></i></div>
          <div>
            <div class="fx-detail-spec__label">معروضة من قبل</div>
            <div class="fx-detail-spec__val"><?= sanitize($auction['seller_name'] ?? $auction['seller'] ?? 'الوطنية لتأجير السيارات') ?></div>
          </div>
        </div>

        <div class="trust-badges">
          <div class="trust-badge"><i class="ph-fill ph-shield-check"></i> مفحوصة 100 نقطة فنية</div>
          <div class="trust-badge"><i class="ph-fill ph-medal"></i> ضمان شامل مسترد لمدة 5 أيام</div>
          <div class="trust-badge"><i class="ph-fill ph-truck"></i> خدمة توصيل مجانية داخل الرياض</div>
        </div>
      </div>
    </div>

  </div>

</div>

<script>
function changePremiumImage(thumbEl, src) {
  const main = document.getElementById('main-gallery-img');
  if (main) main.src = src;
  document.querySelectorAll('.mpg-thumb').forEach(t => t.classList.remove('active'));
  thumbEl.classList.add('active');
}
</script>

<?php include 'includes/footer.php'; ?>