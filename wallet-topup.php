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
        header('Location: /buyer.php?section=wallet&topup=1');
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
  <link rel="stylesheet" href="/assets/css/fleetx.css">
</head>
<body class="page-inner">
<?php include 'includes/navbar.php'; ?>

<div class="fx-wallet-page">
  <div style="text-align:center; margin-bottom:32px;">
    <h1 style="font-size:30px; margin-bottom:12px; color:var(--text-dark); font-weight:900;">شحن المحفظة الرقمية</h1>
    <p style="color:var(--text-muted);">أودع مبالغ في محفظتك لاستخدامها كتأمين للمزايدة أو للشراء الفوري.</p>
  </div>

  <div class="fx-wallet-card">
    <div class="fx-wallet-balance">
      <div>
        <div style="color:var(--text-muted); font-size:14px; margin-bottom:4px;">الرصيد الحالي</div>
        <div style="font-weight:900; font-size:28px; color:var(--text-dark); font-family:var(--font-en);"><?= number_format($_SESSION['wallet_balance'] ?? 0) ?> <span style="font-size:14px; font-family:var(--font-ar); color:var(--primary);">ر.س</span></div>
      </div>
      <i class="ph-fill ph-wallet" style="font-size:48px; color:var(--primary); opacity:0.5;"></i>
    </div>

    <form action="" method="POST">
      <h3 style="font-size:18px; margin-bottom:14px; color:var(--text-dark); font-weight:900;">اختر باقة الشحن</h3>
      <div class="fx-wallet-packages">
        <?php foreach ([5000, 10000, 50000] as $pkg): ?>
        <div class="fx-wallet-pkg" onclick="document.getElementById('custom_amount').value=<?= $pkg ?>; document.querySelectorAll('.fx-wallet-pkg').forEach(e=>e.classList.remove('selected')); this.classList.add('selected');">
          <strong><?= number_format($pkg) ?></strong>
          <span style="font-size:12px; color:var(--text-muted);">ر.س</span>
        </div>
        <?php endforeach; ?>
      </div>
      <input type="number" name="amount" id="custom_amount" class="fx-form-input" placeholder="أو أدخل مبلغاً مخصصاً" min="100" step="100" required style="margin-bottom:18px;">
      <button type="submit" class="fx-btn-auth">شحن المحفظة</button>
    </form>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
<script src="https://unpkg.com/@phosphor-icons/web"></script>
</body>
</html>