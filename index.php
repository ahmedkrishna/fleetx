<?php
require_once 'config.php';

// Fetch Live Auction Events
$events = [];
if ($db_connected) {
    $res = $conn->query("SELECT ae.*, sc.company_name as seller_company,
                                (SELECT COUNT(*) FROM auctions a WHERE a.event_id = ae.id AND a.status='active') as lot_count
                         FROM auction_events ae
                         JOIN seller_companies sc ON ae.seller_id = sc.id
                         WHERE ae.status='active' LIMIT 3");
    if ($res) while ($row = $res->fetch_assoc()) $events[] = $row;
}

// Calculate Dynamic Stats
$total_sales_value = 0;
$total_auctions = 0;
$successful_auctions = 0;

if ($db_connected) {
    // Total sales value
    $res_sales = $conn->query("SELECT COALESCE(SUM(current_price), 0) FROM auctions WHERE status='ended'");
    if ($res_sales) {
        $total_sales_value = floatval($res_sales->fetch_row()[0]);
    }
    
    // Success rate
    $res_total = $conn->query("SELECT COUNT(*) FROM auctions");
    if ($res_total) $total_auctions = intval($res_total->fetch_row()[0]);
    
    $res_success = $conn->query("SELECT COUNT(*) FROM auctions WHERE status='ended'");
    if ($res_success) $successful_auctions = intval($res_success->fetch_row()[0]);
}

$approval_rate = ($total_auctions > 0) ? round(($successful_auctions / $total_auctions) * 100, 1) : 94;
$stats_sales_val = '120';
$stats_sales_unit = '';
$stats_sales_unit_ar = 'مليون ريال';
$stats_success_pct = (string) max(94, (int) round($approval_rate));

// Fetch auctions early for hero bid signs + section 2
$live_auctions = [];
$instant_cars = [];
if ($db_connected) {
    $res = $conn->query("SELECT a.*, v.make, v.model, v.year, v.image_url, v.city, v.mileage
                         FROM auctions a
                         JOIN vehicles v ON a.vehicle_id = v.id
                         WHERE a.type='live' AND a.status='active' LIMIT 6");
    if ($res) while ($row = $res->fetch_assoc()) $live_auctions[] = $row;

    $res = $conn->query("SELECT a.*, v.make, v.model, v.year, v.image_url, v.city, v.mileage
                         FROM auctions a
                         JOIN vehicles v ON a.vehicle_id = v.id
                         WHERE a.type='instant' AND a.status='active' LIMIT 6");
    if ($res) while ($row = $res->fetch_assoc()) $instant_cars[] = $row;
}

$hero_bid_signs = [];
$hero_live_names = ['مزاد الرياض الكبرى', 'مزاد أسطول جدة', 'مزاد سيارات الدفع الرباعي', 'مزاد الشرقية'];
foreach (array_slice($live_auctions, 0, 4) as $i => $a) {
    $car_label = trim(($a['make'] ?? '') . ' ' . ($a['model'] ?? '') . ' ' . ($a['year'] ?? ''));
    if ($car_label === '') $car_label = $hero_live_names[$i % count($hero_live_names)];
    $hero_bid_signs[] = [
        'text' => 'مزايدة حية',
        'car' => $car_label,
        'amount' => number_format((int)($a['current_price'] ?? $a['starting_price'] ?? 50000)) . ' ر.س',
        'url' => '/event.php?id=' . (int)($a['event_id'] ?? (($a['id'] ?? $i) % 3 + 1)),
    ];
}
$hero_inst_makes = ['تويوتا', 'هيونداي', 'نيسان', 'فورد'];
$hero_inst_models = ['كامري', 'سوناتا', 'ألتيما', 'إكسبلورر'];
foreach (array_slice($instant_cars, 0, 3) as $i => $a) {
    $car_label = trim(($a['make'] ?? $hero_inst_makes[$i % 4]) . ' ' . ($a['model'] ?? $hero_inst_models[$i % 4]) . ' ' . ($a['year'] ?? '2023'));
    $hero_bid_signs[] = [
        'text' => 'شراء فوري',
        'car' => $car_label,
        'amount' => number_format((int)($a['current_price'] ?? $a['starting_price'] ?? 65000)) . ' ر.س',
        'url' => '/vehicle-details.php?id=' . (int)($a['id'] ?? (8 + $i)),
    ];
}
if (empty($hero_bid_signs)) {
    $hero_bid_signs = [
        ['text' => 'مزايدة جديدة', 'car' => 'كامري 2023', 'amount' => '٨٥,٠٠٠ ر.س', 'url' => '/event.php?id=1'],
        ['text' => 'عرض مباشر', 'car' => 'توسان 2022', 'amount' => '٧٢,٥٠٠ ر.س', 'url' => '/event.php?id=2'],
        ['text' => 'مزايدة فورية', 'car' => 'باترول 2021', 'amount' => '١٤٣,٠٠٠ ر.س', 'url' => '/auctions.php?type=live'],
        ['text' => 'شراء فوري', 'car' => 'سبورتاج 2022', 'amount' => '٦٨,٠٠٠ ر.س', 'url' => '/auctions.php?type=instant'],
    ];
}

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>منصة FleetX | مزادات السيارات الاحترافية للأساطيل</title>
  <meta name="fx-build" content="<?= FLEETX_CSS_VER ?>">
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
  <link rel="stylesheet" href="<?= fleetx_css_href() ?>">
  <link rel="stylesheet" href="<?= fleetx_home_live_css_href() ?>">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>
  <style id="fx-home-critical">
    body.fx-home-index .fx-home-quick-stats,
    body.fx-home-index .fx-home-tabs-link,
    body.fx-home-index .auctions-tabs-wrapper { display: none !important; }
    body.fx-home-index .fx-auctions-panel { display: block !important; }
    body.fx-home-index .fx-auctions-shell,
    body.fx-home-index .fx-auction-type-toggle { display: none !important; }
    body.fx-home-index .fx-home-auctions-block { display: none !important; }
    body.fx-home-index .fx-home-stats-section { background: #060c16 !important; position: relative; overflow: hidden; }
    body.fx-home-index .fx-stats-video-bg { position: absolute; inset: 0; z-index: 0; }
    body.fx-home-index .fx-stats-video-bg__media { opacity: 0.38; object-fit: cover; width: 100%; height: 100%; }
  </style>
  </head>
<body class="fx-home fx-home-index" data-fx-build="<?= FLEETX_CSS_VER ?>">

<!-- Navbar template -->
<?php include 'includes/navbar.php'; ?>

<!-- ── Hero Section (white fleet1 theme) ── -->
<div class="fx-hero-wrap fx-hero-wrap--fleet1">
<div class="hero-wrapper fx-hero-wrapper--fleet1">
  <div class="fx-hero-bg-stage" aria-hidden="true">
    <div class="fx-hero-bg-gradient" aria-hidden="true"></div>
    <div class="fx-hero-picture fx-hero-picture--desktop fx-hero-picture--float" aria-hidden="true">
      <img src="/assets/images/fleetxhero-desktop.png" alt="" class="fx-hero-picture__img" decoding="async">
    </div>
    <div class="fx-hero-picture fx-hero-picture--mobile fx-hero-picture--float" aria-hidden="true">
      <img src="/assets/images/fleetxhero-mobile.png" alt="" class="fx-hero-picture__img" decoding="async">
    </div>
    <div id="fxBiddingSigns" class="fx-hero-bid-signs" aria-label="عروض مزادات حية"></div>
  </div>
  <section class="hero fx-hero-section fx-hero-section--fleet1">
    <div class="hero-content fx-hero-content fx-hero-content--fleet1">
      <p class="hero-subtitle fx-hero-subtitle--fleet1 fx-hero-motion" id="heroSubtitle"></p>
      <h1 class="hero-title fx-hero-title--fleet1 fx-hero-motion" id="heroMainTitle"></h1>
      <div class="fx-hero-cta-row fx-hero-motion" id="heroCtaRow">
        <a href="/auctions.php?type=live" class="btn btn-primary fx-hero-cta-btn">
          <i class="ph-fill ph-broadcast"></i> استكشف المزادات
        </a>
        <a href="/register.php" class="btn btn-outline-dark fx-hero-cta-btn">
          <i class="ph ph-user-plus"></i> ابدأ الآن
        </a>
      </div>
    </div>

    <script>
      (function() {
        const slides = [
          { title: 'أضخم مزادات السيارات', subtitle: 'أول منصة مزادات ذكية وموثوقة بالمملكة' },
          { title: 'تنفيذ فوري وشفافية تامة', subtitle: 'تقنية متطورة لضمان أفضل العوائد لمركباتك' },
          { title: 'مزايدة ذكية ومضمونة', subtitle: 'تكامل مباشر مع النفاذ الوطني وأعلى معايير الأمان' }
        ];
        let currentIndex = 0;
        let animating = false;
        const titleEl = document.getElementById('heroMainTitle');
        const subtitleEl = document.getElementById('heroSubtitle');
        const ctaEl = document.getElementById('heroCtaRow');

        function applySlide(index, animate) {
          if (!titleEl || !subtitleEl) return;
          const slide = slides[index];
          const run = function() {
            titleEl.textContent = slide.title;
            subtitleEl.textContent = slide.subtitle;
            titleEl.classList.remove('is-exit');
            subtitleEl.classList.remove('is-exit');
            titleEl.classList.add('is-enter');
            subtitleEl.classList.add('is-enter');
            requestAnimationFrame(function() {
              titleEl.classList.add('is-visible');
              subtitleEl.classList.add('is-visible');
            });
            animating = false;
          };
          if (!animate) {
            titleEl.textContent = slide.title;
            subtitleEl.textContent = slide.subtitle;
            titleEl.classList.add('is-visible');
            subtitleEl.classList.add('is-visible');
            if (ctaEl) ctaEl.classList.add('is-visible');
            return;
          }
          titleEl.classList.remove('is-visible', 'is-enter');
          subtitleEl.classList.remove('is-visible', 'is-enter');
          titleEl.classList.add('is-exit');
          subtitleEl.classList.add('is-exit');
          setTimeout(run, 520);
        }

        function cycleHero() {
          if (animating) return;
          animating = true;
          currentIndex = (currentIndex + 1) % slides.length;
          applySlide(currentIndex, true);
        }

        window.addEventListener('load', function() {
          applySlide(0, false);
          if (ctaEl) ctaEl.classList.add('is-visible');
          setInterval(cycleHero, 5500);
        });
      })();
    </script>
  </section>
</div>
</div>

<!-- ── Section 2: Auctions ── -->
<section class="reveal fx-home-auctions">
  <div class="container">
    <div class="fx-home-section-intro fx-home-section-intro--center">
      <h2 class="section-title">استكشف المركبات المتاحة</h2>
      <p class="section-subtitle">تصفح مزادات السيارات والمبيعات الفورية المدرجة في المنصة — بيانات حية من قاعدة المنصة.</p>
    </div>

    <div class="fx-auctions-panel">
      <div class="fx-auctions-panel__tabs auctions-tabs fx-home-tabs">
        <button type="button" class="auctions-tab-btn active" onclick="switchAuctionTab('live', this)"><i class="ph-fill ph-broadcast"></i> المزادات الحية</button>
        <button type="button" class="auctions-tab-btn" onclick="switchAuctionTab('instant', this)"><i class="ph-fill ph-lightning"></i> الشراء الفوري</button>
      </div>

      <div id="tab-content-live" class="auctions-tab-content active">
      <div class="swiper auctions-swiper live-auctions-swiper auctions-swiper--padded fx-auctions-swiper--marquee" dir="ltr">
        <div class="swiper-wrapper">
        <?php
          $status_cycle = ['active', 'upcoming', 'ended'];
          $live_cards = array_slice($live_auctions, 0, 3);
          $live_cities = ['الرياض', 'جدة', 'الدمام', 'مكة المكرمة', 'الخبر', 'المدينة المنورة'];
          $live_prices = [205744, 95000, 78500, 142000, 118500, 67500];
          $live_names = ['مزاد الرياض الكبرى', 'مزاد أسطول جدة', 'مزاد سيارات الدفع الرباعي', 'مزاد الشرقية', 'مزاد التأجير اليومي', 'مزاد الأساطيل الذهبية'];
          while (count($live_cards) < 3) {
            $i = count($live_cards);
            $live_cards[] = [
              'id' => 900 + $i,
              'event_id' => $i + 1,
              'image_url' => '',
              'city' => $live_cities[$i % 6],
              'seller' => 'الوطنية',
              'current_price' => $live_prices[$i % 6],
              'starting_price' => $live_prices[$i % 6],
              'end_time' => date('Y-m-d H:i:s', strtotime('+' . (19 - $i) . ' hours')),
              'is_featured' => ($i === 0),
            ];
          }

          foreach ($live_cards as $ev_index => $a):
            $title_car = $live_names[$ev_index % count($live_names)];
            $is_featured = !empty($a['is_featured']);
            $is_vip = ($is_featured && (($a['id'] ?? $ev_index) % 2 !== 0));
            $card_status = $status_cycle[$ev_index % 3];
            $event_href = '/event.php?id=' . ($a['event_id'] ?? ($a['id'] % 3 + 1));
            $fx_card = [
              'id' => $a['id'],
              'href' => $event_href,
              'title' => $title_car,
              'image_url' => $a['image_url'] ?? '',
              'make' => $a['make'] ?? '',
              'type' => 'live',
              'status' => $card_status,
              'city' => $a['city'] ?? 'الرياض',
              'seller' => $a['seller'] ?? 'الوطنية',
              'vehicles_count' => rand(20, 150),
              'price' => intval($a['current_price'] ?? $a['starting_price'] ?? 50000),
              'price_label' => 'السعر الافتتاحي',
              'end_time' => $a['end_time'] ?? null,
              'timer_data' => ($card_status !== 'ended' && !empty($a['end_time'])) ? timeLeft($a['end_time']) : null,
              'is_vip' => $is_vip,
              'is_featured' => $is_featured,
              'extra_class' => 'animate-card fx-card-modern',
            ];
        ?>
          <div class="swiper-slide">
            <?php include 'includes/fx-auction-card.inc.php'; ?>
          </div>
        <?php endforeach; ?>
        </div>
        <div class="swiper-pagination"></div>
        <div class="swiper-button-prev"></div>
        <div class="swiper-button-next"></div>
      </div>
      <div class="fx-tab-footer">
        <a href="/auctions.php?type=live" class="btn btn-outline-dark">عرض جميع المزادات الحية <i class="ph ph-arrow-left"></i></a>
      </div>
    </div>

    <div id="tab-content-instant" class="auctions-tab-content">
      <div class="swiper auctions-swiper instant-buy-swiper auctions-swiper--padded fx-auctions-swiper--marquee" dir="ltr">
        <div class="swiper-wrapper">
        <?php
          $instant_cards = array_slice($instant_cars, 0, 3);
          $inst_makes = ['تويوتا', 'هيونداي', 'نيسان', 'فورد', 'كيا', 'شيفروليه'];
          $inst_models = ['كامري', 'سوناتا', 'ألتيما', 'إكسبلورر', 'سبورتاج', 'تاهو'];
          $inst_cities = ['الرياض', 'جدة', 'الدمام', 'الطائف', 'أبها', 'تبوك'];
          $inst_prices = [89000, 72000, 65000, 115000, 54000, 98000];
          while (count($instant_cards) < 3) {
            $i = count($instant_cards);
            $instant_cards[] = [
              'id' => 800 + $i,
              'make' => $inst_makes[$i % 6],
              'model' => $inst_models[$i % 6],
              'year' => 2020 + ($i % 4),
              'image_url' => '',
              'city' => $inst_cities[$i % 6],
              'mileage' => 45000 + ($i * 12000),
              'current_price' => $inst_prices[$i % 6],
              'starting_price' => $inst_prices[$i % 6],
              'end_time' => date('Y-m-d H:i:s', strtotime('+' . (3 + $i) . ' days')),
            ];
          }

          foreach ($instant_cards as $inst_index => $a):
            $title_car = $a['title'] ?? ($a['make'] . ' ' . $a['model'] . ' ' . $a['year']);
            $is_featured = ($inst_index % 3 === 0);
            $is_vip = ($is_featured && (($a['id'] ?? $inst_index) % 2 !== 0));
            $fx_card = [
              'id' => $a['id'],
              'href' => '/vehicle-details.php?id=' . $a['id'],
              'title' => $title_car,
              'image_url' => $a['image_url'] ?? '',
              'make' => ($a['make'] ?? '') . ' ' . ($a['model'] ?? ''),
              'type' => 'instant',
              'status' => 'active',
              'city' => $a['city'] ?? 'الرياض',
              'mileage' => intval($a['mileage'] ?? 0),
              'year' => $a['year'] ?? '2023',
              'price' => intval($a['current_price'] ?? $a['starting_price'] ?? 0),
              'price_label' => 'السعر المطلوب',
              'end_time' => $a['end_time'] ?? date('Y-m-d H:i:s', strtotime('+3 days')),
              'is_vip' => $is_vip,
              'is_featured' => $is_featured,
              'show_installment' => (($a['id'] ?? 0) % 2 !== 0),
              'extra_class' => 'animate-card fx-card-modern',
            ];
        ?>
          <div class="swiper-slide">
            <?php include 'includes/fx-auction-card.inc.php'; ?>
          </div>
        <?php endforeach; ?>
        </div>
        <div class="swiper-pagination"></div>
        <div class="swiper-button-prev"></div>
        <div class="swiper-button-next"></div>
      </div>
      <div class="fx-tab-footer">
        <a href="/auctions.php?type=instant" class="btn btn-outline-dark">عرض جميع المركبات <i class="ph ph-arrow-left"></i></a>
      </div>
    </div>
    </div><!-- /.fx-auctions-panel -->
  </div>
</section>

<script>
  function switchAuctionTab(tabId, btn) {
    document.querySelectorAll('.fx-auctions-panel .auctions-tab-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');

    document.querySelectorAll('.auctions-tab-content').forEach(content => content.classList.remove('active'));
    document.getElementById('tab-content-' + tabId).classList.add('active');

    if (window.fxHomeSwipers && window.fxHomeSwipers[tabId]) {
      requestAnimationFrame(function() {
        const sw = window.fxHomeSwipers[tabId];
        sw.update();
        if (sw.autoplay && typeof sw.autoplay.start === 'function') sw.autoplay.start();
      });
    }
  }

  function switchHiwTab(e, type, step) {
    activateHiwStep(type, step, e && e.currentTarget);
  }

  function activateHiwStep(type, step, clickedTab) {
    const tabsContainer = document.getElementById(type + '-tabs');
    if (!tabsContainer) return;

    tabsContainer.querySelectorAll('.b-tab').forEach((t, i) => {
      t.classList.toggle('active', clickedTab ? t === clickedTab : (i + 1 === step));
    });

    document.querySelectorAll(`[id^="${type}-step-"]`).forEach(c => {
      c.style.display = 'none';
      c.classList.remove('active');
    });

    const activeContent = document.getElementById(`${type}-step-${step}`);
    if (activeContent) {
      activeContent.style.display = 'flex';
      activeContent.classList.add('active', 'reveal', 'visible');
    }
  }
</script>

<!-- ── Section 3: How It Works (v1 dark sticky browser tabs) ── -->
<section class="fx-hiw-section fx-hiw-section--dark">
  <div id="buyers-section" class="hiw-wrapper hiw-wrapper--sticky hiw-wrapper--buyers">
    <div class="container">
      <div class="hiw-dark-head">
        <h2 class="hiw-dark-title">كيف تبدأ كـ <span class="fx-text-primary">مشتري؟</span></h2>
        <p class="hiw-dark-sub">للمشترين الأفراد والشركات المعتمدة</p>
      </div>

      <div class="browser-tabs-container fx-hiw-browser">
        <div class="browser-tabs" id="buyer-tabs">
          <button class="b-tab active" onclick="switchHiwTab(event, 'buyer', 1)">التسجيل</button>
          <button class="b-tab" onclick="switchHiwTab(event, 'buyer', 2)">المحفظة</button>
          <button class="b-tab" onclick="switchHiwTab(event, 'buyer', 3)">المزايدة</button>
        </div>
        <div class="browser-content hiw-panel">
          <div id="buyer-step-1" class="hiw-tab-content active" style="display:flex;">
            <div class="hiw-tab-col">
              <i class="ph ph-identification-card hiw-tab-icon"></i>
              <h3 class="hiw-tab-title">التسجيل وتوثيق الهوية <i class="ph-fill ph-warning-circle hiw-info-icon" onclick="openHiwModal('buyer-reg')" title="عرض التفاصيل والآلية"></i></h3>
              <p class="hiw-tab-desc">سجّل حسابك في 3 خطوات سريعة عبر منصة نفاذ الوطني الموحّد لضمان أعلى معايير الأمان والثقة في التعاملات.</p>
            </div>
            <div class="hiw-tab-col">
              <img src="https://images.unsplash.com/photo-1556740758-90de374c12ad?w=600&q=80" class="hiw-tab-img" alt="التسجيل" loading="lazy">
            </div>
          </div>

          <div id="buyer-step-2" class="hiw-tab-content" style="display:none;">
            <div class="hiw-tab-col">
              <i class="ph ph-wallet hiw-tab-icon"></i>
              <h3 class="hiw-tab-title">المحفظة والتأمين <i class="ph-fill ph-warning-circle hiw-info-icon" onclick="openHiwModal('buyer-wallet')" title="عرض التفاصيل والآلية"></i></h3>
              <p class="hiw-tab-desc">أودع مبلغ التأمين المطلوب في محفظتك الرقمية عبر قنوات الدفع المتعددة للبدء في المزايدة المباشرة.</p>
            </div>
            <div class="hiw-tab-col">
              <img src="https://images.unsplash.com/photo-1559526324-4b87b5e36e44?w=600&q=80" class="hiw-tab-img" alt="المحفظة" loading="lazy">
            </div>
          </div>

          <div id="buyer-step-3" class="hiw-tab-content" style="display:none;">
            <div class="hiw-tab-col">
              <i class="ph ph-gavel hiw-tab-icon"></i>
              <h3 class="hiw-tab-title">المزايدة والفوز بالمركبة <i class="ph-fill ph-warning-circle hiw-info-icon" onclick="openHiwModal('buyer-bid')" title="عرض التفاصيل والآلية"></i></h3>
              <p class="hiw-tab-desc">سجّل في المزاد المختار، وحدّد صفتك القانونية، وشارك في غرفة المزاد الحية للفوز بالمركبات.</p>
            </div>
            <div class="hiw-tab-col">
              <img src="https://images.unsplash.com/photo-1603584173870-7f23fdae1b7a?w=600&q=80" class="hiw-tab-img" alt="المزايدة والفوز" loading="lazy">
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="sellers-section" class="hiw-wrapper hiw-wrapper--sticky hiw-wrapper--sellers">
    <div class="container">
      <div class="hiw-dark-head">
        <h2 class="hiw-dark-title">كيف تبدأ كـ <span class="fx-text-primary">بائع معتمد؟</span></h2>
        <p class="hiw-dark-sub">لشركات التأجير والأساطيل</p>
      </div>

      <div class="browser-tabs-container fx-hiw-browser">
        <div class="browser-tabs" id="seller-tabs">
          <button class="b-tab active" onclick="switchHiwTab(event, 'seller', 1)">الاعتماد</button>
          <button class="b-tab" onclick="switchHiwTab(event, 'seller', 2)">الاشتراك</button>
          <button class="b-tab" onclick="switchHiwTab(event, 'seller', 3)">الإدراج</button>
        </div>
        <div class="browser-content hiw-panel">
          <div id="seller-step-1" class="hiw-tab-content active" style="display:flex;">
            <div class="hiw-tab-col">
              <i class="ph ph-buildings hiw-tab-icon"></i>
              <h3 class="hiw-tab-title">توثيق الشركة والاعتماد <i class="ph-fill ph-warning-circle hiw-info-icon" onclick="openHiwModal('seller-reg')" title="عرض التفاصيل والآلية"></i></h3>
              <p class="hiw-tab-desc">سجّل شركتك وأرفق السجل التجاري ليتم تدقيقها واعتماد حسابك كبائع موثوق خلال 48 ساعة عمل.</p>
            </div>
            <div class="hiw-tab-col">
              <img src="https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=600&q=80" class="hiw-tab-img" alt="توثيق الشركة" loading="lazy">
            </div>
          </div>

          <div id="seller-step-2" class="hiw-tab-content" style="display:none;">
            <div class="hiw-tab-col">
              <i class="ph ph-package hiw-tab-icon"></i>
              <h3 class="hiw-tab-title">اختيار الباقة وشحن الحساب <i class="ph-fill ph-warning-circle hiw-info-icon" onclick="openHiwModal('seller-wallet')" title="عرض التفاصيل والآلية"></i></h3>
              <p class="hiw-tab-desc">اختر باقة الاشتراك المناسبة لحجم أسطولك، وأودع رسوم التفعيل للبدء في الاستفادة من أدوات البيع.</p>
            </div>
            <div class="hiw-tab-col">
              <img src="https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=600&q=80" class="hiw-tab-img" alt="شحن الحساب" loading="lazy">
            </div>
          </div>

          <div id="seller-step-3" class="hiw-tab-content" style="display:none;">
            <div class="hiw-tab-col">
              <i class="ph ph-car hiw-tab-icon"></i>
              <h3 class="hiw-tab-title">إدراج المركبات وإطلاق المزادات <i class="ph-fill ph-warning-circle hiw-info-icon" onclick="openHiwModal('seller-list')" title="عرض التفاصيل والآلية"></i></h3>
              <p class="hiw-tab-desc">أضف تفاصيل مركباتك وتقارير الفحص، وأطلق مزادك لتتلقى العروض من آلاف المشترين المعتمدين.</p>
            </div>
            <div class="hiw-tab-col">
              <img src="https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=600&q=80" class="hiw-tab-img" alt="إدراج المركبات" loading="lazy">
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- HIW Modal HTML -->
<div class="hiw-modal-overlay" id="hiwModalOverlay" onclick="if(event.target===this) closeHiwModal()">
  <div class="hiw-modal">
    <button class="hiw-modal-close" onclick="closeHiwModal()"><i class="ph ph-x"></i></button>
    <div id="hiwModalContent"></div>
  </div>
</div>



<script>
function switchBrowserTab(type, index) {
  const container = document.querySelector(`.browser-tabs-container:has(.b-panel[data-type="${type}"])`);
  const tabs = container.querySelectorAll('.b-tab');
  tabs.forEach((t, i) => t.classList.toggle('active', i + 1 === index));
  const panels = container.querySelectorAll(`.b-panel[data-type="${type}"]`);
  panels.forEach(p => p.classList.toggle('active', parseInt(p.dataset.index) === index));
}

const hiwModals = {
  'buyer-reg': { title: 'كيف أسجّل كمشترٍ؟', sub: 'يتم التسجيل في 3 خطوات عبر منصة النفاذ الوطني', steps: ['اضغط زر <strong>تسجيل الدخول</strong>، ثم <strong>إنشاء حساب جديد</strong>.', 'أدخل السجل الوطني. ستُحوَّل إلى <strong>النفاذ الوطني الموحّد</strong> لتوثيق هويتك.', 'أكمل بياناتك الشخصية. سيُفعَّل حسابك فوراً.'], alert: null },
  'buyer-wallet': { title: 'كيف أشحن محفظتي؟', sub: 'من الصفحة الشخصية → المحفظة', steps: ['اضغط على <strong>"المحفظة"</strong> ثم <strong>"المحفظة"</strong>.', 'اختر طريقة الدفع: <strong>مدى / Visa / Apple Pay</strong>.', 'أدخل المبلغ، أكمل الدفع، وسيُضاف الرصيد فوراً.'], alert: '<strong>مهم:</strong> المبالغ تُسترد لنفس الحساب البنكي فقط.' },
  'buyer-bid': { title: 'كيف أسجّل في مزاد؟', sub: 'بعد اختيار المزاد، اتّبع الآتي', steps: ['اضغط على <strong>"سجّل في المزاد"</strong>.', 'حدّد صفتك: <strong>أصيل</strong> / <strong>وكيل</strong> / <strong>منشأة</strong>.', 'أكّد حجز مبلغ الدخول من محفظتك.', 'ادخل غرفة المزاد الحية وزايد بـ <strong>"زايد الآن"</strong>.'], alert: '<strong>ملاحظة:</strong> التسجيل يتم لكل مزاد على حدة.' },
  'seller-reg': { title: 'كيف تُوثّق شركتك؟', sub: 'الاعتماد خلال 48 ساعة عمل', steps: ['اضغط <strong>"سجّل شركتك"</strong>.', 'أرفق <strong>السجل التجاري</strong> وعقد التأسيس.', 'سيتحقق فريقنا من الوثائق خلال 48 ساعة ويُرسل إشعار الاعتماد.'], alert: '<strong>ملاحظة:</strong> يجب أن تكون الشركة ذات نشاط في تأجير أو بيع المركبات.' },
  'seller-wallet': { title: 'كيف تختار الباقة؟', sub: 'وصول كامل لإدارة الأسطول', steps: ['من لوحة البائع، اضغط <strong>"الباقات"</strong>.', 'اختر الباقة المناسبة، ثم ادفع رسوم التفعيل.', 'تُفعَّل الباقة فوراً ويُضاف رصيد لإدراج المركبات.'], alert: null },
  'seller-list': { title: 'كيف تُدرج مركباتك؟', sub: 'أضف مركباتك في دقائق', steps: ['من لوحة البائع، اضغط <strong>"إضافة إعلان"</strong>.', 'أدخل التفاصيل الفنية وأرفق صوراً بجودة عالية.', 'أرفق <strong>تقرير الفحص الفني</strong>.', 'حدّد السعر، ثم اضغط <strong>"نشر"</strong>.'], alert: null }
};

function openHiwModal(key) {
  const data = hiwModals[key];
  if (!data) return;
  const stepsHtml = data.steps.map((s, i) => `<li><div class="hiw-modal-step-badge">${i+1}</div><div>${s}</div></li>`).join('');
  document.getElementById('hiwModalContent').innerHTML = `<h4>${data.title}</h4><div class="hiw-modal-sub">${data.sub}</div><ul class="hiw-modal-steps">${stepsHtml}</ul>${data.alert ? `<div class="hiw-modal-alert">${data.alert}</div>` : ''}`;
  document.getElementById('hiwModalOverlay').classList.add('active');
  document.body.style.overflow = 'hidden';
}
function closeHiwModal() {
  document.getElementById('hiwModalOverlay').classList.remove('active');
  document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if(e.key === 'Escape') closeHiwModal(); });
</script>

<!-- ── Section 4: Why FleetX (modern bento showcase) ── -->
<section class="fx-why-fleetx reveal" id="why-fleetx">
  <div class="container">
    <header class="fx-why-fleetx__head reveal fx-why-fleetx__head--center">
      <span class="fx-why-fleetx__eyebrow"><i class="ph-fill ph-gavel"></i> لماذا FleetX</span>
      <h2 class="fx-why-fleetx__title"><span class="fx-why-fleetx__title-gradient">منصة مزادات</span><br><span class="fx-why-fleetx__title-dark">مصممة للثقة والسرعة</span></h2>
      <p class="fx-why-fleetx__lead">نسهل العمليات اللوجستية والفحص والتسوية من البداية وحتى التسليم النهائي — بتجربة رقمية واحدة متكاملة.</p>
    </header>

    <div class="fx-why-bento">
      <article class="fx-why-card fx-why-card--spotlight reveal">
        <div class="fx-why-card__spotlight-bg" aria-hidden="true"></div>
        <div class="fx-why-card__spotlight-no">100<span>+</span></div>
        <div class="fx-why-card__body">
          <div class="fx-why-card__icon fx-why-card__icon--plain"><i class="ph ph-clipboard-text"></i></div>
          <h3>تقرير فحص 100+ نقطة</h3>
          <p>جميع المركبات تخضع لفحص فني شامل يغطي الهيكل والميكانيكا والكهرباء — معتمد من خبراء المنصة قبل عرضها في المزاد.</p>
          <ul class="fx-why-card__checks">
            <li><i class="ph-fill ph-check-circle"></i> فحص هيكل وميكانيكا</li>
            <li><i class="ph-fill ph-check-circle"></i> تقرير رقمي موثق</li>
          </ul>
        </div>
      </article>

      <article class="fx-why-card fx-why-card--trust reveal">
        <div class="fx-why-card__bg-overlay" aria-hidden="true" style="--fx-why-overlay:url('https://images.unsplash.com/photo-1450101499163-c8848c66ca85?w=800&q=80')"></div>
        <div class="fx-why-card__icon fx-why-card__icon--plain"><i class="ph ph-shield-check"></i></div>
        <h3>بيئة موثقة بالكامل</h3>
        <p>نتحقق من هوية المشترين والبائعين عبر تكامل مباشر مع النفاذ الوطني الموحد لضمان جدية كل مزايدة.</p>
        <span class="fx-why-card__pill"><i class="ph-fill ph-fingerprint"></i> نفاذ وطني</span>
      </article>

      <article class="fx-why-card fx-why-card--ai reveal">
        <div class="fx-why-card__bg-overlay" aria-hidden="true" style="--fx-why-overlay:url('https://images.unsplash.com/photo-1485827404703-89b55fcc595e?w=800&q=80')"></div>
        <div class="fx-why-card__icon fx-why-card__icon--plain"><i class="ph ph-robot"></i></div>
        <h3>نظام المزايدة التلقائية</h3>
        <p>حدد سقف ميزانيتك وسيقوم النظام الذكي بالمزايدة بالنيابة عنك بأقل زيادة ممكنة حتى حدك الأقصى.</p>
        <span class="fx-why-card__pill fx-why-card__pill--ai"><i class="ph-fill ph-lightning"></i> مزايدة ذكية</span>
      </article>

      <div class="fx-why-journey reveal">
        <div class="fx-why-card__bg-overlay fx-why-journey__bg-overlay" aria-hidden="true" style="--fx-why-overlay:url('https://images.unsplash.com/photo-1601584115197-04ecc0da31d7?w=1200&q=80')"></div>
        <div class="fx-why-journey__head">
          <h3>رحلة الصفقة من البداية للتسليم</h3>
          <p>أربع مراحل مترابطة داخل منصة واحدة — بدون تعقيد أو أطراف خارجية.</p>
        </div>
        <ol class="fx-why-journey__steps">
          <li>
            <span class="fx-why-journey__icon"><i class="ph ph-magnifying-glass"></i></span>
            <strong>فحص شامل</strong>
            <small>تقييم فني دقيق</small>
          </li>
          <li>
            <span class="fx-why-journey__icon"><i class="ph ph-gavel"></i></span>
            <strong>مزاد حي</strong>
            <small>مزايدة شفافة فورية</small>
          </li>
          <li>
            <span class="fx-why-journey__icon"><i class="ph ph-hand-coins"></i></span>
            <strong>تسوية آمنة</strong>
            <small>دفع ومحفظة موثقة</small>
          </li>
          <li>
            <span class="fx-why-journey__icon"><i class="ph ph-truck"></i></span>
            <strong>تسليم موثق</strong>
            <small>إغلاق صفقة منظم</small>
          </li>
        </ol>
      </div>
    </div>
  </div>
</section>

<!-- ── Section 5: Stats ── -->
<section class="stats-section stats-section--parallax fx-home-stats-section">
  <?php $fx_stats_video = fleetx_stats_bg_video_url(); if ($fx_stats_video !== ''): ?>
  <div class="fx-stats-video-bg" aria-hidden="true">
    <video class="fx-stats-video-bg__media" autoplay muted loop playsinline preload="metadata">
      <source src="<?= htmlspecialchars($fx_stats_video) ?>" type="video/mp4">
    </video>
  </div>
  <?php endif; ?>
  <div class="stats-section__overlay"></div>
  
  <div class="container reveal stats-section__inner">
    <div class="stats-hero-wrap">
      <div class="stats-hero-inner">
        <h2 class="stats-hero-title">كل ماتحتاجه<br><span class="fx-text-primary">لاتمام صفقات ناجحة</span></h2>
        <p class="stats-hero-desc">الأرقام تؤكد تفوقنا كأول وأكبر منصة مزادات أساطيل رقمية.</p>
      </div>
      <div class="main-stats-container">
        <div class="ac-stat-mobile fx-stat-motion">
          <i class="ph ph-seal-check ac-stat-mobile__icon ac-stat-mobile__icon--white"></i>
          <div class="stats-number stats-number--white"><span class="count-up font-en" data-val="<?= $stats_success_pct ?>">0</span>%</div>
          <div class="stats-desc stats-desc--spaced">نسبة نجاح المزادات</div>
        </div>
        <div class="ac-stat-mobile fx-stat-motion">
          <i class="ph ph-trend-up ac-stat-mobile__icon ac-stat-mobile__icon--primary"></i>
          <div class="stats-number text-gradient"><span class="font-en count-up" data-val="<?= $stats_sales_val ?>">0</span> <span class="stats-unit-ar stats-unit-ar--million"><?= $stats_sales_unit_ar ?></span></div>
          <div class="stats-desc stats-desc--spaced">إجمالي المبيعات عبر المنصة</div>
        </div>
      </div>
    </div>

    <div class="stats-features-grid">
      <div class="stat-feature-card">
        <i class="ph ph-lightning"></i>
        <h4>تنفيذ فوري وسريع</h4>
        <p>احصل على أرباحك وعوائد بيع الأسطول فور الانتهاء بدون رسوم خفية أو تأخير.</p>
      </div>
      <div class="stat-feature-card">
        <i class="ph ph-clock"></i>
        <h4>مزادات بدون انتظار</h4>
        <p>استمتع بتنفيذ وتحديد جداول زمنية عادلة ومضمونة بدون إعادة تسعير أو رفض أوامر.</p>
      </div>
      <div class="stat-feature-card">
        <i class="ph ph-shield-check"></i>
        <h4>لا رسوم خفية</h4>
        <p>لا تدفع أي رسوم أو عمولات مبيت أو تكاليف غير معلنة في كراسات الشروط.</p>
      </div>
      <div class="stat-feature-card">
        <i class="ph ph-arrows-in-line-horizontal"></i>
        <h4>عمولة منخفضة</h4>
        <p>رسوم وعمولات المنصة هي الأقل في المملكة وتصل إلى نسبة ضئيلة جداً على كبار الموردين.</p>
      </div>
    </div>
  </div>
</section>
<!-- ── Section 6: Contact ── -->
<section class="fx-contact-section fx-home-contact" id="contact-us">
  <div class="container">
    <div class="contact-grid">
      
      <!-- Contact Info -->
      <div class="contact-info-col reveal">
        <h2 class="fx-contact-title">دعنا نتحدث عن <span class="fx-text-primary">نجاحك القادم</span></h2>
        <p class="fx-contact-desc">فريق الخبراء لدينا جاهز للإجابة على جميع استفساراتك وتقديم استشارات مخصصة لإدارة أسطولك بأعلى كفاءة.</p>
        
        <div class="fx-contact-items">
          <div class="fx-contact-item">
            <div class="fx-contact-icon fx-contact-icon--primary">
              <i class="ph-fill ph-phone-call"></i>
            </div>
            <div>
              <div class="fx-contact-label">اتصل بنا</div>
              <div class="fx-contact-value fx-contact-value--en" dir="ltr">+20 10 6644 2622</div>
            </div>
          </div>
          
          <div class="fx-contact-item">
            <div class="fx-contact-icon fx-contact-icon--blue">
              <i class="ph-fill ph-envelope-simple"></i>
            </div>
            <div>
              <div class="fx-contact-label">البريد الإلكتروني</div>
              <div class="fx-contact-value fx-contact-value--en">info@bearand.com</div>
            </div>
          </div>
          
          <div class="fx-contact-item">
            <div class="fx-contact-icon fx-contact-icon--purple">
              <i class="ph-fill ph-map-pin"></i>
            </div>
            <div>
              <div class="fx-contact-label">المركز الرئيسي</div>
              <div class="fx-contact-value fx-contact-value--addr">برج المملكة، طريق الملك فهد، الرياض، المملكة العربية السعودية</div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Contact Form -->
      <div class="contact-form-col reveal reveal--delay-2">
        <div class="fx-contact-form-card">
          <h3 class="fx-contact-form-title">أرسل رسالة</h3>
          <form onsubmit="event.preventDefault(); if(typeof showToast==='function'){showToast('تم إرسال رسالتك بنجاح! سنتواصل معك قريباً.','success');} this.reset();">
            <div class="form-grid fx-form-grid">
              <div>
                <label class="fx-form-label">الاسم الكامل</label>
                <input type="text" required class="fx-form-input">
              </div>
              <div>
                <label class="fx-form-label">رقم الجوال</label>
                <input type="tel" required class="fx-form-input fx-form-input--en" placeholder="05X XXX XXXX" dir="ltr">
              </div>
            </div>
            <div class="fx-form-group">
              <label class="fx-form-label">موضوع الرسالة</label>
              <select class="fx-form-select">
                <option>استفسار عام</option>
                <option>الانضمام كبائع معتمد</option>
                <option>مشكلة فنية</option>
                <option>اقتراح</option>
              </select>
            </div>
            <div class="fx-form-group fx-form-group--lg">
              <label class="fx-form-label">تفاصيل الرسالة</label>
              <textarea rows="4" required class="fx-form-textarea"></textarea>
            </div>
            <button type="submit" class="btn btn-primary fx-form-submit">
              إرسال الرسالة <i class="ph ph-paper-plane-right"></i>
            </button>
          </form>
        </div>
      </div>
      
    </div>
  </div>
</section>

<!-- Advanced Search Modal -->
<div id="advancedSearchModal" class="modal-overlay">
  <div class="modal-container modal-container--wide">
    <div class="modal-header">
      <h3 class="modal-title"><i class="ph ph-faders"></i> بحث متقدم</h3>
      <button type="button" class="btn-close" onclick="document.getElementById('advancedSearchModal').classList.remove('active')"><i class="ph ph-x"></i></button>
    </div>
    <div class="modal-body modal-body--pad">
      <form action="/auctions.php" method="GET" class="modal-form">
        
        <!-- Search Keyword -->
        <div class="modal-field">
          <label class="modal-label"><i class="ph ph-text-aa"></i> ما الذي تبحث عنه؟</label>
          <input type="text" name="search" placeholder="أدخل اسم المزاد، نوع المركبة، أو رقم اللوحة..." class="form-control">
        </div>

        <!-- Type & City -->
        <div class="modal-grid-2">
          <div class="modal-field">
            <label class="modal-label"><i class="ph ph-list-dashes"></i> القسم</label>
            <select name="type" class="form-control">
              <option value="">جميع الأقسام</option>
              <option value="live">مزادات حية</option>
              <option value="instant">شراء فوري</option>
            </select>
          </div>
          <div class="modal-field">
            <label class="modal-label"><i class="ph ph-map-pin"></i> المدينة</label>
            <select name="city" class="form-control">
              <option value="">كل المدن</option>
              <option value="الرياض">الرياض</option>
              <option value="جدة">جدة</option>
              <option value="الدمام">الدمام</option>
              <option value="مكة المكرمة">مكة المكرمة</option>
              <option value="المدينة المنورة">المدينة المنورة</option>
              <option value="الخبر">الخبر</option>
              <option value="أبها">أبها</option>
              <option value="تبوك">تبوك</option>
              <option value="الطائف">الطائف</option>
              <option value="بريدة">بريدة</option>
              <option value="جازان">جازان</option>
              <option value="نجران">نجران</option>
              <option value="حائل">حائل</option>
              <option value="الجبيل">الجبيل</option>
              <option value="الأحساء">الأحساء</option>
            </select>
          </div>
        </div>

        <!-- Make & Model -->
        <div class="modal-grid-2">
          <div class="modal-field">
            <label class="modal-label"><i class="ph ph-car"></i> الماركة</label>
            <select name="make" id="modalMakeSelect" class="form-control" onchange="updateModels()">
              <option value="">كل الماركات</option>
              <option value="تويوتا">تويوتا</option>
              <option value="هيونداي">هيونداي</option>
              <option value="نيسان">نيسان</option>
              <option value="فورد">فورد</option>
              <option value="شيفروليه">شيفروليه</option>
              <option value="كيا">كيا</option>
              <option value="جمس">جمس</option>
              <option value="مازدا">مازدا</option>
              <option value="هوندا">هوندا</option>
              <option value="لكزس">لكزس</option>
              <option value="مرسيدس">مرسيدس</option>
              <option value="بي ام دبليو">بي ام دبليو</option>
              <option value="أودي">أودي</option>
              <option value="بورش">بورش</option>
              <option value="جيلي">جيلي</option>
              <option value="شانجان">شانجان</option>
              <option value="إم جي">إم جي</option>
            </select>
          </div>
          <div class="modal-field">
            <label class="modal-label"><i class="ph ph-car-profile"></i> الموديل</label>
            <select name="model" id="modalModelSelect" class="form-control">
              <option value="">كل الموديلات</option>
            </select>
          </div>
        </div>

        <!-- Year & Price -->
        <div class="modal-grid-2">
          <div class="modal-field">
            <label class="modal-label"><i class="ph ph-calendar"></i> سنة الصنع</label>
            <div class="modal-range-row">
              <select name="year_min" class="form-control form-control--compact" dir="ltr">
                <option value="">من</option>
                <?php for($y=date('Y'); $y>=2000; $y--): ?><option value="<?=$y?>"><?=$y?></option><?php endfor; ?>
              </select>
              <span class="modal-range-sep">-</span>
              <select name="year_max" class="form-control form-control--compact" dir="ltr">
                <option value="">إلى</option>
                <?php for($y=date('Y'); $y>=2000; $y--): ?><option value="<?=$y?>"><?=$y?></option><?php endfor; ?>
              </select>
            </div>
          </div>
          <div class="modal-field">
            <label class="modal-label"><i class="ph ph-money"></i> نطاق السعر (ر.س)</label>
            <div class="modal-range-row">
              <input type="number" name="price_min" placeholder="الأدنى" class="form-control form-control--compact" dir="ltr">
              <span class="modal-range-sep">-</span>
              <input type="number" name="price_max" placeholder="الأعلى" class="form-control form-control--compact" dir="ltr">
            </div>
          </div>
        </div>

        <button type="submit" class="btn btn-primary modal-submit-btn">عرض النتائج <i class="ph ph-magnifying-glass ph-space-right"></i></button>
      </form>
      
      <script>
      const carModels = {
        'تويوتا': ['كامري', 'كورولا', 'لاند كروزر', 'هايلوكس', 'يارس', 'راف فور', 'فورتشنر', 'أفالون'],
        'هيونداي': ['إلنترا', 'سوناتا', 'أكسنت', 'توسان', 'سنتافي', 'أزيرا', 'كريتا'],
        'نيسان': ['ألتيما', 'صني', 'باترول', 'مكسيما', 'باثفايندر', 'إكستريل', 'كيكس'],
        'فورد': ['تورس', 'إكسبلورر', 'إكسبدشن', 'F-150', 'موستانج', 'تيريتوري'],
        'شيفروليه': ['تاهو', 'سوبربان', 'إمبالا', 'سيلفرادو', 'كابتيفا', 'ماليبو'],
        'كيا': ['أوبتيما / K5', 'سبورتاج', 'سورينتو', 'ريو', 'سيراتو', 'تيلورايد'],
        'جمس': ['يوكون', 'سييرا', 'تيرين', 'أكاديا'],
        'مازدا': ['مازدا 6', 'مازدا 3', 'CX-5', 'CX-9', 'CX-30'],
        'هوندا': ['أكورد', 'سيفيك', 'CR-V', 'بايلوت', 'HR-V'],
        'لكزس': ['ES', 'LX', 'RX', 'IS', 'NX', 'LS'],
        'مرسيدس': ['S-Class', 'E-Class', 'C-Class', 'G-Class', 'GLE', 'GLC'],
        'بي ام دبليو': ['الفئة السابعة', 'الفئة الخامسة', 'الفئة الثالثة', 'X5', 'X6', 'X7'],
        'أودي': ['A8', 'A6', 'A4', 'Q7', 'Q8', 'Q5'],
        'بورش': ['كايين', 'باناميرا', 'ماكان', '911'],
        'جيلي': ['كولراي', 'توجيلا', 'إمجراند', 'مونجارو', 'أوكافانجو'],
        'شانجان': ['CS95', 'CS85', 'CS75', 'UNI-T', 'UNI-K', 'ألسڤن'],
        'إم جي': ['MG RX5', 'MG HS', 'MG 6', 'MG ZS', 'MG GT']
      };

      function updateModels() {
        const makeSelect = document.getElementById('modalMakeSelect');
        const modelSelect = document.getElementById('modalModelSelect');
        const selectedMake = makeSelect.value;
        
        // Clear current options
        modelSelect.innerHTML = '<option value="">كل الموديلات</option>';
        
        if (selectedMake && carModels[selectedMake]) {
          carModels[selectedMake].forEach(function(model) {
            const option = document.createElement('option');
            option.value = model;
            option.textContent = model;
            modelSelect.appendChild(option);
          });
        }
      }
      </script>
</div>
  </div>
</div>

<!-- Footer template -->
<?php include 'includes/footer.php'; ?>
</body>
</html>
