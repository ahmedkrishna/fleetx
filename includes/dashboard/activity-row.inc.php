<?php
/**
 * Dashboard activity / transaction row.
 * Vars: $activity_icon, $activity_variant, $activity_title, $activity_time
 */
$activity_icon = $activity_icon ?? 'ph ph-bell';
$activity_variant = $activity_variant ?? 'primary';
$activity_title = $activity_title ?? '';
$activity_time = $activity_time ?? '';
?>
<div class="fx-activity-item">
  <div class="fx-activity-item__icon fx-activity-item__icon--<?= htmlspecialchars($activity_variant) ?>">
    <i class="<?= htmlspecialchars($activity_icon) ?>"></i>
  </div>
  <div class="fx-activity-item__body">
    <div class="fx-activity-item__title"><?= htmlspecialchars($activity_title) ?></div>
    <?php if ($activity_time): ?>
    <div class="fx-activity-item__time"><?= htmlspecialchars($activity_time) ?></div>
    <?php endif; ?>
  </div>
</div>