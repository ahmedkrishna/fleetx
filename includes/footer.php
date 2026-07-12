<?php // includes/footer.php — FleetX Shared Footer ?>
<footer class="footer-xm">
  <div class="container">
    <div class="footer-grid-xm">
      <!-- Brand & About -->
      <div>
        <a href="/index.php" class="fx-footer-brand">
          <?php $fx_logo_bg = 'dark'; $fx_logo_link = ''; $fx_logo_class = 'fx-footer-logo-wrap'; $fx_logo_height = 40; include __DIR__ . '/fx-logo.inc.php'; ?>
        </a>
        <p class="fx-footer-about">
          منصة FleetX هي العلامة التجارية الرائدة لتنظيم وإدارة مزادات سيارات الأساطيل وشركات التأجير في المملكة العربية السعودية. نوفر بيئة تداول ذكية وآمنة وموثوقة بالكامل مدعومة بتقنية الذكاء الاصطناعي.
        </p>
        <div class="fx-footer-social footer-social-xm">
          <a href="#" class="fx-footer-social__link" aria-label="X"><i class="ph ph-x-logo"></i></a>
          <a href="#" class="fx-footer-social__link" aria-label="LinkedIn"><i class="ph ph-linkedin-logo"></i></a>
          <a href="#" class="fx-footer-social__link" aria-label="Facebook"><i class="ph ph-facebook-logo"></i></a>
        </div>
      </div>

      <!-- Links 1 -->
      <div>
        <h4 class="footer-title">المزادات والتداول</h4>
        <ul class="footer-links-xm">
          <li><a href="/auctions.php">جميع المزادات النشطة</a></li>
          <li><a href="/auctions.php?type=live">مزادات التنفيذ الفوري</a></li>
          <li><a href="/auctions.php?type=instant">الشراء المباشر والفوري</a></li>
          <li><a href="/about.php">كيف يعمل FleetX</a></li>
        </ul>
      </div>

      <!-- Links 2 -->
      <div>
        <h4 class="footer-title">البوابات والحسابات</h4>
        <ul class="footer-links-xm">
          <li><a href="/register.php">فتح حساب مشتري فردي</a></li>
          <li><a href="/seller.php">بوابة البائعين وشركات التأجير</a></li>
          <li><a href="/buyer.php?section=dashboard">لوحة المشتري</a></li>
          <li><a href="/login.php">تسجيل الدخول للمنصة</a></li>
          <li><a href="/add-auction.php">إضافة إعلان / مزاد</a></li>
        </ul>
      </div>

      <!-- Legal -->
      <div>
        <h4 class="footer-title">الرقابة والاعتمادات</h4>
        <ul class="footer-links-xm">
          <li><a href="#">تراخيص الهيئة العامة للنقل</a></li>
          <li><a href="/terms.php">الوثائق القانونية والشروط</a></li>
          <li><a href="/terms.php">سياسة الخصوصية والاستخدام</a></li>
          <li><a href="#">إخلاء المسؤولية القانونية</a></li>
        </ul>
        <div class="fx-footer-trust">
          <div class="fx-footer-trust__badge" title="منصة موثّقة"><i class="ph ph-shield-check"></i></div>
          <div class="fx-footer-trust__badge" title="تشفير آمن"><i class="ph ph-lock-key"></i></div>
        </div>
      </div>
    </div>

    <!-- Divider & Disclaimer -->
    <div class="fx-footer-bottom">
      <div class="fx-footer-disclaimer">
        <strong>إخلاء المسؤولية:</strong> المزايدة على السيارات والتجارة بها تتطلب مسؤولية مالية عالية. يُرجى التحقق من التقارير الفنية للسيارة وقراءة كراسة الشروط بعناية قبل دفع التأمين.
      </div>
      <div class="fx-footer-copy">
        <a href="https://www.bearand.com" target="_blank" rel="noopener" class="fx-footer-bearand">bearand</a>
        <span>&copy; <?= date('Y') ?> FleetX SA. All Rights Reserved.</span>
      </div>
    </div>
  </div>
</footer>

<!-- WhatsApp Floating Widget -->
<a href="https://wa.me/201066442622" target="_blank" rel="noopener" class="whatsapp-float reveal active" title="تواصل معنا عبر واتساب">
  <i class="ph-fill ph-whatsapp-logo"></i>
</a>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<!-- Swiper JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
  window.FX_LOGGED_IN = <?= isLoggedIn() ? 'true' : 'false' ?>;
  window.FX_LOGIN_URL = '/login.php';
  <?php if (isset($hero_bid_signs) && !empty($hero_bid_signs)): ?>
  window.FX_HERO_BIDS = <?= json_encode($hero_bid_signs, JSON_UNESCAPED_UNICODE) ?>;
  <?php endif; ?>
  window.FX_GUEST_MSG_BID = <?= json_encode(fleetx_t('guest_bid_login'), JSON_UNESCAPED_UNICODE) ?>;
  window.FX_GUEST_MSG_FAV = <?= json_encode(fleetx_t('guest_fav_login'), JSON_UNESCAPED_UNICODE) ?>;

  window.userFavorites = <?= json_encode(
      isLoggedIn() && $db_connected
          ? getUserFavoriteIds($conn, (int)$_SESSION['user_id'])
          : array_map('intval', $_SESSION['favorites'] ?? [])
  ) ?>;

  function syncFavoritesUI() {
      if (!window.userFavorites) return;
      document.querySelectorAll('.card-fav').forEach(btn => {
          const id = btn.dataset.id;
          if (id && (window.userFavorites.includes(parseInt(id, 10)) || window.userFavorites.includes(id.toString()))) {
              let icon = btn.querySelector('i');
              if (icon) {
                  icon.classList.remove('ph');
                  icon.classList.add('ph-fill');
                  icon.style.color = 'var(--danger)';
              }
          }
      });
  }
  document.addEventListener('DOMContentLoaded', syncFavoritesUI);
</script>
<script src="<?= fleetx_js_href() ?>"></script>
<?php if (!empty($_SESSION['fx_toast'])):
  $fx_toast_msg = $_SESSION['fx_toast']['message'];
  $fx_toast_type = $_SESSION['fx_toast']['type'] ?? 'success';
  unset($_SESSION['fx_toast']);
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  if (typeof showToast === 'function') {
    showToast(<?= json_encode($fx_toast_msg, JSON_UNESCAPED_UNICODE) ?>, <?= json_encode($fx_toast_type) ?>);
  }
});
</script>
<?php endif; ?>