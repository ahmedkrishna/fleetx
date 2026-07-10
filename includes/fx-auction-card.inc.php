<?php
/**
 * Unified auction / instant card — classic v1 layout + refined timer
 * Set $fx_card before include
 */
if (empty($fx_card) || !is_array($fx_card)) return;

$c = $fx_card;
$href = $c['href'] ?? '#';
$title = $c['title'] ?? '';
$type = $c['type'] ?? 'live';
$status = $c['status'] ?? 'active';
$is_live = in_array($status, ['live', 'active'], true);
$is_instant = ($type === 'instant');
$is_ended = ($status === 'ended');
$is_upcoming = ($status === 'upcoming');
$is_vip = !empty($c['is_vip']);
$is_featured = !empty($c['is_featured']);
$seed = intval($c['id'] ?? 0);

$image = fleetx_card_image($c['image'] ?? '', $seed, $is_instant ? 'instant' : 'live');
$fallback_img = fleetx_card_image('', $seed, $is_instant ? 'instant' : 'live');

$card_class = 'auction-card auction-card--clickable fx-card-unified';
$card_class .= $is_instant ? ' fx-card--instant' : ' fx-card--auction';
if ($is_vip) $card_class .= ' vip-card';
elseif ($is_featured) $card_class .= ' featured-card';
if (!empty($c['extra_class'])) $card_class .= ' ' . $c['extra_class'];

$status_class = 'badge-status--live';
$status_label = 'جاري';
if ($is_ended) { $status_class = 'badge-status--ended'; $status_label = 'منتهي'; }
elseif ($is_upcoming) { $status_class = 'badge-status--upcoming'; $status_label = 'قادم'; }
elseif ($is_instant) { $status_class = 'badge-status--instant'; $status_label = 'مباشر'; }

$price = intval($c['price'] ?? 0);
$price_label = $c['price_label'] ?? ($is_instant ? 'السعر المطلوب' : 'السعر الافتتاحي');
$timer = $c['timer_data'] ?? null;
$card_id = intval($c['id'] ?? 0);
?>
<article class="<?= $card_class ?>" data-id="<?= $card_id ?>" onclick="window.location.href='<?= htmlspecialchars($href) ?>'">
  <div class="ac-img-wrap ac-img-wrap--square">
    <img
      src="<?= htmlspecialchars($image) ?>"
      alt="<?= htmlspecialchars($title) ?>"
      loading="lazy"
      decoding="async"
      onerror="this.onerror=null;this.src='<?= htmlspecialchars($fallback_img, ENT_QUOTES) ?>';"
    >
    <div class="ac-img-title-overlay" aria-hidden="true"></div>
    <h3 class="ac-title ac-title--on-image"><?= htmlspecialchars($title) ?></h3>
    <div class="card-badges-container">
      <div class="badge-item badge-status <?= $status_class ?>">
        <?php if ($is_live && !$is_ended): ?><span class="badge-status__pulse"></span><?php endif; ?>
        <?= $status_label ?>
      </div>
      <?php if ($is_vip): ?>
        <div class="badge-item badge-vip"><i class="ph-fill ph-crown ph-space-left"></i> VIP</div>
      <?php elseif ($is_featured): ?>
        <div class="badge-item badge-featured"><i class="ph-fill ph-star ph-space-left"></i> مميز</div>
      <?php endif; ?>
      <?php if (!empty($c['show_installment'])): ?>
        <div class="badge-item badge-status badge-status--installment"><i class="ph-fill ph-wallet"></i> تقسيط</div>
      <?php endif; ?>
    </div>
    <?php if ($card_id): ?>
    <div class="card-fav" data-id="<?= $card_id ?>" onclick="event.stopPropagation();"><i class="ph ph-heart"></i></div>
    <?php endif; ?>
  </div>

  <div class="ac-body">
    <div class="ac-meta ac-meta--plain">
      <?php if (!empty($c['seller'])): ?>
      <span><i class="ph ph-storefront"></i><?= htmlspecialchars($c['seller']) ?></span>
      <?php endif; ?>
      <span><i class="ph ph-map-pin"></i><?= htmlspecialchars($c['city'] ?? 'الرياض') ?></span>
      <?php if (!empty($c['vehicles_count'])): ?>
      <span><i class="ph ph-truck"></i><span class="font-en">+<?= intval($c['vehicles_count']) ?></span></span>
      <?php else: ?>
      <span><i class="ph ph-gauge"></i><span class="font-en"><?= number_format(intval($c['mileage'] ?? 0)) ?> KM</span></span>
      <span><i class="ph ph-calendar-blank"></i><span class="font-en"><?= htmlspecialchars($c['year'] ?? '2023') ?></span></span>
      <?php endif; ?>
    </div>

    <div class="ac-info-row ac-info-row--board">
      <div class="ac-price-block">
        <div class="ac-price-label"><?= htmlspecialchars($price_label) ?></div>
        <div class="ac-price-val font-en"><?= number_format($price) ?> <span class="ac-price-currency">ر.س</span></div>
      </div>

      <div class="ac-timer-board">
        <?php if ($is_ended): ?>
        <span class="ac-timer-board__label"><i class="ph ph-check-circle"></i> انتهى</span>
        <?php elseif ($is_instant && !empty($c['end_time'])): ?>
        <span class="ac-timer-board__label"><i class="ph ph-calendar"></i> ينتهي</span>
        <span class="ac-timer-board__val font-en"><?= date('m/d', strtotime($c['end_time'])) ?></span>
        <?php elseif ($timer && ($timer['total'] ?? 0) > 0): ?>
        <span class="ac-timer-board__label"><i class="ph ph-clock-countdown"></i> ينتهي خلال</span>
        <div class="ac-timer-board__chips fx-timer-chips font-en" data-countdown="<?= htmlspecialchars($c['end_time'] ?? '') ?>">
          <span data-unit="hours"><?= str_pad($timer['hours'] ?? 0, 2, '0', STR_PAD_LEFT) ?></span>
          <em>:</em>
          <span data-unit="mins"><?= str_pad($timer['mins'] ?? 0, 2, '0', STR_PAD_LEFT) ?></span>
          <em>:</em>
          <span data-unit="secs"><?= str_pad($timer['secs'] ?? 0, 2, '0', STR_PAD_LEFT) ?></span>
        </div>
        <?php elseif ($is_upcoming): ?>
        <span class="ac-timer-board__label"><i class="ph ph-hourglass"></i> قريباً</span>
        <?php endif; ?>
      </div>
    </div>

    <div class="ac-actions" onclick="event.stopPropagation();">
      <a href="<?= htmlspecialchars($href) ?>" class="btn btn-primary btn-ac-full fx-btn-gradient">
        <?= $is_instant ? 'شراء الآن' : 'عرض التفاصيل' ?> <i class="ph ph-arrow-left"></i>
      </a>
    </div>
  </div>
</article>