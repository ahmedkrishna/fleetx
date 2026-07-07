<?php
// includes/navbar.php - FleetX Shared Navbar
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar" id="navbar">
  <div class="container">
    <div class="navbar-inner">
      
      <!-- Logo -->
      <a href="/index.php" class="navbar-logo">
        <img src="/assets/images/logo.png" alt="FleetX Logo">
      </a>

      <!-- Nav Links -->
      <ul class="navbar-links" id="navLinks">
        <li><a href="/auctions.php" class="<?= $current_page==='auctions.php' && !isset($_GET['type'])?'active':'' ?>">المزادات الحية</a></li>
        <li><a href="/auctions.php?type=instant" class="<?= (isset($_GET['type'])&&$_GET['type']==='instant')?'active':'' ?>">الشراء الفوري</a></li>

        <li class="nav-dropdown">
          <a href="#" class="<?= ($current_page==='add-instant.php'||$current_page==='add-auction.php')?'active':'' ?>">أضف إعلان <i class="ph ph-caret-down" style="font-size:12px; margin-right:4px;"></i></a>
          <div class="nav-dropdown-content">
            <a href="/add-instant.php"><i class="ph ph-lightning ph-space-left"></i> بيع فوري</a>
            <a href="/add-auction.php"><i class="ph ph-gavel ph-space-left"></i> جدولة مزاد</a>
          </div>
        </li>
        <li><a href="/seller.php" class="<?= $current_page==='seller.php'?'active':'' ?>">بيع سيارتك</a></li>
        <li><a href="/about.php" class="<?= $current_page==='about.php'?'active':'' ?>">كيف يعمل</a></li>
      </ul>

      <!-- Actions -->
      <div class="navbar-actions">
        <?php if (isLoggedIn()): ?>
          <a href="<?= getUserRole() === 'admin' ? '/admin/index.php' : '/dashboard.php' ?>" class="btn btn-outline btn-sm" style="border-radius: var(--radius-round)">
            <i class="ph ph-user ph-space-left"></i> <?= sanitize($_SESSION['user_name'] ?? 'حسابي') ?>
          </a>
          <a href="/logout.php" class="btn btn-outline btn-sm" style="opacity:0.6; margin-right:4px; border-radius: var(--radius-round); border-color: transparent">خروج</a>
        <?php else: ?>
          <a href="/login.php" class="btn-login hide-on-mobile">دخول المنصة</a>
          <a href="/register.php" class="btn btn-primary btn-sm hide-on-mobile" style="border-radius: var(--radius-round)">سجل الآن</a>
        <?php endif; ?>
        <button class="navbar-toggle" id="navToggle" aria-label="قائمة"><i class="ph ph-list" style="color: #fff; font-size: 24px;"></i></button>
      </div>

    </div>
  </div>

  <!-- Mobile Menu -->
  <div id="mobileMenu" style="display:none; background:rgba(6,12,22,0.98); border-top:1px solid rgba(255,255,255,0.08); padding:24px; position:absolute; width:100%; left:0; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
    <ul style="display:flex; flex-direction:column; gap:16px;">
      <li><a href="/auctions.php" style="display:block; color:#fff; font-weight:700; font-size:16px;">المزادات الحية</a></li>
      <li><a href="/auctions.php?type=instant" style="display:block; color:#fff; font-weight:700; font-size:16px;">الشراء الفوري</a></li>
      <li><a href="/add-instant.php" style="display:block; color:#fff; font-weight:700; font-size:16px;">أضف إعلان - بيع فوري</a></li>
      <li><a href="/add-auction.php" style="display:block; color:#fff; font-weight:700; font-size:16px;">أضف إعلان - جدولة مزاد</a></li>
      <li><a href="/seller.php" style="display:block; color:#fff; font-weight:700; font-size:16px;">بيع سيارتك</a></li>
      <li><a href="/about.php" style="display:block; color:#fff; font-weight:700; font-size:16px;">كيف يعمل</a></li>
      
      <?php if (!isLoggedIn()): ?>
        <li style="margin-top:16px; display:flex; gap:12px;">
          <a href="/login.php" class="btn btn-outline" style="flex:1; text-align:center">دخول</a>
          <a href="/register.php" class="btn btn-primary" style="flex:1; text-align:center">تسجيل جديد</a>
        </li>
      <?php endif; ?>
    </ul>
  </div>
</nav>
