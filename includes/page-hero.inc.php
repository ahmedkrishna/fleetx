<?php
/**
 * Unified sub-page hero partial.
 * Set before include: $hero_title (required), $hero_bg, $hero_desc, $hero_eyebrow,
 * $hero_back_href, $hero_back_label, $hero_modifier, $hero_actions_html
 */
$hero_bg = $hero_bg ?? 'https://images.unsplash.com/photo-1508962914676-134849a727f0?w=1600&q=80';
$hero_modifier = $hero_modifier ?? '';
$hero_extra_class = $hero_extra_class ?? '';
$hero_class = 'fx-page-hero' . ($hero_modifier ? ' fx-page-hero--' . $hero_modifier : '');
if ($hero_extra_class) $hero_class .= ' ' . $hero_extra_class;
?>
<header class="<?= $hero_class ?>">
  <div class="fx-page-hero__bg" style="background-image:url('<?= htmlspecialchars($hero_bg) ?>')"></div>
  <div class="fx-page-hero__gradient"></div>
  <div class="container fx-page-hero__inner" dir="rtl">
    <div class="fx-page-hero__row">
      <div class="fx-page-hero__content">
        <?php if (!empty($hero_back_href)): ?>
          <a href="<?= htmlspecialchars($hero_back_href) ?>" class="fx-back-link"><?= htmlspecialchars($hero_back_label ?? '← العودة') ?></a>
        <?php endif; ?>
        <?php if (!empty($hero_eyebrow)): ?>
          <p class="fx-page-hero__eyebrow"><?= htmlspecialchars($hero_eyebrow) ?></p>
        <?php endif; ?>
        <?php if (!empty($hero_title_html)): ?>
          <h1 class="fx-page-hero__title"><?= $hero_title_html ?></h1>
        <?php else: ?>
          <h1 class="fx-page-hero__title"><?= htmlspecialchars($hero_title ?? '') ?></h1>
        <?php endif; ?>
        <?php if (!empty($hero_desc)): ?>
          <p class="fx-page-hero__desc"><?= htmlspecialchars($hero_desc) ?></p>
        <?php endif; ?>
      </div>
      <?php if (!empty($hero_actions_html)): ?>
        <div class="fx-page-hero__actions"><?= $hero_actions_html ?></div>
      <?php endif; ?>
    </div>
    <?php if (!empty($hero_meta_html)): ?>
      <div class="fx-page-hero__meta"><?= $hero_meta_html ?></div>
    <?php endif; ?>
    <?php if (!empty($hero_bottom_html)): ?>
      <div class="fx-page-hero__bottom"><?= $hero_bottom_html ?></div>
    <?php endif; ?>
  </div>
</header>