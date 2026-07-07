<?php
require_once 'config.php';

$auction_id = isset($_GET['id']) ? intval($_GET['id']) : 1;
$auction = null;

if ($db_connected) {
    $sql = "SELECT a.*, v.make, v.model, v.year, v.mileage, v.city, v.image_url, v.fuel_type, v.transmission, v.color
            FROM auctions a
            JOIN vehicles v ON a.vehicle_id = v.id
            WHERE a.id = $auction_id LIMIT 1";
    $res = $conn->query($sql);
    if ($res && $res->num_rows > 0) {
        $auction = $res->fetch_assoc();
    }
}

if (!$auction) {
    $mocks = getMockAuctions(30);
    foreach ($mocks as $m) {
        if ($m['id'] == $auction_id) {
            $auction = $m;
            break;
        }
    }
    if (!$auction) $auction = $mocks[0];
}

$title_car = $auction['title'] ?? ($auction['make'].' '.$auction['model'].' '.$auction['year']);
$img = (!empty($auction['image_url']) && strlen($auction['image_url']) > 4) ? $auction['image_url'] : getCarImage($auction['make']);
$current_price = $auction['current_price'] ?? $auction['starting_price'];
$min_increment = $auction['min_increment'] ?? 500;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>غرفة المزايدة: <?= sanitize($title_car) ?> | FleetX</title>
  <link rel="stylesheet" href="/assets/css/fleetx.css">
  <style>
    .room-grid {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 30px;
    }
    .room-main-img {
      width: 100%;
      height: 450px;
      object-fit: cover;
      border-radius: var(--radius-lg);
      margin-bottom: 24px;
    }
    .bidding-panel {
      background: var(--bg-white);
      border: 1px solid var(--border-light);
      border-radius: var(--radius-lg);
      display: flex;
      flex-direction: column;
      height: 600px;
      box-shadow: var(--shadow-card);
      overflow: hidden;
    }
    .bidding-header {
      padding: 20px;
      background: var(--bg-dark);
      color: #fff;
      text-align: center;
      border-bottom: 4px solid var(--primary);
    }
    .bidding-history {
      flex: 1;
      padding: 20px;
      overflow-y: auto;
      background: #f8fafc;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    .bid-item {
      background: #fff;
      padding: 12px 16px;
      border-radius: var(--radius-md);
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 8px rgba(0,0,0,0.03);
      border: 1px solid var(--border-light);
      animation: fadeIn 0.4s ease;
    }
    .bid-item.highest {
      border-color: var(--primary);
      background: rgba(27, 201, 118, 0.05);
    }
    .bid-controls {
      padding: 24px;
      background: #fff;
      border-top: 1px solid var(--border-light);
    }
    @media (max-width: 992px) {
      .room-grid { grid-template-columns: 1fr; }
      .bidding-panel { height: 500px; }
      .room-main-img { height: 300px; }
    }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
  </style>
</head>
<body class="page-inner" style="background: #f1f5f9;">

<?php include 'includes/navbar.php'; ?>

