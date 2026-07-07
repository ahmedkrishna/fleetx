<?php
require_once 'config.php';
$id = isset($_GET['id']) ? intval($_GET['id']) : 1;

$auction = null;
if ($db_connected) {
    // Select a.bid_increment instead of a.min_increment
    $res = $conn->query("SELECT a.id, a.title, a.type, a.current_price, a.end_time, a.bid_increment, v.* FROM auctions a JOIN vehicles v ON a.vehicle_id = v.id WHERE a.id = " . intval($id));
    if ($res) $auction = $res->fetch_assoc();
}
if (!$auction) {
    $mocks = getMockAuctions();
    foreach ($mocks as $m) {
        if ($m['id'] === $id) {
            $auction = $m;
            break;
        }
    }
    if (!$auction) $auction = $mocks[0];
}

$title = $auction['title'] ?? ($auction['make'].' '.$auction['model'].' '.$auction['year']);
$img = getCarImage($auction['make'], $auction['image_url']);
$isLive = ($auction['type'] ?? 'live') === 'live';
$min_increment = isset($auction['bid_increment']) ? floatval($auction['bid_increment']) : 500;
$countdownVal = $auction['end_time'] ?? date('Y-m-d H:i:s', time() + rand(3600, 7200));

// Mock Inspection Scores
$exterior_score = $auction['exterior_score'] ?? 88;
$interior_score = $auction['interior_score'] ?? 90;
$mechanical_score = $auction['mechanical_score'] ?? 85;
$electronics_score = $auction['electronics_score'] ?? 92;
$overall_score = $auction['overall_score'] ?? round(($exterior_score + $interior_score + $mechanical_score + $electronics_score) / 4);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>غرفة المزايدة الحية | <?= sanitize($title) ?></title>
  <link rel="stylesheet" href="/assets/css/fleetx.css">
  <style>
    /* Technical specs card */
    .specs-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 16px;
      margin-top: 20px;
    }
    @media (max-width: 576px) { .specs-grid { grid-template-columns: repeat(2, 1fr); } }
    .spec-item {
      background: var(--bg-light);
      padding: 12px;
      border-radius: var(--radius-sm);
      border: 1px solid var(--border-light);
    }
    .spec-label { font-size: 11px; color: var(--text-muted); margin-bottom: 4px; }
    .spec-value { font-size: 14px; font-weight: 800; color: var(--text-dark); }
    
    /* Progress indicators for inspection report */
    .inspection-row { margin-bottom: 16px; }
    .inspection-info { display: flex; justify-content: space-between; font-size: 14px; font-weight: 700; margin-bottom: 6px; }
    .progress-bar { height: 8px; background: var(--border-light); border-radius: var(--radius-round); overflow: hidden; }
    .progress-inner { height: 100%; background: var(--primary-gradient); border-radius: var(--radius-round); }
  </style>
</head>
<body class="page-inner">

<!-- Navbar template -->
<?php include 'includes/navbar.php'; ?>

<!-- Dark Header (Rounded corners, no bottom fade) -->
<header class="page-header" style="padding-bottom: 90px">
  <div class="page-header-bg" style="background-image:url('<?= $img ?>');"></div>
  <div class="container">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:24px">
      <div>
        <a href="/auctions.php" style="color:var(--primary); font-size:14px; font-weight:700; display:inline-block; margin-bottom:12px"><i class="ph ph-arrow-right"></i> العودة لقاعة المزادات</a>
        <h1 style="margin:0"><?= sanitize($title) ?></h1>
        <div style="display:flex; gap:16px; color:var(--text-light-muted); font-size:14px; margin-top:8px">
          <span>الرقم المرجعي: <span class="font-en">FX-<?= $auction['id'] ?></span></span>
          <span>|</span>
          <span>الموقع: <?= sanitize($auction['city'] ?? 'الرياض') ?></span>
          <span>|</span>
          <span>الممشى: <span class="font-en"><?= number_format($auction['mileage'] ?? 0) ?></span> كم</span>
        </div>
      </div>
      <?php if ($isLive): ?>
      <div style="text-align:left" class="digital-clock" data-countdown="<?= $countdownVal ?>">
        <div style="font-size:12px; color:var(--text-light-muted); margin-bottom:4px">ينتهي المزاد خلال</div>
        <div style="font-size:32px; font-weight:800; font-family:var(--font-en); color:#fff; background:rgba(0,0,0,0.4); padding:10px 24px; border-radius:var(--radius-sm); border:1px solid rgba(255,255,255,0.08);">
          <span data-unit="hours">00</span>:<span data-unit="mins">00</span>:<span data-unit="secs">00</span>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</header>

