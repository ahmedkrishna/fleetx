<?php
// includes/navbar.php - FleetX Shared Navbar
$current_page = basename($_SERVER['PHP_SELF']);
$is_seller = (getUserRole() === 'seller');
$is_buyer  = (getUserRole() === 'buyer');
$is_admin  = (getUserRole() === 'admin');
$is_inspector = (getUserRole() === 'inspector');
$is_logged = isLoggedIn();
$dash_url = $is_logged ? getDashboardUrl() : '/login.php';
$dash_label = 'لوحة التحكم';
if ($is_admin) $dash_label = 'لوحة الإدارة';
elseif ($is_seller) $dash_label = 'لوحة البائع';
elseif ($is_inspector) $dash_label = 'لوحة الفحص';
elseif ($is_buyer) $dash_label = 'لوحة المشتري';

$navbar_logo_src = fleetx_logo_src();
?>

<nav class="navbar" id="navbar">
  <div class="container">
    <div class="navbar-inner">

      <!-- Logo -->
      <a href="/index.php" class="navbar-logo">
        <img src="<?= $navbar_logo_src ?>" alt="FleetX Logo">
      </a>

      <!-- Nav Links -->
      <ul class="navbar-links" id="navLinks">
        <li><a href="/auctions.php" class="<?= ($current_page==='auctions.php' && !isset($_GET['type'])) ? 'active' : '' ?>">المزادات الحية</a></li>
        <li><a href="/auctions.php?type=instant" class="<?= (isset($_GET['type']) && $_GET['type']==='instant') ? 'active' : '' ?>">الشراء الفوري</a></li>
        <li><a href="/companies.php" class="<?= $current_page==='companies.php' ? 'active' : '' ?>">دليل الشركات</a></li>
        <li><a href="/map.php" class="<?= $current_page==='map.php' ? 'active' : '' ?>">خريطة المزادات</a></li>

        <?php if ($is_seller || $is_admin): ?>
        <li class="nav-dropdown" id="addDropdown">
          <a href="#" class="<?= $current_page==='add-auction.php' ? 'active' : '' ?>" onclick="toggleDropdown('addDropdown'); return false;">
            <i class="ph ph-plus-circle" style="font-size:16px;"></i> أضف إعلان
          </a>
          <div class="nav-dropdown-content">
            <a href="/add-auction.php">
              <i class="ph-fill ph-gavel" style="color:var(--primary); font-size:18px;"></i>
              <div>
                <div>جدولة مزاد مباشر</div>
                <div style="font-size:11px; color:var(--text-muted); font-weight:600;">حدد وقت البداية والنهاية</div>
              </div>
            </a>
            <a href="/add-auction.php?type=instant">
              <i class="ph-fill ph-lightning" style="color:#f59e0b; font-size:18px;"></i>
              <div>
                <div>بيع فوري</div>
                <div style="font-size:11px; color:var(--text-muted); font-weight:600;">سعر ثابت بدون مزايدة</div>
              </div>
            </a>
            <a href="/bulk-upload.php">
              <i class="ph-fill ph-upload-simple" style="color:#8b5cf6; font-size:18px;"></i>
              <div>
                <div>رفع مجمّع Excel</div>
                <div style="font-size:11px; color:var(--text-muted); font-weight:600;">رفع أسطول كامل دفعة واحدة</div>
              </div>
            </a>
          </div>
        </li>
        <?php endif; ?>

        <li><a href="/about.php" class="<?= $current_page==='about.php' ? 'active' : '' ?>">كيف يعمل</a></li>
      </ul>

      <!-- Actions -->
      <div class="navbar-actions">

        <?php if ($is_logged): ?>
          <!-- Notification Bell — count fetched server-side for initial badge, list fetched client-side via JS -->
          <?php
          $notif_count = 0;
          if ($db_connected) {
              $uid  = (int)$_SESSION['user_id'];
              $cres = $conn->query("SELECT COUNT(*) FROM notifications WHERE user_id=$uid AND is_read=0");
              if ($cres) { $notif_count = (int)$cres->fetch_row()[0]; }
          } else {
              $notif_count = 2; // mock
          }
          ?>
          <div class="notif-dropdown hide-on-mobile" id="notifDropdown">
            <button class="notif-btn" onclick="toggleDropdown('notifDropdown'); return false;" aria-label="الإشعارات">
              <i class="ph ph-bell"></i>
              <span class="notif-badge" id="notifBadge" style="display:<?= $notif_count > 0 ? 'flex' : 'none' ?>;">
                <?= $notif_count > 9 ? '9+' : $notif_count ?>
              </span>
            </button>
            <div class="notif-panel">
              <div class="notif-head">
                <span>الإشعارات</span>
                <a href="#" onclick="markAllRead(); return false;">تحديد الكل كمقروء</a>
              </div>
              <div class="notif-list" id="notifList">
                <!-- Populated by fetchNotifications() JS -->
                <div class="fx-notif-loading">جاري التحميل...</div>
              </div>
            </div>
          </div>

          <a href="<?= $dash_url ?>" class="btn btn-outline btn-sm hide-on-mobile" style="border-radius: var(--radius-round)">
            <i class="ph ph-squares-four ph-space-left"></i> <?= $dash_label ?>
          </a>
          <span class="hide-on-mobile" style="color:rgba(255,255,255,0.6); font-size:13px; font-weight:600; margin:0 4px;"><?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></span>
          <a href="/logout.php" class="btn btn-outline btn-sm hide-on-mobile" style="opacity:0.6; margin-right:4px; border-radius: var(--radius-round); border-color: transparent">خروج</a>

        <?php else: ?>
          <a href="/login.php" class="btn-login btn-login--green-stroke hide-on-mobile">دخول المنصة</a>
          <a href="/register.php" class="btn btn-primary btn-sm hide-on-mobile" style="border-radius: var(--radius-round)">سجل الآن</a>
        <?php endif; ?>

        <button class="navbar-toggle" id="navToggle" aria-label="قائمة">
          <i class="ph ph-list navbar-toggle-icon"></i>
        </button>
      </div>

    </div>
  </div>

  <!-- Mobile Menu -->
  <div id="mobileMenu">
    <ul>
      <li><a href="/auctions.php"><i class="ph ph-gavel"></i> المزادات الحية</a></li>
      <li><a href="/auctions.php?type=instant"><i class="ph ph-lightning"></i> الشراء الفوري</a></li>
      <li><a href="/companies.php"><i class="ph ph-buildings"></i> دليل الشركات</a></li>
      <li><a href="/map.php"><i class="ph ph-map-trifold"></i> خريطة المزادات</a></li>
      <?php if ($is_logged): ?>
      <li><a href="<?= $dash_url ?>" class="fx-mobile-active"><i class="ph ph-squares-four"></i> <?= $dash_label ?></a></li>
      <?php endif; ?>
      <?php if ($is_seller || $is_admin): ?>
      <li><a href="/add-auction.php"><i class="ph ph-plus-circle"></i> جدولة مزاد</a></li>
      <li><a href="/add-auction.php?type=instant"><i class="ph ph-lightning"></i> بيع فوري</a></li>
      <li><a href="/bulk-upload.php"><i class="ph ph-upload-simple"></i> رفع مجمّع Excel</a></li>
      <?php endif; ?>
      <li><a href="/about.php"><i class="ph ph-question"></i> كيف يعمل</a></li>

      <?php if (!$is_logged): ?>
        <li style="margin-top:16px; display:flex; gap:12px;">
          <a href="/login.php" class="btn-login btn-login--green-stroke fx-mobile-login">دخول المنصة</a>
          <a href="/register.php" class="btn btn-primary fx-mobile-register">تسجيل جديد</a>
        </li>
        <li>
          <a href="/index.php?guest=1" style="display:block; text-align:center; color:rgba(255,255,255,0.5); font-size:13px; margin-top:8px;">تصفح بدون تسجيل</a>
        </li>
      <?php else: ?>
        <li style="margin-top:24px; border-top:1px solid rgba(255,255,255,0.1); padding-top:24px; display:flex; flex-direction:column; align-items:center;">
          <div class="mobile-auth-modern">
            <a href="<?= $dash_url ?>" class="btn-mobile-auth btn-green-stroke">
              <i class="ph-fill ph-user" style="font-size:20px; color:var(--primary);"></i> <?= htmlspecialchars($_SESSION['user_name'] ?? 'حسابي') ?>
            </a>
            <a href="/logout.php" class="btn-mobile-auth btn-white-stroke">
              <i class="ph-bold ph-sign-out" style="font-size:20px; color:#fff;"></i> تسجيل الخروج
            </a>
          </div>
        </li>
      <?php endif; ?>
    </ul>
  </div>
