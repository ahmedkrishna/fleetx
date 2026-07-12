<?php
// includes/navbar.php - FleetX Shared Navbar
include_once __DIR__ . '/splash.inc.php';
$current_page = basename($_SERVER['PHP_SELF']);
$is_seller = (getUserRole() === 'seller');
$is_buyer  = (getUserRole() === 'buyer');
$is_admin  = (getUserRole() === 'admin');
$is_inspector = (getUserRole() === 'inspector');
$is_logged = isLoggedIn();
$notif_count = 0;
$dash_url = $is_logged ? getDashboardUrl() : '/login.php';
$dash_label = 'لوحة التحكم';
if ($is_admin) $dash_label = 'لوحة الإدارة';
elseif ($is_seller) $dash_label = 'لوحة البائع';
elseif ($is_inspector) $dash_label = 'لوحة الفحص';
elseif ($is_buyer) $dash_label = 'لوحة المشتري';

?>

<?= fleetx_db_banner_html() ?>
<nav class="navbar" id="navbar">
  <div class="container">
    <div class="navbar-inner">

      <!-- Logo -->
      <a href="/index.php" class="navbar-logo">
        <?php $fx_logo_bg = 'auto'; $fx_logo_link = ''; include __DIR__ . '/fx-logo.inc.php'; ?>
      </a>

      <!-- Nav Links -->
      <ul class="navbar-links" id="navLinks">
        <li><a href="/auctions.php" class="<?= ($current_page==='auctions.php' && !isset($_GET['type'])) ? 'active' : '' ?>"><?= fleetx_t('nav_auctions') ?></a></li>
        <li><a href="/auctions.php?type=instant" class="<?= (isset($_GET['type']) && $_GET['type']==='instant') ? 'active' : '' ?>"><?= fleetx_t('nav_instant') ?></a></li>
        <li><a href="/companies.php" class="<?= $current_page==='companies.php' ? 'active' : '' ?>"><?= fleetx_t('nav_companies') ?></a></li>
        <li><a href="/map.php" class="<?= $current_page==='map.php' ? 'active' : '' ?>"><?= fleetx_t('nav_map') ?></a></li>

        <?php if ($is_seller || $is_admin): ?>
        <li class="nav-dropdown" id="addDropdown">
          <a href="#" class="<?= $current_page==='add-auction.php' ? 'active' : '' ?>" onclick="toggleDropdown('addDropdown'); return false;">
            <i class="ph ph-plus-circle fx-nav-add-icon"></i> أضف إعلان
          </a>
          <div class="nav-dropdown-content">
            <a href="/add-auction.php">
              <i class="ph-fill ph-gavel fx-nav-dropdown-icon fx-nav-dropdown-icon--primary"></i>
              <div>
                <div class="fx-nav-dropdown-title">جدولة مزاد مباشر</div>
                <div class="fx-nav-dropdown-desc">حدد وقت البداية والنهاية</div>
              </div>
            </a>
            <a href="/add-auction.php?type=instant">
              <i class="ph-fill ph-lightning fx-nav-dropdown-icon fx-nav-dropdown-icon--amber"></i>
              <div>
                <div class="fx-nav-dropdown-title">بيع فوري</div>
                <div class="fx-nav-dropdown-desc">سعر ثابت بدون مزايدة</div>
              </div>
            </a>
            <a href="/bulk-upload.php">
              <i class="ph-fill ph-upload-simple fx-nav-dropdown-icon fx-nav-dropdown-icon--violet"></i>
              <div>
                <div class="fx-nav-dropdown-title">رفع مجمّع Excel</div>
                <div class="fx-nav-dropdown-desc">رفع أسطول كامل دفعة واحدة</div>
              </div>
            </a>
          </div>
        </li>
        <?php endif; ?>

        <li><a href="/about.php" class="<?= $current_page==='about.php' ? 'active' : '' ?>"><?= fleetx_t('nav_about') ?></a></li>
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
              <span class="notif-badge<?= $notif_count > 0 ? ' is-visible' : '' ?>" id="notifBadge">
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

          <a href="<?= $dash_url ?>" class="btn btn-outline btn-sm hide-on-mobile fx-btn-round">
            <i class="ph ph-squares-four ph-space-left"></i> <?= $dash_label ?>
          </a>
          <span class="hide-on-mobile fx-nav-user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></span>
          <a href="/logout.php" class="btn btn-outline btn-sm hide-on-mobile fx-nav-logout">خروج</a>

        <?php else: ?>
          <a href="/login.php" class="btn-login btn-login--green-stroke hide-on-mobile"><?= fleetx_t('nav_login') ?></a>
          <a href="/register.php" class="btn btn-primary btn-sm hide-on-mobile fx-btn-round"><?= fleetx_t('nav_register') ?></a>
        <?php endif; ?>

        <button class="navbar-toggle" id="navToggle" aria-label="قائمة التنقل" aria-expanded="false" aria-controls="mobileMenu">
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
      <li class="fx-mobile-notif-wrap">
        <a href="#" class="fx-mobile-notif-link" id="mobileNotifLink" onclick="toggleMobileNotifs(); return false;" aria-expanded="false">
          <span><i class="ph ph-bell"></i> الإشعارات</span>
          <span class="fx-mobile-notif-badge<?= $notif_count > 0 ? ' is-visible' : '' ?>" id="mobileNotifBadge"><?= $notif_count > 9 ? '9+' : $notif_count ?></span>
        </a>
        <div class="fx-mobile-notif-panel" id="mobileNotifPanel" hidden>
          <div class="fx-mobile-notif-panel__head">
            <span>الإشعارات</span>
            <a href="#" onclick="markAllRead(); return false;">تحديد الكل كمقروء</a>
          </div>
          <div class="fx-mobile-notif-panel__list" id="mobileNotifList">
            <div class="fx-notif-loading">جاري التحميل...</div>
          </div>
        </div>
      </li>
      <?php endif; ?>
      <?php if ($is_seller || $is_admin): ?>
      <li><a href="/add-auction.php"><i class="ph ph-plus-circle"></i> جدولة مزاد</a></li>
      <li><a href="/add-auction.php?type=instant"><i class="ph ph-lightning"></i> بيع فوري</a></li>
      <li><a href="/bulk-upload.php"><i class="ph ph-upload-simple"></i> رفع مجمّع Excel</a></li>
      <?php endif; ?>
      <li><a href="/about.php"><i class="ph ph-question"></i> كيف يعمل</a></li>

      <?php if (!$is_logged): ?>
        <li class="fx-mobile-cta-row">
          <a href="/login.php" class="btn-login btn-login--green-stroke fx-mobile-login">دخول المنصة</a>
          <a href="/register.php" class="btn btn-primary fx-mobile-register">تسجيل جديد</a>
        </li>
        <li>
          <a href="/index.php?guest=1" class="fx-mobile-guest-link">تصفح بدون تسجيل</a>
        </li>
      <?php else: ?>
        <li class="fx-mobile-auth-section">
          <div class="mobile-auth-modern">
            <a href="<?= $dash_url ?>" class="btn-mobile-auth btn-green-stroke">
              <i class="ph-fill ph-user fx-mobile-auth-icon fx-mobile-auth-icon--primary"></i> <?= htmlspecialchars($_SESSION['user_name'] ?? 'حسابي') ?>
            </a>
            <a href="/logout.php" class="btn-mobile-auth btn-white-stroke">
              <i class="ph-bold ph-sign-out fx-mobile-auth-icon fx-mobile-auth-icon--white"></i> تسجيل الخروج
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