<div class="container" style="padding-top: 120px; padding-bottom: 80px;">
  
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
    <div>
      <a href="/auctions.php" style="color:var(--text-muted); margin-bottom:8px; display:inline-block">← العودة لقاعة المزادات</a>
      <h1 style="font-size:28px; font-weight:900; margin:0;"><?= sanitize($title_car) ?></h1>
    </div>
    <div style="display:flex; gap:12px">
      <button class="btn btn-outline" onclick="toggleFavorite(<?= $auction['id'] ?>, this)"><i class="ph ph-heart"></i> حفظ بالمفضلة</button>
    </div>
  </div>

  <div class="room-grid">
    
    <!-- Vehicle Info -->
    <div>
      <div style="position:relative;">
        <div style="position:absolute; top:20px; right:20px; background:rgba(0,0,0,0.6); color:#fff; padding:6px 16px; border-radius:20px; font-weight:800; display:flex; align-items:center; gap:8px; z-index:10; backdrop-filter:blur(4px);"><span style="width:10px; height:10px; background:var(--danger); border-radius:50%; display:inline-block; animation:pulse 2s infinite"></span> بث مباشر</div>
        <img src="<?= $img ?>" class="room-main-img" alt="Car">
      </div>

      <div class="panel-content" style="border-radius:var(--radius-lg)">
        <h3 style="font-size:20px; font-weight:800; margin-bottom:16px;">المواصفات الفنية</h3>
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(150px, 1fr)); gap:16px;">
          <div style="background:var(--bg-light); padding:16px; border-radius:var(--radius-md); text-align:center;">
            <div style="color:var(--text-muted); font-size:12px; margin-bottom:4px;">الممشى</div>
            <div style="font-weight:800;"><?= number_format($auction['mileage'] ?? 0) ?> كم</div>
          </div>
          <div style="background:var(--bg-light); padding:16px; border-radius:var(--radius-md); text-align:center;">
            <div style="color:var(--text-muted); font-size:12px; margin-bottom:4px;">الموديل</div>
            <div style="font-weight:800;"><?= $auction['year'] ?? '2023' ?></div>
          </div>
          <div style="background:var(--bg-light); padding:16px; border-radius:var(--radius-md); text-align:center;">
            <div style="color:var(--text-muted); font-size:12px; margin-bottom:4px;">الوقود</div>
            <div style="font-weight:800;"><?= $auction['fuel_type'] ?? 'بنزين' ?></div>
          </div>
          <div style="background:var(--bg-light); padding:16px; border-radius:var(--radius-md); text-align:center;">
            <div style="color:var(--text-muted); font-size:12px; margin-bottom:4px;">المدينة</div>
            <div style="font-weight:800;"><?= sanitize($auction['city'] ?? 'الرياض') ?></div>
          </div>
        </div>
        <div style="margin-top: 24px;">
           <a href="/vehicle.php?id=<?= $auction['vehicle_id'] ?? 1 ?>" class="btn btn-outline-dark" style="width:100%; justify-content:center"><i class="ph ph-file-text"></i> عرض تقرير الفحص المعتمد للمركبة</a>
        </div>
      </div>
    </div>

    <!-- Live Bidding Panel -->
    <div class="bidding-panel">
      <div class="bidding-header">
        <div style="font-size:14px; opacity:0.8; margin-bottom:4px;">الوقت المتبقي لانتهاء المزاد</div>
        <div style="font-size:32px; font-weight:900; font-family:var(--font-en); letter-spacing:2px; color:var(--primary-light);" id="room-timer" data-endtime="<?= strtotime($auction['end_time'] ?? '+2 hours') ?>">00:00:00</div>
      </div>
      
      <div class="bidding-history" id="bidding-history">
        <!-- History items will be inserted here via JS -->
      </div>
      
      <div class="bid-controls">
        <div style="display:flex; justify-content:space-between; margin-bottom:12px;">
          <span style="color:var(--text-muted); font-size:14px;">السعر الحالي:</span>
          <span style="font-weight:900; font-size:24px; color:var(--text-dark); font-family:var(--font-en);" id="current-price-display"><?= number_format($current_price) ?> <span style="font-size:14px; font-family:var(--font-ar)">ر.س</span></span>
        </div>
        
        <div style="display:flex; gap:12px; margin-bottom:16px;">
          <button class="btn btn-outline" onclick="addBid(<?= $min_increment ?>)" style="flex:1; justify-content:center; font-family:var(--font-en)">+<?= $min_increment ?></button>
          <button class="btn btn-outline" onclick="addBid(<?= $min_increment * 2 ?>)" style="flex:1; justify-content:center; font-family:var(--font-en)">+<?= $min_increment * 2 ?></button>
          <button class="btn btn-outline" onclick="addBid(<?= $min_increment * 5 ?>)" style="flex:1; justify-content:center; font-family:var(--font-en)">+<?= $min_increment * 5 ?></button>
        </div>
        
        <div style="display:flex; gap:12px;">
          <input type="number" id="custom-bid-input" class="form-control" placeholder="أدخل مبلغاً مخصصاً..." style="flex:2">
          <button class="btn btn-primary" onclick="submitCustomBid()" style="flex:1; justify-content:center; background:var(--primary-gradient);">مزايدة <i class="ph-fill ph-gavel"></i></button>
        </div>
      </div>
    </div>

  </div>
