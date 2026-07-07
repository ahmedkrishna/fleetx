<?php
require_once 'config.php';

// Force login
requireLogin();

// Fetch seller auctions from DB if connected
$auctions = [];
$user_id = intval($_SESSION['user_id'] ?? 0);
$seller_name = $_SESSION['user_name'] ?? 'الوطنية للتأجير';

if ($db_connected) {
    $res = $conn->query("SELECT id FROM seller_companies WHERE user_id = $user_id LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $scid = intval($res->fetch_assoc()['id']);
        $res2 = $conn->query("SELECT a.id, a.title, a.type, a.current_price, a.end_time, v.make, v.model, v.image_url, v.city, v.mileage, v.year FROM auctions a JOIN vehicles v ON a.vehicle_id = v.id WHERE a.seller_id = $scid");
        if ($res2) {
            while ($row = $res2->fetch_assoc()) {
                $auctions[] = $row;
            }
        }
        
        // Fetch KPI stats
        $stats = ['total_sales' => 0, 'pending_payments' => 0, 'inspection_rate' => 98.5];
        $kpi_res = $conn->query("SELECT SUM(sale_price) as total_sales, SUM(IF(payment_status='pending', seller_payout, 0)) as pending_payments FROM transactions WHERE seller_id = $scid");
        if ($kpi_res && $kpi_row = $kpi_res->fetch_assoc()) {
            $stats['total_sales'] = floatval($kpi_row['total_sales'] ?? 0);
            $stats['pending_payments'] = floatval($kpi_row['pending_payments'] ?? 0);
        }
        
        // Fetch Completed Sales (Transactions)
        $sales = [];
        $sales_res = $conn->query("SELECT t.sale_price, t.seller_payout, t.payment_status, t.created_at, a.title, v.make, v.model, v.year, v.image_url FROM transactions t JOIN auctions a ON t.auction_id = a.id JOIN vehicles v ON a.vehicle_id = v.id WHERE t.seller_id = $scid ORDER BY t.created_at DESC");
        if ($sales_res) {
            while ($row = $sales_res->fetch_assoc()) {
                $sales[] = $row;
            }
        }
    }
}

