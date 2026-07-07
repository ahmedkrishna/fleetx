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
    }
    $settings_success = true;
    header('Location: ?section=settings&saved=1');
    exit;
}

// Check for saved flag
$show_saved_toast = isset($_GET['saved']) && $_GET['saved'] == '1';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>لوحة المشتري | FleetX</title>
  <link rel="stylesheet" href="/assets/css/fleetx.css">
  </head>
<body class="page-inner fx-page-shell fx-page-shell--dashboard">
<?php include 'includes/navbar.php'; ?>

<?php if ($show_saved_toast): ?>
<div class="success-toast">
  <i class="ph ph-check-circle" style="font-size:20px; color:#fff;"></i>
  تم حفظ التغييرات بنجاح
</div>
<?php endif; ?>

<?php
$hero_title = 'مرحباً بك، ' . $user_name;
$hero_desc = 'تابع مزايداتك، مشترياتك، ومحفظتك من مكان واحد';
$hero_bg = 'https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?w=1600&q=80';
$hero_modifier = 'dashboard';
$hero_extra_class = 'fx-page-hero--buyer';
include 'includes/page-hero.inc.php';
?>

<div class="buyer-container fx-dash-page-body">

  <!-- ── SIDEBAR ──────────────────────────────────────────── -->
  <aside class="buyer-sidebar">
    <div class="buyer-user-info">
      <div class="buyer-user-avatar"><i class="ph-fill ph-user"></i></div>
      <h3 style="font-size: 20px; font-weight: 900; color: var(--text-dark); margin: 0 0 4px;"><?= sanitize($user_name) ?></h3>
      <div class="buyer-role-badge">مشتري معتمد</div>
    </div>
    <ul class="buyer-nav">
      <li><a href="/companies.php"><i class="ph ph-buildings"></i> دليل الشركات</a></li>
      <li><a href="?section=dashboard" class="<?= $section==='dashboard'?'active':'' ?>"><i class="ph ph-squares-four"></i> لوحة التحكم</a></li>
      <li><a href="?section=bids" class="<?= $section==='bids'?'active':'' ?>"><i class="ph ph-gavel"></i> مزايداتي</a></li>
      <li><a href="?section=purchases" class="<?= $section==='purchases'?'active':'' ?>"><i class="ph ph-shopping-bag"></i> مشترياتي</a></li>
      <li><a href="?section=favorites" class="<?= $section==='favorites'?'active':'' ?>"><i class="ph ph-heart"></i> المفضلة</a></li>
      <li><a href="?section=wallet" class="<?= $section==='wallet'?'active':'' ?>"><i class="ph ph-wallet"></i> المحفظة</a></li>
      <li><a href="?section=settings" class="<?= $section==='settings'?'active':'' ?>"><i class="ph ph-gear"></i> إعدادات الحساب</a></li>
    </ul>
  </aside>

  <!-- ── MAIN CONTENT ─────────────────────────────────────── -->
  <main class="buyer-main">
    <div class="fx-dash-mobile-nav">
      <select onchange="if(this.value) window.location.href=this.value" aria-label="قائمة لوحة المشتري">
        <option value="">انتقل إلى قسم...</option>
        <option value="/companies.php">دليل الشركات</option>
        <option value="?section=dashboard" <?= $section==='dashboard'?'selected':'' ?>>لوحة التحكم</option>
        <option value="?section=bids" <?= $section==='bids'?'selected':'' ?>>مزايداتي</option>
        <option value="?section=purchases" <?= $section==='purchases'?'selected':'' ?>>مشترياتي</option>
        <option value="?section=favorites" <?= $section==='favorites'?'selected':'' ?>>المفضلة</option>
        <option value="?section=wallet" <?= $section==='wallet'?'selected':'' ?>>المحفظة</option>
        <option value="?section=settings" <?= $section==='settings'?'selected':'' ?>>إعدادات الحساب</option>
      </select>
    </div>

    <!-- Header Bar -->
    <div class="buyer-header-bar">
      <h1 class="buyer-title">
        <?php
          switch($section) {
            case 'dashboard': echo '<i class="ph-fill ph-squares-four" style="color:var(--primary)"></i> لوحة التحكم'; break;
            case 'bids':      echo '<i class="ph-fill ph-gavel" style="color:var(--primary)"></i> مزايداتي'; break;
            case 'purchases': echo '<i class="ph-fill ph-shopping-bag" style="color:var(--primary)"></i> مشترياتي'; break;
            case 'favorites': echo '<i class="ph-fill ph-heart" style="color:var(--danger)"></i> المفضلة'; break;
            case 'wallet':    echo '<i class="ph-fill ph-wallet" style="color:var(--primary)"></i> المحفظة'; break;
            case 'settings':  echo '<i class="ph-fill ph-gear" style="color:var(--primary)"></i> إعدادات الحساب'; break;
            default:          echo '<i class="ph-fill ph-squares-four" style="color:var(--primary)"></i> لوحة التحكم';
          }
        ?>
      </h1>
      <?php if ($section === 'bids'): ?>
        <a href="/auctions.php" class="btn btn-primary" style="border-radius:30px; padding:10px 24px; font-size:14px;"><i class="ph ph-plus" style="color:#fff"></i> تصفح المزادات</a>
      <?php endif; ?>
    </div>

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
      <div style="background: rgba(239,68,68,0.1); border: 1px solid var(--danger); color: var(--danger); padding: 16px; border-radius: var(--radius-md); margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 12px; font-weight: 700;">
          <i class="ph-fill ph-warning-circle" style="font-size: 24px;"></i>
          <span>حسابك غير موثق في نفاذ. لن تتمكن من المزايدة حتى تقوم بالتوثيق.</span>
        </div>
        <a href="/nafath.php" class="btn btn-primary" style="background: var(--danger); border-color: var(--danger); padding: 8px 16px;">توثيق الآن</a>
      </div>
    <?php else: ?>
      <div style="display: flex; gap: 16px; margin-bottom: 24px;">
        <div style="flex: 1; background: rgba(16,185,129,0.1); border: 1px solid var(--success); color: var(--success); padding: 12px 16px; border-radius: var(--radius-md); display: flex; align-items: center; gap: 10px; font-weight: 700;">
          <i class="ph-fill ph-check-circle" style="font-size: 20px;"></i>
          موثق عبر نفاذ
        </div>
        <div style="flex: 1; background: rgba(17,94,89,0.1); border: 1px solid #115e59; color: #115e59; padding: 12px 16px; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: space-between; font-weight: 700;">
          <div style="display: flex; align-items: center; gap: 10px;">
            <i class="ph-fill ph-file-text" style="font-size: 20px;"></i>
            حد سند لأمر: <?= number_format($sanad_limit) ?> ر.س
          </div>
          <a href="/sanad.php" style="color: #115e59; text-decoration: underline; font-size: 13px;">تعديل الحد</a>
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
      <div class="stat-grid" style="grid-template-columns: repeat(4, 1fr);">
        <div class="stat-card">
          <div class="stat-icon" style="background: rgba(99,102,241,0.1); color: #6366f1;">
            <i class="ph ph-hash"></i>
          </div>
          <div>
            <div class="stat-label">إجمالي المزايدات</div>
            <div class="stat-value"><?= $total_bids ?></div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon" style="background: rgba(14,165,233,0.1); color: var(--info);">
            <i class="ph ph-gavel"></i>
          </div>
          <div>
            <div class="stat-label">مزايدات نشطة</div>
            <div class="stat-value"><?= $active_bids ?></div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon" style="background: rgba(16,185,129,0.1); color: var(--success);">
            <i class="ph ph-trophy"></i>
          </div>
          <div>
            <div class="stat-label">مزادات فائزة</div>
            <div class="stat-value"><?= $won_auctions ?></div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon" style="background: rgba(27,201,118,0.1); color: var(--primary);">
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
      <div class="activity-section" style="margin-bottom:24px; background:#fff; border-radius:20px; padding:24px; border:1px solid var(--border-light);">
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
                          echo '<div style="display:flex; align-items:center; gap:14px; padding:14px 0; border-bottom:1px solid var(--border-light);">';
                          echo '<div style="width:42px;height:42px;border-radius:50%;background:var(--primary-light);display:flex;align-items:center;justify-content:center;"><i class="ph ph-bell" style="font-size:18px;"></i></div>';
                          echo '<div style="flex:1;"><div style="font-weight:700;color:var(--text-dark);font-size:14px;">' . sanitize($row['message']) . '</div>';
                          echo '<div style="font-size:12px;color:var(--text-muted);margin-top:2px;">' . sanitize($row['created_at']) . '</div></div>';
                          echo '</div>';
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
                  echo '<div style="display:flex; align-items:center; gap:14px; padding:14px 0; border-bottom:1px solid var(--border-light);">';
                  echo '<div style="width:42px;height:42px;border-radius:50%;background:rgba(27,201,118,0.1);display:flex;align-items:center;justify-content:center;"><i class="ph ph-gavel" style="font-size:18px;color:var(--primary);"></i></div>';
                  echo '<div style="flex:1;"><div style="font-weight:700;color:var(--text-dark);font-size:14px;">مزايدة ' . number_format($frow['amount']) . ' ر.س على ' . sanitize($frow['title']) . '</div>';
                  echo '<div style="font-size:12px;color:var(--text-muted);">' . sanitize($frow['created_at']) . '</div></div></div>';
              }
          }
          if (!$has_activity):
        ?>
        <div style="text-align:center; padding: 40px 20px;">
          <div style="width:80px;height:80px;border-radius:50%;background:rgba(14,165,233,0.08);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
            <i class="ph ph-clock-counter-clockwise" style="font-size:36px; color:var(--info);"></i>
          </div>
          <h4 style="font-weight:800; color:var(--text-dark); margin-bottom:8px;">لا يوجد نشاط حتى الآن</h4>
          <p style="color:var(--text-muted); font-size:14px;">ستظهر هنا مزايداتك ومعاملاتك الأخيرة</p>
          <a href="/auctions.php" class="btn btn-primary" style="margin-top:20px; border-radius:30px; padding:12px 28px;">تصفح المزادات</a>
        </div>
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
        <div class="buyer-empty">
          <div class="empty-icon" style="background: rgba(14,165,233,0.1);">
            <i class="ph-fill ph-gavel" style="color: var(--info);"></i>
          </div>
          <h3>لم تقم بأي مزايدة بعد</h3>
          <p>تصفح المزادات النشطة وابدأ المزايدة على السيارات التي تهمك</p>
          <a href="/auctions.php" class="btn btn-primary" style="margin-top:24px; border-radius:30px; padding:12px 30px; font-weight:800;">تصفح المزادات</a>
        </div>
      <?php else: ?>
        <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap:24px;">
          <?php foreach ($user_bids as $bid):
            $bid_img = (!empty($bid['image_url']) && strlen($bid['image_url']) > 4) ? $bid['image_url'] : getCarImage($bid['make'] ?? 'default');
            $is_winning = $bid['is_winning'] ?? ($bid['amount'] >= $bid['current_price']);
            $timer = $bid['end_time'] ? timeLeft($bid['end_time']) : null;
          ?>
          <div class="bid-card">
            <div class="bid-card-img">
              <img src="<?= $bid_img ?>" alt="<?= sanitize($bid['auction_title']) ?>" loading="lazy">
              <div class="bid-status-badge <?= $is_winning ? 'bid-status-winning' : 'bid-status-outbid' ?>">
                <?= $is_winning ? 'رابح' : 'تم تجاوزك' ?>
              </div>
            </div>
            <div class="bid-card-body">
              <h3 class="bid-card-title"><?= sanitize($bid['auction_title']) ?></h3>

              <div class="bid-info-row">
                <span class="bid-info-label"><i class="ph ph-map-pin" style="font-size:14px;"></i> المدينة</span>
                <span class="bid-info-value"><?= sanitize($bid['city'] ?? 'الرياض') ?></span>
              </div>
              <div class="bid-info-row">
                <span class="bid-info-label"><i class="ph ph-tag" style="font-size:14px;"></i> مزايدتك</span>
                <span class="bid-info-value price"><?= number_format($bid['amount']) ?> ر.س</span>
              </div>
              <div class="bid-info-row">
                <span class="bid-info-label"><i class="ph ph-trophy" style="font-size:14px;"></i> السعر الحالي</span>
                <span class="bid-info-value price"><?= number_format($bid['current_price']) ?> ر.س</span>
              </div>
              <?php if ($timer && $timer['total'] > 0): ?>
              <div class="bid-info-row">
                <span class="bid-info-label"><i class="ph ph-timer" style="font-size:14px;"></i> المتبقي</span>
                <span class="bid-info-value" style="font-family:var(--font-en); font-size:13px; direction:ltr;">
                  <?= $timer['days'] ?>d <?= $timer['hours'] ?>h <?= $timer['mins'] ?>m
                </span>
              </div>
              <?php endif; ?>

              <a href="/auction-room.php?id=<?= $bid['auction_id'] ?>" class="btn btn-primary" style="width:100%; justify-content:center; border-radius:var(--radius-round); margin-top:16px; font-size:14px;">
                <i class="ph ph-arrow-left" style="color:#fff;"></i> دخول غرفة المزاد
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
        <div class="buyer-empty">
          <div class="empty-icon" style="background: rgba(16,185,129,0.1);">
            <i class="ph-fill ph-shopping-bag" style="color: var(--success);"></i>
          </div>
          <h3>لم تقم بأي عملية شراء بعد</h3>
          <p>عند الفوز بمزاد أو إتمام عملية شراء فوري، ستظهر مشترياتك هنا</p>
          <a href="/auctions.php" class="btn btn-primary" style="margin-top:24px; border-radius:30px; padding:12px 30px; font-weight:800;">تصفح المزادات</a>
        </div>
      <?php else: ?>
        <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap:24px;">
          <?php foreach ($purchases as $p):
            $p_img = (!empty($p['image_url']) && strlen($p['image_url']) > 4) ? $p['image_url'] : getCarImage($p['make'] ?? 'default');
          ?>
          <div class="bid-card">
            <div class="bid-card-img">
              <img src="<?= $p_img ?>" alt="<?= sanitize($p['title']) ?>" loading="lazy">
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
                <span class="bid-info-value" style="font-size:13px;"><?= sanitize($p['created_at'] ?? '') ?></span>
              </div>
              <a href="/vehicle-details.php?id=<?= $p['auction_id'] ?? $p['id'] ?>" class="btn btn-primary" style="width:100%; justify-content:center; border-radius:var(--radius-round); margin-top:16px; font-size:14px;">
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
        <div class="buyer-empty">
          <div class="empty-icon" style="background: rgba(239, 68, 68, 0.1);">
            <i class="ph-fill ph-heart" style="color: #ef4444;"></i>
          </div>
          <h3>لم تقم بإضافة أي مركبات للمفضلة بعد</h3>
          <p>تصفح صالة المزادات والمبيعات الفورية واضغط على أيقونة القلب لحفظها هنا</p>
          <a href="/auctions.php" class="btn btn-primary" style="margin-top:24px; border-radius:30px; padding:12px 30px; font-weight:800;">تصفح المركبات الآن</a>
        </div>
      <?php else: ?>
        <div class="fav-grid">
          <?php foreach ($fav_items as $item):
            $title_car = $item['title'] ?? (($item['make'] ?? '') . ' ' . ($item['model'] ?? '') . ' ' . ($item['year'] ?? ''));
            $img = (!empty($item['image_url']) && strlen($item['image_url']) > 4) ? $item['image_url'] : getCarImage($item['make'] ?? 'default');
            $is_instant = ($item['type'] ?? '') === 'instant';
          ?>
            <div class="auction-card" style="cursor:pointer; background:#fff;" onclick="window.location.href='<?= $is_instant ? '/vehicle-details.php' : '/auction-room.php' ?>?id=<?= $item['id'] ?>'">
              <div class="card-fav active" onclick="event.stopPropagation(); toggleFavorite(<?= $item['id'] ?>, this)" style="z-index:20;">
                <i class="ph-fill ph-heart" style="color:var(--danger);"></i>
              </div>
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
          <div style="display: flex; gap: 12px; position:relative; z-index:2;">
            <button class="wallet-btn" onclick="openTopupModal()">
              شحن الرصيد <i class="ph ph-plus" style="color:#fff; font-size:16px;"></i>
            </button>
            <button class="wallet-btn wallet-btn-secondary">
              استرداد <i class="ph ph-arrow-down" style="color:#fff; font-size:16px;"></i>
            </button>
          </div>
        </div>
        <div class="wallet-verify-card">
          <div class="verify-icon">
            <i class="ph ph-shield-check"></i>
          </div>
          <h4 style="font-size: 18px; font-weight: 800; margin-bottom: 8px;">حسابك موثق ونشط</h4>
          <p style="color: var(--text-muted); font-size: 14px;">تم التحقق من بيانات النفاذ الوطني ويمكنك المزايدة بحرية.</p>
        </div>
      </div>

      <!-- Recent Transactions -->
      <div class="transactions-section">
        <h3 class="transactions-header" style="display:flex; justify-content:space-between; align-items:center;">
          <span>أحدث العمليات</span>
          <button class="btn btn-outline" style="font-size:13px; padding:6px 12px; border-radius:var(--radius-md);" onclick="alert('جاري التصدير...')"><i class="ph ph-download-simple"></i> تصدير CSV</button>
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
                          $icon_bg = $is_credit ? 'rgba(16,185,129,0.1)' : 'rgba(244,63,94,0.1)';
                          $icon_color = $is_credit ? 'var(--success)' : 'var(--danger)';
                          $icon_name = $is_credit ? 'ph-arrow-up-right' : 'ph-arrow-down-left';
                          echo '<div style="display:flex; align-items:center; gap:14px; padding:14px 0; border-bottom:1px solid var(--border-light);">';
                          echo '<div style="width:42px;height:42px;border-radius:50%;background:' . $icon_bg . ';display:flex;align-items:center;justify-content:center;"><i class="ph ' . $icon_name . '" style="font-size:18px;color:' . $icon_color . ';"></i></div>';
                          echo '<div style="flex:1;"><div style="font-weight:700;color:var(--text-dark);font-size:14px;">' . sanitize($row['description'] ?? ($is_credit ? 'إيداع' : 'خصم')) . '</div>';
                          echo '<div style="font-size:12px;color:var(--text-muted);margin-top:2px;">' . sanitize($row['created_at'] ?? '') . '</div></div>';
                          echo '<div style="font-weight:900;font-family:var(--font-en);color:' . ($is_credit ? 'var(--success)' : 'var(--danger)') . ';">' . ($is_credit ? '+' : '-') . number_format($row['amount'] ?? 0) . ' ر.س</div>';
                          echo '</div>';
                      }
                  }
                  $stmt->close();
              }
          }
          if (!$has_transactions):
        ?>
        <div style="text-align:center; padding: 40px 20px; border: 1px dashed var(--border-light); border-radius: var(--radius-md);">
          <i class="ph ph-receipt" style="font-size: 48px; color: var(--text-light-muted); margin-bottom: 12px;"></i>
          <p style="color:var(--text-muted); font-size:15px;">لا توجد عمليات مالية سابقة</p>
        </div>
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
    <!-- SECTION: SETTINGS                                      -->
    <!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
    <?php elseif ($section === 'settings'):
      $current_name = $_SESSION['user_name'] ?? '';
      $current_phone = $_SESSION['user_phone'] ?? '';
      $current_city = $_SESSION['user_city'] ?? '';

      // Try to load from DB
      if ($db_connected) {
          $stmt = $conn->prepare('SELECT name, phone, city FROM users WHERE id = ?');
          if ($stmt) {
              $stmt->bind_param('i', $user_id);
              $stmt->execute();
              $stmt->bind_result($db_name, $db_phone, $db_city);
              if ($stmt->fetch()) {
                  $current_name = $db_name ?: $current_name;
                  $current_phone = $db_phone ?: $current_phone;
                  $current_city = $db_city ?: $current_city;
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

// ── Toast auto-remove ──────────────────────────────────────
document.querySelectorAll('.success-toast').forEach(t => {
  setTimeout(() => t.remove(), 3000);
});
</script>
</body>
</html>