</div>

<?php include 'includes/footer.php'; ?>
<script src="/assets/js/fleetx.js"></script>
<script>
  // Simple Room Logic
  let currentPrice = <?= $current_price ?>;
  const historyContainer = document.getElementById('bidding-history');
  const priceDisplay = document.getElementById('current-price-display');
  let bidCount = 0;

  function formatMoney(amount) {
    return amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") + ' <span style="font-size:14px; font-family:var(--font-ar)">ر.س</span>';
  }

  function appendBidToHistory(amount, isUser) {
    bidCount++;
    const id = "M" + Math.floor(Math.random() * 9000 + 1000);
    const userLabel = isUser ? 'أنت (مزايد حصري)' : 'مزايد ' + id;
    const time = new Date().toLocaleTimeString('ar-SA', {hour: '2-digit', minute:'2-digit', second:'2-digit'});
    
    // Remove 'highest' from previous
    document.querySelectorAll('.bid-item').forEach(el => el.classList.remove('highest'));
    
    const div = document.createElement('div');
    div.className = 'bid-item highest';
    div.innerHTML = `
      <div>
        <div style="font-weight:800; font-size:14px; color: ${isUser ? 'var(--primary)' : 'var(--text-dark)'}">${userLabel}</div>
        <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">${time}</div>
      </div>
      <div style="font-weight:900; font-size:16px; font-family:var(--font-en);">${amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")}</div>
    `;
    historyContainer.prepend(div);
  }

  function addBid(increment) {
    currentPrice += increment;
    priceDisplay.innerHTML = formatMoney(currentPrice);
    appendBidToHistory(currentPrice, true);
  }

  function submitCustomBid() {
    const input = document.getElementById('custom-bid-input');
    const val = parseInt(input.value);
    if (!val || val <= currentPrice) {
      alert("الرجاء إدخال مبلغ أعلى من السعر الحالي.");
      return;
    }
    currentPrice = val;
    priceDisplay.innerHTML = formatMoney(currentPrice);
    appendBidToHistory(currentPrice, true);
    input.value = '';
  }

  // Initial mock history
  appendBidToHistory(currentPrice - 1000, false);
  appendBidToHistory(currentPrice - 500, false);
  setTimeout(() => appendBidToHistory(currentPrice, false), 500);

  // Timer logic for room
  const timerEl = document.getElementById('room-timer');
  const endTime = parseInt(timerEl.getAttribute('data-endtime')) * 1000;
  setInterval(() => {
    let diff = endTime - new Date().getTime();
    if (diff < 0) diff = 0;
    let h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    let m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    let s = Math.floor((diff % (1000 * 60)) / 1000);
    timerEl.innerText = `${h.toString().padStart(2,'0')}:${m.toString().padStart(2,'0')}:${s.toString().padStart(2,'0')}`;
  }, 1000);

  // Random competitor bids every 10-25 seconds
  setInterval(() => {
    if(Math.random() > 0.5) {
      const increments = [500, 1000, 1500];
      const inc = increments[Math.floor(Math.random() * increments.length)];
      currentPrice += inc;
      priceDisplay.innerHTML = formatMoney(currentPrice);
      appendBidToHistory(currentPrice, false);
    }
  }, Math.random() * 15000 + 10000);

</script>
</body>
</html>
