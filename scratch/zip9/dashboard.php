<?php
require_once 'config.php';
requireLogin();

$section = isset($_GET['section']) ? sanitize($_GET['section']) : 'sales';
$user_name = $_SESSION['user_name'] ?? 'مستخدم';
$role = getUserRole();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>لوحة التحكم | FleetX</title>
  <link rel="stylesheet" href="/assets/css/fleetx.css">
  <style>
    body { background: var(--bg-light); }
    
    /* Dashboard Hero Match Premium Dark Theme */
    .dash-hero {
      position: relative;
      background-color: var(--bg-dark);
      background-image: url('https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?w=1600&q=80');
      background-size: cover;
      background-position: center;
      padding: 100px 0 100px;
      margin-bottom: -60px;
      border-radius: 0 0 40px 40px;
    }
    .dash-hero::before {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(to right, rgba(6, 12, 22, 0.95) 0%, rgba(6, 12, 22, 0.8) 100%);
      border-radius: inherit;
    }
    .dash-hero-content {
      position: relative;
      z-index: 2;
      text-align: center;
    }
    .dash-hero-title {
      font-size: 36px;
      font-weight: 900;
      color: #fff;
      margin-bottom: 12px;
    }
    .dash-hero-subtitle {
      color: rgba(255,255,255,0.7);
      font-size: 16px;
    }

    .dash-container { max-width: 1300px; margin: 0 auto 60px; padding: 0 20px; display: flex; gap: 30px; position:relative; z-index:10; }
    
    .dash-sidebar {
      width: 280px;
      background: #fff;
      border-radius: var(--radius-lg);
      padding: 24px 0;
      box-shadow: 0 10px 40px rgba(0,0,0,0.08);
      height: fit-content;
      position: sticky;
      top: 100px;
    }
    .dash-user-info {
      padding: 0 24px 24px;
      border-bottom: 1px solid var(--border-light);
      margin-bottom: 16px;
      text-align: center;
    }
    .dash-user-avatar {
      width: 80px; height: 80px; border-radius: 50%; background: var(--primary-gradient); color: #fff;
      display: flex; align-items: center; justify-content: center; font-size: 36px; margin: 0 auto 16px;
      box-shadow: 0 4px 15px rgba(14, 165, 233, 0.3);
    }
    .dash-nav { list-style: none; padding: 0; margin: 0; }
    .dash-nav li a {
      display: flex; align-items: center; gap: 12px; padding: 14px 24px; color: var(--text-dark);
      font-weight: 700; transition: var(--transition); border-right: 3px solid transparent;
    }
    .dash-nav li a:hover { background: #f8fafc; color: var(--primary); }
    .dash-nav li a.active {
      background: #f0f9ff; color: var(--primary); border-color: var(--primary);
    }
    
    .dash-main { flex: 1; }
    .dash-header-bar {
      background: #fff;
      border-radius: var(--radius-lg);
      padding: 20px 24px;
      display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;
      box-shadow: 0 10px 40px rgba(0,0,0,0.08);
    }
    .dash-title { font-size: 20px; font-weight: 900; color: var(--text-dark); margin: 0; display:flex; align-items:center; gap:10px; }
    
    .dash-filters {
      background: #fff; border-radius: var(--radius-lg); padding: 20px 24px; display: flex; gap: 16px;
      box-shadow: 0 10px 40px rgba(0,0,0,0.08); margin-bottom: 24px;
    }
    .dash-filter-item { flex: 1; }
    .dash-filter-item select {
      width: 100%; padding: 14px 16px; border-radius: var(--radius-md); border: 1px solid var(--border-light);
      outline: none; font-family: var(--font-ar); color: var(--text-dark); background: #f8fafc; font-weight: 600;
    }
    
    .dash-empty {
      background: #fff; border-radius: var(--radius-lg); padding: 80px 20px; text-align: center;
      box-shadow: 0 10px 40px rgba(0,0,0,0.08);
    }
    .dash-empty h3 { color: var(--text-dark); font-size: 22px; font-weight: 900; margin: 0 0 12px; }
    .dash-empty p { color: var(--text-muted); font-size: 15px; }
    
    .btn-action-top {
      background: var(--primary-gradient); color: #fff; padding: 12px 24px; border-radius: 30px;
      font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none;
      transition: var(--transition);
    }
    .btn-action-top:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(14,165,233,0.3); }

    @media (max-width: 992px) {
      .dash-container { flex-direction: column; }
      .dash-sidebar { width: 100%; position: static; }
      .dash-filters { flex-direction: column; }
    }
  </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<!-- Dash Hero -->
<section class="dash-hero">
  <div class="container dash-hero-content">
    <h1 class="dash-hero-title">مرحباً بك، <?= $user_name ?></h1>
    <p class="dash-hero-subtitle">تحكم في جميع مزاداتك، مفضلتك، ومعاملاتك من مكان واحد</p>
  </div>
</section>

