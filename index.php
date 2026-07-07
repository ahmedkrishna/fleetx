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

$approval_rate = ($total_auctions > 0) ? round(($successful_auctions / $total_auctions) * 100, 1) : 0;
// If no real data yet, we can show actual 0s or base minimums to look alive, but user wants real data.
// We'll show exactly what the DB has.
if ($approval_rate == 0) $approval_rate = "0.0";

$sales_display = number_format($total_sales_value / 1000000, 2); // Show in Millions
$sales_unit = "مليون";
if ($total_sales_value >= 1000000000) {
    $sales_display = number_format($total_sales_value / 1000000000, 2);
    $sales_unit = "مليار";
} elseif ($total_sales_value < 1000000) {
    $sales_display = number_format($total_sales_value);
    $sales_unit = "ريال";
}

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>منصة FleetX | مزادات السيارات الاحترافية للأساطيل</title>
  <link rel="stylesheet" href="/assets/css/fleetx.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>
  </head>
<body class="fx-home">

<!-- Navbar template -->
<?php include 'includes/navbar.php'; ?>

<!-- ── Hero Section (clean light theme) ── -->
<div class="fx-hero-wrap fx-hero-wrap--light">
<div class="hero-wrapper fx-hero-wrapper--light">
  <section class="hero fx-hero-section fx-hero-section--clean">
    <div class="fx-hero-bg fx-hero-layer--bg-img--fleet1" aria-hidden="true"></div>

    <div class="hero-content fx-hero-content fx-hero-content--light">
      <div class="hero-tagline fx-hero-tagline--light">
        <span id="heroTaglineTypewriter">أول منصة مزادات أساطيل ذكية وموثوقة بالمملكة</span>
      </div>

      <h1 class="hero-title fx-hero-title--light">
        <span id="heroMainTitle">أضخم مزادات أساطيل السيارات</span>
      </h1>

      <p class="hero-subtitle fx-hero-subtitle--light">تجربة مزايدة حية وسلسة لسيارات الشركات والجهات الحكومية — مع تقارير فحص فنية موثقة وشفافية كاملة.</p>

      <div class="fx-cta-row fx-cta-row--light">
        <a href="/map.php" class="btn btn-primary fx-cta-btn">
          خريطة المزادات <i class="ph ph-map-pin ph-space-left"></i>
        </a>
        <a href="/auctions.php" class="btn btn-outline fx-cta-btn fx-cta-btn--outline-dark">
          تصفح المزادات الحية
        </a>
      </div>
    </div>

    <script>
      (function() {
        const titles = [
          'أضخم مزادات أساطيل السيارات',
          'تنفيذ فوري وشفافية تامة',
          'مزايدة ذكية ومضمونة'
        ];
        const subtitles = [
          'أول منصة مزادات أساطيل ذكية وموثوقة بالمملكة',
          'تقنية متطورة لضمان أفضل العوائد لمركباتك',
          'تكامل مباشر مع النفاذ الوطني وأعلى معايير الأمان'
        ];
        let currentIndex = 0;
        let typeWriterTimeout;

        function typeWriter(text, elementId, speed) {
          const el = document.getElementById(elementId);
          if (!el) return;
          el.innerHTML = '';
          let i = 0;
          function type() {
            if (i < text.length) {
              el.innerHTML += text.charAt(i);
              i++;
              typeWriterTimeout = setTimeout(type, speed || 45);
            }
          }
          type();
        }

        function updateHeroText() {
          const titleEl = document.querySelector('.hero-title');
          if (!titleEl) return;
          titleEl.style.opacity = '0';
          setTimeout(function() {
            currentIndex = (currentIndex + 1) % titles.length;
            document.getElementById('heroMainTitle').innerText = titles[currentIndex];
            titleEl.style.opacity = '1';
            clearTimeout(typeWriterTimeout);
            typeWriter(subtitles[currentIndex], 'heroTaglineTypewriter', 45);
          }, 400);
        }

        window.addEventListener('load', function() {
          typeWriter(subtitles[0], 'heroTaglineTypewriter', 45);
          setInterval(updateHeroText, 7000);
        });
      })();
    </script>
  </section>

  <!-- ── Search Panel ── -->
  <div class="stats-container reveal fx-search-overlap fx-search-overlap--light">
    <div class="container">
        <div class="hero-search-panel hero-search-panel--light">
          <form action="/auctions.php" method="GET" class="hero-search-form hero-search-form--light collapsed-mobile">
            <!-- Search input -->
            <div class="search-field search-field--wide">
              <label><i class="ph ph-magnifying-glass"></i> كلمة البحث</label>
              <input type="text" name="search" placeholder="ابحث عن سيارات، مزادات..." id="quickSearch">
            </div>
            
            <!-- Make filter -->
            <div class="search-field">
              <label><i class="ph ph-car"></i> الماركة</label>
              <select name="make">
                
                
                
                
                
                
                
                
                
                
                
                
                
                
                
                
                
                
                
                
                          <option value="">كل الماركات</option>
            <option value="Toyota">تويوتا</option>
            <option value="Hyundai">هيونداي</option>
            <option value="Nissan">نيسان</option>
            <option value="Ford">فورد</option>
            <option value="Chevrolet">شيفروليه</option>
            <option value="Kia">كيا</option>
            <option value="GMC">جمس</option>
            <option value="Mazda">مازدا</option>
            <option value="Honda">هوندا</option>
            <option value="Lexus">لكزس</option>
            <option value="Mercedes">مرسيدس</option>
            <option value="BMW">بي ام دبليو</option>
            <option value="Audi">أودي</option>
            <option value="Porsche">بورش</option>
            <option value="Geely">جيلي</option>
            <option value="Changan">شانجان</option>
            <option value="MG">إم جي</option>
          </select>
            </div>
            
            <!-- City filter -->
            <div class="search-field">
              <label><i class="ph ph-map-pin"></i> المدينة</label>
              <select name="city">
                
                
                
                
                
                
                
                
                
                
                
                
                
                
                
                
                
                          <option value="">كل المدن</option>
            <option value="Riyadh">الرياض</option>
            <option value="Jeddah">جدة</option>
            <option value="Dammam">الدمام</option>
            <option value="Mecca">مكة المكرمة</option>
            <option value="Medina">المدينة المنورة</option>
            <option value="Khobar">الخبر</option>
            <option value="Abha">أبها</option>
            <option value="Tabuk">تبوك</option>
            <option value="Taif">الطائف</option>
            <option value="Buraidah">بريدة</option>
            <option value="Jizan">جازان</option>
            <option value="Najran">نجران</option>
            <option value="Hail">حائل</option>
            <option value="Jubail">الجبيل</option>
            <option value="Al-Ahsa">الأحساء</option>
          </select>
            </div>
            
            <!-- Transaction Type filter -->
            <div class="search-field">
              <label><i class="ph ph-list-dashes"></i> نوع البيع</label>
              <select name="type">
                <option value="">الكل</option>
                <option value="live">مزايدة</option>
                <option value="instant">شراء فوري</option>
              </select>
            </div>
            
            <!-- Action buttons -->
            <div class="search-actions">
              <button type="submit" class="btn btn-primary btn-search-submit--light" title="بحث">
                <i class="ph ph-magnifying-glass"></i> بحث
              </button>
            </div>
          </form>
          <div class="mobile-search-expand-btn" onclick="document.querySelector('.hero-search-form').classList.toggle('collapsed-mobile'); this.querySelector('.toggle-icon').classList.toggle('is-rotated', !document.querySelector('.hero-search-form').classList.contains('collapsed-mobile'));">
            <i class="ph-bold ph-caret-down toggle-icon"></i>
          </div>
        </div>
      </div>
    </div>