</nav>

<script>
// Dropdown toggle
function toggleDropdown(id) {
  const el = document.getElementById(id);
  document.querySelectorAll('.nav-dropdown.open, .notif-dropdown.open').forEach(d => {
    if (d.id !== id) d.classList.remove('open');
  });
  if (el) el.classList.toggle('open');
}

// Close on outside click
document.addEventListener('click', function(e) {
  if (!e.target.closest('.nav-dropdown') && !e.target.closest('.notif-dropdown')) {
    document.querySelectorAll('.nav-dropdown.open, .notif-dropdown.open').forEach(d => d.classList.remove('open'));
  }
});

// Mobile toggle
document.getElementById('navToggle')?.addEventListener('click', function() {
  document.getElementById('mobileMenu')?.classList.toggle('open');
});

<?php if ($is_logged): ?>
// ── Live Notifications Polling ──────────────────────────────
const notifIcons = {
  bid_outbid:          { icon: 'ph-fill ph-gavel',         bg: 'rgba(239,68,68,0.1)',    color: '#ef4444' },
  auction_won:         { icon: 'ph-fill ph-trophy',        bg: 'rgba(27,201,118,0.1)',   color: '#16a34a' },
  vehicle_approved:    { icon: 'ph-fill ph-check-circle',  bg: 'rgba(14,165,233,0.1)',   color: '#0ea5e9' },
  inspection_assigned: { icon: 'ph-fill ph-magnifying-glass', bg: 'rgba(245,158,11,0.1)', color: '#f59e0b' },
  payment:             { icon: 'ph-fill ph-money',         bg: 'rgba(139,92,246,0.1)',   color: '#8b5cf6' },
  system:              { icon: 'ph-fill ph-bell',          bg: 'rgba(100,116,139,0.1)',  color: '#64748b' },
};

