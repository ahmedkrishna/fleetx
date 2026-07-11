<?php
/**
 * Unified dashboard empty state.
 * Vars: $empty_icon, $empty_variant, $empty_title, $empty_desc,
 *       $empty_cta_href, $empty_cta_label
 */
$empty_icon = $empty_icon ?? 'ph-fill ph-inbox';
$empty_variant = $empty_variant ?? 'info';
$empty_title = $empty_title ?? 'لا توجد بيانات';
$empty_desc = $empty_desc ?? '';
?>
<div class="fx-empty-state-panel fx-empty-state-panel--<?= htmlspecialchars($empty_variant) ?>">
  <div class="fx-empty-state-panel__icon">
    <i class="<?= htmlspecialchars($empty_icon) ?>"></i>
  </div>
  <h3><?= htmlspecialchars($empty_title) ?></h3>
  <?php if ($empty_desc): ?>
  <p><?= htmlspecialchars($empty_desc) ?></p>
  <?php endif; ?>
  <?php if (!empty($empty_cta_href) && !empty($empty_cta_label)): ?>
  <a href="<?= htmlspecialchars($empty_cta_href) ?>" class="btn btn-primary btn--pill fx-empty-state-panel__cta"><?= htmlspecialchars($empty_cta_label) ?></a>
  <?php endif; ?>
</div>