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
if (empty($events)) $events = array_slice(getMockEvents(), 0, 3);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>منصة FleetX | مزادات السيارات الاحترافية للأساطيل</title>
  <link rel="stylesheet" href="/assets/css/fleetx.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>
  <style>
.services-flex-container {
  display: flex;
  gap: 16px;
  height: 400px;
}
.service-flex-card-new {
  flex: 1;
  position: relative;
  border-radius: var(--radius-lg);
  overflow: hidden;
  transition: flex 0.5s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  align-items: center;
  justify-content: flex-end; /* right align */
  padding: 40px;
  cursor: pointer;
}
.service-flex-card-new:hover {
  flex: 3;
}
.service-flex-card-new::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(to bottom, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.3) 100%);
  z-index: 1;
}
.service-flex-card-new .service-content {
  position: relative;
  z-index: 2;
  text-align: right;
  align-items: flex-end;
  width: 100%;
  max-width: 300px;
  transition: all 0.5s ease;
  opacity: 0.8;
}
.service-flex-card-new:hover .service-content {
  opacity: 1;
}
.service-flex-card-new .icon-grad {
  font-size: 48px;
  margin-bottom: 24px;
  color: var(--primary);
  background: none;
  -webkit-text-fill-color: initial;
  transition: all 0.4s ease;
}
.service-flex-card-new:hover .icon-grad {
  color: #fff;
  transform: scale(1.2);
}
.service-flex-card-new h3 {
  font-size: 24px;
  font-weight: 800;
  color: #fff;
  margin-bottom: 12px;
}
.service-flex-card-new p {
  color: rgba(255,255,255,0.7);
  font-size: 15px;
  line-height: 1.6;
}
/* override the previous gradient icons */
    /* Hero Tab Styling */
    .auctions-tabs-wrapper {
      display: flex;
      justify-content: center;
      margin-bottom: 40px;
    }
    .auctions-tabs {
      display: inline-flex;
      background: var(--bg-light);
      padding: 6px;
      border-radius: var(--radius-round);
      border: 1px solid var(--border-light);
    }
    .auctions-tab-btn {
      background: transparent;
      border: none;
      padding: 12px 32px;
      font-size: 16px;
      font-weight: 800;
      color: var(--text-muted);
      border-radius: var(--radius-round);
      cursor: pointer;
      transition: var(--transition);
      font-family: var(--font-ar);
    }
    .auctions-tab-btn.active {
      background: var(--bg-white);
      color: var(--primary);
      box-shadow: none;
    }
    
    .auctions-tab-content {
      display: none;
      animation: fadeInPanel 0.4s ease;
    }
    .auctions-tab-content.active {
      display: block;
    }

    /* Removed fixed slide widths to allow Swiper slidesPerView to handle widths automatically */
    .auctions-swiper .swiper-slide {
      height: auto;
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      transform: scale(0.9);
      opacity: 0.5;
    }
    .auctions-swiper .swiper-slide-active {
      transform: scale(1);
      opacity: 1;
      z-index: 2;
    }
  
      .service-card-1 { background: linear-gradient(to left, #0ea5e9, #1bc976) !important; }
      .service-card-2 { background: linear-gradient(to left, #1bc976, #0ea5e9) !important; }
      .service-card-3 { background: linear-gradient(to left, #0ea5e9, #8b5cf6) !important; }
</style>
</head>
<body>

<!-- Navbar template -->
<?php include 'includes/navbar.php'; ?>

<!-- ── Hero Section ── -->
<div style="background: #fff;">
<div class="hero-wrapper">
  <section class="hero">
    
    <!-- Dark Solid Background with Subtle Fade Image -->
    <div id="heroImageContainer" style="position: absolute; inset: 0; z-index: 0; overflow: hidden; background: var(--bg-dark);">
      <!-- Subtle faded image on top of solid dark -->
      <div style="position: absolute; inset:0; background: url('https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?w=1600&q=80') center/cover no-repeat; opacity: 0.05; mask-image: linear-gradient(to bottom, rgba(0,0,0,1) 40%, rgba(0,0,0,0) 100%); -webkit-mask-image: linear-gradient(to bottom, rgba(0,0,0,1) 40%, rgba(0,0,0,0) 100%); mix-blend-mode: screen;"></div>
    </div>
    
    <!-- Faded Overlay Bottom -->
    <div style="position:absolute; bottom:0; left:0; width:100%; height:30%; background:linear-gradient(to top, var(--bg-dark) 0%, transparent 100%); z-index:2; pointer-events:none;"></div>

    <canvas id="heroParticles" style="position:absolute; inset:0; z-index:1; pointer-events:none;"></canvas>
    <div class="hero-elegant-bg" style="opacity: 0.4; z-index: 1;">
      <div class="glow-orb orb-1"></div>
      <div class="glow-orb orb-2"></div>
    </div>

    <div class="hero-content" style="margin-top: 40px; position: relative; z-index: 2;">
      <div class="hero-tagline">
        <span id="heroTaglineTypewriter"></span>
      </div>
      
      <h1 class="hero-title">
        <span id="heroMainTitle">أضخم مزادات أساطيل السيارات</span>
      </h1>
      
      <p class="hero-subtitle">نجمع بين الحلول التكنولوجية الذكية لتوفر لك تجربة مزايدة حية وسلسة لسيارات الشركات والجهات الحكومية، مدعومة بتقارير فحص فنية موثقة.</p>
      
      <div style="display:flex; gap:20px; justify-content:center; flex-wrap:wrap">
        <a href="/map.php" class="btn btn-primary" style="font-size:16px; padding:14px 40px">
          خريطة السيارات <i class="ph ph-map-pin ph-space-left"></i>
        </a>
        <a href="/auctions.php" class="btn btn-outline" style="font-size:16px; padding:14px 40px;">
          تصفح المزادات الحية
        </a>
      </div>
    </div>

    <script>
      const titles = [
        "أضخم مزادات أساطيل السيارات",
        "تنفيذ فوري وشفافية تامة",
        "مزايدة ذكية ومضمونة"
      ];
      const subtitles = [
        "أول منصة مزادات أساطيل ذكية وموثوقة بالمملكة",
        "تقنية متطورة لضمان أفضل العوائد لمركباتك",
        "تكامل مباشر مع النفاذ الوطني وأعلى معايير الأمان"
      ];
      
      let currentIndex = 0;
      let typeWriterTimeout;
      
      function typeWriter(text, elementId, speed=50) {
        const el = document.getElementById(elementId);
        el.innerHTML = "";
        let i = 0;
        function type() {
          if (i < text.length) {
            el.innerHTML += text.charAt(i);
            i++;
            typeWriterTimeout = setTimeout(type, speed);
          }
        }
        type();
      }

      function updateHeroText() {
        const titleEl = document.querySelector('.hero-title');
        titleEl.style.opacity = 0;
        
        setTimeout(() => {
          document.getElementById('heroMainTitle').innerText = titles[currentIndex];
          titleEl.style.opacity = 1;
          
          // Reset and type subtitle at the exact same time title changes
          clearTimeout(typeWriterTimeout);
          typeWriter(subtitles[currentIndex], "heroTaglineTypewriter", 50);
          
          currentIndex = (currentIndex + 1) % titles.length;
        }, 500);
      }

      window.addEventListener('load', () => {
        // Initial setup without fade for first load
        document.getElementById('heroMainTitle').innerText = titles[0];
        typeWriter(subtitles[0], "heroTaglineTypewriter", 50);
        currentIndex = 1;
        
        setInterval(updateHeroText, 7000);
      // Particle Animation
      const canvas = document.getElementById('heroParticles');
      const ctx = canvas.getContext('2d');
      let particles = [];
      let mouse = { x: null, y: null };

      function resizeCanvas() {
        canvas.width = canvas.offsetWidth || window.innerWidth;
        canvas.height = canvas.offsetHeight || window.innerHeight;
      }
      window.addEventListener('resize', resizeCanvas);
      resizeCanvas();

      document.querySelector('.hero').addEventListener('mousemove', (e) => {
        const rect = canvas.getBoundingClientRect();
        mouse.x = e.clientX - rect.left;
        mouse.y = e.clientY - rect.top;
      });
      document.querySelector('.hero').addEventListener('mouseleave', () => {
        mouse.x = null; mouse.y = null;
      });

      class Particle {
        constructor() {
          this.x = Math.random() * canvas.width;
          this.y = Math.random() * canvas.height;
          this.size = Math.random() * 2 + 1;
          this.baseX = this.x;
          this.baseY = this.y;
          this.density = (Math.random() * 30) + 1;
          this.isNumber = Math.random() > 0.8;
          this.val = Math.floor(Math.random() * 10);
        }
        draw() {
          ctx.fillStyle = 'rgba(251, 191, 36, 0.3)';
          if (this.isNumber) {
            ctx.font = '10px Inter';
            ctx.fillText(this.val, this.x, this.y);
          } else {
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
            ctx.closePath();
            ctx.fill();
          }
        }
        update() {
          let dx = mouse.x - this.x;
          let dy = mouse.y - this.y;
          let distance = Math.sqrt(dx * dx + dy * dy);
          let forceDirX = dx / distance;
          let forceDirY = dy / distance;
          let maxDistance = 150;
          let force = (maxDistance - distance) / maxDistance;
          let dirX = forceDirX * force * this.density;
          let dirY = forceDirY * force * this.density;

          if (distance < maxDistance && mouse.x != null) {
            this.x += dirX;
            this.y += dirY;
          } else {
            if (this.x !== this.baseX) {
              let dx = this.x - this.baseX;
              this.x -= dx / 10;
            }
            if (this.y !== this.baseY) {
              let dy = this.y - this.baseY;
              this.y -= dy / 10;
            }
          }
        }
      }
      function initParticles() {
        particles = [];
        for (let i = 0; i < 60; i++) {
          particles.push(new Particle());
        }
      }
      function animateParticles() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        for (let i = 0; i < particles.length; i++) {
          particles[i].draw();
          particles[i].update();
        }
        requestAnimationFrame(animateParticles);
      }
      initParticles();
      animateParticles();
      });
    </script>
  </section>

  <!-- ── Floating Comprehensive Search Panel Section ── -->
  <div class="stats-container reveal" style="position: relative; z-index: 10; padding-bottom: 40px; margin-top: -120px;">
    <div class="container">
        <div class="hero-search-panel">
          <form action="/auctions.php" method="GET" class="hero-search-form">
            <!-- Search input -->
            <div class="search-field" style="flex: 3;">
              <label style="color: var(--primary);"><i class="ph ph-magnifying-glass"></i> كلمة البحث</label>
              <input type="text" name="search" placeholder="ابحث عن سيارات، مزادات..." id="quickSearch">
            </div>
            
            <!-- Make filter -->
            <div class="search-field">
              <label style="color: var(--primary);"><i class="ph ph-car"></i> الماركة</label>
              <select name="make">
                <option value="">الكل</option>
                <option value="تويوتا">تويوتا</option>
                <option value="هيونداي">هيونداي</option>
                <option value="كيا">كيا</option>
                <option value="نيسان">نيسان</option>
                <option value="مرسيدس">مرسيدس</option>
                <option value="فورد">فورد</option>
                <option value="شيفروليه">شيفروليه</option>
                <option value="بي إم دبليو">بي إم دبليو</option>
                <option value="أودي">أودي</option>
                <option value="لكزس">لكزس</option>
                <option value="جي إم سي">جي إم سي</option>
                <option value="دودج">دودج</option>
                <option value="مازدا">مازدا</option>
                <option value="هوندا">هوندا</option>
                <option value="إيسوزو">إيسوزو</option>
                <option value="شانجان">شانجان</option>
                <option value="جيلي">جيلي</option>
                <option value="إم جي">إم جي</option>
                <option value="هافال">هافال</option>
              </select>
            </div>
            
            <!-- City filter -->
            <div class="search-field">
              <label style="color: var(--primary);"><i class="ph ph-map-pin"></i> المدينة</label>
              <select name="city">
                <option value="">الكل</option>
                <option value="الرياض">الرياض</option>
                <option value="جدة">جدة</option>
                <option value="الدمام">الدمام</option>
                <option value="مكة المكرمة">مكة المكرمة</option>
                <option value="المدينة المنورة">المدينة المنورة</option>
                <option value="الطائف">الطائف</option>
                <option value="بريدة">بريدة</option>
                <option value="تبوك">تبوك</option>
                <option value="أبها">أبها</option>
                <option value="خميس مشيط">خميس مشيط</option>
                <option value="حائل">حائل</option>
                <option value="حفر الباطن">حفر الباطن</option>
                <option value="الجبيل">الجبيل</option>
                <option value="الخرج">الخرج</option>
                <option value="نجران">نجران</option>
                <option value="ينبع">ينبع</option>
              </select>
            </div>
            
            <!-- Transaction Type filter -->
            <div class="search-field">
              <label style="color: var(--primary);"><i class="ph ph-list-dashes"></i> نوع البيع</label>
              <select name="type">
                <option value="">الكل</option>
                <option value="live">مزايدة</option>
                <option value="instant">شراء فوري</option>
              </select>
            </div>
            
            <!-- Action buttons -->
            <div class="search-actions">
              <button type="submit" class="btn btn-primary btn-search-submit" title="بحث" style="width: 56px; height: 56px; border-radius: 50% !important; padding: 0; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                <i class="ph ph-magnifying-glass"></i>
              </button>
            </div>
          </form>
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
if (count($live_auctions) < 6) {
    $all_mock = getMockAuctions(30);
    foreach($all_mock as $m) {
        if (($m['type']??'') === 'live' && count($live_auctions) < 6) {
            $live_auctions[] = $m;
        }
    }
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
if (count($instant_cars) < 6) {
    $all_mock = getMockAuctions(30);
    foreach($all_mock as $m) {
        if (count($instant_cars) < 6 && !in_array($m['id'], array_column($instant_cars, 'id'))) {
            $m['type'] = 'instant';
            $instant_cars[] = $m;
        }
    }
}
?>

<!-- ── Unified Auctions Section (White Background) ── -->
<section class="reveal" style="padding:var(--section-py) 0; background:var(--bg-white);">
  <div class="container">
    <div style="text-align:center; margin-bottom:44px;">
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
      <div class="swiper auctions-swiper" style="padding: 20px 0 60px;">
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
          $status_labels = [
              ['label' => 'جاري', 'color' => '#fff', 'bg' => '#1bc976', 'hex' => '#1bc976'],
              ['label' => 'قادم', 'color' => '#fff', 'bg' => '#8b5cf6', 'hex' => '#8b5cf6'],
              ['label' => 'منتهي', 'color' => '#fff', 'bg' => '#94a3b8', 'hex' => '#94a3b8']
          ];
          $auction_names = ['مزاد الرياض الكبرى', 'مزاد أسطول جدة', 'مزاد سيارات الدفع الرباعي', 'مزاد السيارات الفاخرة', 'مزاد الوفاق', 'مزاد تصفية الشركات'];
          
          foreach($live_auctions as $ev_index => $a): 
            $title_car = $auction_names[$ev_index % count($auction_names)];
            $img = (!empty($a['image_url']) && strlen($a['image_url']) > 4) ? $a['image_url'] : $car_images[$ev_index % count($car_images)];
            $is_featured = ($ev_index % 3 === 0);
            $st = $status_labels[$ev_index % 3];
            $timerData = timeLeft($a['end_time']);
        ?>
        <div class="swiper-slide">
          <div class="auction-card animate-card <?= $is_featured ? 'featured-card' : '' ?>" data-id="<?= $a['id'] ?>" onclick="window.location.href='/vehicle.php?id=<?= $a['id'] ?>'" style="cursor: pointer;">
            <div class="card-badges-container">
              <div class="badge-item" style="color: <?= $st['color'] ?>; background: <?= $st['bg'] ?>; font-weight: 700; padding: 4px 10px; border-radius: var(--radius-sm); font-size: 11px;">
                <span style="display:inline-block; width:6px; height:6px; border-radius:50%; background:<?= $st['color'] ?>; margin-left:4px; vertical-align:middle;"></span>
                <?= $st['label'] ?>
              </div>
              <?php if($is_featured): ?>
                <div class="badge-item badge-featured"><i class="ph-fill ph-star ph-space-left"></i> مميز</div>
              <?php endif; ?>
            </div>
            <div class="card-fav" data-id="<?= $a['id'] ?>"><i class="ph ph-heart"></i></div>
            
            <div class="ac-img-wrap">
              <img src="<?= $img ?>" alt="<?= sanitize($title_car) ?>" loading="lazy">
            </div>
            
            <div class="ac-body">
              <h3 class="ac-title"><?= sanitize($title_car) ?></h3>
              
              <div class="ac-stats-row">
                <div class="ac-stat-cell">
                  <span class="label">البائع</span>
                  <span><?= sanitize($a['seller'] ?? 'الوطنية للتأجير') ?></span>
                </div>
                <div class="ac-stat-cell">
                  <span class="label">الموقع</span>
                  <span><?= sanitize($a['city'] ?? 'الرياض') ?></span>
                </div>
                <div class="ac-stat-cell">
                  <span class="label">المركبات</span>
                  <span class="font-en">+<?= rand(20, 150) ?></span>
                </div>
              </div>
              
              <div class="ac-price-row">
                <div>
                  <div class="ac-price-label">المزاد الحالي</div>
                  <div class="ac-price-val"><?= number_format($a['current_price'] ?? $a['starting_price'] ?? 0) ?> <span class="ac-price-currency">ر.س</span></div>
                </div>
                <div style="text-align:left;">
                   <div style="font-size:11px; color:var(--text-dark); font-weight:800; margin-bottom:4px"><i class="ph ph-activity"></i> مزاد <?= $st['label'] ?></div>
                </div>
              </div>

              <!-- Countdown Timer -->
              <div class="ac-timer-box">
                <?php if($st['label'] === 'منتهي'): ?>
                  <span class="ac-timer-label">المزاد منتهي</span>
                  <div class="ac-timer-val">
                    <div>00</div><span>:</span>
                    <div>00</div><span>:</span>
                    <div>00</div><span>:</span>
                    <div>00</div>
                  </div>
                <?php else: ?>
                  <span class="ac-timer-label">ينتهي خلال:</span>
                  <div class="ac-timer-val" data-endtime="<?= strtotime($a['end_time']) ?>">
                    <div><?= str_pad($timerData['days'], 2, "0", STR_PAD_LEFT) ?></div><span>:</span>
                    <div><?= str_pad($timerData['hours'], 2, "0", STR_PAD_LEFT) ?></div><span>:</span>
                    <div><?= str_pad($timerData['mins'], 2, "0", STR_PAD_LEFT) ?></div><span>:</span>
                    <div><?= str_pad($timerData['secs'], 2, "0", STR_PAD_LEFT) ?></div>
                  </div>
                <?php endif; ?>
              </div>
              
              <div class="ac-actions"><a href="/auction-room.php?id=<?= $a['id'] ?>" class="btn btn-primary" style="width:100%; justify-content:center; border-radius:var(--radius-round)">دخول المزاد <i class="ph-fill ph-gavel"></i></a></div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>


        </div>
        <div class="swiper-pagination"></div>
      </div>
      <div style="text-align: center; margin-top: 10px;">
        <a href="/auctions.php?type=live" class="btn btn-outline-dark">عرض جميع المزادات الحية <i class="ph ph-arrow-left"></i></a>
      </div>
    </div>

    <!-- ── Instant Buy Tab Content ── -->
    <div id="tab-content-instant" class="auctions-tab-content">
      <div class="swiper auctions-swiper" style="padding: 20px 0 60px;">
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
          foreach($instant_cars as $inst_index => $a): 
            $title_car = $a['title'] ?? ($a['make'].' '.$a['model'].' '.$a['year']);
            $img = (!empty($a['image_url']) && strlen($a['image_url']) > 4) ? $a['image_url'] : $random_images[$inst_index % count($random_images)];
            $is_featured = ($inst_index % 3 === 0);
        ?>
        <div class="swiper-slide">
          <div class="auction-card animate-card <?= $is_featured ? 'featured-card' : '' ?>" data-id="<?= $a['id'] ?>" onclick="window.location.href='/vehicle.php?id=<?= $a['id'] ?>'" style="cursor: pointer;">
            <div class="card-badges-container">
              <div class="badge-item" style="color: #fff; background: #1bc976; font-weight: 700; padding: 4px 10px; border-radius: var(--radius-sm); font-size: 11px;">شراء فوري</div>
              <?php if($is_featured): ?>
                <div class="badge-item badge-featured"><i class="ph-fill ph-star ph-space-left"></i> مميز</div>
              <?php endif; ?>
            </div>
            <div class="card-fav" data-id="<?= $a['id'] ?>"><i class="ph ph-heart"></i></div>
            
            <div class="ac-img-wrap">
              <img src="<?= $img ?>" alt="<?= sanitize($title_car) ?>" loading="lazy">
            </div>
            
            <div class="ac-body">
              <h3 class="ac-title"><?= sanitize($title_car) ?></h3>
              
              <div class="ac-stats-row">
                <div class="ac-stat-cell">
                  <span class="label">المدينة</span>
                  <span><?= sanitize($a['city'] ?? 'الرياض') ?></span>
                </div>
                <div class="ac-stat-cell">
                  <span class="label">الممشى</span>
                  <span class="font-en"><?= number_format($a['mileage'] ?? 0) ?> KM</span>
                </div>
                <div class="ac-stat-cell">
                  <span class="label">السنة</span>
                  <span class="font-en"><?= $a['year'] ?? '2023' ?></span>
                </div>
              </div>
              
              <div class="ac-price-row">
                <div>
                  <div class="ac-price-label">السعر المطلوب</div>
                  <div class="ac-price-val"><?= number_format($a['current_price'] ?? $a['starting_price'] ?? 0) ?> <span class="ac-price-currency">ر.س</span></div>
                </div>
                <div style="text-align:left;">
                   <div style="font-size:11px; color:var(--text-dark); font-weight:800; margin-bottom:4px"><i class="ph ph-lightning"></i> سعر فوري</div>
                </div>
              </div>

              <!-- Expiration Date -->
              <div class="ac-timer-box" style="display: flex; justify-content: space-between; align-items: center; background: rgba(14, 165, 233, 0.05); padding: 12px; border-radius: var(--radius-md);">
                <span class="ac-timer-label" style="font-size: 13px;">تاريخ الانتهاء:</span>
                <div style="font-weight: 800; font-size: 13px; color: var(--text-dark);">
                  <?= date('Y-m-d', strtotime($a['end_time'] ?? '+3 days')) ?>
                </div>
              </div>

              <div style="margin-top: auto;">
                <a href="/vehicle-details.php?id=<?= $a['id'] ?>" class="btn btn-primary" style="width:100%; justify-content:center; background: var(--primary-gradient); border-radius:var(--radius-round);">
                  شراء الآن <i class="ph ph-arrow-left"></i>
                </a>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
        </div>
        <div class="swiper-pagination"></div>
      </div>
      <div style="text-align: center; margin-top: 10px;">
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
    contents.forEach(c => {
      c.style.display = 'none';
      c.classList.remove('active');
    });

    const activeContent = document.getElementById(`${type}-step-${step}`);
    if(activeContent) {
      activeContent.style.display = 'flex';
      activeContent.classList.add('active', 'reveal', 'visible');
    }
  }
</script>

<!-- ── HOW IT WORKS: Sticky Sections with Tabs ── -->
<section style="position: relative;">
  
  <!-- ============ SECTION: BUYERS ============ -->
  <div style="position: sticky; top: 0; min-height: 100vh; display: flex; align-items: center; background:var(--bg-light); z-index: 1; padding: 100px 0 140px;">
    <div class="container" style="width: 100%;">
      <div style="text-align:center; margin-bottom:40px;">
        <h2 style="font-size:32px; font-weight:900; color:var(--text-dark); margin-bottom:12px;">كيف تبدأ كـ <span style="color:var(--primary);">مشتري؟</span></h2>
        <p style="color:var(--text-muted); font-size:16px;">للمشترين الأفراد والشركات المعتمدة</p>
      </div>

      <div class="browser-tabs-container">
        <div class="browser-tabs" id="buyer-tabs">
          <button class="b-tab active" onclick="switchHiwTab(event, 'buyer', 1)">التسجيل</button>
          <button class="b-tab" onclick="switchHiwTab(event, 'buyer', 2)">المحفظة</button>
          <button class="b-tab" onclick="switchHiwTab(event, 'buyer', 3)">المزايدة</button>
        </div>
        <div class="browser-content" style="background:#fff; border-radius:24px; padding:40px; box-shadow:none;">
          
          <div id="buyer-step-1" class="hiw-tab-content active" style="display:flex; gap:40px; align-items:center; flex-wrap:wrap;">
            <div style="flex: 1; min-width: 300px;">
              <i class="ph ph-identification-card" style="font-size: 32px; color: var(--primary); margin-bottom: 12px;"></i>
              <h3 style="font-size:24px; font-weight:800; margin-bottom:16px;">التسجيل وتوثيق الهوية <i class="ph-fill ph-warning-circle hiw-info-icon" onclick="openHiwModal('buyer-reg')" style="cursor:pointer; color:var(--primary); font-size:26px; vertical-align:middle; margin-right:8px; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'" title="عرض التفاصيل والآلية"></i></h3>
              <p style="color:var(--text-muted); font-size:15px; line-height:1.8; margin-bottom: 24px;">سجّل حسابك في 3 خطوات سريعة عبر منصة نفاذ الوطني الموحّد لضمان أعلى معايير الأمان والثقة في التعاملات.</p>
              
            </div>
            <div style="flex: 1; min-width: 300px;">
              <img src="https://images.unsplash.com/photo-1517524008697-84bbe3c3fd98?w=600&q=80" style="width:100%; border-radius: var(--radius-md); aspect-ratio: 4/3; object-fit: cover;" alt="التسجيل" loading="lazy">
            </div>
          </div>

          <div id="buyer-step-2" class="hiw-tab-content" style="display:none; gap:40px; align-items:center; flex-wrap:wrap;">
            <div style="flex: 1; min-width: 300px;">
              <i class="ph ph-wallet" style="font-size: 32px; color: var(--primary); margin-bottom: 12px;"></i>
              <h3 style="font-size:24px; font-weight:800; margin-bottom:16px;">المحفظة والتأمين <i class="ph-fill ph-warning-circle hiw-info-icon" onclick="openHiwModal('buyer-wallet')" style="cursor:pointer; color:var(--primary); font-size:26px; vertical-align:middle; margin-right:8px; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'" title="عرض التفاصيل والآلية"></i></h3>
              <p style="color:var(--text-muted); font-size:15px; line-height:1.8; margin-bottom: 24px;">أودع مبلغ التأمين المطلوب في محفظتك الرقمية عبر قنوات الدفع المتعددة للبدء في المزايدة المباشرة.</p>
              
            </div>
            <div style="flex: 1; min-width: 300px;">
              <img src="https://images.unsplash.com/photo-1616081467471-a47ea1fc7fcc?w=600&q=80" style="width:100%; border-radius: var(--radius-md); aspect-ratio: 4/3; object-fit: cover;" alt="المحفظة" loading="lazy">
            </div>
          </div>

          <div id="buyer-step-3" class="hiw-tab-content" style="display:none; gap:40px; align-items:center; flex-wrap:wrap;">
            <div style="flex: 1; min-width: 300px;">
              <i class="ph ph-gavel" style="font-size: 32px; color: var(--primary); margin-bottom: 12px;"></i>
              <h3 style="font-size:24px; font-weight:800; margin-bottom:16px;">المزايدة والفوز بالمركبة <i class="ph-fill ph-warning-circle hiw-info-icon" onclick="openHiwModal('buyer-bid')" style="cursor:pointer; color:var(--primary); font-size:26px; vertical-align:middle; margin-right:8px; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'" title="عرض التفاصيل والآلية"></i></h3>
              <p style="color:var(--text-muted); font-size:15px; line-height:1.8; margin-bottom: 24px;">سجّل في المزاد المختار، وحدّد صفتك القانونية، وشارك في غرفة المزاد الحية للفوز بالمركبات.</p>
              
            </div>
            <div style="flex: 1; min-width: 300px;">
              <img src="https://images.unsplash.com/photo-1600860548174-569420dd7b89?w=600&q=80" style="width:100%; border-radius: var(--radius-md); aspect-ratio: 4/3; object-fit: cover;" alt="المزايدة والفوز" loading="lazy">
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <!-- ============ SECTION: SELLERS ============ -->
  <div style="position: sticky; top: 0; min-height: 100vh; display: flex; align-items: center; background:#f1f5f9; color: var(--text-dark); z-index: 2; padding: 100px 0; border-radius: 40px 40px 0 0; box-shadow: none;">
    <div class="container" style="width: 100%;">
      <div style="text-align:center; margin-bottom:40px;">
        <h2 style="font-size:32px; font-weight:900; color:var(--text-dark); margin-bottom:12px;">كيف تبدأ كـ <span style="color:var(--primary);">بائع معتمد؟</span></h2>
        <p style="color:var(--text-muted); font-size:16px;">لشركات التأجير والأساطيل</p>
      </div>

      <div class="browser-tabs-container">
        <div class="browser-tabs" id="seller-tabs">
          <button class="b-tab active" onclick="switchHiwTab(event, 'seller', 1)">الاعتماد</button>
          <button class="b-tab" onclick="switchHiwTab(event, 'seller', 2)">الاشتراك</button>
          <button class="b-tab" onclick="switchHiwTab(event, 'seller', 3)">الإدراج</button>
        </div>
        <div class="browser-content" style="background:#fff; border-radius:24px; padding:40px; box-shadow:none;">
          
          <div id="seller-step-1" class="hiw-tab-content active" style="display:flex; gap:40px; align-items:center; flex-wrap:wrap;">
            <div style="flex: 1; min-width: 300px;">
              <i class="ph ph-buildings" style="font-size: 32px; color: var(--primary); margin-bottom: 12px;"></i>
              <h3 style="font-size:24px; font-weight:800; margin-bottom:16px;">توثيق الشركة والاعتماد <i class="ph-fill ph-warning-circle hiw-info-icon" onclick="openHiwModal('seller-reg')" style="cursor:pointer; color:var(--primary); font-size:26px; vertical-align:middle; margin-right:8px; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'" title="عرض التفاصيل والآلية"></i></h3>
              <p style="color:var(--text-muted); font-size:15px; line-height:1.8; margin-bottom: 24px;">سجّل شركتك وأرفق السجل التجاري ليتم تدقيقها واعتماد حسابك كبائع موثوق خلال 48 ساعة عمل.</p>
              
            </div>
            <div style="flex: 1; min-width: 300px;">
              <img src="https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=600&q=80" style="width:100%; border-radius: var(--radius-md); aspect-ratio: 4/3; object-fit: cover;" alt="توثيق الشركة" loading="lazy">
            </div>
          </div>

          <div id="seller-step-2" class="hiw-tab-content" style="display:none; gap:40px; align-items:center; flex-wrap:wrap;">
            <div style="flex: 1; min-width: 300px;">
              <i class="ph ph-package" style="font-size: 32px; color: var(--primary); margin-bottom: 12px;"></i>
              <h3 style="font-size:24px; font-weight:800; margin-bottom:16px;">اختيار الباقة وشحن الحساب <i class="ph-fill ph-warning-circle hiw-info-icon" onclick="openHiwModal('seller-wallet')" style="cursor:pointer; color:var(--primary); font-size:26px; vertical-align:middle; margin-right:8px; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'" title="عرض التفاصيل والآلية"></i></h3>
              <p style="color:var(--text-muted); font-size:15px; line-height:1.8; margin-bottom: 24px;">اختر باقة الاشتراك المناسبة لحجم أسطولك، وأودع رسوم التفعيل للبدء في الاستفادة من أدوات البيع.</p>
              
            </div>
            <div style="flex: 1; min-width: 300px;">
              <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=600&q=80" style="width:100%; border-radius: var(--radius-md); aspect-ratio: 4/3; object-fit: cover;" alt="شحن الحساب" loading="lazy">
            </div>
          </div>

          <div id="seller-step-3" class="hiw-tab-content" style="display:none; gap:40px; align-items:center; flex-wrap:wrap;">
            <div style="flex: 1; min-width: 300px;">
              <i class="ph ph-car" style="font-size: 32px; color: var(--primary); margin-bottom: 12px;"></i>
              <h3 style="font-size:24px; font-weight:800; margin-bottom:16px;">إدراج المركبات وإطلاق المزادات <i class="ph-fill ph-warning-circle hiw-info-icon" onclick="openHiwModal('seller-list')" style="cursor:pointer; color:var(--primary); font-size:26px; vertical-align:middle; margin-right:8px; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'" title="عرض التفاصيل والآلية"></i></h3>
              <p style="color:var(--text-muted); font-size:15px; line-height:1.8; margin-bottom: 24px;">أضف تفاصيل مركباتك وتقارير الفحص، وأطلق مزادك لتتلقى العروض من آلاف المشترين المعتمدين.</p>
              
            </div>
            <div style="flex: 1; min-width: 300px;">
              <img src="https://images.unsplash.com/photo-1502877338535-766e1452684a?w=600&q=80" style="width:100%; border-radius: var(--radius-md); aspect-ratio: 4/3; object-fit: cover;" alt="إدراج المركبات" loading="lazy">
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

<style>
.services-flex-container {
  display: flex;
  gap: 16px;
  height: 400px;
}
.service-flex-card-new {
  flex: 1;
  position: relative;
  border-radius: var(--radius-lg);
  overflow: hidden;
  transition: flex 0.5s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  align-items: center;
  justify-content: flex-end; /* right align */
  padding: 40px;
  cursor: pointer;
}
.service-flex-card-new:hover {
  flex: 3;
}
.service-flex-card-new::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(to bottom, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.3) 100%);
  z-index: 1;
}
.service-flex-card-new .service-content {
  position: relative;
  z-index: 2;
  text-align: right;
  align-items: flex-end;
  width: 100%;
  max-width: 300px;
  transition: all 0.5s ease;
  opacity: 0.8;
}
.service-flex-card-new:hover .service-content {
  opacity: 1;
}
.service-flex-card-new .icon-grad {
  font-size: 48px;
  margin-bottom: 24px;
  color: var(--primary);
  background: none;
  -webkit-text-fill-color: initial;
  transition: all 0.4s ease;
}
.service-flex-card-new:hover .icon-grad {
  color: #fff;
  transform: scale(1.2);
}
.service-flex-card-new h3 {
  font-size: 24px;
  font-weight: 800;
  color: #fff;
  margin-bottom: 12px;
}
.service-flex-card-new p {
  color: rgba(255,255,255,0.7);
  font-size: 15px;
  line-height: 1.6;
}
/* override the previous gradient icons */
@keyframes pulseWave {
  0% { box-shadow: 0 0 0 0 rgba(14, 165, 233, 0.7); }
  70% { box-shadow: 0 0 0 10px rgba(14, 165, 233, 0); }
  100% { box-shadow: 0 0 0 0 rgba(14, 165, 233, 0); }
}
.hiw-info-icon {
  border-radius: 50%;
  animation: pulseWave 2s infinite;
  position: relative;
}
.hiw-info-icon::after {
  content: "اضغط لمعرفة الخطوات";
  position: absolute;
  bottom: 100%;
  left: 50%;
  transform: translateX(-50%) scale(0);
  background: var(--bg-dark);
  color: #fff;
  padding: 4px 12px;
  border-radius: 4px;
  font-size: 12px;
  font-family: var(--font-ar);
  white-space: nowrap;
  pointer-events: none;
  transition: all 0.2s ease;
  transform-origin: bottom center;
}
.hiw-info-icon:hover {
  transform: scale(1.15) !important;
  animation: none;
}
.hiw-info-icon:hover::after {
  transform: translateX(-50%) scale(1);
}
/* Browser Tabs Styles */
.browser-tabs-container {
  max-width: 1000px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
}
.browser-tabs {
  display: flex;
  gap: 8px;
  padding: 0 20px;
  justify-content: center;
}
.b-tab {
  background: rgba(255, 255, 255, 0.5);
  border: none;
  padding: 16px 32px;
  font-size: 16px;
  font-weight: 700;
  color: var(--text-muted);
  border-radius: 16px 16px 0 0;
  cursor: pointer;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-family: inherit;
}
.b-tab:hover {
  background: rgba(255, 255, 255, 0.8);
  color: var(--text-dark);
}
.b-tab.active {
  background: #ffffff;
  color: var(--primary);
  box-shadow: 0 -4px 20px rgba(0,0,0,0.02);
}
.browser-box {
  background: #ffffff;
  border-radius: 24px;
  padding: 40px;
  min-height: 400px;
}
.b-panel {
  display: none;
  animation: fadeInTab 0.4s ease;
}
.b-panel.active {
  display: flex;
  align-items: center;
  gap: 40px;
}
.b-panel-content {
  flex: 1;
}
.b-panel-img {
  flex: 1;
  border-radius: 16px;
  overflow: hidden;
}
.b-panel-img img {
  width: 100%;
  height: 320px;
  object-fit: cover;
  display: block;
}
@keyframes fadeInTab {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
@media (max-width: 768px) {
  .b-tab { padding: 12px 16px; font-size: 14px; }
  .b-panel.active { flex-direction: column; text-align: center; }
  .b-panel-img { width: 100%; order: -1; }
  .b-panel-img img { height: 200px; }
  .tab-text { display: none; }
}

.hiw-modal-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);
  z-index: 9999; display: none; align-items: center; justify-content: center; padding: 20px;
}
.hiw-modal-overlay.active { display: flex; animation: fadeIn 0.3s ease; }
.hiw-modal {
  background: #fff; border-radius: 24px; padding: 40px; max-width: 600px; width: 100%;
  position: relative; animation: slideUp 0.3s ease;
}
.hiw-modal-close {
  position: absolute; top: 20px; left: 20px; background: var(--bg-light); border: none;
  width: 40px; height: 40px; border-radius: 50%; cursor: pointer; display: flex; align-items: center;
  justify-content: center; font-size: 18px; color: var(--text-dark);
}
.hiw-modal h4 { font-size: 22px; font-weight: 800; color: var(--text-dark); margin-bottom: 8px; }
.hiw-modal-sub { font-size: 14px; color: var(--primary); font-weight: 700; margin-bottom: 24px; background: var(--primary-light); display: inline-block; padding: 6px 14px; border-radius: 20px; }
.hiw-modal-steps { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 16px; }
.hiw-modal-steps li { display: flex; gap: 16px; align-items: flex-start; font-size: 15px; line-height: 1.6; color: var(--text-dark); }
.hiw-modal-step-badge { width: 32px; height: 32px; flex-shrink: 0; background: var(--bg-dark); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-family: var(--font-en); font-size: 14px; }
.hiw-modal-alert { margin-top: 24px; padding: 16px; background: var(--warning-pale); border-right: 4px solid var(--warning); border-radius: 8px; font-size: 13px; color: var(--text-dark); line-height: 1.6; }

      .service-card-1 { background: linear-gradient(to left, #0ea5e9, #1bc976) !important; }
      .service-card-2 { background: linear-gradient(to left, #1bc976, #0ea5e9) !important; }
      .service-card-3 { background: linear-gradient(to left, #0ea5e9, #8b5cf6) !important; }
</style>

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
<section class="reveal" style="background:var(--bg-white); padding:var(--section-py) 0 80px 0;">
  <div class="container">
    <div style="text-align:center; max-width:700px; margin:0 auto 60px">
      <h2 class="section-title">خدمات مزادات ذكية ومتكاملة</h2>
      <p class="section-subtitle">نسهل العمليات اللوجستية والفحص والتسوية من البداية وحتى التسليم النهائي</p>
    </div>

<style>
.services-flex-container {
  display: flex;
  gap: 16px;
  height: 400px;
}
.service-flex-card-new {
  flex: 1;
  position: relative;
  border-radius: var(--radius-lg);
  overflow: hidden;
  transition: flex 0.5s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  align-items: center;
  justify-content: flex-end; /* right align */
  padding: 40px;
  cursor: pointer;
}
.service-flex-card-new:hover {
  flex: 3;
}
.service-flex-card-new::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(to bottom, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.3) 100%);
  z-index: 1;
}
.service-flex-card-new .service-content {
  position: relative;
  z-index: 2;
  text-align: right;
  align-items: flex-end;
  width: 100%;
  max-width: 300px;
  transition: all 0.5s ease;
  opacity: 0.8;
}
.service-flex-card-new:hover .service-content {
  opacity: 1;
}
.service-flex-card-new .icon-grad {
  font-size: 48px;
  margin-bottom: 24px;
  color: var(--primary);
  background: none;
  -webkit-text-fill-color: initial;
  transition: all 0.4s ease;
}
.service-flex-card-new:hover .icon-grad {
  color: #fff;
  transform: scale(1.2);
}
.service-flex-card-new h3 {
  font-size: 24px;
  font-weight: 800;
  color: #fff;
  margin-bottom: 12px;
}
.service-flex-card-new p {
  color: rgba(255,255,255,0.7);
  font-size: 15px;
  line-height: 1.6;
}
/* override the previous gradient icons */
  .icon-grad { transition: 0.3s ease; }
  .service-card-1:hover .icon-grad {
    background: linear-gradient(135deg, #000000, #064e3b);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }
  .service-card-2:hover .icon-grad {
    background: linear-gradient(135deg, #064e3b, #0ea5e9);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }
  .service-card-3:hover .icon-grad {
    background: linear-gradient(135deg, #0ea5e9, #a855f7);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }

      .service-card-1 { background: linear-gradient(to left, #0ea5e9, #1bc976) !important; }
      .service-card-2 { background: linear-gradient(to left, #1bc976, #0ea5e9) !important; }
      .service-card-3 { background: linear-gradient(to left, #0ea5e9, #8b5cf6) !important; }
</style>
    <div class="services-flex-container">
      <div class="service-flex-card-new service-card-1 reveal" style="background: var(--bg-dark);">
        
        
        <div style="position:absolute; inset:0; background:rgba(0,0,0,0.4); z-index:1; border-radius:inherit; pointer-events:none;"></div>
        <div class="service-content" style="text-align: right; display: flex; flex-direction: column; align-items: flex-end; position:relative; z-index:2;">
          <i class="ph ph-clipboard-text icon-grad"></i>
          <h3 style="font-size:22px; font-weight:800; color:#fff; margin-bottom:12px;">تقرير فحص 100+ نقطة</h3>
          <p style="color:rgba(255,255,255,0.7); font-size:15px; line-height:1.6;">جميع السيارات المعروضة تخضع لفحص فني شامل يغطي الهيكل والميكانيكا والكهرباء معتمد من قبل خبراء المنصة.</p>
        </div>
      </div>
      
      <div class="service-flex-card-new service-card-2 reveal" style="transition-delay:0.1s; background: var(--bg-darker);">
        
        
        <div style="position:absolute; inset:0; background:rgba(0,0,0,0.4); z-index:1; border-radius:inherit; pointer-events:none;"></div>
        <div class="service-content" style="text-align: right; display: flex; flex-direction: column; align-items: flex-end; position:relative; z-index:2;">
          <i class="ph ph-shield-check icon-grad"></i>
          <h3 style="font-size:22px; font-weight:800; color:#fff; margin-bottom:12px;">بيئة موثقة بالكامل</h3>
          <p style="color:rgba(255,255,255,0.7); font-size:15px; line-height:1.6;">نحن نتحقق من هوية المشترين والبائعين عبر تكامل مباشر مع بوابة النفاذ الوطني الموحد لضمان جدية المزايدات.</p>
        </div>
      </div>
      
      <div class="service-flex-card-new service-card-3 reveal" style="transition-delay:0.2s; background: #0f1520;">
        
        
        <div style="position:absolute; inset:0; background:rgba(0,0,0,0.4); z-index:1; border-radius:inherit; pointer-events:none;"></div>
        <div class="service-content" style="text-align: right; display: flex; flex-direction: column; align-items: flex-end; position:relative; z-index:2;">
          <i class="ph ph-robot icon-grad"></i>
          <h3 style="font-size:22px; font-weight:800; color:#fff; margin-bottom:12px;">نظام المزايدة التلقائية</h3>
          <p style="color:rgba(255,255,255,0.7); font-size:15px; line-height:1.6;">حدد سقف ميزانيتك للسيارة، وسيقوم النظام الذكي بالمزايدة بالنيابة عنك بأقل زيادة ممكنة لحين الوصول لحدك الأقصى.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ── Stats Section (Parallax Dark Theme) ── -->
<section class="stats-section reveal" style="background-image: url('https://images.unsplash.com/photo-1580273916550-e323be2ae537?w=1600&q=80'); background-attachment: fixed; background-size: cover; background-position: center; padding: 100px 0 140px;">
  <div style="position: absolute; top:0; left:0; right:0; bottom:0; background: rgba(6, 12, 22, 0.9); z-index: 0;"></div>
  
  <div class="container" style="position: relative; z-index: 1;">
    <div style="text-align: center; margin-bottom: 60px;">
      <div style="max-width: 800px; margin: 0 auto;">
        <h2 style="font-size: 40px; font-weight: 900; margin-bottom: 20px; line-height: 1.4; color: #fff;">ستجد لدينا كل ما تحتاجه<br><span style="color:var(--primary)">لتعزيز نجاحك في المزادات</span></h2>
        <p style="font-size: 16px; color: rgba(255,255,255,0.7);">الأرقام تؤكد تفوقنا كأول وأكبر منصة مزادات أساطيل رقمية.</p>
      </div>
      <div style="display: flex; gap: 80px; justify-content: center; flex-wrap: wrap; margin-top: 40px;">
        <div style="text-align: center;">
          <div class="stats-number" style="color: #fff;">92.9%</div>
          <div class="stats-desc">من المزادات تتم الموافقة عليها تلقائياً</div>
        </div>
        <div style="text-align: center;">
          <div class="stats-number text-gradient"><span class="font-en">13.5</span> <span style="font-family: var(--font-ar)">مليار</span></div>
          <div class="stats-desc">ريال قيمة صفقات تم تنفيذها عبر المنصة</div>
        </div>
      </div>
    </div>

    <div class="stats-features-grid">
      <div class="stat-feature-card">
        <i class="ph ph-lightning"></i>
        <h4 style="color:#fff">تنفيذ فوري وسريع</h4>
        <p>احصل على أرباحك وعوائد بيع الأسطول فور الانتهاء بدون رسوم خفية أو تأخير.</p>
      </div>
      <div class="stat-feature-card">
        <i class="ph ph-clock"></i>
        <h4 style="color:#fff">مزادات بدون انتظار</h4>
        <p>استمتع بتنفيذ وتحديد جداول زمنية عادلة ومضمونة بدون إعادة تسعير أو رفض أوامر.</p>
      </div>
      <div class="stat-feature-card">
        <i class="ph ph-shield-check"></i>
        <h4 style="color:#fff">لا رسوم خفية</h4>
        <p>لا تدفع أي رسوم أو عمولات مبيت أو تكاليف غير معلنة في كراسات الشروط.</p>
      </div>
      <div class="stat-feature-card">
        <i class="ph ph-arrows-in-line-horizontal"></i>
        <h4 style="color:#fff">عمولة منخفضة</h4>
        <p>رسوم وعمولات المنصة هي الأقل في المملكة وتصل إلى نسبة ضئيلة جداً على كبار الموردين.</p>
      </div>
    </div>
  </div>
</section>
<!-- ── Contact Us Section ── -->
<section style="background:var(--bg-light); padding:100px 0; position: relative;" id="contact-us">
  <div class="container">
    <div class="contact-grid">
      
      <!-- Contact Info -->
      <div class="contact-info-col reveal">
        <h2 style="font-size: 36px; font-weight: 900; color: var(--text-dark); margin-bottom: 20px;">دعنا نتحدث عن <span style="color:var(--primary)">نجاحك القادم</span></h2>
        <p style="color: var(--text-muted); font-size: 16px; line-height: 1.8; margin-bottom: 40px;">فريق الخبراء لدينا جاهز للإجابة على جميع استفساراتك وتقديم استشارات مخصصة لإدارة أسطولك بأعلى كفاءة.</p>
        
        <div style="display:flex; flex-direction:column; gap:24px;">
          <div style="display:flex; align-items:flex-start; gap:16px;">
            <div style="width:50px; height:50px; border-radius:12px; background:var(--primary-light); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:24px; flex-shrink:0;">
              <i class="ph-fill ph-phone-call"></i>
            </div>
            <div>
              <div style="font-size:14px; color:var(--text-muted); margin-bottom:4px; font-weight:700">اتصل بنا</div>
              <div style="font-size:18px; color:var(--text-dark); font-weight:400; font-family:var(--font-en)" dir="ltr">+20 10 6644 2622</div>
            </div>
          </div>
          
          <div style="display:flex; align-items:flex-start; gap:16px;">
            <div style="width:50px; height:50px; border-radius:12px; background:rgba(14, 165, 233, 0.1); color:#0ea5e9; display:flex; align-items:center; justify-content:center; font-size:24px; flex-shrink:0;">
              <i class="ph-fill ph-envelope-simple"></i>
            </div>
            <div>
              <div style="font-size:14px; color:var(--text-muted); margin-bottom:4px; font-weight:700">البريد الإلكتروني</div>
              <div style="font-size:18px; color:var(--text-dark); font-weight:400; font-family:var(--font-en)">info@bearand.com</div>
            </div>
          </div>
          
          <div style="display:flex; align-items:flex-start; gap:16px;">
            <div style="width:50px; height:50px; border-radius:12px; background:rgba(168, 85, 247, 0.1); color:#a855f7; display:flex; align-items:center; justify-content:center; font-size:24px; flex-shrink:0;">
              <i class="ph-fill ph-map-pin"></i>
            </div>
            <div>
              <div style="font-size:14px; color:var(--text-muted); margin-bottom:4px; font-weight:700">المركز الرئيسي</div>
              <div style="font-size:16px; color:var(--text-dark); font-weight:400;">برج المملكة، طريق الملك فهد، الرياض، المملكة العربية السعودية</div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Contact Form -->
      <div class="contact-form-col reveal" style="transition-delay:0.2s">
        <div style="background: #fff; padding: 40px; border-radius: var(--radius-lg); box-shadow: 0 20px 40px rgba(0,0,0,0.04); border: 1px solid var(--border-light);">
          <h3 style="font-size: 24px; font-weight: 800; margin-bottom: 24px; color: var(--text-dark);">أرسل رسالة</h3>
          <form onsubmit="event.preventDefault(); alert('تم إرسال رسالتك بنجاح! سنتواصل معك قريباً.');">
            <div class="form-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
              <div>
                <label style="display:block; font-size:14px; font-weight:700; color:var(--text-muted); margin-bottom:8px;">الاسم الكامل</label>
                <input type="text" required style="width:100%; padding:14px 16px; border-radius:var(--radius-md); border:1px solid var(--border-light); font-family:var(--font-ar); outline:none; transition:border 0.3s;" onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='var(--border-light)'">
              </div>
              <div>
                <label style="display:block; font-size:14px; font-weight:700; color:var(--text-muted); margin-bottom:8px;">رقم الجوال</label>
                <input type="tel" required style="width:100%; padding:14px 16px; border-radius:var(--radius-md); border:1px solid var(--border-light); font-family:var(--font-en); text-align:left; outline:none; transition:border 0.3s;" onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='var(--border-light)'" placeholder="05X XXX XXXX" dir="ltr">
              </div>
            </div>
            <div style="margin-bottom:16px;">
              <label style="display:block; font-size:14px; font-weight:700; color:var(--text-muted); margin-bottom:8px;">موضوع الرسالة</label>
              <select style="width:100%; padding:14px 16px; border-radius:var(--radius-md); border:1px solid var(--border-light); font-family:var(--font-ar); outline:none; cursor:pointer;">
                <option>استفسار عام</option>
                <option>الانضمام كبائع معتمد</option>
                <option>مشكلة فنية</option>
                <option>اقتراح</option>
              </select>
            </div>
            <div style="margin-bottom:24px;">
              <label style="display:block; font-size:14px; font-weight:700; color:var(--text-muted); margin-bottom:8px;">تفاصيل الرسالة</label>
              <textarea rows="4" required style="width:100%; padding:14px 16px; border-radius:var(--radius-md); border:1px solid var(--border-light); font-family:var(--font-ar); outline:none; resize:vertical; transition:border 0.3s;" onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='var(--border-light)'"></textarea>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%; padding:16px; font-size:16px; justify-content:center; gap:10px;">
              إرسال الرسالة <i class="ph ph-paper-plane-right"></i>
            </button>
          </form>
        </div>
      </div>
      
    </div>
  </div>
</section>

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
        this.classList.toggle('active');
        let icon = this.querySelector('i');
        if(this.classList.contains('active')) {
          icon.classList.remove('ph');
          icon.classList.add('ph-fill');
          icon.style.color = '#ef4444';
        } else {
          icon.classList.remove('ph-fill');
          icon.classList.add('ph');
          icon.style.color = '';
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
          clock.innerHTML = "<div style='color:var(--danger); font-weight:700;'>انتهى المزاد</div>";
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

</body>
</html>
