<?php
require_once 'config.php';
requireLogin();
if (!in_array(getUserRole(), ['buyer', 'admin'], true)) {
    header('Location: ' . getDashboardUrl());
    exit;
}

$section = isset($_GET['section']) ? sanitize($_GET['section']) : 'dashboard';
if ($section === 'watchlist') $section = 'favorites';
$user_name = $_SESSION['user_name'] ?? 'مستخدم';
$user_id = $_SESSION['user_id'] ?? 0;
if ($db_connected) {
    $wst = $conn->prepare('SELECT wallet_balance, nafath_verified, sanad_limit FROM users WHERE id = ?');
    $wst->bind_param('i', $user_id);
    $wst->execute();
    if ($wrow = $wst->get_result()->fetch_assoc()) {
        $_SESSION['wallet_balance']  = floatval($wrow['wallet_balance'] ?? 0);
        $_SESSION['nafath_verified'] = (int)($wrow['nafath_verified'] ?? 0);
        $_SESSION['sanad_limit']     = floatval($wrow['sanad_limit'] ?? 0);
    }
} else {
    initWalletBalance();
}

// ── Handle wallet top-up POST ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['topup_amount'])) {
    $amount = intval($_POST['topup_amount']);
    if ($amount > 0) {
        $_SESSION['wallet_balance'] = ($_SESSION['wallet_balance'] ?? 0) + $amount;
        if ($db_connected) {
            $stmt = $conn->prepare('UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?');
            $stmt->bind_param('di', $amount, $_SESSION['user_id']);
            $stmt->execute();
        }
    }
    header('Location: ?section=wallet');
    exit;
}

// ── Handle buyer subscription POST ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subscribe_plan']) && $db_connected && fleetx_table_exists($conn, 'buyer_subscriptions')) {
    $plan = $_POST['subscribe_plan'] ?? 'pro';
    $prices = ['free' => 0, 'pro' => (float)(fleetx_get_setting($conn, 'buyer_pro_price', 299)), 'enterprise' => 999];
    $price = $prices[$plan] ?? 299;
    $start = date('Y-m-d');
    $end = date('Y-m-d', strtotime('+1 year'));
    $conn->query("UPDATE buyer_subscriptions SET is_active=0 WHERE user_id=" . (int)$user_id);
    $stmt = $conn->prepare('INSERT INTO buyer_subscriptions (user_id, plan, price, start_date, end_date, is_active) VALUES (?,?,?,?,?,1)');
    $stmt->bind_param('isdss', $user_id, $plan, $price, $start, $end);
    $stmt->execute();
    fleetx_set_toast('تم تفعيل باقة ' . $plan . ' بنجاح');
    header('Location: ?section=subscription');
    exit;
}

// ── Handle settings save POST ──────────────────────────────
$settings_success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $new_name = sanitize($_POST['full_name'] ?? '');
    $new_phone = sanitize($_POST['phone'] ?? '');
    $new_city = sanitize($_POST['city'] ?? '');
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if ($new_name) {
        $_SESSION['user_name'] = $new_name;
        $user_name = $new_name;
    }
    if ($new_phone) $_SESSION['user_phone'] = $new_phone;
    if ($new_city) $_SESSION['user_city'] = $new_city;

    if ($db_connected) {
        if ($new_pass && $new_pass === $confirm_pass) {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE users SET full_name = ?, mobile = ?, city = ?, password_hash = ? WHERE id = ?');
            $stmt->bind_param('ssssi', $new_name, $new_phone, $new_city, $hashed, $_SESSION['user_id']);
            $stmt->execute();
        } else {
            $stmt = $conn->prepare('UPDATE users SET full_name = ?, mobile = ?, city = ? WHERE id = ?');
            $stmt->bind_param('sssi', $new_name, $new_phone, $new_city, $_SESSION['user_id']);
            $stmt->execute();
        }

        $wa_mobile = $new_phone ?: ($_SESSION['user_phone'] ?? '');
        if ($wa_mobile && isset($_POST['whatsapp_optin'])) {
            if (($_POST['whatsapp_optin'] ?? '0') === '1') {
                fleetx_whatsapp_optin_register($wa_mobile, $conn, (int)$_SESSION['user_id']);
            } else {
                fleetx_whatsapp_optout($wa_mobile, $conn, (int)$_SESSION['user_id']);
            }
        }
    }
    fleetx_set_toast('تم حفظ التغييرات بنجاح');
    header('Location: ?section=settings');
    exit;
}

$buyer_section_meta = [
    'dashboard' => ['icon' => 'ph-fill ph-squares-four', 'icon_class' => 'fx-dash-title-icon', 'label' => 'لوحة التحكم'],
    'bids'      => ['icon' => 'ph-fill ph-gavel', 'icon_class' => 'fx-dash-title-icon', 'label' => 'مزايداتي'],
    'purchases' => ['icon' => 'ph-fill ph-shopping-bag', 'icon_class' => 'fx-dash-title-icon', 'label' => 'مشترياتي'],
    'favorites' => ['icon' => 'ph-fill ph-heart', 'icon_class' => 'fx-dash-title-icon fx-dash-title-icon--danger', 'label' => 'المفضلة'],
    'wallet'    => ['icon' => 'ph-fill ph-wallet', 'icon_class' => 'fx-dash-title-icon', 'label' => 'المحفظة'],
    'subscription' => ['icon' => 'ph-fill ph-crown', 'icon_class' => 'fx-dash-title-icon', 'label' => 'الاشتراك'],
    'settings'  => ['icon' => 'ph-fill ph-gear', 'icon_class' => 'fx-dash-title-icon', 'label' => 'إعدادات الحساب'],
];

$buyer_subscription = $db_connected ? getBuyerSubscription($conn, (int)$user_id) : null;
$buyer_sec = $buyer_section_meta[$section] ?? $buyer_section_meta['dashboard'];

