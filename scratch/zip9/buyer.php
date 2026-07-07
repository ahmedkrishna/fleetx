<?php
require_once 'config.php';

// Force login
requireLogin();

$user_id = intval($_SESSION['user_id']);
$auctions = [];
$watchlist_items = [];

if ($db_connected) {
    // 1. Fetch active bids for buyer
    $sql_bids = "SELECT DISTINCT a.id, a.title, a.type, a.current_price, a.end_time, v.make, v.model, v.image_url, v.city, v.mileage, v.year,
                        (SELECT b2.user_id FROM bids b2 WHERE b2.auction_id = a.id ORDER BY b2.amount DESC LIMIT 1) as highest_bidder_id
                 FROM bids b
                 JOIN auctions a ON b.auction_id = a.id
                 JOIN vehicles v ON a.vehicle_id = v.id
                 WHERE b.user_id = $user_id AND a.status='active'";
    $res_bids = $conn->query($sql_bids);
    if ($res_bids) {
        while ($row = $res_bids->fetch_assoc()) {
            $auctions[] = $row;
        }
    }

    // 2. Fetch watchlist/favorites
    $sql_watch = "SELECT a.id, a.title, a.type, a.current_price, a.end_time, v.make, v.model, v.image_url, v.city, v.mileage, v.year
                  FROM watchlist w
                  JOIN auctions a ON w.auction_id = a.id
                  JOIN vehicles v ON a.vehicle_id = v.id
                  WHERE w.user_id = $user_id AND a.status='active'";
    $res_watch = $conn->query($sql_watch);
    if ($res_watch) {
        while ($row = $res_watch->fetch_assoc()) {
            $watchlist_items[] = $row;
        }
    }
    // 3. Fetch KPI stats
    $stats = ['active_bids' => 0, 'wins' => 0, 'reserved' => 0];
    
    // Count active auctions user is bidding on
    $stats['active_bids'] = count($auctions);
    
    // Wins
    $res_wins = $conn->query("SELECT COUNT(*) as c FROM auctions WHERE winner_id = $user_id");
    if ($res_wins && $w_row = $res_wins->fetch_assoc()) {
        $stats['wins'] = intval($w_row['c']);
    }
    
    // Calculate reserved amount (mock logic: 1500 SAR per active auction bid)
    $stats['reserved'] = $stats['active_bids'] * 1500;
    
} else {
    // Mock / Offline Fallback
    $all_mocks = getMockAuctions(30);
    $stats = ['active_bids' => 3, 'wins' => 12, 'reserved' => 4500];
    
    // Bids simulation
    $my_bids = [];
    if (isset($_SESSION['mock_bids']) && is_array($_SESSION['mock_bids'])) {
        foreach ($_SESSION['mock_bids'] as $mb) {
            $my_bids[$mb['auction_id']] = $mb['amount'];
        }
    }
    // Seed initial bids for the demo user if session bids are empty
    if (empty($my_bids)) {
        $my_bids = [1 => 85500, 2 => 94000, 4 => 153000];
    }
    
    foreach ($all_mocks as $a) {
        if (isset($my_bids[$a['id']])) {
            $a['current_price'] = $my_bids[$a['id']];
            $a['highest_bidder_id'] = ($a['id'] % 2 === 0) ? $user_id : ($user_id + 1); // Alternating winning/outbid
            // Force user to be winning if they bid in session
            if (isset($_SESSION['mock_bids'])) {
                foreach ($_SESSION['mock_bids'] as $mb) {
                    if ($mb['auction_id'] == $a['id']) {
                        $a['highest_bidder_id'] = $user_id;
                    }
                }
            }
            $auctions[] = $a;
        }
    }

    // Watchlist simulation
    $mock_favs = isset($_SESSION['mock_watchlist']) ? $_SESSION['mock_watchlist'] : [1, 3, 5];
    foreach ($all_mocks as $a) {
        if (in_array($a['id'], $mock_favs)) {
            $watchlist_items[] = $a;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>لوحة تحكم المشتري | FleetX</title>
  <link rel="stylesheet" href="/assets/css/fleetx.css">
  <style>
    .purchasing-power-badge {
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.15);
      padding: 24px;
      border-radius: var(--radius-lg);
      backdrop-filter: blur(12px);
      text-align: center;
      min-width: 260px;
    }
    @media (max-width: 768px) {
      .purchasing-power-badge { width: 100%; }
      .page-header .container { flex-direction: column; align-items: stretch; text-align: center; gap: 20px;}
    }
  </style>
</head>
<body class="page-inner">

<!-- Navbar template -->
<?php include 'includes/navbar.php'; ?>

<!-- Page Header -->
<header class="page-header" style="padding-bottom: 90px">
  <div class="page-header-bg" style="background-image:url('https://images.unsplash.com/photo-1552519507-da3b142c6e3d?w=1600&q=80')"></div>
  <div class="container" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:24px">
    <div>
      <div style="font-size:14px; color:var(--info); font-weight:800; text-transform:uppercase; margin-bottom:8px">بوابة المشترين المعتمدين</div>
      <h1 style="margin:0">مرحباً بك، <?= sanitize($_SESSION['user_name']) ?></h1>
      <p style="color:var(--text-light-muted); font-size:16px; margin-top:6px; max-width:500px">راقب عروضك الحية، شحن رصيد التأمين، وأدر المزايدات التلقائية الذكية.</p>
    </div>
    <!-- Purchasing Power summary -->
    <div class="purchasing-power-badge">
       <div style="font-size:12px; color:var(--text-light-muted); margin-bottom:6px">القوة الشرائية المتاحة للمزايدة</div>
       <div style="font-size:32px; font-weight:800; color:var(--info); font-family:var(--font-en)">150,000 SAR</div>
       <a href="#" style="font-size:13px; color:#fff; text-decoration:none; font-weight:700; margin-top:8px; display:inline-flex; align-items:center; gap:6px;"><i class="ph ph-credit-card"></i> إيداع مبلغ تأمين جديد</a>
    </div>
  </div>
</header>

<div class="container" style="margin-top:-50px; position:relative; z-index:10; margin-bottom:100px;">
  <div class="dashboard-grid">
    
    <!-- Left Navigation Menu -->
    <aside class="panel-sidebar">
      <div style="padding:0 28px 18px; border-bottom:1px solid var(--border-light); margin-bottom:12px;">
        <div style="font-weight:900; font-size:17px; color:var(--text-dark)">الخدمات الإلكترونية</div>
      </div>
      <a href="#" class="panel-nav-item active" onclick="switchTab(event, 'dashboard', this)"><i class="ph ph-chart-bar ph-space-left"></i> لوحة الإحصائيات</a>
      <a href="#" class="panel-nav-item" onclick="switchTab(event, 'bids', this)"><i class="ph ph-car ph-space-left"></i> مزايداتي النشطة <span class="font-en" style="margin-right:auto; font-size:11px; background:var(--primary-light); padding:4px 10px; border-radius:var(--radius-round); color:var(--primary); font-weight: 800;">3</span></a>
      <a href="#" class="panel-nav-item" onclick="switchTab(event, 'wins', this)"><i class="ph ph-trophy ph-space-left"></i> المزادات الرابحة</a>
      <a href="#" class="panel-nav-item" onclick="switchTab(event, 'wallet', this)"><i class="ph ph-coins ph-space-left"></i> المحفظة والعمليات المالية</a>
      <a href="#" class="panel-nav-item" onclick="switchTab(event, 'auto-bid', this)"><i class="ph ph-robot ph-space-left"></i> حد المزايدة التلقائية</a>
      <a href="#" class="panel-nav-item" onclick="switchTab(event, 'profile', this)"><i class="ph ph-gear ph-space-left"></i> الملف الشخصي والتوثيق</a>
      <a href="/logout.php" class="panel-nav-item" style="color:var(--danger); border-top:1px solid var(--border-light); margin-top:24px; padding-top:20px;"><i class="ph ph-sign-out ph-space-left" style="color: inherit;"></i> تسجيل الخروج</a>
    </aside>

    <!-- Main Section -->
    <main>
      <!-- Dashboard Section -->
      <div id="tab-dashboard" class="panel-section" style="display:block;">
        <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:20px; margin-bottom:30px;">
          <div class="stat-box" style="border-right: 4px solid var(--info)">
            <div>
              <div style="font-size:13px; color:var(--text-muted); font-weight:700">مزايدات جارية</div>
              <div class="val font-en"><?= $stats['active_bids'] ?></div>
            </div>
            <div style="font-size:28px; color: var(--info)"><i class="ph ph-chart-line-up"></i></div>
          </div>
          <div class="stat-box" style="border-right: 4px solid var(--warning)">
            <div>
              <div style="font-size:13px; color:var(--text-muted); font-weight:700">تأمين محجوز</div>
              <div class="val font-en"><?= number_format($stats['reserved']) ?> <span style="font-size:12px; font-weight:700">SAR</span></div>
            </div>
            <div style="font-size:28px; color: var(--warning)"><i class="ph ph-shield-check"></i></div>
          </div>
          <div class="stat-box" style="border-right: 4px solid var(--primary)">
            <div>
              <div style="font-size:13px; color:var(--text-muted); font-weight:700">سيارات تم شراؤها</div>
              <div class="val font-en"><?= $stats['wins'] ?></div>
            </div>
            <div style="font-size:28px; color: var(--primary)"><i class="ph ph-trophy"></i></div>
          </div>
        </div>

        <!-- Watchlist Panel -->
        <div class="panel-content" style="margin-top:30px">
          <h2 style="font-size:20px; margin-bottom:24px; border-bottom:1px solid var(--border-light); padding-bottom:16px">قائمة المراقبة والمفضلة</h2>
          <div id="watchlistContainer" style="display:grid; grid-template-columns:repeat(2, 1fr); gap:20px;">
            <?php if (empty($watchlist_items)): ?>
              <div style="grid-column:1/-1; text-align:center; padding:40px; color:var(--text-muted); font-size:14px; display:inline-flex; align-items:center; justify-content:center; gap:8px;">
                <i class="ph ph-heart" style="color:var(--primary)"></i> لا توجد سيارات في قائمة المفضلة حالياً. يمكنك إضافة سيارات من قاعة المزادات لمراقبتها هنا.
              </div>
            <?php else: ?>
              <?php foreach ($watchlist_items as $item): 
                  $title = $item['title'] ?? ($item['make'].' '.$item['model'].' '.$item['year']);
                  $img = getCarImage($item['make'], $item['image_url']);
              ?>
              <div class="seller-row-card" style="flex-direction: column; align-items: stretch; text-align: right; gap:12px;">
                <img src="<?= $img ?>" style="width:100%; height:130px; object-fit:cover; border-radius:var(--radius-sm);" alt="<?= sanitize($title) ?>">
                <div style="flex: 1;">
                  <h3 style="font-size:15px; font-weight:800; color:var(--text-dark)"><?= sanitize($title) ?></h3>
                  <div style="font-size:12px; color:var(--text-muted); margin-top:4px; display:inline-flex; align-items:center; gap:6px;"><i class="ph ph-map-pin" style="color:var(--primary)"></i> <?= sanitize($item['city'] ?? 'الرياض') ?> | السعر الحالي: <?= number_format($item['current_price']) ?> ر.س</div>
                  <div style="margin-top: 16px; display: flex; gap: 8px;">
                    <a href="/auction-live.php?id=<?= $item['id'] ?>" class="btn btn-primary btn-sm" style="flex: 1; text-align: center; border-radius: var(--radius-round)">دخول المزاد</a>
                    <button onclick="removeFromWatchlist(<?= $item['id'] ?>, this)" class="btn btn-outline-dark btn-sm" style="display:inline-flex; align-items:center; gap:6px; justify-content:center; border-radius: var(--radius-round)">إزالة <i class="ph ph-x-circle" style="color:var(--danger)"></i></button>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Bids Section -->
      <div id="tab-bids" class="panel-section" style="display:none;">
        <div class="panel-content">
          <h2 style="font-size:20px; margin-bottom:24px; border-bottom:1px solid var(--border-light); padding-bottom:16px">المزايدات الحية النشطة</h2>
          
          <div style="display:flex; flex-direction:column; gap:16px;">
            <?php foreach($auctions as $idx => $a): 
               $isWinning = ($a['highest_bidder_id'] == $user_id);
               $statusColor = $isWinning ? 'var(--primary)' : 'var(--danger)';
               $statusBg = $isWinning ? 'var(--primary-light)' : 'var(--danger-pale)';
               $statusText = $isWinning ? 'أنت صاحب السعر الأعلى' : 'تمت المزايدة عليك!';
            ?>
            <div class="seller-row-card">
              <img src="<?= getCarImage($a['make'], $a['image_url']) ?>" style="width:130px; height:85px; object-fit:cover; border-radius:var(--radius-sm);" alt="Car Image" loading="lazy">
              <div style="flex:1;">
                <h3 style="font-size:16px; font-weight:800; color:var(--text-dark)"><?= $a['title'] ?: ($a['make'].' '.$a['model'].' '.$a['year']) ?></h3>
                <div style="font-size:12px; color:var(--text-muted); margin-top:4px">الرقم المرجعي للمزاد: <span class="font-en">FX-<?= 2000 + $a['id'] ?></span></div>
                <div style="font-size:12px; font-weight:800; color:<?= $statusColor ?>; background:<?= $statusBg ?>; padding:6px 14px; border-radius:var(--radius-round); display:inline-flex; align-items:center; gap:6px; margin-top:8px">
                  <span style="display:inline-block; width:6px; height:6px; border-radius:50%; background:<?= $statusColor ?>"></span>
                  <?= $statusText ?>
                </div>
              </div>
              
              <div style="text-align:left; border-right:1px solid var(--border-light); padding-right:24px; min-width:180px">
                <div style="font-size:12px; color:var(--text-muted); margin-bottom:4px">المبلغ الحالي</div>
                <div style="font-size:22px; font-weight:900; font-family:var(--font-en); color:var(--text-dark); margin-bottom:12px"><?= number_format($a['current_price']) ?> SAR</div>
                <a href="/auction-live.php?id=<?= $a['id'] ?>" class="btn btn-outline-dark btn-sm" style="width:100%; text-align:center; display:block; border-radius: var(--radius-round)">دخول غرفة المزايدة</a>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Wallet Section -->
      <div id="tab-wallet" class="panel-section" style="display:none;">
        <div class="panel-content">
          <h2 style="font-size:20px; margin-bottom:24px; border-bottom:1px solid var(--border-light); padding-bottom:16px">المحفظة والعمليات المالية</h2>
          
          <!-- Wallet Balance Card -->
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-bottom:28px;">
            <!-- Balance -->
            <div style="background:var(--bg-dark); color:#fff; padding:32px; border-radius:24px; position:relative; overflow:hidden;">
              <div style="position:absolute;top:-30px;right:-30px;width:140px;height:140px;background:var(--primary-light);border-radius:50%;"></div>
              <div style="font-size:13px;color:rgba(255,255,255,0.6);margin-bottom:8px;">رصيد المحفظة المتاح</div>
              <div id="walletBalance" style="font-size:40px;font-family:var(--font-en);font-weight:900;margin-bottom:6px;">150,000 <span style="font-size:16px;">SAR</span></div>
              <div style="font-size:12px;color:rgba(255,255,255,0.4);margin-bottom:28px;">محجوز كتأمين: <span style="color:var(--warning);font-weight:700;" id="walletReserved">4,500 SAR</span></div>
              <div style="display:flex;gap:10px;">
                <button onclick="openWalletTopup()" class="btn btn-primary" style="flex:1;justify-content:center;font-size:14px;padding:12px 16px; border-radius: var(--radius-round)">
                  <i class="ph ph-plus-circle ph-space-left"></i> شحن المحفظة
                </button>
                <button onclick="openWalletWithdraw()" class="btn btn-outline" style="font-size:14px;padding:12px 16px;flex:1;justify-content:center; border-radius: var(--radius-round)">
                  <i class="ph ph-arrow-up-right ph-space-left"></i> سحب
                </button>
              </div>
            </div>
            <!-- Quick Stats -->
            <div style="display:flex;flex-direction:column;gap:14px;">
              <div style="background:var(--bg-light);border-radius:18px;padding:20px;display:flex;align-items:center;gap:16px;">
                <div style="width:48px;height:48px;background:var(--primary-light);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:22px;color:var(--primary);"><i class="ph ph-arrow-down-left"></i></div>
                <div><div style="font-size:12px;color:var(--text-muted); margin-bottom: 2px;">إجمالي الإيداعات</div><div style="font-weight:900;font-family:var(--font-en); font-size: 18px;">200,000 SAR</div></div>
              </div>
              <div style="background:var(--bg-light);border-radius:18px;padding:20px;display:flex;align-items:center;gap:16px;">
                <div style="width:48px;height:48px;background:var(--danger-pale);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:22px;color:var(--danger);"><i class="ph ph-arrow-up-right"></i></div>
                <div><div style="font-size:12px;color:var(--text-muted); margin-bottom: 2px;">إجمالي المصروفات</div><div style="font-weight:900;font-family:var(--font-en); font-size: 18px;">50,000 SAR</div></div>
              </div>
              <div style="background:var(--bg-light);border-radius:18px;padding:20px;display:flex;align-items:center;gap:16px;">
                <div style="width:48px;height:48px;background:var(--warning-pale);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:22px;color:var(--warning);"><i class="ph ph-shield-check"></i></div>
                <div><div style="font-size:12px;color:var(--text-muted); margin-bottom: 2px;">تأمينات محجوزة</div><div style="font-weight:900;font-family:var(--font-en); font-size: 18px;">4,500 SAR</div></div>
              </div>
            </div>
          </div>

          <!-- Transactions -->
          <h3 style="font-size:16px;font-weight:800;margin-bottom:16px;">سجل العمليات</h3>
          <div id="walletTxList" style="display:flex;flex-direction:column;gap:12px;">
            <?php
            $txs = [
              ['type'=>'credit','label'=>'إيداع بنكي — البنك الأهلي','date'=>'15 مايو 2026','amount'=>'+50,000','color'=>'var(--primary)'],
              ['type'=>'debit','label'=>'حجز تأمين مزاد — تويوتا كامري 2022','date'=>'10 مايو 2026','amount'=>'-4,500','color'=>'var(--danger)'],
              ['type'=>'credit','label'=>'إيداع — STC Pay','date'=>'02 مايو 2026','amount'=>'+100,000','color'=>'var(--primary)'],
              ['type'=>'debit','label'=>'رسوم اشتراك شهري','date'=>'01 مايو 2026','amount'=>'-500','color'=>'var(--danger)'],
            ];
            foreach($txs as $tx): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;background:var(--bg-light);border-radius:16px;">
              <div style="display:flex;align-items:center;gap:14px;">
                <div style="width:42px;height:42px;border-radius:12px;background:<?= $tx['type']==='credit'?'var(--primary-light)':'var(--danger-pale)' ?>;display:flex;align-items:center;justify-content:center;font-size:20px;color:<?= $tx['color'] ?>;">
                  <i class="ph <?= $tx['type']==='credit'?'ph-arrow-down-left':'ph-arrow-up-right' ?>"></i>
                </div>
                <div>
                  <div style="font-size:14px;font-weight:800;"><?= $tx['label'] ?></div>
                  <div style="font-size:12px;color:var(--text-muted); margin-top: 2px;"><?= $tx['date'] ?></div>
                </div>
              </div>
              <div style="font-family:var(--font-en);font-weight:900;font-size:17px;color:<?= $tx['color'] ?>;"><?= $tx['amount'] ?> <span style="font-size:11px;">SAR</span></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>


      <!-- Other sections placeholder -->
      <div id="tab-wins" class="panel-section" style="display:none;">
        <div class="panel-content" style="text-align:center; padding:80px 20px;">
          <i class="ph ph-trophy" style="font-size:72px; color:var(--border-medium); margin-bottom:24px;"></i>
          <h3 style="font-size:20px; color:var(--text-dark); margin-bottom: 8px;">لا توجد مزادات رابحة بعد</h3>
          <p style="color:var(--text-muted); font-size:15px; max-width:400px; margin:0 auto;">بمجرد الفوز بمزاد وتسديد كامل المبلغ، ستظهر جميع مستندات وفواتير السيارة هنا.</p>
        </div>
      </div>

      <div id="tab-auto-bid" class="panel-section" style="display:none;">
        <div class="panel-content" style="text-align:center; padding:80px 20px;">
          <i class="ph ph-robot" style="font-size:72px; color:var(--border-medium); margin-bottom:24px;"></i>
          <h3 style="font-size:20px; color:var(--text-dark); margin-bottom: 8px;">المزايدة التلقائية غير مفعلة</h3>
          <p style="color:var(--text-muted); font-size:15px; max-width:400px; margin:0 auto 24px;">يمكنك تعيين روبوت المزايدة التلقائية للمزايدة نيابة عنك حتى سقف سعر محدد لكل سيارة.</p>
          <button class="btn btn-primary" style="border-radius: var(--radius-round); padding: 12px 28px;"><i class="ph ph-plus"></i> إضافة قاعدة مزايدة</button>
        </div>
      </div>

      <div id="tab-profile" class="panel-section" style="display:none;">
        <div class="panel-content">
           <h2 style="font-size:20px; margin-bottom:24px; border-bottom:1px solid var(--border-light); padding-bottom:16px">الملف الشخصي والتوثيق</h2>
           <p style="margin-bottom: 12px;"><strong>الاسم:</strong> <?= sanitize($_SESSION['user_name']) ?></p>
           <p><strong>حالة التوثيق:</strong> <span style="color:var(--primary); font-weight:800; background: var(--primary-light); padding: 6px 14px; border-radius: var(--radius-round); margin-right: 8px;"><i class="ph ph-shield-check"></i> موثق عبر النفاذ الوطني</span></p>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- ═══════════════════════════════════════
     WALLET TOP-UP MODAL
══════════════════════════════════════ -->
<div id="walletTopupModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.7);backdrop-filter:blur(6px);align-items:center;justify-content:center;padding:20px;">
  <div style="background:#fff;border-radius:28px;padding:40px;max-width:480px;width:100%;position:relative;animation:slideUpModal 0.3s cubic-bezier(0.34,1.56,0.64,1);">
    <button onclick="closeWalletTopup()" style="position:absolute;top:16px;left:16px;width:36px;height:36px;background:var(--bg-light);border:none;border-radius:50%;cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center; color: var(--text-dark)"><i class="ph ph-x"></i></button>

    <!-- Step 1: Choose method & amount -->
    <div id="topup-step-1" class="topup-step">
      <div style="text-align:center;margin-bottom:28px;">
        <div style="width:64px;height:64px;background:var(--primary-light);border-radius:18px;display:flex;align-items:center;justify-content:center;font-size:32px;color:var(--primary);margin:0 auto 16px;"><i class="ph ph-wallet"></i></div>
        <h3 style="font-size:22px;font-weight:900;">شحن المحفظة</h3>
        <p style="font-size:14px;color:var(--text-muted);margin-top:6px;">اختر طريقة الدفع وأدخل المبلغ</p>
      </div>

      <!-- Payment Methods -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:24px;">
        <button class="pay-method-btn" onclick="selectPayMethod('mada',this)" style="border:1.5px solid var(--border-light);border-radius:16px;padding:16px 10px;cursor:pointer;background:#fff;transition:all 0.2s;display:flex;flex-direction:column;align-items:center;gap:10px;font-family:var(--font-ar);">
          <img src="https://upload.wikimedia.org/wikipedia/en/b/b6/Mada_Logo.svg" style="height:28px;object-fit:contain;" alt="mada" onerror="this.outerHTML='<span style=font-weight:800;font-size:15px>mada</span>'">
          <span style="font-size:13px;font-weight:700;color:var(--text-dark);">مدى</span>
        </button>
        <button class="pay-method-btn" onclick="selectPayMethod('stcpay',this)" style="border:1.5px solid var(--border-light);border-radius:16px;padding:16px 10px;cursor:pointer;background:#fff;transition:all 0.2s;display:flex;flex-direction:column;align-items:center;gap:10px;font-family:var(--font-ar);">
          <div style="width:36px;height:32px;background:#5e2d91;border-radius:8px;display:flex;align-items:center;justify-content:center;"><span style="color:#fff;font-size:11px;font-weight:900;">STC</span></div>
          <span style="font-size:13px;font-weight:700;color:var(--text-dark);">STC Pay</span>
        </button>
        <button class="pay-method-btn" onclick="selectPayMethod('applepay',this)" style="border:1.5px solid var(--border-light);border-radius:16px;padding:16px 10px;cursor:pointer;background:#fff;transition:all 0.2s;display:flex;flex-direction:column;align-items:center;gap:10px;font-family:var(--font-ar);">
          <div style="font-size:28px;line-height:1;"><i class="ph ph-apple-logo"></i></div>
          <span style="font-size:13px;font-weight:700;color:var(--text-dark);">Apple Pay</span>
        </button>
        <button class="pay-method-btn" onclick="selectPayMethod('bank',this)" style="border:1.5px solid var(--border-light);border-radius:16px;padding:16px 10px;cursor:pointer;background:#fff;transition:all 0.2s;display:flex;flex-direction:column;align-items:center;gap:10px;font-family:var(--font-ar);">
          <div style="font-size:28px;color:var(--info);"><i class="ph ph-bank"></i></div>
          <span style="font-size:13px;font-weight:700;color:var(--text-dark);">تحويل بنكي</span>
        </button>
      </div>

      <!-- Amount -->
      <div style="margin-bottom:28px;">
        <label style="font-size:14px;font-weight:800;color:var(--text-dark);display:block;margin-bottom:10px;">المبلغ (ريال سعودي)</label>
        <input id="topupAmount" type="number" min="100" max="500000" placeholder="أدخل المبلغ..." style="width:100%;padding:16px;border:1.5px solid var(--border-light);border-radius:14px;font-size:16px;font-family:var(--font-ar);outline:none;transition:border 0.2s;box-sizing:border-box;" onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='var(--border-light)'">
        <div style="display:flex;gap:8px;margin-top:12px;">
          <?php foreach([1000,5000,10000,50000] as $q): ?>
          <button onclick="document.getElementById('topupAmount').value='<?= $q ?>'" style="flex:1;border:1px solid var(--border-light);background:var(--bg-light);border-radius:10px;padding:8px 0;font-size:13px;font-weight:700;cursor:pointer;font-family:var(--font-en);transition:all 0.2s;" onmouseover="this.style.borderColor='var(--primary)';this.style.color='var(--primary)'" onmouseout="this.style.borderColor='var(--border-light)';this.style.color=''">
            <?= number_format($q) ?>
          </button>
          <?php endforeach; ?>
        </div>
      </div>

      <button onclick="proceedTopup()" class="btn btn-primary" style="width:100%;justify-content:center;padding:16px;font-size:16px; border-radius: var(--radius-md);">
        متابعة الدفع <i class="ph ph-arrow-left ph-space-left"></i>
      </button>
    </div>

    <!-- Step 2: Processing -->
    <div id="topup-step-2" class="topup-step" style="display:none;text-align:center;padding:50px 0;">
      <div style="width:80px;height:80px;border:4px solid var(--primary);border-top-color:transparent;border-radius:50%;animation:spin 0.8s linear infinite;margin:0 auto 28px;"></div>
      <h3 style="font-size:20px;font-weight:900;margin-bottom:10px;">جاري معالجة الدفع...</h3>
      <p style="font-size:14px;color:var(--text-muted);">يرجى الانتظار، نحن نتحقق من العملية</p>
    </div>

    <!-- Step 3: Success -->
    <div id="topup-step-3" class="topup-step" style="display:none;text-align:center;padding:30px 0;">
      <div style="width:80px;height:80px;background:var(--primary-light);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:40px;color:var(--primary);margin:0 auto 24px;animation:scaleIn 0.4s cubic-bezier(0.34,1.56,0.64,1);"><i class="ph ph-check"></i></div>
      <h3 style="font-size:22px;font-weight:900;color:var(--text-dark);margin-bottom:10px;">تم الشحن بنجاح!</h3>
      <p style="font-size:15px;color:var(--text-muted);margin-bottom:8px;">تمت إضافة</p>
      <div id="walletTopupSuccessAmount" style="font-size:32px;font-weight:900;font-family:var(--font-en);color:var(--primary);margin-bottom:30px;"></div>
      <button onclick="closeWalletTopup()" class="btn btn-primary" style="padding:14px 40px; border-radius: var(--radius-round)">إغلاق</button>
    </div>
  </div>
</div>

<style>
.pay-method-btn.selected { border-color: var(--primary) !important; background: var(--primary-light) !important; }
@keyframes spin { to { transform: rotate(360deg); } }
@keyframes scaleIn { from { transform: scale(0.5); opacity:0; } to { transform: scale(1); opacity:1; } }
@keyframes slideUpModal { from { transform: translateY(40px); opacity:0; } to { transform: translateY(0); opacity:1; } }
</style>

<!-- Footer template -->
<?php include 'includes/footer.php'; ?>

<script>
function switchTab(e, tabId, element) {
  e.preventDefault();
  document.querySelectorAll('.panel-nav-item').forEach(el => el.classList.remove('active'));
  element.classList.add('active');
  
  document.querySelectorAll('.panel-section').forEach(section => {
    section.style.display = 'none';
  });
  const activeTab = document.getElementById('tab-' + tabId);
  if (activeTab) {
    activeTab.style.display = 'block';
    activeTab.classList.add('reveal', 'visible');
  }
}

function removeFromWatchlist(id, btn) {
  fetch('/api/watchlist.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ auction_id: id })
  })
  .then(resp => resp.json())
  .then(data => {
    if (data.success) {
      localStorage.removeItem(`fav_${id}`);
      btn.closest('.seller-row-card').remove();
      const container = document.getElementById('watchlistContainer');
      if (container.querySelectorAll('.seller-row-card').length === 0) {
        container.innerHTML = `
          <div style="grid-column:1/-1; text-align:center; padding:40px; color:var(--text-muted); font-size:14px; display:inline-flex; align-items:center; justify-content:center; gap:8px;">
            <i class="ph ph-heart" style="color:var(--primary)"></i> لا توجد سيارات في قائمة المفضلة حالياً.
          </div>
        `;
      }
      showToast('تمت الإزالة من المفضلة', 'info');
    } else {
      showToast(data.message || 'فشلت الإزالة', 'error');
    }
  })
  .catch(err => {
    localStorage.removeItem(`fav_${id}`);
    btn.closest('.seller-row-card').remove();
    showToast('تمت الإزالة (محلياً)', 'info');
  });
}

// ═══════════════════════════════════════
// WALLET TOPUP MODAL
// ═══════════════════════════════════════
let walletCurrentBalance = 150000;
let selectedPayMethod = '';
let topupAmount = 0;

function openWalletTopup() {
  document.getElementById('walletTopupModal').style.display = 'flex';
  document.body.style.overflow = 'hidden';
  showTopupStep(1);
}
function closeWalletTopup() {
  document.getElementById('walletTopupModal').style.display = 'none';
  document.body.style.overflow = '';
  selectedPayMethod = '';
  topupAmount = 0;
  document.querySelectorAll('.pay-method-btn').forEach(b => b.classList.remove('selected'));
  document.getElementById('topupAmount').value = '';
}
function showTopupStep(step) {
  document.querySelectorAll('.topup-step').forEach(s => s.style.display = 'none');
  const el = document.getElementById('topup-step-' + step);
  if (el) el.style.display = 'block';
}
function selectPayMethod(method, btn) {
  selectedPayMethod = method;
  document.querySelectorAll('.pay-method-btn').forEach(b => b.classList.remove('selected'));
  btn.classList.add('selected');
}
function proceedTopup() {
  topupAmount = parseInt(document.getElementById('topupAmount').value.replace(/,/g,'')) || 0;
  if (!selectedPayMethod) { alert('اختر طريقة الدفع أولاً'); return; }
  if (topupAmount < 100) { alert('الحد الأدنى للشحن هو 100 ريال'); return; }
  showTopupStep(2);
  setTimeout(() => {
    showTopupStep(3);
    walletCurrentBalance += topupAmount;
    document.getElementById('walletBalance').innerHTML = walletCurrentBalance.toLocaleString('ar-SA') + ' <span style="font-size:16px;">SAR</span>';
    document.getElementById('walletTopupSuccessAmount').textContent = topupAmount.toLocaleString('ar-SA') + ' SAR';
  }, 2200);
}
function openWalletWithdraw() { alert('ميزة السحب ستكون متاحة قريباً. تواصل مع فريق الدعم لمعالجة طلبك.'); }
</script>

</body>
</html>
