<?php
require_once 'config.php';

// Pagination setup
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Filters
$search_query = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$make_filter = isset($_GET['make']) ? $_GET['make'] : [];
$type_filter = isset($_GET['type']) ? sanitize($_GET['type']) : 'live';
$year_min = isset($_GET['year_min']) ? intval($_GET['year_min']) : '';
$year_max = isset($_GET['year_max']) ? intval($_GET['year_max']) : '';
$price_min = isset($_GET['price_min']) ? intval($_GET['price_min']) : '';
$price_max = isset($_GET['price_max']) ? intval($_GET['price_max']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : [];

$items = [];
$total_items = 0;

if ($type_filter === 'live') {
    // 1. Live Auctions => Display EVENTS
    $all_events = [];
    if ($db_connected) {
        $res = $conn->query("SELECT * FROM auction_events");
        if ($res) while ($r = $res->fetch_assoc()) $all_events[] = $r;
    }
    if (empty($all_events)) {
        $all_events = getMockEvents();
    }
    
    // Filter Events
    $filtered = array_filter($all_events, function($e) use ($search_query, $status_filter) {
        if (!empty($search_query) && mb_strpos(mb_strtolower($e['title']), mb_strtolower($search_query)) === false) {
            return false;
        }
        if (!empty($status_filter) && is_array($status_filter) && !in_array($e['status'], $status_filter)) {
            return false;
        }
        return true;
    });
    
    $total_items = count($filtered);
    $items = array_slice($filtered, $offset, $limit);
} else {
    // 2. Instant Purchase => Display VEHICLES
    $all_auctions = [];
    if ($db_connected) {
        $res = $conn->query("SELECT a.*, v.make, v.model, v.year, v.mileage, v.city, v.image_url 
                             FROM auctions a JOIN vehicles v ON a.vehicle_id = v.id 
                             WHERE a.type='instant'");
        if ($res) while ($r = $res->fetch_assoc()) $all_auctions[] = $r;
    }
    if (empty($all_auctions) || count($all_auctions) < 10) {
        // Fallback to mock
        $all_mock = function_exists('getMockAuctions') ? getMockAuctions(100) : [];
        $all_auctions = array_filter($all_mock, function($a){ return ($a['type']??'') === 'instant'; });
    }
    
    // Filter Vehicles
    $filtered = array_filter($all_auctions, function($a) use ($search_query, $make_filter, $year_min, $year_max, $price_min, $price_max) {
        if (!empty($search_query)) {
            $search_lower = mb_strtolower($search_query);
            $title_lower = mb_strtolower($a['title'] ?? ($a['make'].' '.$a['model']));
            if (mb_strpos($title_lower, $search_lower) === false) return false;
        }
        if (!empty($make_filter) && is_array($make_filter) && !in_array($a['make'], $make_filter)) return false;
        
        $year = intval($a['year'] ?? 0);
        if ($year_min && $year < $year_min) return false;
        if ($year_max && $year > $year_max) return false;
        
        $price = intval($a['current_price'] ?? $a['starting_price'] ?? 0);
        if ($price_min && $price < $price_min) return false;
        if ($price_max && $price > $price_max) return false;
        
        return true;
    });
    
    $total_items = count($filtered);
    $items = array_slice($filtered, $offset, $limit);
}

$total_pages = ceil($total_items / $limit);

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>قاعة المزادات | FleetX</title>
  <link rel="stylesheet" href="/assets/css/fleetx.css">
  <style>
    /* Dark Premium Hero Match */
    .auctions-hero {
      position: relative;
      background-color: var(--bg-dark);
      background-image: url('https://images.unsplash.com/photo-1555215695-3004980ad54e?w=1600&q=80');
      background-size: cover;
      background-position: center;
      padding: 140px 0 160px;
      overflow: hidden;
      border-radius: 0 0 60px 60px;
    }
    .auctions-hero::before {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(to right, rgba(6, 12, 22, 0.95) 0%, rgba(6, 12, 22, 0.8) 100%);
    }
    .hero-content-wrapper {
      position: relative;
      z-index: 2;
      text-align: center;
      max-width: 800px;
      margin: 0 auto;
    }
    .hero-title-main {
      font-size: 48px;
      font-weight: 900;
      color: #fff;
      margin-bottom: 16px;
      text-shadow: 0 4px 20px rgba(0,0,0,0.5);
    }
    .hero-subtitle-main {
      font-size: 18px;
      color: rgba(255,255,255,0.7);
      margin-bottom: 40px;
    }

    /* Search Bar */
    .search-bar-wrapper {
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.1);
      backdrop-filter: blur(10px);
      border-radius: 40px;
      padding: 8px;
      display: flex;
      align-items: center;
      box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    }
    .search-input-dark {
      flex: 1;
      background: transparent;
      border: none;
      padding: 0 24px;
      color: #fff;
      font-size: 16px;
      font-family: var(--font-ar);
      outline: none;
    }
    .search-input-dark::placeholder {
      color: rgba(255,255,255,0.4);
    }
    .btn-search-dark {
      background: var(--primary-gradient);
      color: #fff;
      border: none;
      padding: 14px 40px;
      border-radius: 30px;
      font-weight: 800;
      font-size: 16px;
      display: flex;
      align-items: center;
      gap: 10px;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    .btn-search-dark:hover {
      transform: scale(1.05);
      box-shadow: 0 0 20px rgba(14,165,233,0.4);
    }

    /* Type Switcher Tabs */
    .type-tabs {
      display: flex;
      justify-content: center;
      gap: 12px;
      margin-bottom: 30px;
    }
    .type-tab {
      background: transparent;
      border: 1px solid rgba(255,255,255,0.2);
      color: #fff;
      padding: 10px 24px;
      border-radius: 30px;
      font-size: 15px;
      font-weight: 700;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.3s ease;
    }
    .type-tab.active {
      background: var(--primary-gradient);
      border-color: transparent;
    }
    .type-tab:hover:not(.active) {
      background: rgba(255,255,255,0.1);
    }

    /* Layout */
    .layout-grid {
      display: grid;
      grid-template-columns: 280px 1fr;
      gap: 30px;
      margin-top: -60px;
      position: relative;
      z-index: 10;
      padding-bottom: 100px;
    }
    
    .sidebar-card {
      background: #fff;
      border-radius: 20px;
      padding: 24px;
      box-shadow: 0 10px 40px rgba(0,0,0,0.08);
      position: sticky;
      top: 100px;
    }
    
    .results-top-bar {
      background: #fff;
      border-radius: 20px;
      padding: 16px 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 10px 40px rgba(0,0,0,0.08);
      margin-bottom: 24px;
    }

    .filter-group { margin-bottom: 24px; }
    .filter-group:last-child { margin-bottom: 0; }
    .filter-title { font-size: 16px; font-weight: 800; color: var(--text-dark); margin-bottom: 16px; }
    .filter-label { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; cursor: pointer; color: var(--text-dark); font-weight: 600; }
    
    .form-control-light {
      width: 100%;
      background: #f8fafc;
      border: 1px solid var(--border-light);
      padding: 12px;
      border-radius: 12px;
      font-family: var(--font-ar);
      outline: none;
      transition: border 0.3s;
    }
    .form-control-light:focus { border-color: var(--primary); }

    .auctions-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 24px;
    }
    .auction-card { background: #fff !important; }
    
    @media (max-width: 992px) {
      .layout-grid { grid-template-columns: 1fr; margin-top: 20px; }
      .sidebar-card { position: static; }
      .auctions-hero { padding: 100px 0; border-radius: 0 0 30px 30px; }
    }
  </style>
</head>
<body style="background: var(--bg-light);">

<?php include 'includes/navbar.php'; ?>

<!-- Hero Section Match -->
<section class="auctions-hero">
  <div class="container hero-content-wrapper">


    <h1 class="hero-title-main">قاعة المزادات و المبيعات الفورية</h1>
    <p class="hero-subtitle-main">تصفح فعاليات مزادات أساطيل السيارات الكبرى للشركات والجهات المعتمدة في المملكة.</p>

    <form action="" method="GET">
      <input type="hidden" name="type" value="<?= $type_filter ?>">
      <div class="search-bar-wrapper">
        <input type="text" name="search" class="search-input-dark" placeholder="ابحث باسم المزاد، نوع المركبة، أو اسم الشركة البائعة..." value="<?= htmlspecialchars($search_query) ?>">
        <button type="submit" class="btn-search-dark"><i class="ph ph-magnifying-glass"></i> بحث</button>
      </div>
    </form>
  </div>
</section>

<div class="container">
  <div class="layout-grid">
    
    <!-- Sidebar Filters -->
    <div>
      <aside class="sidebar-card">
        <form id="sideFilterForm" method="GET" action="">
          <input type="hidden" name="type" value="<?= $type_filter ?>">
          <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
          
          <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border-light); padding-bottom:16px; margin-bottom:24px;">
            <h3 style="font-size:18px; font-weight:900; margin:0;">تصفية الفعاليات</h3>
            <a href="auctions.php?type=<?= $type_filter ?>" style="font-size:13px; color:var(--primary); font-weight:700">إعادة ضبط</a>
          </div>

          <?php if ($type_filter === 'live'): ?>
            <div class="filter-group">
              <h4 class="filter-title">حالة المزاد</h4>
              <?php 
                $statuses = ['active' => 'جاري', 'upcoming' => 'قادم', 'ended' => 'منتهي'];
                foreach($statuses as $k => $l):
              ?>
              <label class="filter-label">
                <input type="checkbox" name="status[]" value="<?= $k ?>" <?= (is_array($status_filter) && in_array($k, $status_filter))?'checked':'' ?> onchange="document.getElementById('sideFilterForm').submit()" style="width:20px; height:20px; accent-color:var(--primary);">
                <span><?= $l ?></span>
              </label>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="filter-group">
              <h4 class="filter-title">الشركة الصانعة</h4>
              <select name="make[]" class="form-control-light" onchange="document.getElementById('sideFilterForm').submit()">
                <option value="">الكل</option>
                <?php foreach(['Toyota', 'Hyundai', 'Kia', 'Nissan', 'Ford', 'BMW', 'Mercedes'] as $m): ?>
                <option value="<?= $m ?>" <?= (is_array($make_filter) && in_array($m, $make_filter))?'selected':'' ?>><?= $m ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="filter-group">
              <h4 class="filter-title">سنة الصنع</h4>
              <div style="display:flex; gap:10px;">
                <input type="number" name="year_min" value="<?= htmlspecialchars($year_min ?: '') ?>" placeholder="من" class="form-control-light" onchange="document.getElementById('sideFilterForm').submit()">
                <input type="number" name="year_max" value="<?= htmlspecialchars($year_max ?: '') ?>" placeholder="إلى" class="form-control-light" onchange="document.getElementById('sideFilterForm').submit()">
              </div>
            </div>

            <div class="filter-group">
              <h4 class="filter-title">السعر (ر.س)</h4>
              <div style="display:flex; gap:10px;">
                <input type="number" name="price_min" value="<?= htmlspecialchars($price_min ?: '') ?>" placeholder="من" class="form-control-light" onchange="document.getElementById('sideFilterForm').submit()">
                <input type="number" name="price_max" value="<?= htmlspecialchars($price_max ?: '') ?>" placeholder="إلى" class="form-control-light" onchange="document.getElementById('sideFilterForm').submit()">
              </div>
            </div>
          <?php endif; ?>
        </form>
      </aside>
    </div>

    <!-- Main Results -->
    <div>
      <div class="results-top-bar">
        <div style="font-weight:800; color:var(--text-dark);">
          إجمالي الفعاليات النشطة: <span style="color:var(--primary); font-family:var(--font-en); font-size:18px;"><?= $total_items ?></span> <?= $type_filter==='live'?'مزاداً':'مركبة' ?>
        </div>
        <div style="color:var(--text-muted); font-size:14px; font-weight:700;">
          الصفحة <?= $page ?> من <?= max(1, $total_pages) ?>
        </div>
      </div>

      <?php if(count($items) === 0): ?>
        <div style="text-align:center; padding:80px 20px; background:#fff; border-radius:20px; box-shadow: 0 10px 40px rgba(0,0,0,0.04);">
          <i class="ph ph-magnifying-glass" style="font-size:64px; color:var(--text-light-muted); margin-bottom:16px;"></i>
          <h3 style="font-size:22px; font-weight:900; color:var(--text-dark);">لم نتمكن من العثور على نتائج</h3>
          <p style="color:var(--text-muted); margin-top:8px;">حاول تعديل فلاتر البحث للحصول على نتائج أكثر دقة.</p>
        </div>
      <?php else: ?>
        <div class="auctions-grid">
          <?php foreach($items as $item): ?>
            
            <?php if ($type_filter === 'live'): 
              $st = ['label'=>'جاري', 'bg'=>'#1bc976', 'hex'=>'#1bc976', 'color'=>'#fff'];
              if(($item['status']??'')==='upcoming') $st=['label'=>'قادم', 'bg'=>'#8b5cf6', 'hex'=>'#8b5cf6', 'color'=>'#fff'];
              if(($item['status']??'')==='ended') $st=['label'=>'منتهي', 'bg'=>'#94a3b8', 'hex'=>'#94a3b8', 'color'=>'#fff'];
              // Get timer safely
              $timerData = function_exists('timeLeft') ? timeLeft($item['end_time'] ?? '+1 day') : ['days'=>0,'hours'=>0,'mins'=>0,'secs'=>0];
            ?>
              <div class="auction-card animate-card" data-id="<?= $item['id'] ?>" onclick="window.location.href='/event.php?id=<?= $item['id'] ?>'" style="cursor: pointer; border:1px solid var(--border-light);">
                <div class="card-fav"><i class="ph ph-heart"></i></div>
                <div class="card-badges-container">
                  <div class="badge-item" style="color: <?= $st['color'] ?>; background: <?= $st['bg'] ?>; font-weight: 700; padding: 4px 10px; border-radius: var(--radius-sm); font-size: 11px;">
                    <span style="display:inline-block; width:6px; height:6px; border-radius:50%; background:<?= $st['color'] ?>; margin-left:4px; vertical-align:middle;"></span>
                    <?= $st['label'] ?>
                  </div>
                </div>
                <div class="ac-img-wrap">
                  <img src="<?= $item['image_url'] ?? 'https://images.unsplash.com/photo-1555215695-3004980ad54e?w=800&q=80' ?>" alt="<?= sanitize($item['title']??'') ?>" loading="lazy">
                </div>
                <div class="ac-body">
                  <h3 class="ac-title"><?= sanitize($item['title']??'مزاد') ?></h3>
                  
                  <div class="ac-stats-row">
                    <div class="ac-stat-cell">
                      <span class="label">البائع</span>
                      <span><?= sanitize($item['seller'] ?? 'الوطنية للتأجير') ?></span>
                    </div>
                    <div class="ac-stat-cell">
                      <span class="label">الموقع</span>
                      <span><?= sanitize($item['city'] ?? 'الرياض') ?></span>
                    </div>
                    <div class="ac-stat-cell">
                      <span class="label">المركبات</span>
                      <span class="font-en">+<?= rand(20, 150) ?></span>
                    </div>
                  </div>
                  
                  <div class="ac-price-row">
                    <div>
                      <div class="ac-price-label">المزاد الحالي</div>
                      <div class="ac-price-val"><?= number_format($item['current_price'] ?? $item['starting_price'] ?? 0) ?> <span class="ac-price-currency">ر.س</span></div>
                    </div>
                    <div style="text-align:left;">
                       <div style="font-size:11px; color:var(--text-dark); font-weight:800; margin-bottom:4px"><i class="ph ph-activity"></i> مزاد <?= $st['label'] ?></div>
                    </div>
                  </div>

                  <div class="ac-timer-box">
                    <?php if($st['label'] === 'منتهي'): ?>
                      <span class="ac-timer-label">المزاد منتهي</span>
                      <div class="ac-timer-val"><div>00</div><span>:</span><div>00</div><span>:</span><div>00</div><span>:</span><div>00</div></div>
                    <?php else: ?>
                      <span class="ac-timer-label">ينتهي خلال:</span>
                      <div class="ac-timer-val" data-endtime="<?= strtotime($item['end_time'] ?? '+1 day') ?>">
                        <div><?= str_pad($timerData['days'], 2, "0", STR_PAD_LEFT) ?></div><span>:</span>
                        <div><?= str_pad($timerData['hours'], 2, "0", STR_PAD_LEFT) ?></div><span>:</span>
                        <div><?= str_pad($timerData['mins'], 2, "0", STR_PAD_LEFT) ?></div><span>:</span>
                        <div><?= str_pad($timerData['secs'], 2, "0", STR_PAD_LEFT) ?></div>
                      </div>
                    <?php endif; ?>
                  </div>
                  
                  <div class="ac-actions">
                    <a href="/event.php?id=<?= $item['id'] ?>" class="btn btn-primary" style="width:100%; justify-content:center; border-radius:var(--radius-round)">دخول قاعة المزاد <i class="ph-fill ph-gavel"></i></a>
                  </div>
                </div>
              </div>

            <?php else: 
              $title_car = $item['title'] ?? (($item['make']??'').' '.($item['model']??'').' '.($item['year']??''));
              $img = (!empty($item['image_url']) && strlen($item['image_url']) > 4) ? $item['image_url'] : 'https://images.unsplash.com/photo-1580273916550-e323be2ae537?w=800&q=80';
            ?>
              <div class="auction-card animate-card" data-id="<?= $item['id'] ?>" onclick="window.location.href='/vehicle-details.php?id=<?= $item['id'] ?>'" style="cursor: pointer; border:1px solid var(--border-light);">
                <div class="card-fav"><i class="ph ph-heart"></i></div>
                <div class="card-badges-container">
                  <div class="badge-item" style="color: #fff; background: #1bc976; font-weight: 700; padding: 4px 10px; border-radius: var(--radius-sm); font-size: 11px;">شراء فوري</div>
                </div>
                <div class="ac-img-wrap">
                  <img src="<?= $img ?>" alt="<?= sanitize($title_car) ?>" loading="lazy">
                </div>
                <div class="ac-body">
                  <h3 class="ac-title"><?= sanitize($title_car) ?></h3>
                  
                  <div class="ac-stats-row">
                    <div class="ac-stat-cell">
                      <span class="label">المدينة</span>
                      <span><?= sanitize($item['city'] ?? 'الرياض') ?></span>
                    </div>
                    <div class="ac-stat-cell">
                      <span class="label">الممشى</span>
                      <span class="font-en"><?= number_format($item['mileage'] ?? 0) ?> KM</span>
                    </div>
                    <div class="ac-stat-cell">
                      <span class="label">السنة</span>
                      <span class="font-en"><?= $item['year'] ?? '2023' ?></span>
                    </div>
                  </div>
                  
                  <div class="ac-price-row">
                    <div>
                      <div class="ac-price-label">السعر المطلوب</div>
                      <div class="ac-price-val"><?= number_format($item['current_price'] ?? $item['starting_price'] ?? 0) ?> <span class="ac-price-currency">ر.س</span></div>
                    </div>
                    <div style="text-align:left;">
                       <div style="font-size:11px; color:var(--text-dark); font-weight:800; margin-bottom:4px"><i class="ph ph-lightning"></i> سعر فوري</div>
                    </div>
                  </div>

                  <div class="ac-timer-box" style="display: flex; justify-content: space-between; align-items: center; background: rgba(14, 165, 233, 0.05); padding: 12px; border-radius: var(--radius-md);">
                    <span class="ac-timer-label" style="font-size: 13px;">تاريخ الانتهاء:</span>
                    <div style="font-weight: 800; font-size: 13px; color: var(--text-dark);">
                      <?= date('Y-m-d', strtotime($item['end_time'] ?? '+3 days')) ?>
                    </div>
                  </div>
                  
                  <div class="ac-actions">
                    <a href="/vehicle-details.php?id=<?= $item['id'] ?>" class="btn btn-primary" style="width:100%; justify-content:center; border-radius:var(--radius-round); background:var(--primary-gradient);">شراء الآن <i class="ph ph-arrow-left"></i></a>
                  </div>
                </div>
              </div>
            <?php endif; ?>

          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      
      <?php if($total_pages > 1): ?>
        <style>
          .page-btn {
            width: 40px; height: 40px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: var(--radius-round);
            background: #fff; border: 1px solid var(--border-light);
            color: var(--text-dark); font-weight: 800; font-family: var(--font-en);
            transition: all 0.3s ease; text-decoration: none;
          }
          .page-btn:hover, .page-btn.active {
            background: var(--primary-gradient); color: #fff; border-color: transparent;
          }
        </style>
        <div style="display:flex; justify-content:center; gap:8px; margin-top:40px;">
          <?php for($i=1; $i<=$total_pages; $i++): 
            $url_params = $_GET;
            $url_params['page'] = $i;
            $page_url = '?' . http_build_query($url_params);
          ?>
            <a href="<?= $page_url ?>" class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
          <?php endfor; ?>
        </div>
      <?php endif; ?>
      
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

<!-- Scripts -->
<script src="/assets/js/fleetx.js"></script>
<script>
  // Favorites Logic
  document.querySelectorAll('.card-fav').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault(); e.stopPropagation();
      this.classList.toggle('active');
      let icon = this.querySelector('i');
      if(this.classList.contains('active')) {
        icon.classList.remove('ph'); icon.classList.add('ph-fill'); icon.style.color = '#ef4444';
      } else {
        icon.classList.remove('ph-fill'); icon.classList.add('ph'); icon.style.color = '';
      }
    });
  });
</script>
</body>
</html>