// Hero quick stats
$buyer_wallet_hero = $_SESSION['wallet_balance'] ?? 0;
$buyer_active_bids_hero = 0;
$buyer_won_hero = 0;
if ($db_connected) {
    $hb = $conn->prepare('SELECT COUNT(DISTINCT b.auction_id) FROM bids b JOIN auctions a ON b.auction_id = a.id WHERE b.user_id = ? AND a.status IN ("active","live")');
    if ($hb) {
        $hb->bind_param('i', $user_id);
        $hb->execute();
        $buyer_active_bids_hero = (int)($hb->get_result()->fetch_row()[0] ?? 0);
    }
    $hw = $conn->prepare('SELECT COUNT(*) FROM auctions WHERE winner_id = ?');
    if ($hw) {
        $hw->bind_param('i', $user_id);
        $hw->execute();
        $buyer_won_hero = (int)($hw->get_result()->fetch_row()[0] ?? 0);
    }
    $hws = $conn->prepare('SELECT wallet_balance FROM users WHERE id = ?');
    if ($hws) {
        $hws->bind_param('i', $user_id);
        $hws->execute();
        if ($wrow = $hws->get_result()->fetch_row()) {
            $buyer_wallet_hero = (float)($wrow[0] ?? $buyer_wallet_hero);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>لوحة المشتري | FleetX</title>
  <link rel="stylesheet" href="<?= fleetx_css_href() ?>">
  </head>
<body class="fx-home fx-page-shell fx-page-shell--buyer">
<?php include 'includes/navbar.php'; ?>

<?php
$hero_title = ($section === 'dashboard')
    ? ('مرحباً بك، ' . sanitize($user_name))
    : htmlspecialchars($buyer_sec['label']);
$hero_desc = ($section === 'dashboard')
    ? 'تابع مزايداتك، مشترياتك، ومحفظتك من مكان واحد'
    : ('لوحة المشتري — ' . sanitize($user_name));
$hero_bg = 'https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?w=1600&q=80';
$hero_eyebrow = 'لوحة المشتري';
$hero_meta_html = '<span class="fx-page-hero__chip"><i class="ph-fill ph-wallet"></i> ' . number_format((float)$buyer_wallet_hero) . ' ر.س محفظة</span>'
    . '<span class="fx-page-hero__chip"><i class="ph-fill ph-gavel"></i> ' . (int)$buyer_active_bids_hero . ' مزايدة نشطة</span>'
    . '<span class="fx-page-hero__chip fx-page-hero__chip--accent"><i class="ph-fill ph-trophy"></i> ' . (int)$buyer_won_hero . ' مزاد فائز</span>';
$hero_actions_html = '<a href="/auctions.php" class="btn btn-primary"><i class="ph ph-gavel ph-space-left"></i> تصفح المزادات</a>'
    . '<a href="/companies.php" class="btn btn-outline"><i class="ph ph-buildings ph-space-left"></i> دليل الشركات</a>';
$hero_modifier = 'dashboard';
$hero_extra_class = 'fx-page-hero--buyer';
include 'includes/page-hero.inc.php';
?>

<div class="container fx-page-body fx-page-body--overlap fx-buyer-page">
  <div class="fx-buyer-layout">

  <!-- ── SIDEBAR ──────────────────────────────────────────── -->
  <aside class="fx-profile-sidebar fx-profile-sidebar--home fx-buyer-sidebar">
    <div class="fx-buyer-profile">
      <div class="fx-buyer-avatar"><i class="ph-fill ph-user"></i></div>
      <div class="fx-buyer-name"><?= sanitize($user_name) ?></div>
      <div class="buyer-role-badge">مشتري معتمد</div>
    </div>
    <ul class="fx-profile-nav fx-buyer-nav">
      <li><a href="/companies.php"><i class="ph ph-buildings"></i> دليل الشركات</a></li>
      <li><a href="?section=dashboard" class="<?= $section==='dashboard'?'active':'' ?>"><i class="ph ph-squares-four"></i> لوحة التحكم</a></li>
      <li><a href="?section=bids" class="<?= $section==='bids'?'active':'' ?>"><i class="ph ph-gavel"></i> مزايداتي</a></li>
      <li><a href="?section=purchases" class="<?= $section==='purchases'?'active':'' ?>"><i class="ph ph-shopping-bag"></i> مشترياتي</a></li>
      <li><a href="?section=favorites" class="<?= $section==='favorites'?'active':'' ?>"><i class="ph ph-heart"></i> المفضلة</a></li>
      <li><a href="?section=wallet" class="<?= $section==='wallet'?'active':'' ?>"><i class="ph ph-wallet"></i> المحفظة</a></li>
      <li><a href="?section=subscription" class="<?= $section==='subscription'?'active':'' ?>"><i class="ph ph-crown"></i> الاشتراك</a></li>
      <li><a href="?section=settings" class="<?= $section==='settings'?'active':'' ?>"><i class="ph ph-gear"></i> إعدادات الحساب</a></li>
      <li><a href="/logout.php" class="danger"><i class="ph ph-sign-out"></i> تسجيل خروج</a></li>
    </ul>
  </aside>

  <!-- ── MAIN CONTENT ─────────────────────────────────────── -->
  <main class="fx-buyer-main">
    <div class="fx-dash-mobile-nav">
      <select onchange="if(this.value) window.location.href=this.value" aria-label="قائمة لوحة المشتري">
        <option value="">انتقل إلى قسم...</option>
        <option value="/companies.php">دليل الشركات</option>
        <option value="?section=dashboard" <?= $section==='dashboard'?'selected':'' ?>>لوحة التحكم</option>
        <option value="?section=bids" <?= $section==='bids'?'selected':'' ?>>مزايداتي</option>
        <option value="?section=purchases" <?= $section==='purchases'?'selected':'' ?>>مشترياتي</option>
        <option value="?section=favorites" <?= $section==='favorites'?'selected':'' ?>>المفضلة</option>
        <option value="?section=wallet" <?= $section==='wallet'?'selected':'' ?>>المحفظة</option>
        <option value="?section=subscription" <?= $section==='subscription'?'selected':'' ?>>الاشتراك</option>
        <option value="?section=settings" <?= $section==='settings'?'selected':'' ?>>إعدادات الحساب</option>
      </select>
    </div>

    <!-- Header Bar -->
    <?php if ($section !== 'dashboard'): ?>
    <div class="buyer-header-bar fx-buyer-card">
      <h1 class="buyer-title">
        <i class="<?= $buyer_sec['icon'] ?> <?= $buyer_sec['icon_class'] ?>"></i> <?= htmlspecialchars($buyer_sec['label']) ?>
      </h1>
      <?php if ($section === 'bids'): ?>
        <a href="/api/export-report.php?type=buyer_bids" class="btn btn-outline btn--pill"><i class="ph ph-download-simple"></i> تصدير</a>
        <a href="/auctions.php" class="btn btn-primary btn--pill"><i class="ph ph-plus"></i> تصفح المزادات</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php
    // Nafath & Sanad Check
    $nafath_verified = $_SESSION['nafath_verified'] ?? 0;
    $sanad_limit = $_SESSION['sanad_limit'] ?? 0;
    if ($db_connected && !$nafath_verified) {
        $nst = $conn->prepare("SELECT nafath_verified, sanad_limit FROM users WHERE id = ?");
        if($nst) {
            $nst->bind_param('i', $user_id); $nst->execute();
            if ($row = $nst->get_result()->fetch_assoc()) {
                $nafath_verified = $row['nafath_verified'];
                $sanad_limit = $row['sanad_limit'] ?? 0;
                $_SESSION['nafath_verified'] = $nafath_verified;
                $_SESSION['sanad_limit'] = $sanad_limit;
            }
        }
    }
    ?>

    <?php if (!$nafath_verified): ?>
      <div class="fx-dash-alert fx-dash-alert--danger">
        <div class="fx-dash-alert__body">
          <i class="ph-fill ph-warning-circle"></i>
          <span>حسابك غير موثق في نفاذ. لن تتمكن من المزايدة حتى تقوم بالتوثيق.</span>
        </div>
        <a href="/nafath.php" class="btn btn-primary fx-dash-alert__btn">توثيق الآن</a>
      </div>
    <?php else: ?>
      <div class="fx-buyer-status-row">
        <div class="fx-dash-alert fx-dash-alert--success fx-buyer-status-chip">
          <i class="ph-fill ph-check-circle"></i>
          <span>موثق عبر نفاذ</span>
        </div>
        <div class="fx-buyer-sanad-chip">
          <div class="fx-buyer-sanad-chip__body">
            <i class="ph-fill ph-file-text"></i>
            <span>حد سند لأمر: <?= number_format($sanad_limit) ?> ر.س</span>
          </div>
          <a href="/sanad.php" class="fx-buyer-sanad-link">تعديل الحد</a>
        </div>
      </div>
    <?php endif; ?>

    <!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
    <!-- SECTION: DASHBOARD                                     -->
    <!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
    <?php if ($section === 'dashboard'):
      // Fetch all dashboard stats from DB
      $total_bids = 0;
      $active_bids = 0;
      $won_auctions = 0;
      $wallet_bal = $_SESSION['wallet_balance'] ?? 0;

      if ($db_connected) {
          // Total bids by this user
          $stmt = $conn->prepare('SELECT COUNT(*) FROM bids WHERE user_id = ?');
          if ($stmt) { $stmt->bind_param('i', $user_id); $stmt->execute(); $stmt->bind_result($total_bids); $stmt->fetch(); $stmt->close(); }

          // Active bids (distinct auctions with active/live status)
          $stmt = $conn->prepare('SELECT COUNT(DISTINCT b.auction_id) FROM bids b JOIN auctions a ON b.auction_id = a.id WHERE b.user_id = ? AND a.status IN ("active","live")');
          if ($stmt) { $stmt->bind_param('i', $user_id); $stmt->execute(); $stmt->bind_result($active_bids); $stmt->fetch(); $stmt->close(); }

          // Won auctions
          $stmt = $conn->prepare('SELECT COUNT(*) FROM auctions WHERE winner_id = ?');
          if ($stmt) { $stmt->bind_param('i', $user_id); $stmt->execute(); $stmt->bind_result($won_auctions); $stmt->fetch(); $stmt->close(); }

          // Wallet balance from DB
          $stmt = $conn->prepare('SELECT wallet_balance FROM users WHERE id = ?');
          if ($stmt) {
              $stmt->bind_param('i', $user_id);
              $stmt->execute();
              $stmt->bind_result($db_wallet);
              if ($stmt->fetch() && $db_wallet !== null) {
                  $wallet_bal = $db_wallet;
                  $_SESSION['wallet_balance'] = $wallet_bal;
              }
              $stmt->close();
          }
      } else {
          // Mock/fallback data when DB is not connected
          $total_bids = 5;
          $active_bids = 2;
          $won_auctions = 1;
          $wallet_bal = $_SESSION['wallet_balance'] ?? 15000;
      }
    ?>

      <!-- Stat Cards -->
      <div class="stat-grid fx-buyer-stats">
        <div class="stat-card">
          <div class="stat-icon stat-icon--indigo">
            <i class="ph ph-hash"></i>
          </div>
          <div>
            <div class="stat-label">إجمالي المزايدات</div>
            <div class="stat-value"><?= $total_bids ?></div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon stat-icon--info">
            <i class="ph ph-gavel"></i>
          </div>
          <div>
            <div class="stat-label">مزايدات نشطة</div>
            <div class="stat-value"><?= $active_bids ?></div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon stat-icon--success">
            <i class="ph ph-trophy"></i>
          </div>
          <div>
            <div class="stat-label">مزادات فائزة</div>
            <div class="stat-value"><?= $won_auctions ?></div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon stat-icon--primary">
            <i class="ph ph-wallet"></i>
          </div>
          <div>
            <div class="stat-label">رصيد المحفظة</div>
            <div class="stat-value"><?= number_format($wallet_bal) ?> <small>ر.س</small></div>
          </div>
        </div>
      </div>

      <?php
        $buyer_chart_labels = [];
        $buyer_chart_bids = [];
        if ($db_connected) {
            for ($m = 5; $m >= 0; $m--) {
                $ms = date('Y-m-01', strtotime("-$m months"));
                $me = date('Y-m-t', strtotime("-$m months"));
                $buyer_chart_labels[] = date('M Y', strtotime($ms));
                $bcs = $conn->prepare('SELECT COUNT(*) FROM bids WHERE user_id=? AND created_at BETWEEN ? AND ?');
                $bcs->bind_param('iss', $user_id, $ms, $me . ' 23:59:59');
                $bcs->execute();
                $buyer_chart_bids[] = (int)($bcs->get_result()->fetch_row()[0] ?? 0);
            }
        }
      ?>
      <div class="activity-section fx-buyer-chart-card">
        <h3 class="activity-header">المزايدات الشهرية</h3>
        <canvas id="buyerBidsChart" height="80"></canvas>
      </div>
      <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
      <script>
      (function(){
        const el = document.getElementById('buyerBidsChart');
        if (!el || typeof Chart === 'undefined') return;
        new Chart(el, {
          type: 'line',
          data: {
            labels: <?= json_encode($buyer_chart_labels ?? [], JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{ label: 'مزايدات', data: <?= json_encode($buyer_chart_bids ?? []) ?>, borderColor: '#1bc976', backgroundColor: 'rgba(27,201,118,0.1)', fill: true, tension: 0.3 }]
          },
          options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
        });
      })();
      </script>

      <!-- Recent Activity -->
      <div class="activity-section">
        <h3 class="activity-header">النشاط الأخير</h3>
        <?php
          $has_activity = false;
          if ($db_connected) {
              $stmt = $conn->prepare('SELECT * FROM activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 10');
              if ($stmt) {
                  $stmt->bind_param('i', $user_id);
                  $stmt->execute();
                  $result = $stmt->get_result();
                  if ($result && $result->num_rows > 0) {
                      $has_activity = true;
                      while ($row = $result->fetch_assoc()) {
                          $activity_icon = 'ph ph-bell';
                          $activity_variant = 'default';
                          $activity_title = sanitize($row['message']);
                          $activity_time = sanitize($row['created_at']);
                          include 'includes/dashboard/activity-row.inc.php';
                      }
                  }
                  $stmt->close();
              }
          }
          if (!$has_activity && $db_connected) {
              $fb = $conn->prepare('SELECT b.amount, b.created_at, a.title FROM bids b JOIN auctions a ON b.auction_id=a.id WHERE b.user_id=? ORDER BY b.created_at DESC LIMIT 5');
              $fb->bind_param('i', $user_id);
              $fb->execute();
              $fbr = $fb->get_result();
              while ($frow = $fbr->fetch_assoc()) {
                  $has_activity = true;
                  $activity_icon = 'ph ph-gavel';
                  $activity_variant = 'primary';
                  $activity_title = 'مزايدة ' . number_format($frow['amount']) . ' ر.س على ' . sanitize($frow['title']);
                  $activity_time = sanitize($frow['created_at']);
                  include 'includes/dashboard/activity-row.inc.php';
              }
          }
          if (!$has_activity):
        ?>
        <?php
          $empty_icon = 'ph ph-clock-counter-clockwise';
          $empty_variant = 'info';
          $empty_title = 'لا يوجد نشاط حتى الآن';
          $empty_desc = 'ستظهر هنا مزايداتك ومعاملاتك الأخيرة';
          $empty_cta_href = '/auctions.php';
          $empty_cta_label = 'تصفح المزادات';
          include 'includes/dashboard/empty-state.inc.php';
        ?>
        <?php endif; ?>
      </div>


    <!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
    <!-- SECTION: BIDS                                          -->
    <!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
    <?php elseif ($section === 'bids'):
      $user_bids = [];

      if ($db_connected) {
          $stmt = $conn->prepare('
              SELECT b.*, a.title AS auction_title, a.current_price, a.end_time, a.status AS auction_status,
                     v.make, v.model, v.year, v.image_url, v.city
              FROM bids b
              JOIN auctions a ON b.auction_id = a.id
              JOIN vehicles v ON a.vehicle_id = v.id
              WHERE b.user_id = ?
              ORDER BY b.created_at DESC
          ');
          if ($stmt) {
              $stmt->bind_param('i', $user_id);
              $stmt->execute();
              $result = $stmt->get_result();
              while ($row = $result->fetch_assoc()) {
                  $user_bids[] = $row;
              }
              $stmt->close();
          }
      } else {
          // Mock data when DB is not connected
          $user_bids = [
              [
                  'id' => 1, 'auction_id' => 101, 'amount' => 45000,
                  'auction_title' => 'تويوتا كامري 2023 - فل كامل',
                  'current_price' => 48000, 'end_time' => date('Y-m-d H:i:s', strtotime('+2 days')),
                  'auction_status' => 'active', 'is_winning' => false,
                  'make' => 'Toyota', 'model' => 'Camry', 'year' => 2023,
                  'image_url' => '', 'city' => 'الرياض', 'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
              ],
              [
                  'id' => 2, 'auction_id' => 102, 'amount' => 62000,
                  'auction_title' => 'هيونداي سوناتا 2024 - ستاندرد',
                  'current_price' => 62000, 'end_time' => date('Y-m-d H:i:s', strtotime('+5 hours')),
                  'auction_status' => 'active', 'is_winning' => true,
                  'make' => 'Hyundai', 'model' => 'Sonata', 'year' => 2024,
                  'image_url' => '', 'city' => 'جدة', 'created_at' => date('Y-m-d H:i:s', strtotime('-3 hours'))
              ],
          ];
      }
    ?>

      <?php if (empty($user_bids)): ?>
        <?php
          $empty_icon = 'ph-fill ph-gavel';
          $empty_variant = 'info';
          $empty_title = 'لم تقم بأي مزايدة بعد';
          $empty_desc = 'تصفح المزادات النشطة وابدأ المزايدة على السيارات التي تهمك';
          $empty_cta_href = '/auctions.php';
          $empty_cta_label = 'تصفح المزادات';
          include 'includes/dashboard/empty-state.inc.php';
        ?>
      <?php else: ?>
        <div class="fx-dash-card-grid">
          <?php foreach ($user_bids as $bid):
            $bid_thumb = fleetx_vehicle_thumb($bid['image_url'] ?? '', intval($bid['id'] ?? 0), 'live', $bid['make'] ?? '');
            $is_winning = $bid['is_winning'] ?? ($bid['amount'] >= $bid['current_price']);
            $timer = $bid['end_time'] ? timeLeft($bid['end_time']) : null;
          ?>
          <div class="bid-card">
            <div class="bid-card-img">
              <img src="<?= htmlspecialchars($bid_thumb['src']) ?>" alt="<?= sanitize($bid['auction_title']) ?>" loading="lazy" decoding="async" onerror="<?= $bid_thumb['onerror'] ?>">
              <div class="bid-status-badge <?= $is_winning ? 'bid-status-winning' : 'bid-status-outbid' ?>">
                <?= $is_winning ? 'رابح' : 'تم تجاوزك' ?>
              </div>
            </div>
            <div class="bid-card-body">
              <h3 class="bid-card-title"><?= sanitize($bid['auction_title']) ?></h3>

              <div class="bid-info-row">
                <span class="bid-info-label"><i class="ph ph-map-pin bid-info-icon"></i> المدينة</span>
                <span class="bid-info-value"><?= sanitize($bid['city'] ?? 'الرياض') ?></span>
              </div>
              <div class="bid-info-row">
                <span class="bid-info-label"><i class="ph ph-tag bid-info-icon"></i> مزايدتك</span>
                <span class="bid-info-value price"><?= number_format($bid['amount']) ?> ر.س</span>
              </div>
              <div class="bid-info-row">
                <span class="bid-info-label"><i class="ph ph-trophy bid-info-icon"></i> السعر الحالي</span>
                <span class="bid-info-value price"><?= number_format($bid['current_price']) ?> ر.س</span>
              </div>
              <?php if ($timer && $timer['total'] > 0): ?>
              <div class="bid-info-row">
                <span class="bid-info-label"><i class="ph ph-timer bid-info-icon"></i> المتبقي</span>
                <span class="bid-info-value bid-timer-val">
                  <?= $timer['days'] ?>d <?= $timer['hours'] ?>h <?= $timer['mins'] ?>m
                </span>
              </div>
              <?php endif; ?>

              <a href="/auction-room.php?id=<?= $bid['auction_id'] ?>" class="btn btn-primary btn--pill btn--block">
                <i class="ph ph-arrow-left"></i> دخول غرفة المزاد
              </a>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>


    <!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
    <!-- SECTION: PURCHASES                                     -->
    <!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
    <?php elseif ($section === 'purchases'):
      $purchases = [];
      if ($db_connected) {
          $stmt = $conn->prepare('
              SELECT t.*, t.sale_price AS amount, a.title, v.image_url, v.make, v.model, v.year, v.city
              FROM transactions t
              JOIN auctions a ON t.auction_id = a.id
              JOIN vehicles v ON a.vehicle_id = v.id
              WHERE t.buyer_id = ?
              ORDER BY t.created_at DESC
          ');
          if ($stmt) {
              $stmt->bind_param('i', $user_id);
              $stmt->execute();
              $result = $stmt->get_result();
              while ($row = $result->fetch_assoc()) {
                  $purchases[] = $row;
              }
              $stmt->close();
          }
      } else {
          // Mock data when DB is not connected
          $purchases = [
              [
                  'id' => 1, 'auction_id' => 50, 'amount' => 55000,
                  'title' => 'نيسان التيما 2022 - نظيفة جداً',
                  'make' => 'Nissan', 'model' => 'Altima', 'year' => 2022,
                  'image_url' => '', 'city' => 'الدمام',
                  'payment_status' => 'paid', 'created_at' => date('Y-m-d H:i:s', strtotime('-10 days'))
              ],
          ];
      }
    ?>

      <?php if (empty($purchases)): ?>
        <?php
          $empty_icon = 'ph-fill ph-shopping-bag';
          $empty_variant = 'success';
          $empty_title = 'لم تقم بأي عملية شراء بعد';
          $empty_desc = 'عند الفوز بمزاد أو إتمام عملية شراء فوري، ستظهر مشترياتك هنا';
          $empty_cta_href = '/auctions.php';
          $empty_cta_label = 'تصفح المزادات';
          include 'includes/dashboard/empty-state.inc.php';
        ?>
      <?php else: ?>
        <div class="fx-dash-card-grid">
          <?php foreach ($purchases as $p):
            $p_thumb = fleetx_vehicle_thumb($p['image_url'] ?? '', intval($p['id'] ?? 0), 'instant', $p['make'] ?? '');
          ?>
          <div class="bid-card">
            <div class="bid-card-img">
              <img src="<?= htmlspecialchars($p_thumb['src']) ?>" alt="<?= sanitize($p['title']) ?>" loading="lazy" decoding="async" onerror="<?= $p_thumb['onerror'] ?>">
              <div class="bid-status-badge bid-status-winning">تم الشراء</div>
            </div>
            <div class="bid-card-body">
              <h3 class="bid-card-title"><?= sanitize($p['title']) ?></h3>
              <div class="bid-info-row">
                <span class="bid-info-label">المدينة</span>
                <span class="bid-info-value"><?= sanitize($p['city'] ?? 'الرياض') ?></span>
              </div>
              <div class="bid-info-row">
                <span class="bid-info-label">المبلغ المدفوع</span>
                <span class="bid-info-value price"><?= number_format($p['amount'] ?? 0) ?> ر.س</span>
              </div>
              <div class="bid-info-row">
                <span class="bid-info-label">تاريخ الشراء</span>
                <span class="bid-info-value bid-date-val"><?= sanitize($p['created_at'] ?? '') ?></span>
              </div>
              <a href="/vehicle-details.php?id=<?= $p['auction_id'] ?? $p['id'] ?>" class="btn btn-primary btn--pill btn--block">
                عرض التفاصيل
              </a>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>


    <!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
    <!-- SECTION: FAVORITES                                     -->
    <!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
    <?php elseif ($section === 'favorites'):
      $fav_items = getUserFavoriteAuctions($db_connected ? $conn : null, $user_id);
    ?>

      <?php if (empty($fav_items)): ?>
        <?php
          $empty_icon = 'ph-fill ph-heart';
          $empty_variant = 'danger';
          $empty_title = 'لم تقم بإضافة أي مركبات للمفضلة بعد';
          $empty_desc = 'تصفح صالة المزادات والمبيعات الفورية واضغط على أيقونة القلب لحفظها هنا';
          $empty_cta_href = '/auctions.php';
          $empty_cta_label = 'تصفح المركبات الآن';
          include 'includes/dashboard/empty-state.inc.php';
        ?>
      <?php else: ?>
        <div class="fav-grid">
          <?php foreach ($fav_items as $item):
            $title_car = $item['title'] ?? (($item['make'] ?? '') . ' ' . ($item['model'] ?? '') . ' ' . ($item['year'] ?? ''));
            $fav_type = (($item['type'] ?? '') === 'instant') ? 'instant' : 'live';
            $fav_thumb = fleetx_vehicle_thumb($item['image_url'] ?? '', intval($item['id'] ?? 0), $fav_type, $item['make'] ?? '');
            $is_instant = ($item['type'] ?? '') === 'instant';
          ?>
            <div class="auction-card fx-fav-card" onclick="window.location.href='<?= $is_instant ? '/vehicle-details.php' : '/auction-room.php' ?>?id=<?= $item['id'] ?>'">
              <div class="card-fav active fx-fav-heart" onclick="event.stopPropagation(); toggleFavorite(<?= $item['id'] ?>, this)">
                <i class="ph-fill ph-heart"></i>
              </div>
              <div class="ac-img-wrap">
                <img src="<?= htmlspecialchars($fav_thumb['src']) ?>" alt="<?= sanitize($title_car) ?>" loading="lazy" decoding="async" onerror="<?= $fav_thumb['onerror'] ?>">
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
                  <a href="<?= $is_instant ? '/vehicle-details.php' : '/auction-room.php' ?>?id=<?= $item['id'] ?>" class="btn btn-primary" style="width:100%; justify-content:center; border-radius:var(--radius-round);" onclick="event.stopPropagation();">
                    <?= $is_instant ? 'شراء الآن' : 'دخول المزاد' ?>
                  </a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>


    <!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
    <!-- SECTION: WALLET                                        -->
    <!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
    <?php elseif ($section === 'wallet'):
      $wallet_bal = $_SESSION['wallet_balance'] ?? 0;
      if ($db_connected) {
          $stmt = $conn->prepare('SELECT wallet_balance FROM users WHERE id = ?');
          if ($stmt) {
              $stmt->bind_param('i', $user_id);
              $stmt->execute();
              $stmt->bind_result($db_wallet);
              if ($stmt->fetch() && $db_wallet !== null) {
                  $wallet_bal = $db_wallet;
                  $_SESSION['wallet_balance'] = $wallet_bal;
              }
              $stmt->close();
          }
      }
    ?>

      <!-- Balance & Verify Cards -->
      <div class="wallet-grid">
        <div class="wallet-balance-card">
          <i class="ph-fill ph-wallet bg-icon" style="color:#fff;"></i>
          <h4 class="wallet-balance-label">الرصيد المتاح</h4>
          <div class="wallet-balance-amount"><?= number_format($wallet_bal) ?> <span>ر.س</span></div>
          <div class="fx-wallet-card-actions">
            <button class="wallet-btn" onclick="openTopupModal()">
              شحن الرصيد <i class="ph ph-plus"></i>
            </button>
            <button class="wallet-btn wallet-btn-secondary">
              استرداد <i class="ph ph-arrow-down"></i>
            </button>
          </div>
        </div>
        <div class="wallet-verify-card">
          <div class="verify-icon">
            <i class="ph ph-shield-check"></i>
          </div>
          <h4 class="fx-wallet-verify-title">حسابك موثق ونشط</h4>
          <p class="fx-wallet-verify-desc">تم التحقق من بيانات النفاذ الوطني ويمكنك المزايدة بحرية.</p>
        </div>
      </div>

      <!-- Recent Transactions -->
      <div class="transactions-section">
        <h3 class="transactions-header fx-tx-header">
          <span>أحدث العمليات</span>
          <a href="/api/export-report.php?type=buyer_purchases" class="btn btn-outline btn-sm"><i class="ph ph-download-simple"></i> تصدير CSV</a>
        </h3>
        <?php
          $has_transactions = false;
          if ($db_connected) {
              $stmt = $conn->prepare('
                  SELECT t.*, t.sale_price AS amount, "purchase" AS type, CONCAT("شراء سيارة ", v.make, " ", v.model, " ", v.year) AS description 
                  FROM transactions t 
                  JOIN auctions a ON t.auction_id = a.id 
                  JOIN vehicles v ON a.vehicle_id = v.id 
                  WHERE t.buyer_id = ? 
                  ORDER BY t.created_at DESC 
                  LIMIT 10
              ');
              if ($stmt) {
                  $stmt->bind_param('i', $user_id);
                  $stmt->execute();
                  $result = $stmt->get_result();
                  if ($result && $result->num_rows > 0) {
                      $has_transactions = true;
                      while ($row = $result->fetch_assoc()) {
                          $is_credit = ($row['type'] ?? '') === 'topup' || ($row['seller_id'] ?? 0) == $user_id;
                          $tx_icon = $is_credit ? 'ph-arrow-up-right' : 'ph-arrow-down-left';
                          $tx_variant = $is_credit ? 'credit' : 'debit';
                          $tx_desc = sanitize($row['description'] ?? ($is_credit ? 'إيداع' : 'خصم'));
                          $tx_time = sanitize($row['created_at'] ?? '');
                          $tx_amount = ($is_credit ? '+' : '-') . number_format($row['amount'] ?? 0) . ' ر.س';
                          echo '<div class="fx-tx-item">';
                          echo '<div class="fx-tx-item__icon fx-tx-item__icon--' . $tx_variant . '"><i class="ph ' . $tx_icon . '"></i></div>';
                          echo '<div class="fx-activity-item__body"><div class="fx-activity-item__title">' . $tx_desc . '</div><div class="fx-activity-item__time">' . $tx_time . '</div></div>';
                          echo '<div class="fx-tx-item__amount fx-tx-item__amount--' . $tx_variant . '">' . $tx_amount . '</div>';
                          echo '</div>';
                      }
                  }
                  $stmt->close();
              }
          }
          if (!$has_transactions):
        ?>
        <?php
          $empty_icon = 'ph ph-receipt';
          $empty_variant = 'primary';
          $empty_title = 'لا توجد عمليات مالية سابقة';
          $empty_desc = 'ستظهر هنا عمليات الشحن والشراء والاسترداد';
          $empty_cta_href = '';
          $empty_cta_label = '';
          include 'includes/dashboard/empty-state.inc.php';
        ?>
        <?php endif; ?>
      </div>

      <!-- Top-up Modal -->
      <div class="topup-overlay" id="topupOverlay">
        <div class="topup-modal">
          <button class="topup-close" onclick="closeTopupModal()"><i class="ph ph-x" style="color:var(--text-muted);"></i></button>
          <h3 class="topup-title">شحن المحفظة</h3>
          <p class="topup-subtitle">اختر مبلغ الشحن أو أدخل مبلغ مخصص</p>

          <form method="POST" action="?section=wallet" id="topupForm">
            <div class="topup-presets">
              <div class="topup-preset" onclick="selectPreset(5000, this)">
                5,000
                <small>ر.س</small>
              </div>
              <div class="topup-preset" onclick="selectPreset(10000, this)">
                10,000
                <small>ر.س</small>
              </div>
              <div class="topup-preset" onclick="selectPreset(50000, this)">
                50,000
                <small>ر.س</small>
              </div>
            </div>
            <input type="number" name="topup_amount" id="topupAmount" class="topup-custom" placeholder="أو أدخل مبلغ مخصص..." min="100" required>
            <button type="submit" class="topup-submit">
              <i class="ph ph-credit-card" style="color:#fff; font-size:18px;"></i>
              تأكيد الشحن
            </button>
          </form>
        </div>
      </div>


    <!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
    <!-- SECTION: SUBSCRIPTION                                  -->
    <!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
    <?php elseif ($section === 'subscription'):
      $buyer_plans = [
        'free' => ['name' => 'مجاني', 'price' => 0, 'features' => ['تصفح المزادات', 'مزايدة بعد توثيق نفاذ', '3 مزايدات نشطة']],
        'pro' => ['name' => 'احترافي', 'price' => (float)(fleetx_get_setting($conn, 'buyer_pro_price', 299) ?? 299), 'features' => ['مزايدات غير محدودة', 'تنبيهات SMS', 'أولوية الدعم', 'بحث محفوظ غير محدود']],
        'enterprise' => ['name' => 'مؤسسات', 'price' => 999, 'features' => ['حسابات متعددة', 'تقارير مخصصة', 'مدير حساب', 'API']],
      ];
      $active_plan = $buyer_subscription['plan'] ?? 'free';
    ?>
    <div class="plans-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:20px;">
      <?php foreach ($buyer_plans as $key => $bp):
        $is_current = ($active_plan === $key);
      ?>
      <div class="plan-card fx-buyer-card" style="padding:24px;border:1px solid var(--border-light);border-radius:16px;<?= $is_current ? 'border-color:var(--primary);box-shadow:0 0 0 2px rgba(27,201,118,.15);' : '' ?>">
        <h3 style="font-weight:900;margin:0 0 8px;"><?= $bp['name'] ?></h3>
        <div style="font-size:28px;font-weight:900;color:var(--primary);margin-bottom:16px;">
          <?= $bp['price'] > 0 ? number_format($bp['price']) . ' <span style="font-size:14px">ر.س/سنة</span>' : 'مجاناً' ?>
        </div>
        <ul style="list-style:none;padding:0;margin:0 0 20px;">
          <?php foreach ($bp['features'] as $f): ?>
          <li style="padding:6px 0;font-size:14px;"><i class="ph-fill ph-check-circle" style="color:var(--primary);"></i> <?= $f ?></li>
          <?php endforeach; ?>
        </ul>
        <?php if ($is_current): ?>
          <button class="btn btn-outline" disabled style="width:100%;">باقتك الحالية</button>
        <?php else: ?>
          <form method="POST"><input type="hidden" name="subscribe_plan" value="<?= $key ?>"><button type="submit" class="btn btn-primary" style="width:100%;">اشترك الآن</button></form>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php if ($buyer_subscription): ?>
    <p style="margin-top:20px;color:var(--text-muted);font-size:14px;">الباقة النشطة حتى <?= sanitize($buyer_subscription['end_date']) ?></p>
    <?php endif; ?>

    <!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
    <!-- SECTION: SETTINGS                                      -->
    <!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
    <?php elseif ($section === 'settings'):
      $current_name = $_SESSION['user_name'] ?? '';
      $current_phone = $_SESSION['user_phone'] ?? '';
      $current_city = $_SESSION['user_city'] ?? '';
      $whatsapp_opted_in = false;

      // Try to load from DB
      if ($db_connected) {
          fleetx_ensure_whatsapp_optin_schema($conn);
          $stmt = $conn->prepare('SELECT full_name, mobile, city, whatsapp_optin FROM users WHERE id = ?');
          if ($stmt) {
              $stmt->bind_param('i', $user_id);
              $stmt->execute();
              $stmt->bind_result($db_name, $db_phone, $db_city, $db_wa_optin);
              if ($stmt->fetch()) {
                  $current_name = $db_name ?: $current_name;
                  $current_phone = $db_phone ?: $current_phone;
                  $current_city = $db_city ?: $current_city;
                  $whatsapp_opted_in = (int)($db_wa_optin ?? 0) === 1;
              }
              $stmt->close();
          }
      }
    ?>

      <div class="settings-card">
        <div style="display:flex; align-items:center; gap:16px; margin-bottom:28px; padding-bottom:20px; border-bottom:1px solid var(--border-light);">
          <div style="width:56px; height:56px; border-radius:50%; background:var(--primary-gradient); display:flex; align-items:center; justify-content:center;">
            <i class="ph-fill ph-user" style="font-size:24px; color:#fff;"></i>
          </div>
          <div>
            <h3 style="font-size:18px; font-weight:900; color:var(--text-dark); margin:0 0 4px;">تعديل بيانات الحساب</h3>
            <p style="font-size:13px; color:var(--text-muted); margin:0;">قم بتحديث معلوماتك الشخصية وكلمة المرور</p>
          </div>
        </div>

        <form method="POST" action="?section=settings">
          <input type="hidden" name="save_settings" value="1">

          <div class="settings-row">
            <div class="settings-group">
              <label class="settings-label">الاسم الكامل</label>
              <input type="text" name="full_name" class="settings-input" value="<?= sanitize($current_name) ?>" placeholder="أدخل اسمك الكامل" required>
            </div>
            <div class="settings-group">
              <label class="settings-label">رقم الجوال</label>
              <input type="tel" name="phone" class="settings-input" value="<?= sanitize($current_phone) ?>" placeholder="05XXXXXXXX" dir="ltr" style="text-align:right;">
            </div>
          </div>

          <div class="settings-group">
            <label class="settings-label">المدينة</label>
            <select name="city" class="settings-select">
              <option value="">اختر المدينة</option>
              <?php
                $cities = ['الرياض','جدة','مكة المكرمة','المدينة المنورة','الدمام','الخبر','الظهران','الأحساء','الطائف','تبوك','بريدة','عنيزة','حائل','نجران','جازان','أبها','الباحة','ينبع','الجبيل','القطيف'];
                foreach ($cities as $c):
              ?>
                <option value="<?= $c ?>" <?= $current_city === $c ? 'selected' : '' ?>><?= $c ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div style="margin-top:8px; padding-top:20px; border-top:1px solid var(--border-light);">
            <h4 style="font-size:16px; font-weight:800; color:var(--text-dark); margin-bottom:12px;">إشعارات واتساب</h4>
            <label class="promissory-check" style="margin-bottom:20px;">
              <input type="hidden" name="whatsapp_optin" value="0">
              <input type="checkbox" name="whatsapp_optin" value="1" <?= $whatsapp_opted_in ? 'checked' : '' ?>>
              استلام إشعارات المزادات والمزايدات عبر واتساب
            </label>
          </div>

          <div style="margin-top:8px; padding-top:20px; border-top:1px solid var(--border-light);">
            <h4 style="font-size:16px; font-weight:800; color:var(--text-dark); margin-bottom:16px;">تغيير كلمة المرور</h4>
            <div class="settings-row">
              <div class="settings-group">
                <label class="settings-label">كلمة المرور الجديدة</label>
                <input type="password" name="new_password" class="settings-input" placeholder="اتركها فارغة إن لم ترد التغيير">
              </div>
              <div class="settings-group">
                <label class="settings-label">تأكيد كلمة المرور</label>
                <input type="password" name="confirm_password" class="settings-input" placeholder="أعد كتابة كلمة المرور الجديدة">
              </div>
            </div>
          </div>

          <div style="display:flex; justify-content:flex-start; margin-top:12px;">
            <button type="submit" class="settings-submit">
              <i class="ph ph-floppy-disk" style="color:#fff; font-size:18px; margin-left:6px;"></i>
              حفظ التغييرات
            </button>
          </div>
        </form>
      </div>

    <?php else: ?>
      <!-- Fallback: unknown section → dashboard redirect -->
      <script>window.location.href = '?section=dashboard';</script>
    <?php endif; ?>

  </main>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
// ── Topup Modal ────────────────────────────────────────────
function openTopupModal() {
  document.getElementById('topupOverlay').classList.add('active');
  document.body.style.overflow = 'hidden';
}
function closeTopupModal() {
  document.getElementById('topupOverlay').classList.remove('active');
  document.body.style.overflow = '';
}
function selectPreset(amount, el) {
  document.querySelectorAll('.topup-preset').forEach(p => p.classList.remove('selected'));
  el.classList.add('selected');
  document.getElementById('topupAmount').value = amount;
}
// Close on overlay click
document.getElementById('topupOverlay')?.addEventListener('click', function(e) {
  if (e.target === this) closeTopupModal();
});
// Close on Escape
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeTopupModal();
});

</script>
</body>
</html>
