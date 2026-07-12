<?php
require_once 'config.php';
requireLogin();

$user_id = (int)getUserId();
$items = getUserFavoriteAuctions($db_connected ? $conn : null, $user_id);

$cart_count = count($items);
$instant_count = 0;
$cart_total = 0.0;
$first_instant_id = null;

foreach ($items as $item) {
    $is_instant = (($item['type'] ?? '') === 'instant');
    $price = (float)($item['sale_price'] ?? $item['current_price'] ?? $item['starting_price'] ?? 0);
    if ($is_instant) {
        $instant_count++;
        $cart_total += $price;
        if ($first_instant_id === null) {
            $first_instant_id = (int)($item['id'] ?? 0);
        }
    }
}

$page_title = 'سلة المشتريات | FleetX';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= sanitize($page_title) ?></title>
  <meta name="fx-build" content="<?= FLEETX_CSS_VER ?>">
  <link rel="stylesheet" href="<?= fleetx_css_href() ?>">
</head>
<body class="fx-home fx-page-shell fx-page-shell--cart" data-fx-build="<?= FLEETX_CSS_VER ?>">

<?php include 'includes/navbar.php'; ?>

<?php
$hero_title = 'سلة المشتريات';
$hero_desc = 'راجع المركبات المحفوظة وتابع الشراء الفوري أو المزادات من مكان واحد.';
$hero_bg = 'https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?w=1600&q=80';
$hero_eyebrow = 'سلة المشتريات';
$hero_modifier = 'cover';
$hero_meta_html = '<span class="fx-page-hero__chip"><i class="ph-fill ph-shopping-cart"></i> ' . (int)$cart_count . ' مركبة محفوظة</span>'
    . ($instant_count > 0
        ? '<span class="fx-page-hero__chip fx-page-hero__chip--accent"><i class="ph-fill ph-lightning"></i> ' . (int)$instant_count . ' شراء فوري</span>'
        : '');
include 'includes/page-hero.inc.php';
?>