if (empty($auctions)) {
    // Fetch mock seller auctions (cars sold/selling) filtered by logged-in seller
    $all_mocks = getMockAuctions(40);
    foreach ($all_mocks as $a) {
        if ($a['seller'] === $seller_name) {
            $auctions[] = $a;
        }
    }
    if (empty($auctions)) {
        $auctions = array_slice($all_mocks, 0, 3);
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>بوابة الموردين المعتمدين | FleetX</title>
  <link rel="stylesheet" href="/assets/css/fleetx.css">
  <style>
    .seller-row-card {
      display: flex;
      align-items: center;
      gap: 20px;
      padding: 20px;
      border: 1px solid var(--border-light);
      border-radius: var(--radius-lg);
      background: var(--bg-white);
      transition: var(--transition);
    }
    .seller-row-card:hover {
      transform: translateY(-3px);
      box-shadow: var(--shadow-card-hover);
      border-color: var(--primary);
    }
    @media (max-width: 768px) {
      .seller-row-card { flex-direction: column; text-align: center; align-items: stretch; }
      .page-header .container { flex-direction: column; align-items: center; text-align: center; gap: 16px; }
    }
  </style>
</head>
<body class="page-inner">

<!-- Navbar template -->
<?php include 'includes/navbar.php'; ?>

<!-- Page Header -->
<header class="page-header" style="padding-bottom: 90px">
  <div class="page-header-bg" style="background-image:url('https://images.unsplash.com/photo-1573164713988-8665fc963095?w=1600&q=80')"></div>
  <div class="container" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:24px">
    <div>
      <div style="font-size:14px; color:var(--primary); font-weight:800; text-transform:uppercase; margin-bottom:8px">بوابة الشركات والموردين المعتمدة</div>
      <h1 style="margin:0">مرحباً بك، شركة <?= sanitize($_SESSION['user_name']) ?></h1>
      <p style="color:var(--text-light-muted); font-size:16px; margin-top:6px; max-width:600px">أدر أسطولك المعروض للمزاد، تابع العروض الحية لسياراتك، واستخرج عوائد مبيعاتك.</p>
    </div>
    <!-- Quick Action -->
    <div>
       <a href="/add-auction.php" class="btn btn-primary" style="padding:14px 32px; display:inline-flex; align-items:center; gap:8px; border-radius: var(--radius-round)"><i class="ph ph-plus-circle"></i> عرض سيارة جديدة في المزاد</a>
    </div>
  </div>
</header>

<div class="container" style="margin-top:-50px; position:relative; z-index:10; margin-bottom:100px;">
  <div class="dashboard-grid">
    
    <!-- Left Navigation Menu -->
    <aside class="panel-sidebar">
      <div style="padding:0 28px 18px; border-bottom:1px solid var(--border-light); margin-bottom:12px;">
        <div style="font-weight:900; font-size:17px; color:var(--text-dark)">إدارة المبيعات</div>
      </div>
      <a href="#" class="panel-nav-item active" onclick="switchTab(event, 'dashboard', this)"><i class="ph ph-chart-bar ph-space-left"></i> لوحة الإحصائيات</a>
      <a href="#" class="panel-nav-item" onclick="switchTab(event, 'my-cars', this)"><i class="ph ph-car ph-space-left"></i> أسطولي المعروض <span class="font-en" style="margin-right:auto; font-size:11px; background:var(--primary-light); padding:4px 10px; border-radius:var(--radius-round); color:var(--primary); font-weight: 800;"><?= count($auctions) ?></span></a>
      <a href="#" class="panel-nav-item" onclick="switchTab(event, 'payouts', this)"><i class="ph ph-wallet ph-space-left"></i> المستحقات المالية</a>
      <a href="#" class="panel-nav-item" onclick="switchTab(event, 'reports', this)"><i class="ph ph-file-text ph-space-left"></i> تقارير الفحص والتقييم</a>
      <a href="/logout.php" class="panel-nav-item" style="color:var(--danger); border-top:1px solid var(--border-light); margin-top:24px; padding-top:20px;"><i class="ph ph-sign-out ph-space-left" style="color: inherit;"></i> تسجيل الخروج</a>
    </aside>

    <!-- Main Section -->
    <main>
      <!-- Dashboard Section -->
      <div id="tab-dashboard" class="panel-section" style="display:block;">
        <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:20px; margin-bottom:30px;">
          <div class="stat-box" style="border-right: 4px solid var(--info)">
            <div>
              <div style="font-size:13px; color:var(--text-muted); font-weight:700">السيارات المعروضة</div>
              <div class="val font-en"><?= count($auctions) ?></div>
            </div>
            <div style="font-size:28px; color: var(--info)"><i class="ph ph-car"></i></div>
          </div>
          <div class="stat-box" style="border-right: 4px solid var(--primary)">
            <div>
              <div style="font-size:13px; color:var(--text-muted); font-weight:700">إجمالي المبيعات المؤكدة</div>
              <div class="val font-en">1,250,000 <span style="font-size:12px; font-weight:700">SAR</span></div>
            </div>
            <div style="font-size:28px; color: var(--primary)"><i class="ph ph-bank"></i></div>
          </div>
          <div class="stat-box" style="border-right: 4px solid var(--warning)">
            <div>
              <div style="font-size:13px; color:var(--text-muted); font-weight:700">مستحقات قيد التحويل</div>
              <div class="val font-en">85,000 <span style="font-size:12px; font-weight:700">SAR</span></div>
            </div>
            <div style="font-size:28px; color: var(--warning)"><i class="ph ph-clock-countdown"></i></div>
          </div>
        </div>

        <!-- Recent Performance Chart placeholder -->
        <div class="panel-content" style="margin-bottom:30px">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
            <h2 style="font-size:18px; margin:0">مؤشر أداء المبيعات (آخر 6 أشهر)</h2>
            <select style="padding:6px 12px; border:1px solid var(--border-light); border-radius:var(--radius-md); font-family:inherit; font-size:13px; outline:none; font-weight: 700; color: var(--text-dark)">
              <option>2026</option>
              <option>2025</option>
            </select>
          </div>
          <div style="height:250px; background:var(--bg-light); border-radius:var(--radius-md); display:flex; align-items:flex-end; padding:20px; gap:10px;">
             <!-- Mock Chart Bars -->
             <?php 
               $heights = [40, 60, 35, 80, 50, 95];
               $months = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو'];
               foreach($heights as $idx => $h):
             ?>
             <div style="flex:1; display:flex; flex-direction:column; align-items:center; gap:10px;">
               <div style="width:100%; height:200px; display:flex; align-items:flex-end; justify-content:center;">
                 <div style="width:60%; max-width:40px; height:<?= $h ?>%; background:var(--primary); border-radius:6px 6px 0 0; transition:height 1s ease;"></div>
               </div>
               <div style="font-size:12px; color:var(--text-muted); font-weight:700"><?= $months[$idx] ?></div>
             </div>
             <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- My Cars Section -->
      <div id="tab-my-cars" class="panel-section" style="display:none;">
        <div class="panel-content">
          <h2 style="font-size:20px; margin-bottom:24px; border-bottom:1px solid var(--border-light); padding-bottom:16px">أسطولي المعروض حالياً</h2>
          <div style="display:flex; flex-direction:column; gap:16px;">
            <?php foreach($auctions as $idx => $a): 
               $img = getCarImage($a['make'], $a['image_url']);
            ?>
            <div class="seller-row-card">
              <img src="<?= $img ?>" style="width:140px; height:90px; object-fit:cover; border-radius:var(--radius-sm);" alt="<?= sanitize($a['title']) ?>">
              <div style="flex:1;">
                <h3 style="font-size:16px; font-weight:800; color:var(--text-dark)"><?= sanitize($a['title'] ?: ($a['make'].' '.$a['model'].' '.$a['year'])) ?></h3>
                <div style="font-size:12px; color:var(--text-muted); margin-top:4px">رقم الإدراج: <span class="font-en">FX-<?= $a['id'] ?></span></div>
                
                <div style="display:flex; gap:16px; margin-top:12px">
                  <div style="font-size:13px; color:var(--text-dark); font-weight:700;"><i class="ph ph-eye" style="color:var(--primary)"></i> 1,240 مشاهدة</div>
                  <div style="font-size:13px; color:var(--text-dark); font-weight:700;"><i class="ph ph-users" style="color:var(--primary)"></i> 24 مزايد</div>
                </div>
              </div>
              
              <div style="text-align:left; border-right:1px solid var(--border-light); padding-right:24px; min-width:180px">
                <div style="font-size:12px; color:var(--text-muted); margin-bottom:4px">أعلى مزايدة حتى الآن</div>
                <div style="font-size:22px; font-weight:900; font-family:var(--font-en); color:var(--text-dark); margin-bottom:12px"><?= number_format($a['current_price']) ?> SAR</div>
                <a href="/auction-live.php?id=<?= $a['id'] ?>" class="btn btn-outline-dark btn-sm" style="width:100%; text-align:center; display:block; border-radius: var(--radius-round)">مراقبة المزاد الحي</a>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Payouts Section -->
      <div id="tab-payouts" class="panel-section" style="display:none;">
        <div class="panel-content">
          <h2 style="font-size:20px; margin-bottom:24px; border-bottom:1px solid var(--border-light); padding-bottom:16px">المستحقات المالية والتحويلات</h2>
          
          <div style="background:var(--bg-dark); color:#fff; padding:32px; border-radius:24px; display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; position: relative; overflow: hidden;">
            <div style="position:absolute;top:-30px;right:-30px;width:140px;height:140px;background:var(--primary-light);border-radius:50%;"></div>
            <div style="position: relative; z-index: 1;">
              <div style="font-size:13px; color:rgba(255,255,255,0.6); margin-bottom:8px">الرصيد المتاح للتحويل لحسابكم البنكي</div>
              <div style="font-size:36px; font-weight:900; font-family:var(--font-en)">85,000 <span style="font-size:16px">SAR</span></div>
            </div>
            <button class="btn btn-primary" onclick="alert('ميزة التحويل البنكي ستكون متاحة قريباً. تواصل مع الدعم الفني لمزيد من التفاصيل.')" style="padding:14px 32px; border-radius: var(--radius-round)">طلب تحويل بنكي</button>
          </div>

          <h3 style="font-size:16px; font-weight:800; margin-bottom:16px;">سجل الحوالات السابقة</h3>
          <table style="width:100%; text-align:right; border-collapse:collapse;">
            <thead>
              <tr style="border-bottom:1px solid var(--border-light); color:var(--text-muted); font-size:13px;">
                <th style="padding:12px">رقم العملية</th>
                <th style="padding:12px">التاريخ</th>
                <th style="padding:12px">المبلغ</th>
                <th style="padding:12px">الحالة</th>
              </tr>
            </thead>
            <tbody>
              <tr style="border-bottom:1px solid var(--border-light)">
                <td style="padding:16px; font-family:var(--font-en); font-weight:700; font-size:14px">TRX-99823</td>
                <td style="padding:16px; font-size:14px">12 مايو 2026</td>
                <td style="padding:16px; font-family:var(--font-en); font-weight:800; font-size:15px">140,000 SAR</td>
                <td style="padding:16px;"><span style="background:var(--primary-light); color:var(--primary); padding:4px 12px; border-radius:var(--radius-round); font-size:12px; font-weight:800">مكتمل</span></td>
              </tr>
              <tr>
                <td style="padding:16px; font-family:var(--font-en); font-weight:700; font-size:14px">TRX-99754</td>
                <td style="padding:16px; font-size:14px">05 مايو 2026</td>
                <td style="padding:16px; font-family:var(--font-en); font-weight:800; font-size:15px">225,500 SAR</td>
                <td style="padding:16px;"><span style="background:var(--primary-light); color:var(--primary); padding:4px 12px; border-radius:var(--radius-round); font-size:12px; font-weight:800">مكتمل</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      
      <!-- Reports -->
      <div id="tab-reports" class="panel-section" style="display:none;">
        <div class="panel-content" style="text-align:center; padding:80px 20px;">
          <i class="ph ph-file-text" style="font-size:72px; color:var(--border-medium); margin-bottom:24px;"></i>
          <h3 style="font-size:20px; color:var(--text-dark); margin-bottom: 8px;">تقارير الفحص الفني</h3>
          <p style="color:var(--text-muted); font-size:15px; max-width:400px; margin:0 auto 24px;">جميع سياراتكم تم فحصها من قبل فرق الفحص الفني المعتمدة لدينا. سيتم إرفاق التقارير هنا قريباً.</p>
        </div>
      </div>

    </main>
  </div>
</div>

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
</script>

</body>
</html>
