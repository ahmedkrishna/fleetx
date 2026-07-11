<?php
require_once 'config.php';
require_once __DIR__ . '/includes/integrations.php';
requireLogin();

$user_id = (int)$_SESSION['user_id'];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_nafath'])) {
    $national_id = trim($_POST['national_id'] ?? '');
    $mobile = $_SESSION['user_phone'] ?? '';
    if ($national_id) {
        nafathRequestVerification($national_id, $mobile);
    }
    if ($db_connected) {
        try {
            $conn->query("ALTER TABLE users ADD COLUMN sanad_limit DECIMAL(12,2) DEFAULT 0.00");
        } catch (Throwable $e) { /* column exists */ }
        $stmt = $conn->prepare("UPDATE users SET nafath_verified = 1, sanad_limit = GREATEST(COALESCE(sanad_limit, 0), 500000) WHERE id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $_SESSION['sanad_limit'] = max(floatval($_SESSION['sanad_limit'] ?? 0), 500000);
        
        if (getUserRole() === 'seller') {
            $cstmt = $conn->prepare("UPDATE seller_companies SET is_verified = 1 WHERE user_id = ?");
            $cstmt->bind_param('i', $user_id);
            $cstmt->execute();
        }
    }
    $_SESSION['nafath_verified'] = 1;
    $success = true;
}

$is_verified = $_SESSION['nafath_verified'] ?? 0;
if ($db_connected && !$is_verified) {
    $vstmt = $conn->prepare("SELECT nafath_verified FROM users WHERE id = ?");
    $vstmt->bind_param('i', $user_id);
    $vstmt->execute();
    $vres = $vstmt->get_result();
    if ($row = $vres->fetch_assoc()) {
        $is_verified = $row['nafath_verified'];
        $_SESSION['nafath_verified'] = $is_verified;
    }
}

$nafath_number = rand(10, 99);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>التوثيق الوطني (نفاذ) | FleetX</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="<?= fleetx_css_href() ?>">
</head>
<body class="fx-verify-page">

<header class="fx-flow-topbar">
  <a href="/index.php" class="fx-flow-topbar__logo"><?php $fx_logo_bg = 'light'; $fx_logo_link = ''; include 'includes/fx-logo.inc.php'; ?></a>
  <a href="/index.php" class="fx-flow-topbar__link"><i class="ph ph-house"></i> الرئيسية</a>
</header>

<div class="fx-nafath-card">
  <div class="fx-nafath-logo">
    <i class="ph-fill ph-fingerprint" style="font-size: 56px; color: var(--primary); position: relative; z-index: 2;"></i>
  </div>
  
  <?php if ($success || $is_verified): ?>
    <div class="fx-verify-success">
      <i class="ph-fill ph-check-circle" style="font-size: 28px;"></i>
      تم التحقق من هويتك بنجاح
    </div>
    <h3 class="fx-nafath-title">هويتك موثقة بالكامل</h3>
    <p class="fx-nafath-desc">تم ربط حسابك ببيانات النفاذ الوطني الموحد بنجاح. يمكنك الآن استخدام كافة خدمات المنصة بموثوقية عالية.</p>
    <a href="/index.php" class="btn btn-primary fx-btn-block fx-btn-round">العودة للرئيسية</a>
  <?php else: ?>
    <h3 class="fx-nafath-title">التحقق من نفاذ</h3>
    <p class="fx-nafath-desc">الرجاء فتح تطبيق نفاذ من هاتفك المحمول وتأكيد الطلب باختيار الرقم التالي:</p>
    
    <div class="fx-nafath-number"><?= $nafath_number ?></div>
    
    <form method="POST">
      <input type="hidden" name="verify_nafath" value="1">
      <button type="submit" class="btn btn-primary fx-btn-block fx-btn-round fx-btn-lg">
        (محاكاة) تم قبول الطلب في التطبيق
      </button>
    </form>
    <div class="fx-sanad-cancel">
      <a href="/index.php">إلغاء والعودة</a>
    </div>
  <?php endif; ?>
</div>

</body>
</html>