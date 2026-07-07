<?php
require_once 'config.php';

$event_id = isset($_GET['id']) ? intval($_GET['id']) : 1;

$event = null;
$vehicles = [];
$total_vehicles = 0;
$total_pages = 1;

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$limit = 6;
$offset = ($page - 1) * $limit;

if ($db_connected) {
    // 1. Fetch Event details
    $sql_ev = "SELECT ae.*, sc.company_name as seller_company, sc.rating as seller_rating, sc.total_auctions as seller_total
               FROM auction_events ae
               JOIN seller_companies sc ON ae.seller_id = sc.id
               WHERE ae.id = $event_id LIMIT 1";
    $res_ev = $conn->query($sql_ev);
    if ($res_ev && $res_ev->num_rows > 0) {
        $event = $res_ev->fetch_assoc();
    }

    if ($event) {
        // 2. Fetch total count of lots for pagination
        $sql_count = "SELECT COUNT(*) as total FROM auctions WHERE event_id = $event_id AND status='active'";
        $res_count = $conn->query($sql_count);
        if ($res_count) {
            $total_vehicles = intval($res_count->fetch_assoc()['total']);
        }
        $total_pages = ceil($total_vehicles / $limit);

        // 3. Fetch lots inside this event
        $sql_lots = "SELECT a.id, a.title, a.type, a.current_price, a.starting_price, a.end_time, a.is_featured,
                            v.make, v.model, v.year, v.mileage, v.city, v.image_url,
                            (SELECT COUNT(*) FROM bids b WHERE b.auction_id = a.id) as bid_count
                     FROM auctions a
                     JOIN vehicles v ON a.vehicle_id = v.id
                     WHERE a.event_id = $event_id AND a.status='active'
                     ORDER BY a.is_featured DESC, a.created_at DESC
                     LIMIT $limit OFFSET $offset";
        $res_lots = $conn->query($sql_lots);
        if ($res_lots) {
            while ($row = $res_lots->fetch_assoc()) {
                $vehicles[] = $row;
            }
        }
    }
}

// Fallback to mock data if DB empty or not connected
if (!$event) {
    $all_mock_events = getMockEvents();
    foreach ($all_mock_events as $me) {
        if ($me['id'] === $event_id) {
            $event = $me;
            break;
        }
    }
    if (!$event) {
        $event = $all_mock_events[0];
    }
    
    // Fetch mock vehicles inside this event
    $all_mock_lots = getMockAuctions(30);
    $filtered_lots = array_filter($all_mock_lots, function($l) use ($event_id) {
        return $l['event_id'] == $event_id;
    });

    $total_vehicles = count($filtered_lots);
    $total_pages = ceil($total_vehicles / $limit);
    $vehicles = array_slice($filtered_lots, $offset, $limit);
}

$title = $event['title'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= sanitize($title) ?> | FleetX</title>
  <link rel="stylesheet" href="/assets/css/fleetx.css">
  <style>
    /* Styling for Event page layout */
    .event-details-grid {
      display: grid;
      grid-template-columns: 1fr 320px;
      gap: 30px;
    }
    @media (max-width: 992px) {
      .event-details-grid { grid-template-columns: 1fr; }
    }
    
    .event-info-sidebar {
      display: flex;
      flex-direction: column;
      gap: 24px;
    }
    
    .brochure-download-box {
      background: var(--bg-white);
      border: 1px solid var(--border-light);
      border-radius: var(--radius-lg);
      padding: 24px;
      text-align: center;
      box-shadow: var(--shadow-card);
      position: relative;
      overflow: hidden;
    }
    .brochure-download-box::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: var(--primary-gradient);
    }
    
    .seller-rating-box {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 14px;
      color: var(--warning);
      font-weight: 700;
    }
  </style>
</head>
<body class="page-inner">

<!-- Navbar template -->
<?php include 'includes/navbar.php'; ?>

<!-- Event Premium Header -->
<header class="page-header">
  <div class="page-header-bg" style="background-image:url('https://images.unsplash.com/photo-1573164713988-8665fc963095?w=1600&q=80');"></div>
  <div class="container">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:24px;">
      <div style="max-width:700px">
        <a href="/auctions.php" style="color:var(--primary); font-size:14px; font-weight:700; display:inline-block; margin-bottom:12px">← العودة لقاعة فعاليات المزادات</a>
        <h1 style="margin:0; line-height: 1.3"><?= sanitize($event['title']) ?></h1>
        <div style="display:flex; gap:16px; color:var(--text-light-muted); font-size:14px; margin-top:12px; flex-wrap:wrap">
          <span><i class="ph ph-buildings" style="color:var(--primary)"></i> البائع: <?= sanitize($event['seller'] ?? ($event['seller_company'] ?? 'شركة معتمدة')) ?></span>
          <span>|</span>
          <span><i class="ph ph-car" style="color:var(--primary)"></i> إجمالي المركبات: <strong class="font-en" style="color:#fff"><?= $total_vehicles ?></strong> سيارات</span>
          <span>|</span>
          <span><i class="ph ph-clock" style="color:var(--primary)"></i> الحالة: <strong style="color:var(--primary)">مزاد نشط</strong></span>
        </div>
      </div>
      
      <!-- Event Countdown -->
      <div style="text-align:left">
        <div style="font-size:12px; color:var(--text-light-muted); margin-bottom:6px">ينتهي حدث المزاد خلال</div>
        <div style="font-size:24px; font-weight:800; font-family:var(--font-en); color:#fff; background:rgba(0,0,0,0.4); padding:8px 20px; border-radius:var(--radius-sm); border:1px solid rgba(255,255,255,0.08);" data-countdown="<?= $event['end_time'] ?>">
          <span data-unit="days">00</span>ي : <span data-unit="hours">00</span>س : <span data-unit="mins">00</span>د : <span data-unit="secs">00</span>ث
        </div>
      </div>
    </div>
  </div>
