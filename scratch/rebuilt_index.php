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
453:       <div class="swiper-button-prev"></div>
454:     </div>
455:   </div>
456: </section>
457: 
458: <?php
459: // Fetch instant buy vehicles
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
      <div style="position: absolute; inset:0; background: url('https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?w=1600&q=80') center/cover no-repeat; opacity: 0.15; mask-image: linear-gradient(to bottom, rgba(0,0,0,1) 40%, rgba(0,0,0,0) 100%); -webkit-mask-image: linear-gradient(to bottom, rgba(0,0,0,1) 40%, rgba(0,0,0,0) 100%); mix-blend-mode: screen;"></div>
    </div>
    
    <!-- Faded Overlay Bottom -->
    <div style="position:absolute; bottom:0; left:0; width:100%; height:30%; background:linear-gradient(to top, var(--bg-dark) 0%, transparent 100%); z-index:2; pointer-events:none;"></div>

    <div class="hero-elegant-bg" style="opacity: 0.4; z-index: 1;">
      <div class="glow-orb orb-1"></div>
      <div class="glow-orb orb-2"></div>
    </div>

    <div class="hero-content" style="margin-top: 40px; position: relative; z-index: 2;">
      <div class="hero-tagline">
        <span id="heroTaglineTypewriter"></span>
      </div>
      
      <h1 cl
      
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
      
   
  </section>

  <!-- ── Floating Comprehensive Search Panel Section ── -->
    top: -20px;
    width: 120px;
    height: 120px;
    background: radial-gradient(circle, var(--primary) 0%, transparent 70%);
    opacity: 0.05;
    border-radius: 50%;
    transition: all 0.3s ease;
    z-index: 1;
  }
  .bpc-card:hover::before {
    opacity: 0.15;
    transform: scale(1.2);
  }

  /* Stats background fixed */
  .stats-section-bg {
    background-attachment: fixed !important;
  }
  
  /* Counter animation init */
  .stats-val { visibility: visible; }
