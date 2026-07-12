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

if (!$event) {
    header("Location: /auctions.php");
    exit;
}

$title = $event['title'];
$hero_title = $event['title'];
$hero_bg = 'https://images.unsplash.com/photo-1555215695-3004980ad54e?w=1600&q=80';
if (!empty($vehicles[0]['image_url'])) {
    $hero_bg = fleetx_card_image($vehicles[0]['image_url'], (int)($vehicles[0]['id'] ?? 0), 'live', $vehicles[0]['make'] ?? '');
}
$hero_back_href = '/auctions.php';
$hero_back_label = '← العودة لقاعة المزادات';
$hero_eyebrow = 'حدث مزاد نشط';
$hero_desc = sanitize($event['seller'] ?? ($event['seller_company'] ?? '')) . ' — ' . $total_vehicles . ' مركبة معروضة في هذا الحدث';
$hero_extra_class = 'fx-page-hero--cover fx-page-hero--compact fx-page-hero--event';
$event_countdown_end = $db_connected
    ? fleetx_event_countdown_end($conn, (int)$event_id, $event['end_time'] ?? null)
    : date('Y-m-d H:i:s', time() + 86400 * 3);
$hero_meta_html = '
  <span class="fx-page-hero__chip"><i class="ph-fill ph-buildings"></i> ' . sanitize($event['seller'] ?? ($event['seller_company'] ?? 'الوطنية للتأجير')) . '</span>
  <span class="fx-page-hero__chip"><span class="fx-live-dot"></span> مزاد نشط الآن</span>
  <span class="fx-page-hero__chip"><i class="ph ph-car"></i> ' . $total_vehicles . ' مركبة</span>';
$hero_actions_html = '
  <div class="fx-countdown-panel">
    <div class="fx-countdown-lbl">ينتهي الحدث خلال</div>
    <div class="fx-countdown-row" data-countdown="' . htmlspecialchars($event_countdown_end) . '">
      <div class="fx-countdown-unit"><div class="fx-countdown-val"><span data-unit="days">00</span></div><span class="fx-countdown-unit-lbl">أيام</span></div>
      <span class="fx-countdown-sep">:</span>
      <div class="fx-countdown-unit"><div class="fx-countdown-val"><span data-unit="hours">00</span></div><span class="fx-countdown-unit-lbl">ساعات</span></div>
      <span class="fx-countdown-sep">:</span>
      <div class="fx-countdown-unit"><div class="fx-countdown-val"><span data-unit="mins">00</span></div><span class="fx-countdown-unit-lbl">دقائق</span></div>
      <span class="fx-countdown-sep">:</span>
      <div class="fx-countdown-unit"><div class="fx-countdown-val accent"><span data-unit="secs">00</span></div><span class="fx-countdown-unit-lbl">ثواني</span></div>
    </div>
  </div>';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= sanitize($title) ?> | FleetX</title>
  <link rel="stylesheet" href="<?= fleetx_css_href() ?>">
</head>
<body class="fx-home fx-page-shell fx-page-shell--listing">

<?php include 'includes/navbar.php'; ?>
<?php include 'includes/page-hero.inc.php'; ?>

<div class="container fx-page-body fx-page-body--overlap-lg">
  
  <div class="event-details-grid">
    
    <!-- Right Column: Vehicles Grid -->
    <div>
      <!-- Section Header -->
      <div class="fx-toolbar">
        <h2>سيارات المزاد المعروضة</h2>
        <div class="fx-detail-spec__label">يعرض الآن: <strong class="font-en"><?= count($vehicles) ?></strong> سيارة</div>
      </div>

      <div class="fx-event-lots-grid">
        <?php foreach($vehicles as $a):
          $title_car = $a['title'] ?? ($a['make'].' '.$a['model'].' '.$a['year']);
          $isFeatured = !empty($a['is_featured']);
          $isVip = ($isFeatured && ($a['id'] % 2 !== 0));
          $isInstant = ($a['type'] ?? 'live') === 'instant';
          $href = $isInstant ? '/vehicle-details.php?id=' . $a['id'] : '/auction-live.php?id=' . $a['id'];
          $fx_card = [
            'id' => $a['id'],
            'href' => $href,
            'title' => $title_car,
            'image_url' => $a['image_url'] ?? '',
            'make' => trim(($a['make'] ?? '') . ' ' . ($a['model'] ?? '')),
            'type' => $a['type'] ?? 'live',
            'status' => 'active',
            'city' => $a['city'] ?? 'الرياض',
            'mileage' => intval($a['mileage'] ?? 0),
            'year' => $a['year'] ?? '2023',
            'price' => intval($a['current_price'] ?? $a['starting_price'] ?? 0),
            'price_label' => 'المزايدة الحالية',
            'end_time' => $a['end_time'] ?? $event['end_time'],
            'timer_data' => timeLeft($a['end_time'] ?? $event['end_time']),
            'is_vip' => $isVip,
            'is_featured' => $isFeatured,
            'show_installment' => $isInstant && ($a['id'] % 2 !== 0),
          ];
          include 'includes/fx-auction-card.inc.php';
        endforeach; ?>
      </div>

      <!-- Pagination inside event -->
      <?php if ($total_pages > 1): ?>
      <nav class="fx-pagination" aria-label="صفحات الحدث">
        <?php for ($p = 1; $p <= $total_pages; $p++):
            $url_params = $_GET;
            $url_params['page'] = $p;
            $page_url = '?' . http_build_query($url_params);
        ?>
          <a href="<?= $page_url ?>" class="fx-pagination__link<?= $p === $page ? ' is-active' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>
      </nav>
      <?php endif; ?>
    </div>
    
    <!-- Left Column: Event details & Brochure download -->
    <div class="event-info-sidebar fx-event-sidebar">
      <div class="fx-event-panel fx-event-panel--brochure">
        <i class="ph ph-file-pdf fx-event-panel__icon"></i>
        <h3>كراسة الشروط والمواصفات</h3>
        <p>قم بتحميل الكراسة الرسمية للمزاد متضمنة شروط الدخول واللوائح المنظمة ومواصفات المركبات المعتمدة.</p>
        <a href="<?= $event['brochure_pdf'] ?? '#' ?>" download class="btn btn-primary btn-sm fx-event-panel__btn">
          <i class="ph ph-download-simple"></i> تحميل البروشور (PDF)
        </a>
      </div>

      <div class="fx-event-panel">
        <h3 class="fx-event-panel__title">الجهة المالكة (البائع)</h3>
        <?php $seller_name = $event['seller'] ?? ($event['seller_company'] ?? 'شركة معتمدة'); ?>
        <div class="fx-event-seller-name">
          <i class="ph ph-buildings"></i> <?= sanitize($seller_name) ?>
        </div>
        <div class="seller-rating-box fx-event-rating">
          <i class="ph ph-star-fill"></i>
          <span><?= number_format(floatval($event['seller_rating'] ?? 4.8), 1) ?> / 5</span>
          <span class="fx-event-rating__note">(بائع موثوق)</span>
        </div>
        <p class="fx-event-panel__text">
          تعتبر شركة <?= sanitize($seller_name) ?> من الموردين المعتمدين لدى المنصة وتخضع كافة مركباتها لعمليات فحص تقني دقيق قبل الإدراج.
        </p>
      </div>

      <div class="fx-event-panel fx-event-panel--notes">
        <h3 class="fx-event-panel__title">ملاحظات المزاد الهامة</h3>
        <ul class="fx-event-notes-list">
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
