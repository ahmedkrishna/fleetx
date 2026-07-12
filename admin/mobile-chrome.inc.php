<?php
$admin_active = $admin_active ?? '';
$fx_admin_name = sanitize($_SESSION['user_name'] ?? 'مدير FleetX');
$fx_admin_initial = mb_substr($fx_admin_name, 0, 1, 'UTF-8');
?>
  <div class="fx-dash-mobile-profile fx-dash-mobile-profile--admin">
    <div class="fx-dash-mobile-profile__avatar" aria-hidden="true"><?= htmlspecialchars($fx_admin_initial) ?></div>
    <div>
      <div class="fx-dash-mobile-profile__name"><?= $fx_admin_name ?></div>
      <div class="fx-dash-mobile-profile__meta">مدير FleetX · لوحة الإدارة</div>
    </div>
    <button type="button" id="sidebar-toggle-mobile" class="btn btn-secondary btn-sm admin-sidebar-toggle fx-admin-mobile-menu-btn" aria-label="فتح القائمة" aria-expanded="false" aria-controls="admin-sidebar">
      <i class="fas fa-bars"></i>
    </button>
  </div>
  <div class="fx-dash-mobile-nav fx-admin-mobile-nav">
    <select onchange="if(this.value) window.location.href=this.value" aria-label="قائمة لوحة الإدارة">
      <option value="">انتقل إلى قسم...</option>
      <option value="index.php" <?= $admin_active === 'dashboard' ? 'selected' : '' ?>>لوحة التحكم</option>
      <option value="auctions.php" <?= $admin_active === 'auctions' ? 'selected' : '' ?>>إدارة المزادات</option>
      <option value="users.php" <?= $admin_active === 'users' ? 'selected' : '' ?>>إدارة المستخدمين</option>
      <option value="inspections.php" <?= $admin_active === 'inspections' ? 'selected' : '' ?>>إدارة الفحوصات</option>
      <option value="subscriptions.php" <?= $admin_active === 'subscriptions' ? 'selected' : '' ?>>إدارة الاشتراكات</option>
      <option value="approvals.php" <?= $admin_active === 'approvals' ? 'selected' : '' ?>>موافقات الإعلانات</option>
      <option value="activity.php" <?= $admin_active === 'activity' ? 'selected' : '' ?>>سجل النشاط</option>
      <option value="settings.php" <?= $admin_active === 'settings' ? 'selected' : '' ?>>إعدادات المنصة</option>
    </select>
  </div>