</style>
    <style>
      @media (min-width: 993px) {
        .hero-search-main-row { display: contents; }
        .hero-advanced-fields { display: contents; }
        .hero-search-toggle { display: none !important; }
          .search-actions { order: 99; }
      }
      @media (max-width: 992px) {
        .hero-search-main-row { display: flex; width: 100%; align-items: center; gap: 8px; }
        .hero-search-main-row .search-field { width: 100%; padding: 0 !important; border: none !important; }
        .hero-search-main-row .search-actions { display: flex; gap: 8px; flex-shrink: 0; }
        .hero-advanced-fields { display: none; flex-direction: column; width: 100%; gap: 12px; margin-top: 16px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 16px; }
        .hero-search-form.expanded .hero-advanced-fields { display: flex; }
        .hero-search-toggle { display: flex; justify-content: center; align-items: center; width: 100%; margin-top: 12px; color: var(--primary); font-size: 24px; cursor: pointer; transition: transform 0.3s ease; }
        .hero-search-form.expanded .hero-search-toggle i { transform: rotate(180deg); }
      }
      /* Ensure HIW images are fully visible */
  .hiw-image img { object-fit: contain !important; padding: 10px; }

  /* BPC Smart Auction Services Cards */
  .bpc-card {
    height: auto !important;
    min-height: unset !important;
    padding: 24px 30px !important;
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 20px;
    text-align: right;
    position: relative;
    overflow: hidden;
  }
  .bpc-card .bpc-icon {
    transition: color 0.3s ease;
    flex-shrink: 0;
  }
  .bpc-card:hover .bpc-icon {
    color: #fff !important;
  }
  .bpc-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    position: relative;
    z-index: 2;
  }
  /* Faded background on the left side of card */
  .bpc-card::before {
    content: '';
    position: absolute;
    left: -20px;
    top: -20px;
    width: 120px;
    height: 120px;
    background: radial-gradient(circle, var(--primary) 0%, transparent 70%);
    opacity: 0.05;
    border-radius: 50%;
    transition: all 0.3s ease;
    z-index: 1;
  }
  .bpc-card:hover::before {
    opacity: 0.15;
    transform: scale(1.2);
  }

  /* Stats background fixed */
  .stats-section-bg {
    background-attachment: fixed !important;
  }
  
  /* Counter animation init */
  .stats-val { visibility: visible; }
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
        const hero = document.querySelector('.hero');
        canvas.width = hero.offsetWidth || window.innerWidth;
        canvas.height = hero.offsetHeight || window.innerHeight;
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
          this.size = Math.random() * 1 + 0.5; // Smaller points
          this.baseX = this.x;
          this.baseY = this.y;
          this.density = (Math.random() * 30) + 1;
          this.isNumber = Math.random() > 0.65; // more numbers
          this.val = Math.floor(Math.random() * 10);
        }
        draw() {
          // Add transparency to numbers and dots
          ctx.fillStyle = this.isNumber ? 'rgba(27, 201, 118, 0.15)' : 'rgba(27, 201, 118, 0.08)';
          if (this.isNumber) {
            ctx.font = 'bold 12px var(--font-en)';
            ctx.fillText(this.val, this.x, this.y);
          } else {
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
            ctx.closePath();
            ctx.fill();
          }
        }
        update() {
          // Add a subtle ambient drift for numbers to float slowly
          if (this.isNumber) {
            this.baseX += Math.sin(Date.now()/2000 + this.val) * 0.15;
            this.baseY += Math.cos(Date.now()/2000 + this.val) * 0.15;
    </div>
  </div>
</section>

<script>
  function switchAuctionTab(tabId) {
    document.querySelectorAll('.auctions-tab-btn').forEach(btn => btn.classList.remove('active'));
    event.currentTarget.classList.add('active');
    
    document.querySelectorAll('.auctions-tab-content').forEach(content => content.classList.remove('active'));
<script>
  function switchAuctionTab(tabId) {
    document.querySelectorAll('.auctions-tab-btn').forEach(btn => btn.classList.remove('active'));
    event.currentTarget.classList.add('active');
    
    document.querySelectorAll('.auctions-tab-content').forEach(content => content.classList.remove('active'));
    document.getElementById('tab-content-' + tabId).classList.add('active');
    
    // Update swiper
                  <div class="ac-price-val"><?= number_format($a['current_price'] ?? $a['starting_price'] ?? 0) ?> <span class="ac-price-currency">ر.س</span></div>
                </div>
                <div style="text-align:left;">
                   <div style="font-size:11px; color:var(--info); font-weight:800; margin-bottom:4px"><i class="ph ph-lightning"></i> سعر فوري</div>
                </div>
              </div>

              <!-- Countdown Timer -->
              <div class="ac-timer-box">
                <span class="ac-timer-label">ينتهي خلال:</span>
            <!-- Search input & actions in one row for mobile -->
            <div class="hero-search-main-row">
              <div class="search-field" style="flex: 1.5; ">
                <label style="color: var(--primary);"><i class="ph ph-magnifying-glass"></i> كلمة البحث</label>
                <input type="text" name="search" placeholder="ابحث عن سيارات، مزادات..." id="quickSearch">
              </div>
              
              <div class="search-actions" style="display: flex; gap: 8px; align-items: center; padding-right: 12px; border-right: 1px solid rgba(255,255,255,0.1);">
                <button type="button" class="btn-filter-icon" title="تصفية متقدمة" onclick="window.location.href='auctions.php?type=instant'" style="background: transparent; border: none; color: var(--primary); font-size: 28px; cursor: pointer; display: flex; align-items: center; justify-content: center; padding: 0 10px; transition: transform 0.3s ease;">
                  <i class="ph ph-faders"></i>
                </button>
                <button type="submit" class="btn-search-stroke" title="بحث" style="width: 50px; height: 50px; border-radius: 50%; padding: 0; display: flex; align-items: center; justify-content: center; font-size: 24px; background: transparent; border: 2px solid var(--primary); color: var(--primary); cursor: pointer; transition: all 0.3s ease;">
                  <i class="ph ph-magnifying-glass"></i>
                </button>
              </div>
            
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
        <h2 style="font-size:32px; font-weight:900; color:var(--text-dark); margin-bottom:12px;">كيف تبدأ كـ <span style="color:var(--primary);">مشتري؟</span></h2>
        <p style="color:var(--text-muted); font-size:16px;">للمشترين الأفراد والشركات المعتمدة</p>
      </div>

      <div class="browser-tabs-container">
        <div class="browser-tabs" id="buyer-tabs">
          <button class="b-tab active" onclick="switchHiwTab(event, 'buyer', 1)">1. التسجيل</button>
          <button class="b-tab" onclick="switchHiwTab(event, 'buyer', 2)">2. شحن المحفظة</button>

    const tabs = tabsContainer.querySelectorAll('.b-tab');
    tabs.forEach(t => t.classList.remove('active'));
    e.currentTarget.classList.add('active');

    const contents = document.querySelectorAll(`[id^="${type}-step-"]`);
    contents.forEach(c => {
      c.style.display = 'none';
      c.classList.remove('active');
    });

    const activeContent = document.getElementById
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
            </div>

            <!-- Mobile toggle arrow -->
            <div class="hero-search-toggle" onclick="document.getElementById('heroSearchForm').classList.toggle('expanded')">
              <i class="ph ph-caret-down"></i>
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
            </div>
            <div style="flex: 1; min-width: 300px;">
              <img src="https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=600&q=80" style="width:100%; border-radius: var(--radius-md); aspect-ratio: 4/3; object-fit: cover;" alt="المزايدة والفوز" loading="lazy">
            </div>
          </div>
        <p style="color:rgba(255,255,255,0.6); font-size:16px;">لشركات التأجير والأساطيل</p>
      </div>

      <div class="browser-tabs-container">
        <div class="browser-tabs" id="seller-tabs">
          <button class="b-tab active" onclick="switchHiwTab(event, 'seller', 1)" style="background:rgba(255,255,255,0.05); color:rgba(255,255,255,0.5);">1. الاعتماد</button>
          <button class="b-tab" onclick="switchHiwTab(event, 'seller', 2)" style="background:rgba(255,255,255,0.05); color:rgba(255,255,255,0.5);">2. الاشتراك</button>
          <button class="b-tab" onclick="switchHiwTab(event, 'seller', 3)" style="background:rgba(255,255,255,0.05); color:rgba(255,255,255,0.5);">3. الإدراج</button>
        </div>
        <div class="browser-content" style="background:rgba(255,255,255,0.03); border-radius:24px; padding:40px; box-shadow:0 10px 30px 
  <div class="hiw-modal" onclick="event.stopPropagation()">
    <button class="hiw-modal-close" onclick="closeHiwModal()"><i class="ph ph-x"></i></button>
    <div id="hiwModalContent"></div>
  </div>
</div>

<style>
/* Browser Tabs Styles */
.browser-tabs-container {
  max-width: 1000px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
            <div style="flex: 1; min-width: 300px;">
              <i class="ph ph-buildings" style="font-size: 48px; color: var(--primary); margin-bottom: 16px;"></i>
              <h3 style="font-size:24px; font-weight:800; margin-bottom:16px;">توثيق الشركة والاعتماد</h3>
              <p style="color:var(--text-muted); font-size:15px; line-height:1.8; margin-bottom: 24px;">سجّل شركتك وأرفق السجل التجاري ليتم تدقيقها واعتماد حسابك كبائع موثوق خلال 48 ساعة عمل.</p>
              <button class="btn btn-outline" onclick="openHiwModal('seller-reg')" style="color:var(--primary); border-color:#e2e8f0; background:transparent;" style="white-space: nowrap; font-size: 14px; padding: 10px 20px;"><i class="ph ph-info"></i> آلية توثيق الشركة</button>
            </div>
            <div style="flex: 1; min-width: 300px;">
              <img src="https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=600&q=80" style="width:100%; border-radius: var(--radius-md); aspect-ratio: 4/3; object-fit: cover;" alt="توثيق الشركة" loading="lazy">
            </div>
          </div>

          <div id="seller-step-2" class="hiw-tab-content" style="display:none; gap:40px; align-items:center; flex-wrap:wrap;">
            <div style="flex: 1; min-width: 300px;">
              <i class="ph ph-package" style="font-size: 48px; color: var(--primary); margin-bottom: 16px;"></i>
              <h3 style="font-size:24px; font-weight:800; margin-bottom:16px;">اختيار 
            </div>
            <div style="flex: 1; min-width: 300px;">
  display: inline-flex;
            <div style="flex: 1; min-width: 300px;">
              <img src="https://images.unsplash.com/photo-1542744173-8e7e53415bb0?w=600&q=80" style="width:100%; border-radius: var(--radius-md); aspect-ratio: 4/3; object-fit: cover;" alt="شحن الحساب" loading="lazy">
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
    const tabsContainer = document.getElementById(type + '-ta
  z-index: 2;
}
.hiw-tab {
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
.hiw-modal-steps { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 16px;
    alert: null
  }
};

function openHiwModal(key) {
  const data = hiwModals[key];
  if (!data) return;
  const stepsHtml = data.steps.map((s, i) => `
    <li>
      <div class="hiw-modal-step-badge">${i+1}</div>
      <div>${s}</div>
    </li>`).join('');
  const alertHtml = data.alert ? `<div class="hiw-modal-alert">${data.alert}</div>` : '';
  document.getElementById('hiwModalContent').innerHTML = `
    <h4>${data.title}</h4>
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
  <div style="position: sticky; top: 0; min-height: 100vh; display: flex; align-items: center; background:#f1f5f9; color: var(--text-dark); z-index: 2; padding: 100p
          <button class="b-tab active" onclick="switchHiwTab(event, 'buyer', 1)">1. التسجيل</button>
          <button class="b-tab" onclick="switchHiwTab(event, 'buyer', 2)">2. المحفظة</button>
          <button class="b-tab" onclick="switchHiwTab(event, 'buyer', 3)">3. المزايدة</button>
        </div>
        <div class="browser-content" style="background:#fff; border-radius:24px; padding:40px; box-shadow:none;">
          
          <div id="buyer-step-1" class="hiw-tab-content active" style="display:flex; gap:40px; align-items:center; flex-wrap:wrap;">
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
        
        <div class="service-content" style="text-align: center; display: flex; flex-direction: column; align-items: center; position:relative; z-index:2;">
          <i class="ph ph-shield-check icon-grad"></i>
          <h3 style="font-size:22px; font-weight:800; color:#fff; margin-bottom:12px;">بيئة موثقة بالكامل</h3>
          <p style="color:rgba(255,255,255,0.7); font-size:15px; line-height:1.6;">نحن نتحقق من هوية المشترين والبائعين عبر تكامل مباشر مع بوابة النفاذ الوطني الموحد لضمان جدية المزايدات.</p>
        </div>
      </div>
      
      <div class="service-flex-card-new service-card-3 reveal" style="transition-delay:0.2s; background: #0f1520;">
        <div class="service-bg-fade" style="background-image:url('https://images.unsplash.com/photo-1551281216-ff2bb5ddc5eb?w=600&q=80');"></div>
        
        <div class="service-content" style="text-align: center; display: flex; flex-direction: column; align-items: center; position:relative; z-index:2;">
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
    <div id="hiwModalContent"></div>
  </div>
</div>

<style>
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
  position
        loop: true,
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
  0% { box-shadow: 0 0 0 0 rgba(27, 201, 118, 0.4); }
  70% { box-shadow: 0 0 0 10px rgba(27, 201, 118, 0); }
  100% { box-shadow: 0 0 0 0 rgba(27, 201, 118, 0); }
}
.hiw-info-icon { background: transparent !important; border: none !important; 
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
  /* Ensure HIW images are fully visible */
  .hiw-image img { object-fit: contain !important; padding: 10px; }

  /* BPC Smart Auction Services Cards */
  .bpc-card {
    height: auto !important;
    min-height: unset !important;
    padding: 24px 30px !important;


























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
      

const hiwModals = {
  'buyer-reg': { title: 'كيف أسجّل كمشترٍ؟', sub: 'يتم التسجيل في 3 خطوات عبر منصة النفاذ الوطني', steps: ['اضغط زر <strong>تسجيل الدخول</strong>، ثم <strong>إنشاء حساب جديد</strong>.', 'أدخل السجل الوطني. ستُحوَّل إلى <strong>النفاذ الوطني الموحّد</strong> لتوثيق هويتك.', 'أكمل بياناتك الشخصية. سيُفعَّل حسابك فوراً.'], alert: null },
  'buyer-wallet': { title: 'كيف أشحن محفظتي؟', sub: 'من الصفحة الشخصية → المحفظة', steps: ['اضغط على <strong>"المحفظة"</strong> ثم <strong>"المحفظة"</strong>.', 'اختر طريقة الدفع: <strong>مدى / Visa / Apple Pay</strong>.', 'أدخل المبلغ، أكمل الدفع، وسيُضاف الرصيد فوراً.'], alert: '<strong>مهم:</strong> المبالغ تُسترد لنفس الحساب البنكي فقط.' },
  'buyer-bid': { title: 'كيف أسجّل في مزاد؟', sub: 'بعد اختيار المزاد، اتّبع الآتي', steps: ['اضغط على <strong>"سجّل في المزاد"</strong>.', 'حدّد صفتك: <strong>أصيل</strong> / <strong>وكيل</strong> / <strong>منشأة</strong>.', 'أكّد حجز مبلغ الدخول من محفظتك.', 'ادخل غرفة المزاد الحية وزايد بـ <strong>"زايد الآن"</strong>.'], alert: '<strong>ملاحظة:</strong> التسجيل يتم لكل مزا
        <div style="display:flex; flex-direction:column; gap:24px;">
          <div style="display:flex; align-items:flex-start; gap:16px;">
            <div style="width:50px; height:50px; border-radius:12px; background:var(--primary-light); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:24px; flex-shrink:0;">
              <i class="ph-fill ph-phone-call"></i>
            </div>
            <div>
              <div style="f
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

<!-- Footer template -->
      </div>
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
  /* Ensure HIW images are fully visible */
  .hiw-image img { object-fit: contain !important; padding: 10px; }

  /* BPC Smart Auction Services Cards */
  .bpc-card {
    height: auto !important;
    min-height: unset !important;
    padding: 40px !important;
    border-radius: 30px !important;
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 20px;
    text-align: right;
    position: relative;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    transform: scale(1);
    z-index: 1;
  }
  .bpc-card:hover {
    transform: scale(1.03) translateY(-5px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.4);
    z-index: 10;
  }
  .bpc-card .bpc-icon {
    transition: color 0.3s ease;
    flex-shrink: 0;
  }
  .bpc-card:hover .bpc-icon {
    color: #fff !important;
  }
  .bpc-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    position: relative;
    z-index: 2;
  }
  /* Faded background on the left side of card */
  .bpc-card::before {
    cont
      </div>
      
      <div class="service-flex-card-new service-card-3 reveal" style="transition-delay:0.2s; background: url('https://images.unsplash.com/photo-1583121274602-3e2820c69888?w=800&q=80') center/cover no-repeat;">
        
        
        <div style="position:absolute; inset:0; background:linear-gradient(to right, rgba(6,12,22,0.95), rgba(14,165,233,0.3)); z-index:1; border-radius:inherit; pointer-events:none;"></div>
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
<section class="stats-section reveal" style="background-image: url('https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?w=1600&q=80'); background-attachment: fixed; background-size: cover; background-position: center center; padding: 120px 0 160px; box-shadow: inset 0 0 100px rgba(0,0,0,0.8);">
  <div style="position: absolute; top:0; left:0; right:0; bottom:0; background: rgba(6, 12, 22, 0.9); z-index: 0;"></div>
  
  <div class="container" style="position: relative; z-index: 1;">
    <div style="text-

























































              <div style="font-size:16px; color:var(--text-dark); font-weight:400; font-family:var(--font-en)" dir="ltr">+20 10 6644 2622</div>
            </div>
          </div>
          
          <div style="display:flex; align-items:flex-start; gap:16px;">
            <div style="width:50px; height:50px; border-radius:12px; background:rgba(14, 165, 233, 0.1); color:#0ea5e9; display:flex; align-items:center; justify-content:center; font-size:24px; flex-shrink:0;">
              <i class="ph-fill ph-envelope-simple"></i>
            </div>
            <div>
              <div style="font-size:13px; color:var(--text-muted); margin-bottom:4px; font-weight:700">البريد الإلكتروني</div>
              <div style="font-size:16px; color:var(--text-dark); font-weight:400; font-family:var(--font-en)">info@bearand.com</div>
            </div>
          </div>
          
          <div style="display:flex; align-items:flex-start; gap:16px;">
            <div style="width:50px; height:50px; border-radius:12px; background:rgba(168, 85, 247, 0.1); color:#a855f7; display:flex; align-items:center; justify-content:center; font-size:24px; flex-shrink:0;">
              <i class="ph-fill ph-map-pin"></i>
            </div>
            <div>
              <div style="font-size:13px; color:var(--text-muted); margin-bottom:4px; font-weight:700">المركز الرئيسي</div>
              <div style="font-size:14.5px; color:var(--text-dark); font-weight:400;">برج المملكة، طريق الملك فهد، الرياض، المملكة العربية السعودية</div>
            </div>
          </div>
        </div>
      </div>



































































































































































        clock.querySelector('[data-unit="secs"]').innerText = secs.toString().padStart(2, '0');
      });
    }, 1000);

  });
</script>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const counters = document.querySelectorAll('.counter-up');
    const speed = 100;
    
    const animateCounters = (entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counter = entry.target;
                const target = +counter.getAttribute('data-count');
                let count = 0;
                
                const updateCount = () => {
                    const inc = target / speed;
                    if (count < target) {
                        count += inc;
                        counter.innerText = Math.ceil(count).toLocaleString('en-US');
                        setTimeout(updateCount, 20);
                    } else {
                        // format with K if > 1000 and originally was 12000
                        if (target >= 1000) {
                            counter.innerText = Math.floor(target/1000) + 'K';
                        } else {
                            counter.innerText = target.toLocaleString('en-US');
                        }
                    }
                };
                updateCount();
                observer.unobserve(counter);
            }
        });
    };
    
    const obs = new IntersectionObserver(animateCounters, { threshold: 0.5 });
    counters.forEach(c => obs.observe(c));
});
</script>
</body>
</html>

