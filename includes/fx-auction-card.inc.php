<?php
/**
 * Unified auction / instant card for grids and swipers.
 * Set $fx_card before include:
 * id, href, title, image, type (live|instant), status, city, mileage, year,
 * price, price_label?, end_time?, is_vip?, is_featured?, show_installment?,
 * seller?, vehicles_count?, timer_data? (array from timeLeft)
 */
if (empty($fx_card) || !is_array($fx_card)) return;

$c = $fx_card;
$href = $c['href'] ?? '#';
$title = $c['title'] ?? '';
$image = $c['image'] ?? '';
$type = $c['type'] ?? 'live';
$status = $c['status'] ?? 'active';
$is_live = in_array($status, ['live', 'active'], true);
$is_instant = ($type === 'instant');
$is_ended = ($status === 'ended');
$is_upcoming = ($status === 'upcoming');
$is_vip = !empty($c['is_vip']);
$is_featured = !empty($c['is_featured']);
$card_class = 'auction-card auction-card--clickable fx-card';
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
<div class="<?= $card_class ?>" data-id="<?= $card_id ?>" onclick="window.location.href='<?= htmlspecialchars($href) ?>'">
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

  <div class="ac-img-wrap">
    <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($title) ?>" loading="lazy">
  </div>

  <div class="ac-body">
    <h3 class="ac-title"><?= htmlspecialchars($title) ?></h3>

    <div class="ac-stats-row">
      <?php if (!empty($c['seller'])): ?>
      <div class="ac-stat-cell">
        <span class="label">البائع</span>
        <span><?= htmlspecialchars($c['seller']) ?></span>
      </div>
      <?php endif; ?>
      <div class="ac-stat-cell">
        <span class="label">المدينة</span>
        <span><?= htmlspecialchars($c['city'] ?? 'الرياض') ?></span>
      </div>
      <?php if (!empty($c['vehicles_count'])): ?>
      <div class="ac-stat-cell">
        <span class="label">المركبات</span>
        <span class="font-en">+<?= intval($c['vehicles_count']) ?></span>
      </div>
      <?php else: ?>
      <div class="ac-stat-cell">
        <span class="label">الممشى</span>
        <span class="font-en"><?= number_format(intval($c['mileage'] ?? 0)) ?> KM</span>
      </div>
      <div class="ac-stat-cell">
        <span class="label">السنة</span>
        <span class="font-en"><?= htmlspecialchars($c['year'] ?? '2023') ?></span>
      </div>
      <?php endif; ?>
    </div>

    <div class="ac-price-row">
      <div>
        <div class="ac-price-label"><?= htmlspecialchars($price_label) ?></div>
        <div class="ac-price-val"><?= number_format($price) ?> <span class="ac-price-currency">ر.س</span></div>
      </div>
    </div>

    <?php if ($is_ended): ?>
    <div class="ac-timer-box ac-timer-box--row ac-timer-box--ended">
      <span class="ac-timer-label ac-timer-label--sm">حالة المزاد:</span>
      <div class="ac-timer-ended-msg">انتهى المزاد بنجاح</div>
    </div>
    <?php elseif ($is_instant && !empty($c['end_time'])): ?>
    <div class="ac-timer-box ac-timer-box--row ac-timer-box--instant">
      <span class="ac-timer-label ac-timer-label--sm">تاريخ الانتهاء:</span>
      <div class="ac-timer-date"><?= date('Y-m-d', strtotime($c['end_time'])) ?></div>
    </div>
    <?php elseif ($timer && ($timer['total'] ?? 0) > 0): ?>
    <div class="ac-timer-box ac-timer-box--row ac-timer-box--live">
      <span class="ac-timer-label ac-timer-label--sm">ينتهي خلال:</span>
      <div class="ac-timer-digits" data-endtime="<?= strtotime($c['end_time'] ?? '') ?>">
        <div class="ac-timer-unit"><span data-unit="hours"><?= str_pad($timer['hours'] ?? 0, 2, '0', STR_PAD_LEFT) ?></span></div>
        <span class="ac-timer-sep">:</span>
        <div class="ac-timer-unit"><span data-unit="mins"><?= str_pad($timer['mins'] ?? 0, 2, '0', STR_PAD_LEFT) ?></span></div>
        <span class="ac-timer-sep">:</span>
        <div class="ac-timer-unit"><span data-unit="secs"><?= str_pad($timer['secs'] ?? 0, 2, '0', STR_PAD_LEFT) ?></span></div>
      </div>
    </div>
    <?php endif; ?>

    <div class="ac-actions" onclick="event.stopPropagation();">
      <a href="<?= htmlspecialchars($href) ?>" class="btn btn-primary btn-ac-full<?= $is_instant ? ' btn-ac-full--gradient' : '' ?>">
        <?= $is_instant ? 'شراء الآن' : 'عرض تفاصيل المزاد' ?> <i class="ph ph-arrow-left"></i>
      </a>
    </div>
  </div>
</div>