</header>

<div class="container" style="margin-top:-30px; position:relative; z-index:10; margin-bottom:100px;">
  
  <div class="event-details-grid">
    
    <!-- Right Column: Vehicles Grid -->
    <div>
      <!-- Section Header -->
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; padding:16px 24px; background:var(--bg-white); border:1px solid var(--border-light); border-radius:var(--radius-lg); box-shadow:var(--shadow-card);">
        <h2 style="font-size:18px; font-weight:800; color:var(--text-dark); margin:0;">سيارات المزاد المعروضة للبيع</h2>
        <div style="color:var(--text-muted); font-size:13px; font-weight:700">
          يعرض الآن: <span style="color:var(--text-dark)" class="font-en"><?= count($vehicles) ?></span> سيارة
        </div>
      </div>

      <!-- Vehicles Card Grid -->
      <div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:24px;" class="auctions-grid">
        <?php 
        foreach($vehicles as $a): 
          $title_car = $a['title'] ?? ($a['make'].' '.$a['model'].' '.$a['year']);
          $img = getCarImage($a['make'], $a['image_url']);
          $isLive = ($a['type'] ?? 'live') === 'live';
          $isFeatured = isset($a['is_featured']) && $a['is_featured'];
          $countdownVal = $a['end_time'] ?? $event['end_time'];
        ?>
        <div class="auction-card animate-card <?= $isFeatured ? 'featured-card' : '' ?>" data-id="<?= $a['id'] ?>" data-type="<?= $a['type'] ?? 'live' ?>">
          <div class="card-fav" data-id="<?= $a['id'] ?>"><i class="ph ph-heart"></i></div>
          
          <!-- Unified Stacked Badges Container -->
          <?php
            $status_text = $isLive ? 'مباشر' : 'شراء فوري';
            $status_class = $isLive ? 'badge-live' : 'badge-instant';
            if (($a['status'] ?? '') === 'ended') {
                $status_text = 'منتهي';
                $status_class = 'badge-ended';
            } elseif (($a['status'] ?? '') === 'upcoming') {
                $status_text = 'قادم';
                $status_class = 'badge-upcoming';
            }
          ?>
          <div class="card-badges-container">
            <div class="badge-item <?= $status_class ?>"><?= $status_text ?></div>
            <?php if($isFeatured): ?>
              <div class="badge-item badge-featured"><i class="ph-fill ph-star"></i> مميز</div>
            <?php endif; ?>
          </div>

          <div class="ac-img-wrap">
            <img src="<?= $img ?>" alt="<?= sanitize($title_car) ?>" loading="lazy">
          </div>
          <div class="ac-body">
            <h3 class="ac-title"><?= sanitize($title_car) ?></h3>
            <div class="ac-meta">
              <span><i class="ph ph-map-pin ph-space-left"></i><?= sanitize($a['city'] ?? 'الرياض') ?></span>
              <span><i class="ph ph-gauge ph-space-left"></i><?= number_format($a['mileage'] ?? 0) ?> كم</span>
              <span><i class="ph ph-calendar-blank ph-space-left"></i><?= $a['year'] ?? '2023' ?></span>
            </div>
            
            <div class="ac-price-box">
              <div>
                <div class="ac-price-label">السعر الافتتاحي / الحالي</div>
                <div class="ac-price-val"><?= number_format($a['current_price'] ?? $a['starting_price']) ?> <span style="font-size:12px">ر.س</span></div>
              </div>
              <?php if ($isLive): ?>
              <div class="ac-timer-box" data-countdown="<?= $countdownVal ?>">
                <div class="ac-timer-label">ينتهي في</div>
                <div class="ac-timer-val">
                  <span data-unit="hours">00</span>:<span data-unit="mins">00</span>:<span data-unit="secs">00</span>
                </div>
              </div>
              <?php else: ?>
              <div class="ac-timer-box">
                <div style="font-size:12px; color:var(--primary); font-weight:700; margin-bottom:4px">سعر فوري</div>
                <div style="font-weight:800; font-family:var(--font-en); font-size:14px; color:var(--primary)"><i class="ph ph-lightning ph-space-left"></i>فوري</div>
              </div>
              <?php endif; ?>
            </div>
            <!-- Participate button -->
            <a href="/auction-live.php?id=<?= $a['id'] ?>" class="btn btn-primary" style="width:100%; text-align:center; display:inline-flex; align-items:center; justify-content:center; gap:8px;">
              دخول غرفة المزايدة <i class="ph ph-arrow-up-right"></i>
            </a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Pagination inside event -->
      <?php if ($total_pages > 1): ?>
      <div style="display:flex; justify-content:center; gap:8px; margin-top:48px;">
        <?php for ($p = 1; $p <= $total_pages; $p++): 
            $url_params = $_GET;
            $url_params['page'] = $p;
            $page_url = '?' . http_build_query($url_params);
        ?>
          <a href="<?= $page_url ?>" class="btn btn-outline-dark btn-sm <?= $p === $page ? 'active' : '' ?>" style="min-width:40px; text-align:center; padding:8px 0; <?= $p === $page ? 'border-color:var(--primary); color:var(--primary); font-weight:800;' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>
      </div>
      <?php endif; ?>
    </div>
    
    <!-- Left Column: Event details & Brochure download -->
    <div class="event-info-sidebar">
      
      <!-- Download Terms Brochure Box -->
      <div class="brochure-download-box">
        <i class="ph ph-file-pdf" style="color:var(--danger); font-size:64px; margin-bottom:12px; display:block"></i>
        <h3 style="font-size:16px; font-weight:800; color:var(--text-dark); margin-bottom:8px">كراسة الشروط والمواصفات</h3>
        <p style="color:var(--text-muted); font-size:12px; line-height:1.6; margin-bottom:20px;">
          قم بتحميل الكراسة الرسمية للمزاد متضمنة شروط الدخول واللوائح المنظمة ومواصفات المركبات المعتمدة.
        </p>
        <a href="<?= $event['brochure_pdf'] ?? '#' ?>" download class="btn btn-primary btn-sm" style="width:100%; display:inline-flex; align-items:center; justify-content:center; gap:8px;">
          <i class="ph ph-download-simple" style="color:#fff"></i> تحميل البروشور (PDF)
        </a>
      </div>
      
      <!-- Seller info card -->
      <div class="panel-content" style="border-radius:var(--radius-lg)">
        <h3 style="font-size:15px; font-weight:800; border-bottom:1px solid var(--border-light); padding-bottom:12px; margin-bottom:16px;">الجهة المالكة (البائع)</h3>
        <?php $seller_name = $event['seller'] ?? ($event['seller_company'] ?? 'شركة معتمدة'); ?>
        <div style="font-weight:800; font-size:16px; color:var(--text-dark); display:flex; align-items:center; gap:6px;">
          <i class="ph ph-buildings" style="color:var(--primary)"></i> <?= sanitize($seller_name) ?>
        </div>
        <div style="display:flex; align-items:center; gap:4px; margin-top:8px;" class="seller-rating-box">
          <i class="ph ph-star-fill"></i>
          <span style="color:var(--text-dark)">4.8 / 5</span>
          <span style="color:var(--text-muted); font-size:12px; font-weight:500">(بائع موثوق)</span>
        </div>
        <div style="font-size:12px; color:var(--text-muted); margin-top:16px; line-height:1.6">
          تعتبر شركة <?= sanitize($seller_name) ?> من الموردين المعتمدين لدى المنصة وتخضع كافة مركباتها لعمليات فحص تقني دقيق قبل الإدراج.
        </div>
      </div>
      
      <!-- Bid Terms summary -->
      <div class="panel-content" style="border-radius:var(--radius-lg); font-size:12px; line-height:1.6; color:var(--text-muted)">
        <h3 style="font-size:14px; font-weight:800; color:var(--text-dark); border-bottom:1px solid var(--border-light); padding-bottom:12px; margin-bottom:12px;">ملاحظات المزاد الهامة</h3>
        <ul style="padding-right:16px; list-style-type:disc; display:flex; flex-direction:column; gap:8px;">
          <li>المشاركة في المزايدة تتطلب إيداع مبلغ التأمين المحدد في حسابك المالي.</li>
          <li>جميع عروض المزايدة ملزمة قانونياً ولا يمكن سحبها أو إلغاؤها بعد تقديمها.</li>
          <li>يتم تطبيق عمولة إضافية قدرها 5% على القيمة الإجمالية للمركبة بعد رسو المزاد.</li>
        </ul>
      </div>

    </div>

  </div>

</div>

<!-- Footer template -->
<?php include 'includes/footer.php'; ?>

<!-- Scripts -->
<script src="/assets/js/fleetx.js"></script>
</body>
</html>
