<?php
/**
 * Seller fleet vehicle card — shared by dashboard preview and fleet section.
 * Expects: $car (array), optional $idx (int), optional $fx_seller_card_compact (bool)
 */
if (empty($car) || !is_array($car)) return;

$idx = $idx ?? 0;
$compact = !empty($fx_seller_card_compact);
$title = $car['title'] ?? trim(($car['make'] ?? '') . ' ' . ($car['model'] ?? '') . ' ' . ($car['year'] ?? ''));
$vid = intval($car['vehicle_id'] ?? $car['id'] ?? $idx);
$make_label = trim(($car['make'] ?? '') . ' ' . ($car['model'] ?? ''));
$img_type = (($car['type'] ?? '') === 'instant') ? 'instant' : 'live';
$thumb = fleetx_vehicle_thumb($car['image_url'] ?? '', $vid, $img_type, $make_label);
$bids = $car['bid_count'] ?? 0;
$price = $car['current_price'] ?? $car['starting_price'] ?? 0;
$views = 0;
$vst = $car['v_status'] ?? 'pending';
$statuses = ['active' => 'نشط', 'pending' => 'قيد المراجعة', 'ended' => 'منتهي', 'sold' => 'مباع'];
$status_classes = ['active' => 'status-active', 'pending' => 'status-pending', 'ended' => 'status-ended', 'sold' => 'status-sold'];
$vstatus_labels = [
    'pending' => 'مسودة', 'awaiting_admin' => 'بانتظار الإدارة',
    'inspection_scheduled' => 'مجدول للفحص', 'awaiting_seller_approval' => 'بانتظار موافقتك',
    'approved' => 'معتمدة', 'in_auction' => 'في المزاد', 'sold' => 'مباعة',
    'withdrawn' => 'مسحوبة', 'suspended' => 'موقوفة',
];
$st = !empty($car['id']) ? ($car['status'] ?? 'active') : $vst;
$st_class = $status_classes[$st] ?? ($vst === 'approved' ? 'status-active' : 'status-pending');
$st_label = $statuses[$st] ?? ($vstatus_labels[$vst] ?? 'قيد المراجعة');
?>
<div class="fleet-card<?= $compact ? ' fleet-card--compact' : '' ?>">
  <div class="fleet-card-img">
    <img
      src="<?= htmlspecialchars($thumb['src']) ?>"
      alt="<?= sanitize($title) ?>"
      loading="lazy"
      decoding="async"
      onerror="<?= $thumb['onerror'] ?>"
    >
    <span class="fleet-card-status <?= $st_class ?>"><?= $st_label ?></span>
  </div>
  <div class="fleet-card-body">
    <div class="fleet-card-title"><?= sanitize($title) ?></div>
    <div class="fleet-card-meta">
      <span><i class="ph ph-map-pin" style="font-size:14px;"></i> <?= sanitize($car['city'] ?? 'الرياض') ?></span>
      <span><i class="ph ph-gauge" style="font-size:14px;"></i> <?= number_format($car['mileage'] ?? 0) ?> كم</span>
      <span><i class="ph ph-calendar" style="font-size:14px;"></i> <?= $car['year'] ?? '2023' ?></span>
    </div>
    <?php if (!$compact && !empty($car['autodata_price_min']) && !empty($car['autodata_price_max'])): ?>
    <div class="fleet-card-meta fleet-card-meta--accent">
      <span><i class="ph ph-chart-line-up" style="font-size:14px;"></i> تقييم AutoData: <?= number_format($car['autodata_price_min']) ?> - <?= number_format($car['autodata_price_max']) ?> ر.س</span>
    </div>
    <?php endif; ?>
    <div class="fleet-card-stats">
      <div class="fleet-card-price"><?= number_format($price) ?> <span class="cur">ر.س</span></div>
      <?php if (!$compact): ?>
      <div class="fleet-card-bids">
        <i class="ph ph-gavel" style="font-size:14px; color:var(--text-muted);"></i> <?= $bids ?> مزايدة
        <span style="margin:0 6px; color:var(--border-light);">|</span>
        <i class="ph ph-eye" style="font-size:14px; color:var(--text-muted);"></i> <?= $views ?>
      </div>
      <?php endif; ?>
    </div>
    <?php if ($compact): ?>
    <a href="?section=fleet" class="fleet-btn fleet-btn-view" style="width:100%;margin-top:8px;"><i class="ph ph-arrow-left"></i> إدارة المركبة</a>
    <?php else: ?>
    <div class="fleet-card-actions fleet-card-actions--stack">
      <?php if (in_array($car['v_status'] ?? '', ['pending', 'withdrawn', 'suspended'], true)): ?>
      <a href="?section=fleet&push_inspection=<?= (int)$car['vehicle_id'] ?>" class="fleet-btn" style="background:#f59e0b; color:#fff; width:100%; margin-bottom:8px;"><i class="ph ph-magnifying-glass" style="font-size:14px;"></i> إرسال للفحص</a>
      <?php endif; ?>
      <?php if (($car['v_status'] ?? '') === 'suspended'): ?>
      <a href="?section=fleet&unsuspend=<?= (int)$car['vehicle_id'] ?>" class="fleet-btn" style="background:#10b981; color:#fff; width:100%; margin-bottom:8px;"><i class="ph ph-play"></i> إعادة التفعيل</a>
      <?php elseif (!in_array($car['v_status'] ?? '', ['sold','in_auction'], true)): ?>
      <a href="?section=fleet&suspend=<?= (int)$car['vehicle_id'] ?>" class="fleet-btn fleet-btn-edit" style="width:100%; margin-bottom:8px;" onclick="return confirm('إيقاف المركبة مؤقتاً؟')"><i class="ph ph-pause"></i> إيقاف مؤقت</a>
      <?php endif; ?>
      <?php if (($car['v_status'] ?? '') === 'approved' && empty($car['id'])): ?>
      <a href="?section=fleet&publish_auction=<?= (int)$car['vehicle_id'] ?>" class="fleet-btn" style="background:var(--primary); color:#000; width:100%; margin-bottom:8px;"><i class="ph ph-gavel" style="font-size:14px;"></i> نشر في المزاد</a>
      <?php endif; ?>
      <?php if (!empty($car['id'])): ?>
      <a href="/auction-room.php?id=<?= (int)$car['id'] ?>" class="fleet-btn fleet-btn-view"><i class="ph ph-eye" style="font-size:14px; color:inherit;"></i> عرض</a>
      <?php endif; ?>
      <a href="/add-auction.php?vehicle_id=<?= (int)$car['vehicle_id'] ?>" class="fleet-btn fleet-btn-edit"><i class="ph ph-pencil-simple" style="font-size:14px; color:inherit;"></i> تعديل</a>
      <button type="button" class="fleet-btn fleet-btn-delete" onclick="if(confirm('هل أنت متأكد من حذف هذه المركبة؟')) window.location.href='?section=fleet&delete=<?= (int)$car['vehicle_id'] ?>'"><i class="ph ph-trash" style="font-size:16px; color:inherit;"></i></button>
    </div>
    <?php endif; ?>
  </div>
</div>