<div class="container">
  <div class="live-room-grid">
    
    <!-- Left Column: Gallery & Details -->
    <div>
      <!-- Gallery Card -->
      <div class="gallery-card">
        <div class="gallery-main">
          <img src="<?= $img ?>" id="mainImage" alt="Car Large View">
        </div>
        <div class="gallery-thumbs">
          <div class="gallery-thumb active"><img src="<?= $img ?>" alt="Thumb 1"></div>
          <div class="gallery-thumb"><img src="https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=500&q=80" alt="Thumb 2"></div>
          <div class="gallery-thumb"><img src="https://images.unsplash.com/photo-1580273916550-e323be2ae537?w=500&q=80" alt="Thumb 3"></div>
          <div class="gallery-thumb" style="background:var(--bg-light); display:flex; align-items:center; justify-content:center; font-weight:800; color:var(--text-muted); cursor:pointer"><i class="ph ph-images" style="font-size: 20px;"></i></div>
        </div>
      </div>

      <!-- Specifications Card -->
      <div class="panel-content" style="margin-top:30px; border-radius:var(--radius-lg)">
        <h2 style="font-size:20px; border-bottom:1px solid var(--border-light); padding-bottom:16px; margin-bottom:4px">المواصفات الفنية للسيارة</h2>
        <div class="specs-grid">
          <div class="spec-item"><div class="spec-label">الموديل</div><div class="spec-value"><?= $auction['year'] ?></div></div>
          <div class="spec-item"><div class="spec-label">ناقل الحركة</div><div class="spec-value"><?= sanitize($auction['transmission'] ?? 'أوتوماتيك') ?></div></div>
          <div class="spec-item"><div class="spec-label">نوع الوقود</div><div class="spec-value"><?= sanitize($auction['fuel_type'] ?? 'بنزين') ?></div></div>
          <div class="spec-item"><div class="spec-label">اللون</div><div class="spec-value"><?= sanitize($auction['color'] ?? 'أبيض لؤلؤي') ?></div></div>
          <div class="spec-item"><div class="spec-label">سعة المحرك</div><div class="spec-value font-en"><?= sanitize($auction['engine_size'] ?? '2.5L') ?></div></div>
          <div class="spec-item"><div class="spec-label">البائعة</div><div class="spec-value"><?= sanitize($auction['seller'] ?? 'الوطنية للتأجير') ?></div></div>
        </div>
      </div>

      <!-- Inspection Score Breakdown -->
      <div class="panel-content" style="margin-top:30px; border-radius:var(--radius-lg)">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border-light); padding-bottom:16px; margin-bottom:20px">
          <h2 style="font-size:20px; margin:0">تقرير الفحص الفني المعتمد (100+ نقطة)</h2>
          <div style="background:var(--primary); color:#fff; font-family:var(--font-en); font-weight:800; font-size:18px; padding:6px 16px; border-radius:var(--radius-sm)">
            <?= $overall_score ?>/100
          </div>
        </div>
        
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:30px">
          <div>
            <div class="inspection-row">
              <div class="inspection-info"><span>الهيكل والشاسيه</span><span class="font-en"><?= $exterior_score ?>%</span></div>
              <div class="progress-bar"><div class="progress-inner" style="width:<?= $exterior_score ?>%"></div></div>
            </div>
            <div class="inspection-row" style="margin-bottom:0">
              <div class="inspection-info"><span>المقصورة الداخلية</span><span class="font-en"><?= $interior_score ?>%</span></div>
              <div class="progress-bar"><div class="progress-inner" style="width:<?= $interior_score ?>%"></div></div>
            </div>
          </div>
          <div>
            <div class="inspection-row">
              <div class="inspection-info"><span>المحرك والميكانيكا</span><span class="font-en"><?= $mechanical_score ?>%</span></div>
              <div class="progress-bar"><div class="progress-inner" style="width:<?= $mechanical_score ?>%"></div></div>
            </div>
            <div class="inspection-row" style="margin-bottom:0">
              <div class="inspection-info"><span>الكهرباء والحساسات</span><span class="font-en"><?= $electronics_score ?>%</span></div>
              <div class="progress-bar"><div class="progress-inner" style="width:<?= $electronics_score ?>%"></div></div>
            </div>
          </div>
        </div>
        <div style="margin-top:24px; background:var(--bg-light); padding:16px 20px; border-radius:var(--radius-sm); font-size:13px; color:var(--text-muted); line-height:1.7; border:1px solid var(--border-light)">
          <strong><i class="ph ph-note-pencil ph-space-left"></i>ملاحظات الفاحص:</strong> الهيكل سليم مع رش تجميلي بالرفرف الأيسر الخلفي. العضلات والمحركات بحالة ممتازة وخالية من الأعطال والتهريبات.
        </div>
        <a href="/vehicle.php?id=<?= $auction['id'] ?>" class="btn btn-outline-dark" style="width:100%; margin-top:20px; font-weight:700; display:inline-flex; align-items:center; justify-content:center; gap:8px;"><i class="ph ph-file-text"></i>عرض تقرير الفحص التفاعلي والطباعة</a>
      </div>
    </div>

    <!-- Right Column: Sticky Bid Panel -->
    <div>
      <div class="bid-panel-card" style="position: sticky; top: 100px;">
        <div class="bpc-header">
          <div style="background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.15); display:inline-flex; align-items:center; gap:6px; font-size:12px; font-weight:700; padding:4px 12px; border-radius:var(--radius-round); margin-bottom:12px">
            <span style="display:inline-block; width:6px; height:6px; border-radius:50%; background:var(--primary)"></span> مزاد مباشر الآن
          </div>
          <div style="font-size:13px; color:var(--text-light-muted)">العرض الحالي الأعلى</div>
          <div class="bpc-price-display" id="currentBidDisplay"><?= number_format($auction['current_price']) ?> ر.س</div>
          <div style="font-size:12px; color:var(--text-light-muted)" id="minBidInfo">الحد الأدنى للمزايدة القادمة: <?= number_format($auction['current_price'] + $min_increment) ?> ر.س</div>
        </div>
        
        <!-- Live Bid history list -->
        <div class="bpc-history" id="bidHistory">
          <div class="bid-history-item winner">
            <div class="bid-avatar" style="background: var(--primary); color: #fff;">أ</div>
            <div>
              <div class="bid-user">أنت (أعلى مزايد حالي)</div>
              <div class="bid-time">الآن</div>
            </div>
            <div class="bid-amount"><?= number_format($auction['current_price']) ?> ر.س</div>
          </div>
          <div class="bid-history-item">
            <div class="bid-avatar">م</div>
            <div>
              <div class="bid-user">مزايد #782</div>
              <div class="bid-time">منذ دقيقة</div>
            </div>
            <div class="bid-amount"><?= number_format($auction['current_price'] - $min_increment) ?> ر.س</div>
          </div>
          <div class="bid-history-item">
            <div class="bid-avatar">خ</div>
            <div>
              <div class="bid-user">خالد الزهراني</div>
              <div class="bid-time">منذ 3 دقائق</div>
            </div>
            <div class="bid-amount"><?= number_format($auction['current_price'] - ($min_increment * 2)) ?> ر.س</div>
          </div>
        </div>

        <!-- Action Bids -->
        <div class="bpc-actions">
          <?php if (isLoggedIn()): ?>
            <div class="quick-bids-grid">
              <button class="quick-bid-btn" data-amount="500">+ 500</button>
              <button class="quick-bid-btn" data-amount="1000">+ 1,000</button>
              <button class="quick-bid-btn" data-amount="2000">+ 2,000</button>
              <button class="quick-bid-btn" data-amount="5000">+ 5,000</button>
            </div>
            <div style="display:flex; gap:10px; margin-bottom:12px">
              <input type="number" id="bidAmount" placeholder="مبلغ المزايدة المخصص..." class="form-input font-en" style="font-weight:800; font-size:16px; border-radius: var(--radius-md);">
              <button class="btn btn-primary" id="submitBid" style="border-radius: var(--radius-md);">زايد الآن</button>
            </div>
          <?php else: ?>
            <a href="/login.php" class="btn btn-primary" style="width:100%; margin-bottom:12px; border-radius: var(--radius-round);">تسجيل الدخول للمزايدة</a>
          <?php endif; ?>
          <div style="text-align:center; font-size:11px; color:var(--text-muted); line-height:1.6">
            بالنقر على المزايدة، فإنك تلتزم قانونياً بشراء السيارة ودفع ثمنها في حال فوزك بالمزاد.
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- Footer template -->
<?php include 'includes/footer.php'; ?>

<!-- Initialize live auction engine from fleetx.js -->
<script>
  // Pass server-side values to the centralized initLiveAuction() from fleetx.js
  document.addEventListener('DOMContentLoaded', () => {
    initLiveAuction(
      <?= intval($auction['id'] ?? 1) ?>,
      <?= floatval($auction['current_price'] ?? 0) ?>,
      <?= floatval($min_increment ?? 500) ?>
    );
  });
</script>

</body>
</html>
