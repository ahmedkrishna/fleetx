<?php
/** Reusable optional service bundles (checkout, vehicle details, live auction). */
$fx_bundle_context = $fx_bundle_context ?? 'card';
$fx_bundle_compact = !empty($fx_bundle_compact);
?>
<div class="fx-service-bundles <?= $fx_bundle_compact ? 'fx-service-bundles--compact' : '' ?>" data-context="<?= htmlspecialchars($fx_bundle_context) ?>">
  <h4 class="fx-service-bundles__title"><i class="ph ph-package"></i> خدمات إضافية اختيارية</h4>
  <div class="fx-service-bundles__list">
    <label class="fx-service-bundle">
      <input type="checkbox" name="extra_transfer" value="1500" class="extra-service-cb">
      <span class="fx-service-bundle__body">
        <strong>نقل ملكية وتأمين أساسي</strong>
        <small>إنهاء إجراءات النقل خلال 5 أيام عمل</small>
      </span>
      <span class="fx-service-bundle__price">+1,500 ر.س</span>
    </label>
    <label class="fx-service-bundle">
      <input type="checkbox" name="extra_delivery" value="500" class="extra-service-cb">
      <span class="fx-service-bundle__body">
        <strong>توصيل لباب بيتك</strong>
        <small>داخل المدينة خلال 48 ساعة</small>
      </span>
      <span class="fx-service-bundle__price">+500 ر.س</span>
    </label>
    <label class="fx-service-bundle">
      <input type="checkbox" name="extra_gold" value="3000" class="extra-service-cb">
      <span class="fx-service-bundle__body">
        <strong>باقة الذهبية</strong>
        <small>ضمان ممتد + صيانة + تلميع</small>
      </span>
      <span class="fx-service-bundle__price">+3,000 ر.س</span>
    </label>
  </div>
</div>