function fxEscapeHtml(str) {
  const d = document.createElement('div');
  d.textContent = str || '';
  return d.innerHTML;
}

function fxSetMobileMenuOpen(open) {
  const menu = document.getElementById('mobileMenu');
  const toggle = document.getElementById('navToggle');
  if (!menu || !toggle) return;
  menu.classList.toggle('open', open);
  document.body.classList.toggle('fx-mobile-menu-open', open);
  toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  const icon = toggle.querySelector('i');
  if (icon) {
    icon.className = open ? 'ph ph-x navbar-toggle-icon' : 'ph ph-list navbar-toggle-icon';
  }
}

document.getElementById('navToggle')?.addEventListener('click', function() {
  const menu = document.getElementById('mobileMenu');
  fxSetMobileMenuOpen(!menu?.classList.contains('open'));
});

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') fxSetMobileMenuOpen(false);
});

document.getElementById('mobileMenu')?.querySelectorAll('a').forEach(function(link) {
  link.addEventListener('click', function() { fxSetMobileMenuOpen(false); });
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

function toggleMobileNotifs() {
  const panel = document.getElementById('mobileNotifPanel');
  const link = document.getElementById('mobileNotifLink');
  if (!panel) return;
  const open = panel.hasAttribute('hidden');
  if (open) {
    panel.removeAttribute('hidden');
    link?.setAttribute('aria-expanded', 'true');
    fetchNotifications();
  } else {
    panel.setAttribute('hidden', '');
    link?.setAttribute('aria-expanded', 'false');
  }
}

function renderNotificationItems(notifications) {
  return notifications.map(n => {
    const ic = notifIcons[n.type] || notifIcons.system;
    const link = fxEscapeHtml(n.link || '#');
    const title = fxEscapeHtml(n.title);
    const body = fxEscapeHtml(n.body);
    const time = fxEscapeHtml(n.time_ago);
    return `
      <a href="${link}" class="notif-item ${!n.is_read ? 'unread' : ''}" onclick="markRead(${parseInt(n.id, 10)});">
        <div class="notif-icon" style="background:${ic.bg}; color:${ic.color};">
          <i class="${ic.icon}"></i>
        </div>
        <div class="notif-text">
          <div class="notif-title">${title}</div>
          <div class="notif-body">${body}</div>
          <div class="notif-time">${time}</div>
        </div>
      </a>`;
  }).join('');
}

function renderNotifications(data) {
  const badge  = document.getElementById('notifBadge');
  const list   = document.getElementById('notifList');
  const mobileList = document.getElementById('mobileNotifList');

  const mobileBadge = document.getElementById('mobileNotifBadge');
  const countLabel = data.unread_count > 9 ? '9+' : String(data.unread_count || '');
  [badge, mobileBadge].forEach(function(b) {
    if (!b) return;
    if (data.unread_count > 0) {
      b.textContent = countLabel;
      b.classList.add('is-visible');
    } else {
      b.textContent = '';
      b.classList.remove('is-visible');
    }
  });

  const emptyHtml = '<div class="fx-notif-empty">لا توجد إشعارات</div>';
  if (!data.notifications || data.notifications.length === 0) {
    if (list) list.innerHTML = emptyHtml;
    if (mobileList) mobileList.innerHTML = emptyHtml;
    return;
  }

  const html = renderNotificationItems(data.notifications);
  if (list) list.innerHTML = html;
  if (mobileList) mobileList.innerHTML = html;
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
      if (badge) badge.classList.remove('is-visible');
    })
    .catch(() => {});
}

// Initial fetch + poll every 30s
fetchNotifications();
setInterval(fetchNotifications, 30000);
<?php endif; ?>
</script>