function renderNotifications(data) {
  const badge  = document.getElementById('notifBadge');
  const list   = document.getElementById('notifList');
  if (!list) return;

  // Update badge
  if (badge) {
    if (data.unread_count > 0) {
      badge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
      badge.style.display = 'flex';
    } else {
      badge.style.display = 'none';
    }
  }

  // Render list
  if (!data.notifications || data.notifications.length === 0) {
    list.innerHTML = '<div class="fx-notif-empty">لا توجد إشعارات</div>';
    return;
  }

  list.innerHTML = data.notifications.map(n => {
    const ic = notifIcons[n.type] || notifIcons.system;
    return `
      <a href="${n.link || '#'}" class="notif-item ${!n.is_read ? 'unread' : ''}" onclick="markRead(${n.id});">
        <div class="notif-icon" style="background:${ic.bg}; color:${ic.color};">
          <i class="${ic.icon}"></i>
        </div>
        <div class="notif-text">
          <div class="notif-title">${n.title}</div>
          <div class="notif-body">${n.body}</div>
          <div class="notif-time">${n.time_ago}</div>
        </div>
      </a>`;
  }).join('');
}

function fetchNotifications() {
  fetch('/api/notifications.php?action=list')
    .then(r => r.json())
    .then(data => { if (data.success) renderNotifications(data); })
    .catch(() => {}); // silent fail
}

function markRead(id) {
  fetch('/api/notifications.php?action=mark_one&id=' + id).catch(() => {});
  const item = document.querySelector(`.notif-item.unread[onclick*="${id}"]`);
  if (item) item.classList.remove('unread');
}

function markAllRead() {
  fetch('/api/notifications.php?action=mark_read')
    .then(() => {
      document.querySelectorAll('.notif-item.unread').forEach(i => i.classList.remove('unread'));
      const badge = document.getElementById('notifBadge');
      if (badge) badge.style.display = 'none';
    })
    .catch(() => {});
}

// Initial fetch + poll every 30s
fetchNotifications();
setInterval(fetchNotifications, 30000);
<?php endif; ?>
</script>
