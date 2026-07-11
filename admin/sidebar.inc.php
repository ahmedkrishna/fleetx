<?php
$admin_active = $admin_active ?? '';
?>
<aside class="admin-sidebar" id="admin-sidebar" role="complementary" aria-label="القائمة الجانبية">
  <div class="admin-sidebar-header">
    <a href="/index.php" class="navbar-brand">
      <?php $fx_logo_bg = 'light'; $fx_logo_link = ''; $fx_logo_height = 36; include dirname(__DIR__) . '/includes/fx-logo.inc.php'; ?>
      <div class="navbar-brand-text">
        <span class="brand-ar" style="font-size:14px;color:#1E293B">FleetX</span>
        <span class="brand-en" style="font-size:10px;color:#1bc976">لوحة الإدارة</span>
      </div>
    </a>
  </div>

  <nav class="admin-nav" role="navigation" aria-label="قائمة الإدارة">
    <div class="admin-nav-section" style="color:var(--text-muted)">الرئيسية</div>
    <a href="index.php" class="admin-nav-link<?= $admin_active === 'dashboard' ? ' active' : '' ?>" id="nav-dashboard">
      <i class="fas fa-chart-line"></i> لوحة التحكم
    </a>
    <a href="auctions.php" class="admin-nav-link<?= $admin_active === 'auctions' ? ' active' : '' ?>" id="nav-auctions">
      <i class="fas fa-gavel"></i> إدارة المزادات
    </a>
    <a href="users.php" class="admin-nav-link<?= $admin_active === 'users' ? ' active' : '' ?>" id="nav-users">
      <i class="fas fa-users"></i> إدارة المستخدمين
    </a>
    <a href="inspections.php" class="admin-nav-link<?= $admin_active === 'inspections' ? ' active' : '' ?>" id="nav-inspections">
      <i class="fas fa-clipboard-check"></i> إدارة الفحوصات
    </a>
    <a href="subscriptions.php" class="admin-nav-link<?= $admin_active === 'subscriptions' ? ' active' : '' ?>" id="nav-subscriptions">
      <i class="fas fa-id-card"></i> إدارة الاشتراكات
    </a>
    <a href="approvals.php" class="admin-nav-link<?= $admin_active === 'approvals' ? ' active' : '' ?>" id="nav-approvals">
      <i class="fas fa-check-double"></i> موافقات الإعلانات
    </a>
    <a href="activity.php" class="admin-nav-link<?= $admin_active === 'activity' ? ' active' : '' ?>" id="nav-activity">
      <i class="fas fa-list-check"></i> سجل النشاط
    </a>

    <div class="admin-nav-section" style="color:var(--text-muted)">إعدادات المنصة</div>
    <a href="settings.php" class="admin-nav-link<?= $admin_active === 'settings' ? ' active' : '' ?>" id="nav-settings">
      <i class="fas fa-sliders"></i> إعدادات المنصة
    </a>
    <a href="/index.php" class="admin-nav-link">
      <i class="fas fa-arrow-right"></i> الموقع الرئيسي
    </a>
    <a href="/logout.php" class="admin-nav-link" style="color:var(--danger) !important">
      <i class="fas fa-right-from-bracket"></i> تسجيل الخروج
    </a>
  </nav>
</aside>
<div class="admin-sidebar-overlay" id="admin-sidebar-overlay" aria-hidden="true"></div>