</div>
</div>

<?php 
// Fetch Live Auctions
$live_auctions = [];
if ($db_connected) {
    $res = $conn->query("SELECT a.*, v.make, v.model, v.year, v.image_url, v.city, v.mileage 
                         FROM auctions a 
                         JOIN vehicles v ON a.vehicle_id = v.id 
                         WHERE a.type='live' AND a.status='active' LIMIT 6");
    if ($res) while ($row = $res->fetch_assoc()) $live_auctions[] = $row;
}


// Fetch Instant Buy
$instant_cars = [];
if ($db_connected) {
    $res = $conn->query("SELECT a.*, v.make, v.model, v.year, v.image_url, v.city, v.mileage 
                         FROM auctions a 
                         JOIN vehicles v ON a.vehicle_id = v.id 
                         WHERE a.type='instant' AND a.status='active' LIMIT 6");
    if ($res) while ($row = $res->fetch_assoc()) $instant_cars[] = $row;
}

?>

<!-- ── Unified Auctions Section (White Background) ── -->
<section class="reveal fx-section--white">
  <div class="container">
    <div class="fx-section-head">
      <h2 class="section-title">استكشف المركبات المتاحة</h2>
      <p class="section-subtitle">تصفح أحدث مزادات السيارات والمبيعات الفورية المدرجة في المنصة</p>
    </div>

    <!-- Tabs Container -->
    <div class="auctions-tabs-wrapper">
      <div class="auctions-tabs">
        <button class="auctions-tab-btn active" onclick="switchAuctionTab('live')">المزادات الحية والمباشرة</button>
        <button class="auctions-tab-btn" onclick="switchAuctionTab('instant')">الشراء الفوري</button>
      </div>
    </div>

    <!-- ── Live Auctions Tab Content ── -->
    <div id="tab-content-live" class="auctions-tab-content active">
      <div class="swiper auctions-swiper auctions-swiper--padded">
        <div class="swiper-wrapper">
        <?php
          $car_images = [
              'https://images.unsplash.com/photo-1494976388531-d1058494cdd8?w=800&q=80',
              'https://images.unsplash.com/photo-1550355291-bbee04a92027?w=800&q=80',
              'https://images.unsplash.com/photo-1503376712341-ea1925b4be40?w=800&q=80',
              'https://images.unsplash.com/photo-1583121274602-3e2820c69888?w=800&q=80',
              'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?w=800&q=80',
              'https://images.unsplash.com/photo-1617531653332-bd46c24f2068?w=800&q=80'
          ];
          $status_cycle = ['active', 'upcoming', 'ended'];
          $auction_names = ['مزاد الرياض الكبرى', 'مزاد أسطول جدة', 'مزاد سيارات الدفع الرباعي', 'مزاد السيارات الفاخرة', 'مزاد الوفاق', 'مزاد تصفية الشركات'];

          foreach ($live_auctions as $ev_index => $a):
            $title_car = $auction_names[$ev_index % count($auction_names)];
            $img = (!empty($a['image_url']) && strlen($a['image_url']) > 4) ? $a['image_url'] : $car_images[$ev_index % count($car_images)];
            $is_featured = !empty($a['is_featured']);
            $is_vip = ($is_featured && ($a['id'] % 2 !== 0));
            $card_status = $status_cycle[$ev_index % 3];
            $event_href = '/event.php?id=' . ($a['event_id'] ?? ($a['id'] % 3 + 1));
            $fx_card = [
              'id' => $a['id'],
              'href' => $event_href,
              'title' => $title_car,
              'image' => $img,
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
              'extra_class' => 'animate-card',
            ];
        ?>
        <div class="swiper-slide">
          <?php include 'includes/fx-auction-card.inc.php'; ?>
        </div>
        <?php endforeach; ?>


        </div>
        <div class="swiper-pagination"></div>
      </div>
      <div class="fx-tab-footer">
        <a href="/auctions.php?type=live" class="btn btn-outline-dark">عرض جميع المزادات الحية <i class="ph ph-arrow-left"></i></a>
      </div>
    </div>

    <!-- ── Instant Buy Tab Content ── -->
    <div id="tab-content-instant" class="auctions-tab-content">
      <div class="swiper auctions-swiper auctions-swiper--padded">
        <div class="swiper-wrapper">
        <?php
          $random_images = [
              'https://images.unsplash.com/photo-1580273916550-e323be2ae537?w=800&q=80',
              'https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?w=800&q=80',
              'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?w=800&q=80',
              'https://images.unsplash.com/photo-1555215695-3004980ad54e?w=800&q=80',
              'https://images.unsplash.com/photo-1606664515524-ed2f786a0bd6?w=800&q=80',
              'https://images.unsplash.com/photo-1568844293986-ca9c5c6f8b8a?w=800&q=80'
          ];
          foreach ($instant_cars as $inst_index => $a):
            $title_car = $a['title'] ?? ($a['make'] . ' ' . $a['model'] . ' ' . $a['year']);
            $img = (!empty($a['image_url']) && strlen($a['image_url']) > 4) ? $a['image_url'] : $random_images[$inst_index % count($random_images)];
            $is_featured = ($inst_index % 3 === 0);
            $is_vip = ($is_featured && ($a['id'] % 2 !== 0));
            $fx_card = [
              'id' => $a['id'],
              'href' => '/vehicle-details.php?id=' . $a['id'],
              'title' => $title_car,
              'image' => $img,
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
              'extra_class' => 'animate-card',
            ];
        ?>
        <div class="swiper-slide">
          <?php include 'includes/fx-auction-card.inc.php'; ?>
        </div>
        <?php endforeach; ?>
        </div>
        <div class="swiper-pagination"></div>
      </div>
      <div class="fx-tab-footer">
        <a href="/auctions.php?type=instant" class="btn btn-outline-dark">عرض جميع المركبات <i class="ph ph-arrow-left"></i></a>
      </div>
    </div>
  </div>
</section>

<script>
  function switchAuctionTab(tabId) {
    document.querySelectorAll('.auctions-tab-btn').forEach(btn => btn.classList.remove('active'));
    event.currentTarget.classList.add('active');
    
    document.querySelectorAll('.auctions-tab-content').forEach(content => content.classList.remove('active'));
    document.getElementById('tab-content-' + tabId).classList.add('active');
    
    // Update swiper
    if(window.auctionsSwipers) {
      window.auctionsSwipers.forEach(s => s.update());
    }
  }

  function switchHiwTab(e, type, step) {
    const tabsContainer = document.getElementById(type + '-tabs');
    const tabs = tabsContainer.querySelectorAll('.b-tab');
    tabs.forEach(t => t.classList.remove('active'));
    e.currentTarget.classList.add('active');

    const contents = document.querySelectorAll(`[id^="${type}-step-"]`);
    contents.forEach(c => c.classList.remove('active', 'visible'));

    const tabs2 = e.currentTarget.parentElement.querySelectorAll('.b-tab');
    tabs2.forEach(t => t.classList.remove('active'));
    e.currentTarget.classList.add('active');

    const activeContent = document.getElementById(`${type}-step-${step}`);
    if(activeContent) {
      activeContent.classList.add('active', 'reveal', 'visible');
    }
  }
</script>

<!-- ── HOW IT WORKS: Sticky Sections with Tabs ── -->
<section class="fx-hiw-section">
  
  <!-- ============ SECTION: BUYERS ============ -->
  <div id="buyers-section" class="hiw-wrapper hiw-wrapper--sticky hiw-wrapper--buyers">
    <div class="container container--full">
      <div class="hiw-dark-head">
        <h2 class="hiw-dark-title">كيف تبدأ كـ <span class="fx-text-primary">مشتري؟</span></h2>
        <p class="hiw-dark-sub">للمشترين الأفراد والشركات المعتمدة</p>
      </div>

      <div class="browser-tabs-container">
        <div class="browser-tabs" id="buyer-tabs">
          <button class="b-tab active" onclick="switchHiwTab(event, 'buyer', 1)">التسجيل</button>
          <button class="b-tab" onclick="switchHiwTab(event, 'buyer', 2)">المحفظة</button>
          <button class="b-tab" onclick="switchHiwTab(event, 'buyer', 3)">المزايدة</button>
        </div>
        <div class="browser-content hiw-panel hiw-panel--browser">
          
          <div id="buyer-step-1" class="hiw-tab-content active">
            <div class="hiw-tab-col">
              <i class="ph ph-identification-card hiw-tab-icon"></i>
              <h3 class="hiw-tab-title">التسجيل وتوثيق الهوية <i class="ph-fill ph-warning-circle hiw-info-icon" onclick="openHiwModal('buyer-reg')" title="عرض التفاصيل والآلية"></i></h3>
              <p class="hiw-tab-desc">سجّل حسابك في 3 خطوات سريعة عبر منصة نفاذ الوطني الموحّد لضمان أعلى معايير الأمان والثقة في التعاملات.</p>
            </div>
            <div class="hiw-tab-col">
              <img src="https://images.unsplash.com/photo-1556740758-90de374c12ad?w=600&q=80" class="hiw-tab-img" alt="التسجيل" loading="lazy">
            </div>
          </div>

          <div id="buyer-step-2" class="hiw-tab-content">
            <div class="hiw-tab-col">
              <i class="ph ph-wallet hiw-tab-icon"></i>
              <h3 class="hiw-tab-title">المحفظة والتأمين <i class="ph-fill ph-warning-circle hiw-info-icon" onclick="openHiwModal('buyer-wallet')" title="عرض التفاصيل والآلية"></i></h3>
              <p class="hiw-tab-desc">أودع مبلغ التأمين المطلوب في محفظتك الرقمية عبر قنوات الدفع المتعددة للبدء في المزايدة المباشرة.</p>
            </div>
            <div class="hiw-tab-col">
              <img src="https://images.unsplash.com/photo-1559526324-4b87b5e36e44?w=600&q=80" class="hiw-tab-img" alt="المحفظة" loading="lazy">
            </div>
          </div>

          <div id="buyer-step-3" class="hiw-tab-content">
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

  <!-- ============ SECTION: SELLERS ============ -->
  <div id="sellers-section" class="hiw-wrapper hiw-wrapper--sticky hiw-wrapper--sellers">
    <div class="container container--full">
      <div class="hiw-dark-head">
        <h2 class="hiw-dark-title">كيف تبدأ كـ <span class="fx-text-primary">بائع معتمد؟</span></h2>
        <p class="hiw-dark-sub">لشركات التأجير والأساطيل</p>
      </div>

      <div class="browser-tabs-container">
        <div class="browser-tabs" id="seller-tabs">
          <button class="b-tab active" onclick="switchHiwTab(event, 'seller', 1)">الاعتماد</button>
          <button class="b-tab" onclick="switchHiwTab(event, 'seller', 2)">الاشتراك</button>
          <button class="b-tab" onclick="switchHiwTab(event, 'seller', 3)">الإدراج</button>
        </div>
        <div class="browser-content hiw-panel hiw-panel--browser">
          
          <div id="seller-step-1" class="hiw-tab-content active">
            <div class="hiw-tab-col">
              <i class="ph ph-buildings hiw-tab-icon"></i>
              <h3 class="hiw-tab-title">توثيق الشركة والاعتماد <i class="ph-fill ph-warning-circle hiw-info-icon" onclick="openHiwModal('seller-reg')" title="عرض التفاصيل والآلية"></i></h3>
              <p class="hiw-tab-desc">سجّل شركتك وأرفق السجل التجاري ليتم تدقيقها واعتماد حسابك كبائع موثوق خلال 48 ساعة عمل.</p>
            </div>
            <div class="hiw-tab-col">
              <img src="https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=600&q=80" class="hiw-tab-img" alt="توثيق الشركة" loading="lazy">
            </div>
          </div>

          <div id="seller-step-2" class="hiw-tab-content">
            <div class="hiw-tab-col">
              <i class="ph ph-package hiw-tab-icon"></i>
              <h3 class="hiw-tab-title">اختيار الباقة وشحن الحساب <i class="ph-fill ph-warning-circle hiw-info-icon" onclick="openHiwModal('seller-wallet')" title="عرض التفاصيل والآلية"></i></h3>
              <p class="hiw-tab-desc">اختر باقة الاشتراك المناسبة لحجم أسطولك، وأودع رسوم التفعيل للبدء في الاستفادة من أدوات البيع.</p>
            </div>
            <div class="hiw-tab-col">
              <img src="https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=600&q=80" class="hiw-tab-img" alt="شحن الحساب" loading="lazy">
            </div>
          </div>

          <div id="seller-step-3" class="hiw-tab-content">
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

<!-- ── Features Section (SaaS / Premium style) ── -->
<section class="reveal fx-section--services">
  <div class="container">
    <div class="fx-section-head fx-section-head--narrow">
      <h2 class="section-title">خدمات مزادات ذكية ومتكاملة</h2>
      <p class="section-subtitle">نسهل العمليات اللوجستية والفحص والتسوية من البداية وحتى التسليم النهائي</p>
    </div>


    <div class="services-flex-container">
      <div class="service-flex-card-new service-card-1 reveal" >
        
        
        
        <div class="service-content service-content--stack">
          <i class="ph ph-clipboard-text icon-grad"></i>
          <h3>تقرير فحص 100+ نقطة</h3>
          <p>جميع السيارات المعروضة تخضع لفحص فني شامل يغطي الهيكل والميكانيكا والكهرباء معتمد من قبل خبراء المنصة.</p>
        </div>
      </div>
      
      <div class="service-flex-card-new service-card-2 service-flex-card-new--delay-1 reveal">
        <div class="service-content service-content--stack">
          <i class="ph ph-shield-check icon-grad"></i>
          <h3>بيئة موثقة بالكامل</h3>
          <p>نحن نتحقق من هوية المشترين والبائعين عبر تكامل مباشر مع بوابة النفاذ الوطني الموحد لضمان جدية المزايدات.</p>
        </div>
      </div>
      
      <div class="service-flex-card-new service-card-3 service-flex-card-new--delay-2 reveal">
        <div class="service-content service-content--stack">
          <i class="ph ph-robot icon-grad"></i>
          <h3>نظام المزايدة التلقائية</h3>
          <p>حدد سقف ميزانيتك للسيارة، وسيقوم النظام الذكي بالمزايدة بالنيابة عنك بأقل زيادة ممكنة لحين الوصول لحدك الأقصى.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ── Stats Section (Parallax Dark Theme) ── -->
<section class="stats-section stats-section--parallax">
  <div class="stats-section__overlay"></div>
  
  <div class="container reveal stats-section__inner">
    <div class="stats-hero-wrap">
      <div class="stats-hero-inner">
        <h2 class="stats-hero-title">كل ماتحتاجه<br><span class="fx-text-primary">لاتمام صفقات ناجحة</span></h2>
        <p class="stats-hero-desc">الأرقام تؤكد تفوقنا كأول وأكبر منصة مزادات أساطيل رقمية.</p>
      </div>
      <div class="main-stats-container">
        <div class="ac-stat-mobile">
          <i class="ph ph-seal-check ac-stat-mobile__icon ac-stat-mobile__icon--white"></i>
          <div class="stats-number stats-number--white"><span class="count-up" data-val="<?= $approval_rate ?>">0</span>%</div>
          <div class="stats-desc stats-desc--spaced">نسبة نجاح المزادات</div>
        </div>
        <div class="ac-stat-mobile">
          <i class="ph ph-trend-up ac-stat-mobile__icon ac-stat-mobile__icon--primary"></i>
          <div class="stats-number text-gradient"><span class="font-en count-up" data-val="<?= $sales_display ?>">0</span> <span class="stats-unit-ar"><?= $sales_unit ?></span></div>
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
<!-- ── Contact Us Section ── -->
<section class="fx-contact-section" id="contact-us">
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
          <form onsubmit="event.preventDefault(); alert('تم إرسال رسالتك بنجاح! سنتواصل معك قريباً.');">
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

<!-- Initialise Swipers on Home page -->
<script>
  document.addEventListener('DOMContentLoaded', function() {
    window.auctionsSwipers = [];
    document.querySelectorAll('.auctions-swiper').forEach(function(el) {
      const swiper = new Swiper(el, {
        slidesPerView: 3,
        centeredSlides: true,
        spaceBetween: 24,
        loop: true,
        observer: true,
        observeParents: true,
        autoplay: {
          delay: 5000,
          disableOnInteraction: false,
        },
        navigation: {
          nextEl: '.swiper-button-next',
          prevEl: '.swiper-button-prev',
        },
        pagination: {
          el: '.swiper-pagination',
          clickable: true,
        },
        breakpoints: {
          320: { slidesPerView: 1, spaceBetween: 16 },
          768: { slidesPerView: 2, spaceBetween: 24 },
          1024: { slidesPerView: 3, spaceBetween: 24 }
        }
      });
      window.auctionsSwipers.push(swiper);
    });

    // Favorites Logic
    document.querySelectorAll('.card-fav').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation(); // prevent navigating to auction room if click bubbles
        this.classList.toggle('active');
        let icon = this.querySelector('i');
        if(this.classList.contains('active')) {
          icon.classList.remove('ph');
          icon.classList.add('ph-fill');
          icon.style.color = '#ef4444';
          if(typeof showToast === 'function') showToast('تمت الإضافة للمفضلة بنجاح!', 'success');
        } else {
          icon.classList.remove('ph-fill');
          icon.classList.add('ph');
          icon.style.color = '';
          if(typeof showToast === 'function') showToast('تم الحذف من المفضلة', 'info');
        }
      });
    });

    // Live Countdown Logic
    setInterval(function() {
      document.querySelectorAll('.digital-clock').forEach(clock => {
        let countdownDate = new Date(clock.getAttribute('data-countdown')).getTime();
        let now = new Date().getTime();
        let distance = countdownDate - now;
        
        if (distance < 0) {
          clock.innerHTML = "<div class='ac-timer-expired'>انتهى المزاد</div>";
          return;
        }

        let days = Math.floor(distance / (1000 * 60 * 60 * 24));
        let hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        let mins = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        let secs = Math.floor((distance % (1000 * 60)) / 1000);

        clock.querySelector('[data-unit="days"]').innerText = days.toString().padStart(2, '0');
        clock.querySelector('[data-unit="hours"]').innerText = hours.toString().padStart(2, '0');
        clock.querySelector('[data-unit="mins"]').innerText = mins.toString().padStart(2, '0');
        clock.querySelector('[data-unit="secs"]').innerText = secs.toString().padStart(2, '0');
      });
    }, 1000);

  });
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const counters = document.querySelectorAll('.count-up');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if(entry.isIntersecting) {
                const el = entry.target;
                if(el.classList.contains('counted')) return;
                el.classList.add('counted');
                
                let targetNum = parseFloat(el.getAttribute('data-val'));
                let hasDecimals = el.getAttribute('data-val').includes('.');
                let duration = 2000;
                let startTime = null;
                
                function animateCount(timestamp) {
                    if(!startTime) startTime = timestamp;
                    let progress = timestamp - startTime;
                    if(progress > duration) progress = duration;
                    
                    // easeOutQuart
                    let t = progress / duration;
                    t--;
                    let current = targetNum * (1 - (t * t * t * t));
                    
                    if(hasDecimals) {
                        el.innerText = current.toFixed(1);
                    } else {
                        el.innerText = Math.floor(current);
                    }
                    
                    if(progress < duration) {
                        requestAnimationFrame(animateCount);
                    } else {
                        el.innerText = el.getAttribute('data-val');
                    }
                }
                requestAnimationFrame(animateCount);
            }
        });
    }, { threshold: 0.1 });
    
    counters.forEach(c => observer.observe(c));
});
</script>
</body>
</html>
