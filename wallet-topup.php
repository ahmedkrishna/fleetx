<?php
require_once 'config.php';
requireLogin();

$page_title = 'شحن المحفظة | FleetX';
$user_id = (int)getUserId();

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
    if ($amount > 0) {
        $_SESSION['wallet_balance'] = ($_SESSION['wallet_balance'] ?? 0) + $amount;
        if ($db_connected) {
            $stmt = $conn->prepare('UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?');
            $stmt->bind_param('di', $amount, $user_id);
            $stmt->execute();
            createNotification($conn, $user_id, 'payment', 'تم شحن المحفظة', 'تم إضافة ' . number_format($amount) . ' ر.س إلى محفظتك', '/buyer.php?section=wallet');
        }
        $back = getUserRole() === 'seller' ? '/seller.php?section=wallet&topup=1' : '/buyer.php?section=wallet&topup=1';
        header('Location: ' . $back);
        exit;
    }
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
$hero_desc = 'أودع مبالغ في محفظتك لاستخدامها كتأمين للمزايدة أو للشراء الفوري.';
$hero_bg = 'https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=1600&q=80';
$hero_eyebrow = 'المحفظة';
$hero_back_href = '/profile.php?tab=wallet';
$hero_back_label = '← العودة لحسابي';
include 'includes/page-hero.inc.php';
?>

<div class="container fx-page-body fx-page-body--overlap fx-wallet-page">

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
      <input type="number" name="amount" id="custom_amount" class="fx-form-input fx-wallet-input-spaced" placeholder="أو أدخل مبلغاً مخصصاً" min="100" step="100" required>
      <button type="submit" class="fx-btn-auth">شحن المحفظة</button>
    </form>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>