<div class="dash-container">
  
  <aside class="dash-sidebar">
    <div class="dash-user-info">
      <div class="dash-user-avatar"><i class="ph-fill ph-user"></i></div>
      <h3 style="font-size: 20px; font-weight: 900; color: var(--text-dark); margin:0 0 4px;"><?= $user_name ?></h3>
      <div style="font-size: 14px; color: var(--primary); font-weight:700; background:var(--primary-light); padding:4px 12px; border-radius:20px; display:inline-block; margin-top:8px;"><?= $role === 'seller' ? 'بائع معتمد' : 'مشتري معتمد' ?></div>
    </div>
    <ul class="dash-nav">
      <li><a href="?section=sales" class="<?= $section==='sales'?'active':'' ?>"><i class="ph ph-trend-up"></i> <?= $role === 'seller' ? 'مبيعاتي وأسطولي' : 'مبيعاتي' ?></a></li>
      <li><a href="?section=purchases" class="<?= $section==='purchases'?'active':'' ?>"><i class="ph ph-shopping-bag"></i> مشترياتي</a></li>
      <li><a href="?section=offers" class="<?= $section==='offers'?'active':'' ?>"><i class="ph ph-handshake"></i> عروضي والمزايدات</a></li>
      <li><a href="?section=favorites" class="<?= $section==='favorites'?'active':'' ?>"><i class="ph ph-heart"></i> المفضلة</a></li>
      <li><a href="?section=payments" class="<?= $section==='payments'?'active':'' ?>"><i class="ph ph-wallet"></i> المحفظة والعمليات</a></li>
      <li><a href="?section=settings" class="<?= $section==='settings'?'active':'' ?>"><i class="ph ph-gear"></i> إعدادات الحساب</a></li>
    </ul>
  </aside>
  
  <main class="dash-main">
    <div class="dash-header-bar">
      <h1 class="dash-title">
        <?php
          switch($section) {
            case 'sales': echo '<i class="ph-fill ph-trend-up" style="color:var(--primary)"></i> ' . ($role === 'seller' ? 'مبيعاتي وأسطولي' : 'مبيعاتي'); break;
            case 'purchases': echo '<i class="ph-fill ph-shopping-bag" style="color:var(--primary)"></i> مشترياتي'; break;
            case 'offers': echo '<i class="ph-fill ph-handshake" style="color:var(--primary)"></i> عروضي والمزايدات'; break;
            case 'favorites': echo '<i class="ph-fill ph-heart" style="color:var(--danger)"></i> المفضلة'; break;
            case 'payments': echo '<i class="ph-fill ph-wallet" style="color:var(--primary)"></i> المحفظة والعمليات'; break;
            case 'settings': echo '<i class="ph-fill ph-gear" style="color:var(--primary)"></i> إعدادات الحساب'; break;
          }
        ?>
      </h1>
      <?php if($role === 'seller'): ?>
      <a href="/add-auction.php" class="btn-action-top"><i class="ph ph-plus"></i> أضف مزادك</a>
      <?php endif; ?>
    </div>

    <?php if(in_array($section, ['sales', 'purchases', 'offers'])): ?>
    <div class="dash-filters">
      <div class="dash-filter-item">
        <select>
          <option value="">التصنيف</option>
          <option value="cars">مركبات</option>
          <option value="fleets">أساطيل</option>
        </select>
      </div>
      <div class="dash-filter-item">
        <select>
          <option value="">حالة الطلب</option>
          <option value="active">جاري</option>
          <option value="pending">قيد المراجعة</option>
          <option value="completed">منتهي</option>
        </select>
      </div>
      <div class="dash-filter-item">
        <select>
          <option value="">التاريخ</option>
          <option value="newest">الأحدث</option>
          <option value="oldest">الأقدم</option>
        </select>
      </div>
    </div>
    <?php endif; ?>

    <!-- CONTENT AREA -->
    <?php if ($section === 'favorites'): ?>
      <?php
        $favs = isset($_COOKIE['favorites']) ? array_filter(explode(',', $_COOKIE['favorites'])) : [];
        $fav_items = [];
        if (!empty($favs)) {
            $all_mocks = getMockAuctions(200);
            foreach ($all_mocks as $m) {
                if (in_array((string)$m['id'], $favs)) {
                    $fav_items[] = $m;
                }
            }
        }
      ?>
      
      <?php if(empty($fav_items)): ?>
        <div class="dash-empty">
          <div style="width: 100px; height: 100px; background: rgba(239, 68, 68, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
            <i class="ph-fill ph-heart" style="font-size: 48px; color: #ef4444;"></i>
          </div>
          <h3>لم تقم بإضافة أي مركبات للمفضلة بعد</h3>
          <p>تصفح صالة المزادات والمبيعات الفورية واضغط على أيقونة القلب لحفظها هنا.</p>
          <a href="/auctions.php" class="btn btn-primary" style="margin-top:24px; border-radius: 30px; padding: 12px 30px; font-weight: 800;">تصفح المركبات الآن</a>
        </div>
      <?php else: ?>
        <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px;">
          <?php foreach ($fav_items as $item): 
            $title_car = $item['title'] ?? ($item['make'].' '.$item['model'].' '.$item['year']);
            $img = (!empty($item['image_url']) && strlen($item['image_url']) > 4) ? $item['image_url'] : getCarImage($item['make']);
            $is_instant = $item['type'] === 'instant';
          ?>
            <div class="auction-card animate-card" data-id="<?= $item['id'] ?>" onclick="window.location.href='<?= $is_instant ? '/vehicle-details.php' : '/event.php' ?>?id=<?= $item['id'] ?>'" style="cursor: pointer; background:#fff">
              <div class="card-fav active" onclick="event.stopPropagation(); toggleFavorite(<?= $item['id'] ?>, this)"><i class="ph-fill ph-heart" style="color:var(--danger)"></i></div>
              <div class="ac-img-wrap">
                <img src="<?= $img ?>" alt="<?= sanitize($title_car) ?>" loading="lazy">
              </div>
              <div class="ac-body">
                <h3 class="ac-title"><?= sanitize($title_car) ?></h3>
                <div class="ac-meta">
                  <span><i class="ph ph-map-pin ph-space-left"></i><?= sanitize($item['city'] ?? 'الرياض') ?></span>
                  <span><i class="ph ph-gauge ph-space-left"></i><?= number_format($item['mileage'] ?? 0) ?> كم</span>
                </div>
                
                <div class="ac-price-row">
                  <div>
                    <div class="ac-price-label"><?= $is_instant ? 'السعر الفوري' : 'السعر الحالي' ?></div>
                    <div class="ac-price-val"><?= number_format($item['current_price'] ?? $item['starting_price'] ?? 0) ?> <span class="ac-price-currency">ر.س</span></div>
                  </div>
                </div>
                
                <div class="ac-actions">
                  <a href="<?= $is_instant ? '/vehicle-details.php' : '/event.php' ?>?id=<?= $item['id'] ?>" class="btn btn-primary" style="width:100%; justify-content:center; border-radius:var(--radius-round); <?= $is_instant ? 'background:var(--primary-gradient);' : '' ?>"><?= $is_instant ? 'شراء الآن' : 'دخول المزاد' ?></a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    <?php elseif ($section === 'payments'): ?>
      <!-- Wallet Design -->
      <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
        <div style="background: linear-gradient(135deg, #0f172a, #1e293b); border-radius: 24px; padding: 30px; color: #fff; position: relative; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
          <i class="ph-fill ph-wallet" style="font-size: 120px; position: absolute; left: -20px; bottom: -20px; opacity: 0.1;"></i>
          <h4 style="font-size: 16px; color: rgba(255,255,255,0.7); margin-bottom: 12px; font-weight: 700;">الرصيد المتاح</h4>
          <div style="font-size: 40px; font-weight: 900; font-family: var(--font-en); margin-bottom: 24px;">25,000 <span style="font-size: 18px; font-family: var(--font-ar);">ر.س</span></div>
          <div style="display: flex; gap: 12px;">
            <button class="btn" style="background: rgba(255,255,255,0.2); color: #fff; border:none; padding: 10px 20px; border-radius: 20px; font-weight: 700;">شحن الرصيد <i class="ph ph-plus"></i></button>
            <button class="btn" style="background: rgba(255,255,255,0.1); color: #fff; border:none; padding: 10px 20px; border-radius: 20px; font-weight: 700;">استرداد <i class="ph ph-arrow-down"></i></button>
          </div>
        </div>
        <div style="background: #fff; border-radius: 24px; padding: 30px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center;">
          <div style="width: 60px; height: 60px; border-radius: 50%; background: var(--success-light); color: var(--success); display: flex; align-items: center; justify-content: center; font-size: 28px; margin-bottom: 16px;">
            <i class="ph ph-shield-check"></i>
          </div>
          <h4 style="font-size: 18px; font-weight: 800; margin-bottom: 8px;">حسابك موثق ونشط</h4>
          <p style="color: var(--text-muted); font-size: 14px;">تم التحقق من بيانات النفاذ الوطني ويمكنك المزايدة بحرية.</p>
        </div>
      </div>
      <div style="background: #fff; border-radius: var(--radius-lg); padding: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.08);">
        <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid var(--border-light);">أحدث العمليات</h3>
        <div class="dash-empty" style="padding: 40px 20px; box-shadow: none; border: 1px dashed var(--border-light);">
          <i class="ph ph-receipt" style="font-size: 48px; color: var(--text-light-muted); margin-bottom:12px;"></i>
          <p>لا توجد عمليات مالية سابقة</p>
        </div>
      </div>

    <?php else: ?>
      <div class="dash-empty">
        <div style="width: 100px; height: 100px; background: rgba(14, 165, 233, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
          <i class="ph-fill ph-folder-open" style="font-size: 48px; color: var(--primary);"></i>
        </div>
        <h3>لا توجد بيانات متاحة حالياً</h3>
        <p>لا توجد <?= $section === 'sales' ? 'مزادات مضافة في هذا القسم' : 'سجلات لعرضها هنا' ?></p>
      </div>
    <?php endif; ?>

  </main>
</div>

<?php include 'includes/footer.php'; ?>
<script src="/assets/js/fleetx.js"></script>
</body>
</html>