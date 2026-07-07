<?php
require_once 'config.php';
$id = isset($_GET['id']) ? intval($_GET['id']) : 1;

$auction = null;
if ($db_connected) {
    $res = $conn->query("SELECT a.id AS id, a.title, a.type, a.current_price, a.starting_price, a.end_time, a.bid_increment, a.event_id,
                                v.make, v.model, v.year, v.mileage, v.color, v.fuel_type, v.transmission, v.city, v.condition_grade, v.description, v.image_url, v.images
                         FROM auctions a 
                         JOIN vehicles v ON a.vehicle_id = v.id 
                         WHERE a.id = " . intval($id));
    if ($res) $auction = $res->fetch_assoc();
}
if (!$auction) {
    echo "المزاد غير موجود.";
    exit;
}

$title_car = $auction['title'] ?? ($auction['make'].' '.$auction['model'].' '.$auction['year']);
$img = getCarImage($auction['make'], $auction['image_url'] ?? '');
$isLive = ($auction['type'] ?? 'live') === 'live';
$min_increment = isset($auction['bid_increment']) ? floatval($auction['bid_increment']) : 500;
$current_price = $auction['current_price'] ?? ($auction['starting_price'] ?? 50000);
$countdownVal = $auction['end_time'] ?? date('Y-m-d H:i:s', time() + rand(3600, 7200));

$gallery_images = [];
if (!empty($auction['images'])) {
    $gallery_images = json_decode($auction['images'], true);
}
if (empty($gallery_images) || !is_array($gallery_images)) {
    $gallery_images = [
        $img,
        'https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=1200&q=80',
        'https://images.unsplash.com/photo-1600712242805-5f78671b24da?w=1200&q=80',
        'https://images.unsplash.com/photo-1511919884226-fd3cad34687c?w=1200&q=80'
    ];
}
$bid_viewers = 0;
if ($db_connected) {
    $vr = $conn->query('SELECT COUNT(DISTINCT user_id) FROM bids WHERE auction_id=' . intval($id));
    if ($vr) $bid_viewers = (int)$vr->fetch_row()[0];
}
$viewers = max(12, $bid_viewers * 3 + rand(8, 24));
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>غرفة المزاد المباشر: <?= sanitize($title_car) ?> | FleetX</title>
  <link rel="stylesheet" href="/assets/css/fleetx.css">
</head>
<body class="page-inner fx-page-shell fx-page-shell--detail">

<?php include 'includes/navbar.php'; ?>

<?php
$defaultCover = 'https://images.unsplash.com/photo-1508962914676-134849a727f0?w=1600&q=80';
$coverImage = (!empty($auction['cover_image']) && strlen($auction['cover_image']) > 5) ? $auction['cover_image'] : $defaultCover;
$hero_title = $title_car;
$hero_bg = $coverImage;
$hero_back_href = '/event.php?id=' . ($auction['event_id'] ?? 1);
$hero_back_label = '← العودة إلى الحدث';
$hero_modifier = 'overlap';
$hero_eyebrow = 'غرفة المزاد المباشر';
$hero_meta_html = '
  <span class="fx-page-hero__chip"><span class="fx-live-dot"></span> مزاد حي</span>
  <span class="fx-page-hero__chip"><i class="ph-fill ph-eye"></i> ' . number_format($viewers) . ' مشاهد</span>
  <span class="fx-page-hero__chip"><i class="ph ph-gavel"></i> ' . number_format($current_price) . ' ر.س</span>';
include 'includes/page-hero.inc.php';
?>

<div class="container fx-page-body fx-page-body--overlap-lg">
  <div class="fx-live-room">
    
    <!-- Right Panel: Vehicle Display (Order 1) -->
    <div class="live-gallery-panel">
      <!-- White Wrapper Card for Gallery and Header -->
      <div class="fx-live-card">
          <div class="fx-live-card-header">
            <div class="fx-live-card-title">
                <span class="fx-live-dot"></span>
                معرض صور المركبة
            </div>
            <div class="fx-viewers-badge">
                المشاهدين:
                <span class="font-en"><i class="ph-fill ph-eye fx-icon-primary"></i> <?= number_format($viewers) ?></span>
            </div>
          </div>

          <!-- PREMIUM GALLERY COMPONENT -->

          <div class="mazad-premium-gallery">
            <div class="mpg-main-view">
                <div class="mpg-status-badge">
                    <i class="ph-bold ph-shield-check fx-icon-primary"></i> مفحوصة معتمدة
                </div>
                <img id="main-gallery-img" src="<?= $gallery_images[0] ?>" alt="Main Car">
                <div class="mpg-overlay">
                    <div class="mpg-badge"><i class="ph-bold ph-camera"></i> <?= count($gallery_images) ?> صور</div>
                </div>
            </div>
            
            <div class="mpg-thumbs-strip">
                <?php foreach($gallery_images as $index => $gImg): ?>
                <div class="mpg-thumb <?= $index === 0 ? 'active' : '' ?>" onclick="changePremiumImage(this, '<?= $gImg ?>')">
                    <img src="<?= $gImg ?>">
                </div>
                <?php endforeach; ?>
            </div>
          </div>
      </div>
      <!-- End Wrapper -->
      
      <div class="fx-detail-block">
        <h3><?= sanitize($title_car) ?></h3>
        <p class="fx-live-desc">
          هذه المركبة معروضة في المزاد المباشر. جميع المركبات مفحوصة فنياً بأكثر من 100 نقطة قبل العرض.
        </p>
        <div class="fx-live-specs-grid">
          <div class="fx-live-spec"><i class="ph ph-calendar-blank"></i><div class="lbl">سنة الصنع</div><div class="val"><?= sanitize($auction['year'] ?? '2023') ?></div></div>
          <div class="fx-live-spec"><i class="ph ph-gauge"></i><div class="lbl">الممشى</div><div class="val font-en"><?= number_format($auction['mileage'] ?? 45000) ?> كم</div></div>
          <div class="fx-live-spec"><i class="ph ph-gas-pump"></i><div class="lbl">الوقود</div><div class="val"><?= sanitize($auction['fuel_type'] ?? 'بنزين') ?></div></div>
          <div class="fx-live-spec"><i class="ph ph-steering-wheel"></i><div class="lbl">ناقل الحركة</div><div class="val"><?= sanitize($auction['transmission'] ?? 'أوتوماتيك') ?></div></div>
          <div class="fx-live-spec"><i class="ph ph-paint-brush"></i><div class="lbl">اللون</div><div class="val"><?= sanitize($auction['color'] ?? 'أبيض') ?></div></div>
          <div class="fx-live-spec"><i class="ph ph-map-pin"></i><div class="lbl">المدينة</div><div class="val"><?= sanitize($auction['city'] ?? 'الرياض') ?></div></div>
        </div>
      </div>
    </div>

    <!-- Left Panel: Bidding Panel (Order 2) -->

<div class="pbb-board">
    
    <!-- Top Header -->
    <div class="pbb-top">
        <div class="pbb-status"><span class="pulse"></span> مزاد مباشر</div>
        <div class="pbb-timer" id="room-timer" data-endtime="<?= strtotime($countdownVal) ?>">
            <span id="timer-h">00</span>:<span id="timer-m">00</span>:<span id="timer-s">00</span>
        </div>
    </div>

    <!-- Price -->
    <div class="pbb-price-wrap">
        <div class="pbb-price-lbl">السعر الحالي للمركبة</div>
        <div class="pbb-price-val" id="current-price-display"><?= number_format($current_price) ?> <span>SAR</span></div>
    </div>

    <!-- History -->
    <div class="pbb-history-container">
        <div class="pbb-history-title">
            سجل المزايدات
            <span class="live-txt"><i class="ph-bold ph-wifi-high"></i> تحديث لحظي</span>
        </div>
        <div class="pbb-history-list" id="bidding-history">
            <div class="pbb-loading">
                <i class="ph-duotone ph-spinner-gap pbb-loading__icon"></i>
                <div class="pbb-loading__text">جاري تحميل السجل...</div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="pbb-action-area">
        <div class="pbb-quick-grid">
            <button class="pbb-btn-quick" onclick="addBid(500)">+500</button>
            <button class="pbb-btn-quick" onclick="addBid(1000)">+1K</button>
            <button class="pbb-btn-quick" onclick="addBid(2000)">+2K</button>
            <button class="pbb-btn-quick" onclick="addBid(5000)">+5K</button>
        </div>
        <div class="pbb-input-row">
            <input type="number" id="custom-bid-input" class="pbb-input" placeholder="أدخل مبلغك (SAR)..." step="500">
            <button class="pbb-btn-submit" onclick="submitCustomBid()">مزايدة الآن</button>
        </div>

        <!-- AI Toggle -->
        <div class="pbb-ai-wrapper" id="ai-wrapper-box">
            <div class="pbb-ai-header">
                <div class="pbb-ai-title">
                    <i class="ph-fill ph-robot"></i> المساعد الذكي
                </div>
                <label class="switch">
                    <input type="checkbox" id="ai-toggle-switch" onchange="handleAIToggle(this)">
                    <span class="slider"></span>
                </label>
            </div>
            <div class="pbb-ai-body" id="ai-config-body">
                <div class="pbb-ai-desc">سيقوم المساعد بالمزايدة التلقائية عنك بالحد الأدنى للزيادة حتى تصل للحد الأقصى لضمان فوزك بأفضل سعر.</div>
                <div class="pbb-input-row pbb-input-row--flush">
                    <input type="number" id="ai-max-bid" class="pbb-input pbb-input--dark-border" placeholder="الحد الأقصى (SAR)...">
                    <button class="pbb-btn-submit pbb-btn-submit--primary" onclick="startAI()">تفعيل</button>
                </div>
            </div>
            <div class="pbb-ai-body" id="ai-active-body">
                <div class="pbb-ai-active-banner">
                    <i class="ph-duotone ph-spinner-gap pbb-ai-active-banner__spin"></i>
                    المساعد نشط - الحد الأقصى: <span id="ai-max-display" class="pbb-ai-active-banner__max"></span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="pbb-terms">بالنقر، أنت توافق على شروط وأحكام المزاد المباشر وتلتزم بالشراء.</div>
</div>

</div>
</div>

<script>
// --- New Premium JS Logic ---
const auctionId = <?= $auction['id'] ?? 1 ?>;
let currentPrice = <?= $current_price ?>;
let minIncrement = <?= $min_increment ?>;
const historyContainer = document.getElementById('bidding-history');
const priceDisplay = document.getElementById('current-price-display');

function formatMoney(amount) {
  return amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") + ' <span>SAR</span>';
}

let renderedBidAmounts = new Set();

function renderBids(bids) {
  if (renderedBidAmounts.size === 0) {
      historyContainer.innerHTML = '';
  }
  
  for (let i = bids.length - 1; i >= 0; i--) {
      const bid = bids[i];
      if (!renderedBidAmounts.has(bid.amount)) {
          renderedBidAmounts.add(bid.amount);
          
          const isUser = bid.isUser;
          const userLabel = bid.user;
          const formattedValue = bid.amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
          
          const div = document.createElement('div');
          div.className = `pbb-card ${isUser ? 'me' : ''}`;
          div.dataset.amount = bid.amount;
          
          div.innerHTML = `
            <div class="pbb-c-info">
                <div class="pbb-c-user">
                    ${userLabel}
                    <i class="ph-fill ph-check-circle pbb-c-icon" style="display:none; font-size:14px;"></i>
                </div>
                <div class="pbb-c-time">${bid.time}</div>
            </div>
            <div class="pbb-c-price">${formattedValue}</div>
          `;
          
          historyContainer.insertBefore(div, historyContainer.firstChild);
      }
  }

  const allItems = historyContainer.querySelectorAll('.pbb-card');
  allItems.forEach((item, index) => {
      const icon = item.querySelector('.pbb-c-icon');
      if (index === 0) {
          item.classList.add('highest');
          if(icon) {
              icon.style.display = 'inline-block';
              if(item.classList.contains('me')) icon.style.color = '#1bc976';
          }
      } else {
          item.classList.remove('highest');
          if(icon) icon.style.display = 'none';
      }
  });
}

// AI Autobidding State
let aiBiddingActive = false;
let aiMaxBid = 0;

function handleAIToggle(checkbox) {
    const configBody = document.getElementById('ai-config-body');
    const activeBody = document.getElementById('ai-active-body');
    const wrapper = document.getElementById('ai-wrapper-box');
    
    if(checkbox.checked) {
        // Show config to enter max bid
        configBody.classList.add('show');
        activeBody.classList.remove('show');
        wrapper.classList.add('active');
    } else {
        // Turn off completely
        aiBiddingActive = false;
        configBody.classList.remove('show');
        activeBody.classList.remove('show');
        wrapper.classList.remove('active');
        if(typeof showToast === 'function') showToast("تم إيقاف المساعد الذكي", 'info');
    }
}

function startAI() {
    const val = parseInt(document.getElementById('ai-max-bid').value);
    if (isNaN(val) || val <= currentPrice) {
        if(typeof showToast === 'function') showToast("يرجى إدخال حد أقصى أعلى من السعر الحالي.", 'warning');
        return;
    }
    aiMaxBid = val;
    aiBiddingActive = true;
    
    document.getElementById('ai-max-display').innerText = aiMaxBid.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    document.getElementById('ai-config-body').classList.remove('show');
    document.getElementById('ai-active-body').classList.add('show');
    
    if(typeof showToast === 'function') showToast("تم تفعيل المساعد الذكي بنجاح", 'success');
}

async function fetchBids() {
  try {
    const res = await fetch(`/api/get-bids.php?auction_id=${auctionId}`);
    const data = await res.json();
    if (data.success) {
      if (data.current_price > currentPrice) {
        currentPrice = data.current_price;
        priceDisplay.innerHTML = formatMoney(currentPrice);
        syncMobileBidBar();
      }
      if (data.bids && data.bids.length > 0) {
        renderBids(data.bids);
        
        const highestBid = data.bids[0];
        if (aiBiddingActive && !highestBid.isUser) {
           const nextBidAmount = currentPrice + minIncrement;
           if (aiMaxBid >= nextBidAmount) {
              placeBidAPI(nextBidAmount, true);
           } else {
               if(typeof showToast === 'function') showToast("تجاوز السعر حدك الأقصى وتم إيقاف المساعد الذكي.", 'warning');
               document.getElementById('ai-toggle-switch').click(); // toggle off
           }
        }
      }
    }
  } catch (e) {
    console.error(e);
  }
}

async function placeBidAPI(amount, isAuto = false) {
  try {
    if(!isAuto && aiBiddingActive) {
        if(typeof showToast === 'function') showToast("المساعد الذكي مفعل. أوقفه أولاً للقيام بمزايدة يدوية.", 'warning');
        return;
    }
    
    const formData = new FormData();
    formData.append('auction_id', auctionId);
    formData.append('amount', amount);
    
    const res = await fetch('/api/place-bid.php', { method: 'POST', body: formData });
    const data = await res.json();
    
    if (data.success) {
      currentPrice = amount;
      priceDisplay.innerHTML = formatMoney(currentPrice);
      syncMobileBidBar();
      if(!isAuto && typeof showToast === 'function') {
         showToast(data.message || "تمت المزايدة بنجاح", 'success');
      } else if (isAuto && typeof showToast === 'function') {
         showToast("المساعد الذكي أضاف مزايدة جديدة!", 'success');
      }
      fetchBids();
    } else {
      if(!isAuto && typeof showToast === 'function') showToast(data.message || "حدث خطأ في المزايدة", 'error');
    }
  } catch (e) {
    console.error(e);
  }
}

function addBid(increment) { placeBidAPI(currentPrice + increment); }

function submitCustomBid() {
  const input = document.getElementById('custom-bid-input');
  const val = parseInt(input.value);
  if (!val || val <= currentPrice) {
    if(typeof showToast === 'function') showToast("يرجى إدخال مبلغ أعلى من السعر الحالي.", 'warning');
    return;
  }
  placeBidAPI(val);
  input.value = '';
}

fetchBids();
setInterval(fetchBids, 3000);

const timerEl = document.getElementById('room-timer');
const endTime = parseInt(timerEl.getAttribute('data-endtime')) * 1000;
const hEl = document.getElementById('timer-h');
const mEl = document.getElementById('timer-m');
const sEl = document.getElementById('timer-s');

setInterval(() => {
  let diff = endTime - new Date().getTime();
  if (diff < 0) diff = 0;
  let h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
  let m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
  let s = Math.floor((diff % (1000 * 60)) / 1000);
  
  if (hEl && mEl && sEl) {
      hEl.innerText = h.toString().padStart(2,'0');
      mEl.innerText = m.toString().padStart(2,'0');
      sEl.innerText = s.toString().padStart(2,'0');
  }
}, 1000);

function syncMobileBidBar() {
  const m = document.getElementById('mobile-bid-price');
  if (m) m.textContent = currentPrice.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',') + ' SAR';
}
function changePremiumImage(thumbEl, src) {
  document.getElementById('main-gallery-img').src = src;
  document.querySelectorAll('.mpg-thumb').forEach(t => t.classList.remove('active'));
  thumbEl.classList.add('active');
}
</script>

<!-- Mobile sticky bid bar -->
<div class="fx-mobile-bid-bar" id="mobile-bid-bar">
  <div class="fx-mobile-bid-inner">
    <div class="fx-mobile-bid-price">
      <small>السعر الحالي</small>
      <strong id="mobile-bid-price"><?= number_format($current_price) ?> SAR</strong>
    </div>
    <button class="fx-mobile-bid-btn" onclick="document.getElementById('custom-bid-input')?.focus(); window.scrollTo({top:0,behavior:'smooth'}); addBid(<?= $min_increment ?>);">
      <i class="ph-bold ph-gavel"></i> زايد الآن
    </button>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
