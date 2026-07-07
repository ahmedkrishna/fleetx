<?php
require_once 'config.php';
requireLogin('/login.php');

$tab = $_GET['tab'] ?? 'wallet';
$uid = (int)$_SESSION['user_id'];
$role = getUserRole();
$user_name = $_SESSION['user_name'] ?? 'مستخدم';
$wallet_bal = $_SESSION['wallet_balance'] ?? 0;
$nafath_verified = false;
$active_bid = null;
$fav_items = [];

if ($db_connected) {
    $wst = $conn->prepare('SELECT wallet_balance, nafath_verified FROM users WHERE id = ?');
    $wst->bind_param('i', $uid);
    $wst->execute();
    $wrow = $wst->get_result()->fetch_assoc();
    if ($wrow) {
        $wallet_bal = floatval($wrow['wallet_balance'] ?? 0);
        $_SESSION['wallet_balance'] = $wallet_bal;
        $nafath_verified = !empty($wrow['nafath_verified']);
    }

    $bst = $conn->prepare("SELECT a.id, a.current_price, v.make, v.model, v.year, v.image_url
                           FROM bids b
                           JOIN auctions a ON b.auction_id = a.id
                           JOIN vehicles v ON a.vehicle_id = v.id
                           WHERE b.user_id = ? AND a.status = 'active'
                           ORDER BY b.amount DESC LIMIT 1");
    $bst->bind_param('i', $uid);
    $bst->execute();
    $active_bid = $bst->get_result()->fetch_assoc();
}

if ($tab === 'favorites') {
    $fav_items = getUserFavoriteAuctions($db_connected ? $conn : null, $uid);
}

$role_labels = [
    'buyer' => 'مشتري',
    'seller' => 'بائع',
    'inspector' => 'مفتش',
    'admin' => 'مدير',
];
$role_label = $role_labels[$role] ?? 'عضو';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>حسابي | FleetX</title>
  <link rel="stylesheet" href="/assets/css/fleetx.css">
</head>
<body class="page-inner fx-page-shell fx-page-shell--legal">

<?php include 'includes/navbar.php'; ?>

<div class="container fx-profile-page">
  <div class="fx-profile-header">
    <div class="fx-profile-user">
      <div class="fx-profile-avatar"><?= mb_substr($user_name, 0, 1) ?></div>
      <div>
        <h1 class="fx-profile-name"><?= sanitize($user_name) ?></h1>
        <p class="fx-profile-badge">
          <?php if ($nafath_verified): ?>
            <i class="ph-fill ph-check-circle"></i> حساب <?= $role_label ?> موثق (Nafath)
          <?php else: ?>
            <i class="ph ph-user"></i> حساب <?= $role_label ?>
          <?php endif; ?>
        </p>
      </div>
    </div>
    <a href="<?= getDashboardUrl() ?>" class="btn btn-outline btn-sm hide-on-mobile">
      <i class="ph ph-squares-four ph-space-left"></i> لوحة التحكم
    </a>
  </div>

  <div class="fx-profile-layout">
    <aside class="fx-profile-sidebar">
      <ul class="fx-profile-nav">
        <li><a href="?tab=wallet" class="<?= $tab === 'wallet' ? 'active' : '' ?>"><i class="ph-fill ph-wallet"></i> المحفظة والمشتريات</a></li>
        <li><a href="?tab=favorites" class="<?= $tab === 'favorites' ? 'active' : '' ?>"><i class="ph ph-heart"></i> المفضلة</a></li>
        <li><a href="?tab=bids" class="<?= $tab === 'bids' ? 'active' : '' ?>"><i class="ph ph-gavel"></i> سجل المزايدات</a></li>
        <li><a href="?tab=settings" class="<?= $tab === 'settings' ? 'active' : '' ?>"><i class="ph ph-gear"></i> الإعدادات</a></li>
        <li><a href="/logout.php" class="danger"><i class="ph ph-sign-out"></i> تسجيل خروج</a></li>
      </ul>
    </aside>

    <main class="fx-profile-main">
      <?php if ($tab === 'wallet'): ?>
        <div class="fx-profile-card fx-wallet-card">
          <div class="fx-wallet-glow"></div>
          <div class="fx-wallet-row">
            <div>
              <div class="fx-wallet-label">الرصيد المتاح (التأمين)</div>
              <div class="fx-wallet-amount">
                <?= number_format($wallet_bal) ?> <span>ر.س</span>
              </div>
            </div>
            <div class="fx-wallet-actions">
              <a href="/wallet-topup.php" class="btn btn-primary"><i class="ph ph-plus"></i> شحن المحفظة</a>
              <button class="btn btn-outline" onclick="alert('خاصية الاسترداد تتطلب مراجعة حسابك البنكي أولاً.')"><i class="ph ph-arrow-down"></i> استرداد</button>
            </div>
          </div>
        </div>

        <div class="fx-profile-card">
          <h3>مزايدات نشطة تشارك بها</h3>
          <?php if ($active_bid): ?>
            <?php $bid_title = sanitize($active_bid['make'] . ' ' . $active_bid['model'] . ' ' . $active_bid['year']); ?>
            <div class="fx-bid-preview">
              <div class="fx-bid-preview-info">
                <div class="fx-bid-preview-img">
                  <img src="<?= sanitize($active_bid['image_url'] ?? '') ?>" alt="<?= $bid_title ?>">
                </div>
                <div>
                  <div class="fx-bid-preview-title"><?= $bid_title ?></div>
                  <div class="fx-bid-preview-status">مزايدة نشطة</div>
                </div>
              </div>
              <div class="fx-bid-preview-price">
                <strong><?= number_format($active_bid['current_price']) ?> SAR</strong>
                <a href="/auction-live.php?id=<?= (int)$active_bid['id'] ?>">متابعة المزاد</a>
              </div>
            </div>
          <?php else: ?>
            <p class="fx-empty-tab">لا توجد مزايدات نشطة حالياً. <a href="/auctions.php" style="color:var(--primary); font-weight:800;">تصفح المزادات</a></p>
          <?php endif; ?>
        </div>

      <?php elseif ($tab === 'favorites'): ?>
        <div class="fx-profile-card">
          <h3>المفضلة <span style="font-size:14px; color:var(--text-muted); font-weight:700;">(<?= count($fav_items) ?>)</span></h3>
          <?php if (empty($fav_items)): ?>
            <div class="fx-empty-state" style="padding:48px 24px; border:none; box-shadow:none;">
              <i class="ph-fill ph-heart" style="font-size:48px; color:#ef4444; margin-bottom:14px;"></i>
              <h3>لم تقم بإضافة أي مركبات للمفضلة بعد</h3>
              <p>تصفح المزادات أو الشراء الفوري واضغط على أيقونة القلب لحفظ المركبات هنا</p>
              <a href="/auctions.php" class="btn btn-primary" style="margin-top:20px;">تصفح المركبات الآن</a>
            </div>
          <?php else: ?>
            <div class="fav-grid">
              <?php foreach ($fav_items as $item):
                $title_car = $item['title'] ?? (($item['make'] ?? '') . ' ' . ($item['model'] ?? '') . ' ' . ($item['year'] ?? ''));
                $img = getCarImage($item['make'] ?? 'default', $item['image_url'] ?? null);
                $is_instant = ($item['type'] ?? '') === 'instant';
                $detail_url = $is_instant ? '/vehicle-details.php?id=' . (int)$item['id'] : '/auction-live.php?id=' . (int)$item['id'];
              ?>
              <div class="auction-card fx-fav-card" data-auction-id="<?= (int)$item['id'] ?>">
                <div class="card-fav active" data-id="<?= (int)$item['id'] ?>" data-remove-on-unfav="1">
                  <i class="ph-fill ph-heart" style="color:var(--danger);"></i>
                </div>
                <a href="<?= $detail_url ?>" class="fx-fav-card-link">
                  <div class="ac-img-wrap">
                    <img src="<?= sanitize($img) ?>" alt="<?= sanitize($title_car) ?>" loading="lazy">
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
                  </div>
                </a>
                <div class="ac-actions" style="padding:0 20px 20px;">
                  <a href="<?= $detail_url ?>" class="btn btn-primary" style="width:100%; justify-content:center; border-radius:var(--radius-round);">
                    <?= $is_instant ? 'شراء الآن' : 'دخول المزاد' ?>
                  </a>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

      <?php elseif ($tab === 'bids'): ?>
        <div class="fx-profile-card">
          <h3>سجل المزايدات والمشتريات</h3>
          <p class="fx-empty-tab">جميع المشتريات والمزايدات التي قمت بها ستظهر هنا مع الفواتير. يمكنك أيضاً مراجعة سجل المزايدات من <a href="/buyer.php?section=bids" style="color:var(--primary); font-weight:800;">لوحة المشتري</a>.</p>
        </div>

      <?php elseif ($tab === 'settings'): ?>
        <div class="fx-profile-card">
          <h3>إعدادات الحساب</h3>
          <form onsubmit="event.preventDefault(); alert('تم حفظ الإعدادات!');">
            <div class="form-group" style="margin-bottom:16px;">
              <label style="display:block; margin-bottom:8px; font-weight:800; color:var(--text-dark);">الاسم الكامل</label>
              <input type="text" class="form-control" value="<?= sanitize($user_name) ?>">
            </div>
            <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
          </form>
        </div>
      <?php endif; ?>
    </main>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>