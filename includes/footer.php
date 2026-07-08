<?php // includes/footer.php — FleetX Shared Footer ?>
<footer class="footer-xm">
  <div class="container">
    <div class="footer-grid-xm">
      <!-- Brand & About -->
      <div>
        <a href="/index.php" style="display:inline-block; margin-bottom:20px;">
          <img src="/assets/images/logo.png" alt="FleetX" style="height:44px; filter: brightness(0) invert(1);">
        </a>
        <p style="margin-bottom:24px; line-height:1.8; color:#fff; font-size:14px">
          منصة FleetX هي العلامة التجارية الرائدة لتنظيم وإدارة مزادات سيارات الأساطيل وشركات التأجير في المملكة العربية السعودية. نوفر بيئة تداول ذكية وآمنة وموثوقة بالكامل مدعومة بتقنية الذكاء الاصطناعي.
        </p>
        <div style="display:flex; gap:12px">
          <a href="#" style="width:40px; height:40px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:50%; display:flex; align-items:center; justify-content:center; transition:var(--transition); color:#fff; font-size:18px" onmouseover="this.style.background='var(--primary)'; this.style.borderColor='var(--primary)';" onmouseout="this.style.background='rgba(255,255,255,0.04)'; this.style.borderColor='rgba(255,255,255,0.08)';"><i class="ph ph-x-logo"></i></a>
          <a href="#" style="width:40px; height:40px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:50%; display:flex; align-items:center; justify-content:center; transition:var(--transition); color:#fff; font-size:18px" onmouseover="this.style.background='var(--primary)'; this.style.borderColor='var(--primary)';" onmouseout="this.style.background='rgba(255,255,255,0.04)'; this.style.borderColor='rgba(255,255,255,0.08)';"><i class="ph ph-linkedin-logo"></i></a>
          <a href="#" style="width:40px; height:40px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:50%; display:flex; align-items:center; justify-content:center; transition:var(--transition); color:#fff; font-size:18px" onmouseover="this.style.background='var(--primary)'; this.style.borderColor='var(--primary)';" onmouseout="this.style.background='rgba(255,255,255,0.04)'; this.style.borderColor='rgba(255,255,255,0.08)';"><i class="ph ph-facebook-logo"></i></a>
        </div>
      </div>

      <!-- Links 1 -->
      <div>
        <h4 class="footer-title">المزادات والتداول</h4>
        <ul class="footer-links-xm">
          <li><a href="/auctions.php">جميع المزادات النشطة</a></li>
          <li><a href="/auctions.php?type=live">مزادات التنفيذ الفوري</a></li>
          <li><a href="/auctions.php?type=instant">الشراء المباشر والفوري</a></li>
          <li><a href="/about.php">شروط التداول والمشاركة</a></li>
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
        <div style="margin-top: 24px; display: flex; gap: 10px;">
          <div style="width:50px; height:50px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.1); border-radius:var(--radius-sm); display:flex; align-items:center; justify-content:center; font-size:24px; color:#fff"><i class="ph ph-shield-check"></i></div>
           <div style="width:50px; height:50px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.1); border-radius:var(--radius-sm); display:flex; align-items:center; justify-content:center; font-size:24px; color:#fff"><i class="ph ph-lock-key"></i></div>
        </div>
      </div>
    </div>

    <!-- Divider & Disclaimer -->
    <div class="fx-footer-bottom">
      <div class="fx-footer-disclaimer">
        <strong>إخلاء المسؤولية:</strong> المزايدة على السيارات والتجارة بها تتطلب مسؤولية مالية عالية. يُرجى التحقق من التقارير الفنية للسيارة وقراءة كراسة الشروط بعناية قبل دفع التأمين.
      </div>
      <div class="fx-footer-copy">
        <a href="https://www.bearand.com" target="_blank" style="color:var(--primary); text-decoration:none; font-weight:800; transition:color 0.3s ease;">bearand</a>
        <span>&copy; <?= date('Y') ?> FleetX SA. All Rights Reserved.</span>
      </div>
    </div>
  </div>
</footer>

<!-- WhatsApp Floating Widget -->
<a href="https://wa.me/201066442622" target="_blank" class="whatsapp-float reveal active" title="تواصل معنا عبر واتساب">
  <i class="ph-fill ph-whatsapp-logo" style="font-size: 32px; color: #fff;"></i>
</a>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<!-- Swiper JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
  // Intersection Observer for Reveal Animations
  function reveal() {
    var reveals = document.querySelectorAll(".reveal");
    for (var i = 0; i < reveals.length; i++) {
      var windowHeight = window.innerHeight;
      var elementTop = reveals[i].getBoundingClientRect().top;
      var elementVisible = 50;
      if (elementTop < windowHeight - elementVisible) {
        reveals[i].classList.add("active");
      }
    }
  }
  window.addEventListener("scroll", reveal);
  reveal(); // Trigger on load
  
  // Inject favorites for JS consumption (DB-backed when logged in)
  window.userFavorites = <?= json_encode(
      isLoggedIn() && $db_connected
          ? getUserFavoriteIds($conn, (int)$_SESSION['user_id'])
          : array_map('intval', $_SESSION['favorites'] ?? [])
  ) ?>;
  
  // Sync favorites UI on load
  function syncFavoritesUI() {
      if (!window.userFavorites) return;
      document.querySelectorAll('.card-fav').forEach(btn => {
          const id = btn.dataset.id;
          if (id && window.userFavorites.includes(parseInt(id)) || window.userFavorites.includes(id.toString())) {
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
<script src="/assets/js/fleetx.js"></script>
