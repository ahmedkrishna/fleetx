<?php
/**
 * FleetX loading splash — include immediately after <body>
 */
if (defined('FLEETX_SPLASH_RENDERED')) return;
if (!function_exists('fleetx_show_splash') || !fleetx_show_splash()) return;
define('FLEETX_SPLASH_RENDERED', true);
?>
<div id="fxSplash" class="fx-splash" role="status" aria-live="polite" aria-label="جاري تحميل FleetX">
  <div class="fx-splash__grid" aria-hidden="true"></div>
  <div class="fx-splash__glow" aria-hidden="true"></div>
  <div class="fx-splash__content">
    <div class="fx-splash__logo-wrap">
      <div class="fx-splash__ring" aria-hidden="true"></div>
      <div class="fx-splash__ring fx-splash__ring--inner" aria-hidden="true"></div>
      <?php $fx_logo_bg = 'dark'; $fx_logo_height = 56; include __DIR__ . '/fx-logo.inc.php'; ?>
    </div>
    <h1 class="fx-splash__title"><span>FleetX</span></h1>
    <p class="fx-splash__tagline">منصة مزادات أساطيل السيارات الذكية في المملكة</p>
    <div class="fx-splash__progress" aria-hidden="true">
      <span class="fx-splash__progress-bar"></span>
    </div>
    <span class="fx-splash__status">جاري التحميل</span>
  </div>
  <div class="fx-splash__cars" aria-hidden="true">
    <i class="ph-fill ph-car"></i>
    <i class="ph-fill ph-gavel"></i>
    <i class="ph-fill ph-shield-check"></i>
  </div>
</div>
<script>
(function () {
  var splash = document.getElementById('fxSplash');
  if (!splash || splash.dataset.managed) return;
  splash.dataset.managed = '1';
  document.documentElement.classList.add('fx-splash-active');

  var MIN_MS = 750;
  var MAX_MS = 4500;
  var SESSION_KEY = 'fx_splash_seen';
  var start = Date.now();
  var finished = false;

  if (sessionStorage.getItem(SESSION_KEY)) {
    splash.remove();
    document.documentElement.classList.remove('fx-splash-active');
    return;
  }

  function dismiss() {
    if (finished) return;
    finished = true;
    var wait = Math.max(0, MIN_MS - (Date.now() - start));
    setTimeout(function () {
      splash.classList.add('fx-splash--out');
      document.documentElement.classList.remove('fx-splash-active');
      sessionStorage.setItem(SESSION_KEY, '1');
      setTimeout(function () { splash.remove(); }, 520);
    }, wait);
  }

  if (document.readyState === 'complete') dismiss();
  else window.addEventListener('load', dismiss, { once: true });
  setTimeout(dismiss, MAX_MS);
})();
</script>