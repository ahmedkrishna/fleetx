<?php
require_once '../config.php';
requireLogin();
if (getUserRole() !== 'admin') {
    header('Location: ' . getDashboardUrl());
    exit;
}

$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db_connected) {
    $keys = [
        'inspection_fee', 'platform_fee_percent', 'buyer_pro_price', 'anti_snipe_seconds',
        'sms_enabled', 'whatsapp_enabled', 'email_enabled',
        'sms_api_url', 'sms_api_token', 'sms_sender_name',
        'whatsapp_api_url', 'whatsapp_api_token', 'whatsapp_template_name', 'whatsapp_template_lang',
        'whatsapp_optin_url', 'whatsapp_optout_url',
    ];
    foreach ($keys as $key) {
        if (!isset($_POST[$key])) continue;
        $val = trim($_POST[$key]);
        if (in_array($key, ['whatsapp_api_token', 'sms_api_token'], true) && $val === '') continue;
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
<?php include __DIR__ . '/mobile-chrome.inc.php'; ?>
  <div class="admin-topbar">
    <div style="display:flex;align-items:center;gap:var(--space-4)">
      <button type="button" id="sidebar-toggle" class="btn btn-secondary btn-sm admin-sidebar-toggle" aria-label="فتح القائمة" aria-expanded="false" aria-controls="admin-sidebar"><i class="fas fa-bars"></i></button>
      <h2 class="admin-page-title">إعدادات المنصة</h2>
    </div>
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
      <div class="admin-card" style="margin:20px 0;padding:16px;background:#f8fafc;border-radius:8px;">
        <h4 style="margin:0 0 12px;">إعدادات SMS (Taqnyat)</h4>
        <p style="font-size:13px;color:#64748b;margin:0 0 16px;">Bearer token واسم المرسل المعتمد من <a href="https://portal.taqnyat.sa" target="_blank" rel="noopener">portal.taqnyat.sa</a>.</p>
        <div class="form-group" style="margin-bottom:16px;">
          <label>رابط API (اختياري)</label>
          <input type="url" name="sms_api_url" class="form-control" placeholder="https://api.taqnyat.sa/v1/messages" value="<?= htmlspecialchars($settings['sms_api_url'] ?? '') ?>">
        </div>
        <div class="form-group" style="margin-bottom:16px;">
          <label>Bearer Token</label>
          <input type="password" name="sms_api_token" class="form-control" placeholder="<?= !empty($settings['sms_api_token']) ? '•••• محفوظ — اتركه فارغاً للإبقاء' : 'الصق التوكن هنا' ?>" value="">
          <?php if (!empty($settings['sms_api_token'])): ?>
          <small style="color:#16a34a;">✓ التوكن محفوظ (<?= strlen($settings['sms_api_token']) ?> حرف)</small>
          <?php endif; ?>
        </div>
        <div class="form-group" style="margin-bottom:0;">
          <label>اسم المرسل (Sender)</label>
          <input type="text" name="sms_sender_name" class="form-control" placeholder="FleetX" value="<?= htmlspecialchars($settings['sms_sender_name'] ?? '') ?>">
        </div>
      </div>
      <div class="form-group" style="margin-bottom:16px;">
        <label>تفعيل WhatsApp (1/0)</label>
        <input type="number" name="whatsapp_enabled" class="form-control" value="<?= htmlspecialchars($settings['whatsapp_enabled'] ?? '1') ?>">
      </div>
      <div class="admin-card" style="margin:20px 0;padding:16px;background:#f8fafc;border-radius:8px;">
        <h4 style="margin:0 0 12px;">إعدادات WhatsApp (Taqnyat)</h4>
        <p style="font-size:13px;color:#64748b;margin:0 0 16px;">Bearer token من <a href="https://portal.taqnyat.sa" target="_blank" rel="noopener">portal.taqnyat.sa</a> → Developer → Applications. القالب يجب أن يكون معتمداً (مثال: نص واحد {{1}}).</p>
        <div class="form-group" style="margin-bottom:16px;">
          <label>رابط API (اختياري)</label>
          <input type="url" name="whatsapp_api_url" class="form-control" placeholder="https://api.taqnyat.sa/wa/v2/messages/" value="<?= htmlspecialchars($settings['whatsapp_api_url'] ?? '') ?>">
        </div>
        <div class="form-group" style="margin-bottom:16px;">
          <label>Bearer Token</label>
          <input type="password" name="whatsapp_api_token" class="form-control" placeholder="<?= !empty($settings['whatsapp_api_token']) ? '•••• محفوظ — اتركه فارغاً للإبقاء' : 'الصق التوكن هنا' ?>" value="">
          <?php if (!empty($settings['whatsapp_api_token'])): ?>
          <small style="color:#16a34a;">✓ التوكن محفوظ (<?= strlen($settings['whatsapp_api_token']) ?> حرف)</small>
          <?php endif; ?>
        </div>
        <div class="form-group" style="margin-bottom:16px;">
          <label>اسم القالب (Template)</label>
          <input type="text" name="whatsapp_template_name" class="form-control" placeholder="fleetx_notify" value="<?= htmlspecialchars($settings['whatsapp_template_name'] ?? '') ?>">
        </div>
        <div class="form-group" style="margin-bottom:16px;">
          <label>لغة القالب</label>
          <input type="text" name="whatsapp_template_lang" class="form-control" placeholder="ar" value="<?= htmlspecialchars($settings['whatsapp_template_lang'] ?? 'ar') ?>">
        </div>
        <div class="form-group" style="margin-bottom:16px;">
          <label>رابط Opt-In (اختياري)</label>
          <input type="url" name="whatsapp_optin_url" class="form-control" placeholder="https://api.taqnyat.sa/wa/v2/contacts/optin/" value="<?= htmlspecialchars($settings['whatsapp_optin_url'] ?? '') ?>">
        </div>
        <div class="form-group" style="margin-bottom:0;">
          <label>رابط Opt-Out (اختياري)</label>
          <input type="url" name="whatsapp_optout_url" class="form-control" placeholder="https://api.taqnyat.sa/wa/v2/contacts/optout/" value="<?= htmlspecialchars($settings['whatsapp_optout_url'] ?? '') ?>">
        </div>
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