<div class="container fx-page-body fx-page-body--overlap">
  <div class="fx-cart-layout" id="fxCartLayout">

    <div class="fx-cart-items-box">
      <h3 class="fx-cart-heading">المركبات المحفوظة</h3>

      <?php if (empty($items)): ?>
        <?php
          $empty_icon = 'ph-fill ph-shopping-cart';
          $empty_variant = 'info';
          $empty_title = 'سلتك فارغة حالياً';
          $empty_desc = 'تصفح المزادات والمبيعات الفورية واضغط على أيقونة القلب لحفظ المركبات هنا';
          $empty_cta_href = '/auctions.php';
          $empty_cta_label = 'تصفح المركبات الآن';
          include 'includes/dashboard/empty-state.inc.php';
        ?>
      <?php else: ?>
        <div class="fx-cart-list" id="fxCartList">
          <?php foreach ($items as $item):
            $title_car = $item['title'] ?? trim(($item['make'] ?? '') . ' ' . ($item['model'] ?? '') . ' ' . ($item['year'] ?? ''));
            $is_instant = (($item['type'] ?? '') === 'instant');
            $cart_type = $is_instant ? 'instant' : 'live';
            $thumb = fleetx_vehicle_thumb($item['image_url'] ?? '', (int)($item['id'] ?? 0), $cart_type, $item['make'] ?? '');
            $price = (float)($item['sale_price'] ?? $item['current_price'] ?? $item['starting_price'] ?? 0);
            $detail_href = $is_instant ? '/vehicle-details.php?id=' . (int)$item['id'] : '/auction-room.php?id=' . (int)$item['id'];
            $cta_href = $is_instant ? '/checkout.php?id=' . (int)$item['id'] : $detail_href;
            $cta_label = $is_instant ? 'إتمام الشراء' : 'دخول المزاد';
          ?>
          <article class="fx-cart-item" data-auction-id="<?= (int)$item['id'] ?>">
            <a href="<?= $detail_href ?>" class="fx-cart-item__media" aria-label="<?= sanitize($title_car) ?>">
              <img src="<?= htmlspecialchars($thumb['src']) ?>" alt="<?= sanitize($title_car) ?>" loading="lazy" decoding="async" onerror="<?= $thumb['onerror'] ?>">
            </a>
            <div class="fx-cart-item__body">
              <a href="<?= $detail_href ?>" class="fx-cart-item__title"><?= sanitize($title_car) ?></a>
              <div class="fx-cart-item__meta">
                <span><i class="ph ph-map-pin"></i> <?= sanitize($item['city'] ?? 'الرياض') ?></span>
                <span><i class="ph ph-gauge"></i> <?= number_format((int)($item['mileage'] ?? 0)) ?> كم</span>
              </div>
              <div class="fx-cart-item__type <?= $is_instant ? 'fx-cart-item__type--instant' : '' ?>">
                <?= $is_instant ? 'شراء فوري' : 'مزاد مباشر' ?>
              </div>
              <div class="fx-cart-item__price">
                <span><?= $is_instant ? 'السعر' : 'السعر الحالي' ?></span>
                <strong class="font-en"><?= number_format($price) ?> SAR</strong>
              </div>
            </div>
            <div class="fx-cart-item__actions">
              <a href="<?= $cta_href ?>" class="btn btn-primary fx-cart-item__cta"><?= $cta_label ?></a>
              <button type="button" class="fx-cart-item__remove" onclick="toggleFavorite(<?= (int)$item['id'] ?>, this)" data-remove-on-unfav="1" aria-label="إزالة من السلة">
                <i class="ph ph-trash"></i> إزالة
              </button>
            </div>
          </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <aside class="fx-cart-summary" aria-label="ملخص السلة">
      <div class="fx-cart-summary-card">
        <h3>ملخص السلة</h3>

        <div class="fx-cart-line">
          <span>عدد المركبات</span>
          <span class="font-en" id="fxCartCountDisplay"><?= (int)$cart_count ?></span>
        </div>
        <div class="fx-cart-line">
          <span>شراء فوري</span>
          <span class="font-en" id="fxCartInstantDisplay"><?= (int)$instant_count ?></span>
        </div>

        <?php if ($instant_count > 0): ?>
        <div class="fx-cart-total">
          <span class="fx-cart-total-label">إجمالي الشراء الفوري</span>
          <span class="fx-cart-total-val font-en" id="fxCartTotalDisplay"><?= number_format($cart_total) ?> SAR</span>
        </div>
        <?php endif; ?>

        <div class="fx-cart-summary-actions">
          <?php if ($first_instant_id): ?>
          <a href="/checkout.php?id=<?= (int)$first_instant_id ?>" class="btn btn-primary fx-cart-summary-btn fx-cart-summary-btn--desktop">
            <i class="ph-fill ph-lock-key"></i> إتمام أول عملية شراء
          </a>
          <?php endif; ?>
          <a href="/auctions.php" class="btn btn-outline fx-cart-summary-btn">متابعة التصفح</a>
        </div>

        <p class="fx-cart-summary-note">المركبات في السلة محفوظة في حسابك ويمكنك إزالتها أو إتمام الشراء في أي وقت.</p>
      </div>
    </aside>

  </div>
</div>

<?php if (!empty($items)): ?>
<div class="fx-mobile-bid-bar fx-cart-mobile-bar" id="fxCartMobileBar" aria-label="إجراءات السلة">
  <div class="fx-mobile-bid-inner">
    <div class="fx-mobile-bid-price">
      <small>السلة</small>
      <strong class="font-en" id="fxCartMobileCount"><?= (int)$cart_count ?> مركبة</strong>
    </div>
    <?php if ($first_instant_id): ?>
    <a href="/checkout.php?id=<?= (int)$first_instant_id ?>" class="fx-mobile-bid-btn">
      <i class="ph-fill ph-lock-key"></i> إتمام الشراء
    </a>
    <?php else: ?>
    <a href="/auctions.php" class="fx-mobile-bid-btn">
      <i class="ph ph-gavel"></i> تصفح المزادات
    </a>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
<script src="<?= fleetx_js_href() ?>"></script>
</body>
</html>