<?php
require_once '../config.php';
requireLogin();
if (getUserRole() !== 'admin') {
    header('Location: ' . getDashboardUrl());
    exit;
}

$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db_connected) {
    $keys = ['inspection_fee', 'platform_fee_percent', 'buyer_pro_price', 'anti_snipe_seconds', 'sms_enabled', 'whatsapp_enabled', 'email_enabled'];
    foreach ($keys as $key) {
        if (!isset($_POST[$key])) continue;
        $val = trim($_POST[$key]);
        $stmt = $conn->prepare('INSERT INTO platform_settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
        $stmt->bind_param('ss', $key, $val);
        $stmt->execute();
    }
    $success = 'تم حفظ إعدادات المنصة';
}

$settings = [];
if ($db_connected && fleetx_table_exists($conn, 'platform_settings')) {
    $res = $conn->query('SELECT setting_key, setting_value FROM platform_settings');
    if ($res) while ($r = $res->fetch_assoc()) $settings[$r['setting_key']] = $r['setting_value'];
}

$admin_page_title = 'إعدادات المنصة | FleetX';
$admin_active = 'settings';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head><?php include __DIR__ . '/head.inc.php'; ?></head>
<body class="admin-body">
<?php include __DIR__ . '/sidebar.inc.php'; ?>
<main class="admin-content">
  <div class="admin-topbar">
    <h2 class="admin-page-title">إعدادات المنصة</h2>
  </div>
  <?php if ($success): ?><div class="admin-alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>
  <div class="admin-card">
    <form method="POST">
      <div class="form-group" style="margin-bottom:16px;">
        <label>رسوم الفحص (ر.س)</label>
        <input type="number" name="inspection_fee" class="form-control" value="<?= htmlspecialchars($settings['inspection_fee'] ?? '100') ?>">
      </div>
      <div class="form-group" style="margin-bottom:16px;">
        <label>نسبة عمولة المنصة (%)</label>
        <input type="number" step="0.1" name="platform_fee_percent" class="form-control" value="<?= htmlspecialchars($settings['platform_fee_percent'] ?? '5') ?>">
      </div>
      <div class="form-group" style="margin-bottom:16px;">
        <label>سعر اشتراك المشتري الاحترافي (ر.س/سنة)</label>
        <input type="number" name="buyer_pro_price" class="form-control" value="<?= htmlspecialchars($settings['buyer_pro_price'] ?? '299') ?>">
      </div>
      <div class="form-group" style="margin-bottom:16px;">
        <label>تمديد مكافحة القنص (ثوانٍ)</label>
        <input type="number" name="anti_snipe_seconds" class="form-control" value="<?= htmlspecialchars($settings['anti_snipe_seconds'] ?? '180') ?>">
      </div>
      <div class="form-group" style="margin-bottom:16px;">
        <label>تفعيل SMS (1/0)</label>
        <input type="number" name="sms_enabled" class="form-control" value="<?= htmlspecialchars($settings['sms_enabled'] ?? '1') ?>">
      </div>
      <div class="form-group" style="margin-bottom:16px;">
        <label>تفعيل WhatsApp (1/0)</label>
        <input type="number" name="whatsapp_enabled" class="form-control" value="<?= htmlspecialchars($settings['whatsapp_enabled'] ?? '1') ?>">
      </div>
      <div class="form-group" style="margin-bottom:16px;">
        <label>تفعيل البريد الإلكتروني (1/0)</label>
        <input type="number" name="email_enabled" class="form-control" value="<?= htmlspecialchars($settings['email_enabled'] ?? '1') ?>">
      </div>
      <button type="submit" class="btn btn-primary">حفظ الإعدادات</button>
    </form>
  </div>
</main>
</body>
</html>