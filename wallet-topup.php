<?php
require_once 'config.php';
requireLogin();

$page_title = 'شحن المحفظة | FleetX';
$user_id = (int)getUserId();
$cancelled = isset($_GET['cancelled']);

if ($db_connected) {
    $wst = $conn->prepare('SELECT wallet_balance FROM users WHERE id = ?');
    $wst->bind_param('i', $user_id);
    $wst->execute();
    if ($wrow = $wst->get_result()->fetch_assoc()) {
        $_SESSION['wallet_balance'] = floatval($wrow['wallet_balance'] ?? 0);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = intval($_POST['amount'] ?? 0);
    $method = $_POST['payment_method'] ?? 'mada';
    if ($amount >= 100 && $db_connected && fleetx_table_exists($conn, 'payment_intents')) {
        require_once __DIR__ . '/includes/integrations.php';
        $intent = paymentGatewayCreateWalletIntent($conn, $user_id, (float)$amount, $method);
        if ($intent && !empty($intent['redirect'])) {
            header('Location: ' . $intent['redirect']);
            exit;
        }
    }
    fleetx_set_toast('تعذّر بدء عملية الشحن. تأكد من المبلغ (100 ر.س كحد أدنى) وحاول مرة أخرى.', 'error');
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $page_title ?></title>
  <link rel="stylesheet" href="<?= fleetx_css_href() ?>">
</head>
<body class="fx-home fx-page-shell fx-page-shell--wallet">
<?php include 'includes/navbar.php'; ?>

<?php
$hero_title = 'شحن المحفظة الرقمية';
$hero_desc = 'أودع مبالغ في محفظتك عبر بوابة دفع آمنة (مدى / Visa / Apple Pay) لاستخدامها كتأمين للمزايدة.';
$hero_bg = fleetx_subpage_hero_bg('wallet-topup');
$hero_eyebrow = 'المحفظة';
$hero_back_href = '/profile.php?tab=wallet';
$hero_back_label = '← العودة لحسابي';
include 'includes/page-hero.inc.php';
?>

<div class="container fx-page-body fx-page-body--overlap fx-wallet-page">
  <?php if ($cancelled): ?>
  <div class="fx-error-box" style="margin-bottom:16px;"><i class="ph ph-info"></i> تم إلغاء عملية الشحن.</div>
  <?php endif; ?>

  <div class="fx-wallet-card">
    <div class="fx-wallet-balance">
      <div>
        <div class="fx-wallet-balance__label">الرصيد الحالي</div>
        <div class="fx-wallet-balance__amount"><?= number_format($_SESSION['wallet_balance'] ?? 0) ?> <span class="fx-wallet-balance__unit">ر.س</span></div>
      </div>
      <i class="ph-fill ph-wallet fx-wallet-balance__icon"></i>
    </div>

    <form action="" method="POST">
      <h3 class="fx-wallet-form-title">اختر باقة الشحن</h3>
      <div class="fx-wallet-packages">
        <?php foreach ([5000, 10000, 50000] as $pkg): ?>
        <div class="fx-wallet-pkg" onclick="document.getElementById('custom_amount').value=<?= $pkg ?>; document.querySelectorAll('.fx-wallet-pkg').forEach(e=>e.classList.remove('selected')); this.classList.add('selected');">
          <strong><?= number_format($pkg) ?></strong>
          <span class="fx-wallet-pkg-unit">ر.س</span>
        </div>
        <?php endforeach; ?>
      </div>
      <input type="number" name="amount" id="custom_amount" class="fx-form-input fx-wallet-input-spaced" placeholder="أو أدخل مبلغاً مخصصاً (حد أدنى 100)" min="100" step="100" required>

      <h3 class="fx-wallet-form-title" style="margin-top:20px;">طريقة الدفع</h3>
      <div class="fx-wallet-pay-methods">
        <label class="fx-wallet-pay-opt"><input type="radio" name="payment_method" value="mada" checked> <i class="ph-fill ph-credit-card"></i> مدى</label>
        <label class="fx-wallet-pay-opt"><input type="radio" name="payment_method" value="card"> <i class="ph-fill ph-credit-card"></i> Visa / Mastercard</label>
        <label class="fx-wallet-pay-opt"><input type="radio" name="payment_method" value="apple_pay"> <i class="ph-fill ph-apple-logo"></i> Apple Pay</label>
      </div>

      <button type="submit" class="fx-btn-auth"><i class="ph-fill ph-lock"></i> متابعة للدفع الآمن</button>
      <p class="fx-wallet-pay-note">يتم إضافة الرصيد بعد تأكيد الدفع عبر بوابة FleetX الآمنة.</p>
    </form>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>