<?php
require_once 'config.php';
requireLogin();

$ref = trim($_GET['ref'] ?? '');
if (!$ref || !$db_connected) {
    header('Location: /auctions.php');
    exit;
}

$stmt = $conn->prepare("SELECT pi.*, a.title, v.make, v.model, v.year FROM payment_intents pi JOIN auctions a ON pi.auction_id=a.id JOIN vehicles v ON a.vehicle_id=v.id WHERE pi.reference=? AND pi.buyer_id=? AND pi.status='pending' LIMIT 1");
$uid = (int)getUserId();
$stmt->bind_param('si', $ref, $uid);
$stmt->execute();
$intent = $stmt->get_result()->fetch_assoc();
if (!$intent) {
    fleetx_set_toast('جلسة الدفع غير صالحة أو منتهية', 'error');
    header('Location: /buyer.php?section=purchases');
    exit;
}

$title = $intent['title'] ?: ($intent['make'] . ' ' . $intent['model'] . ' ' . $intent['year']);
$method_labels = [
    'card' => 'بطاقة ائتمانية',
    'mada' => 'مدى',
    'apple_pay' => 'Apple Pay',
    'sadad' => 'سداد',
];
$method_label = $method_labels[$intent['method']] ?? $intent['method'];
?>
<!DOCTYPE html>
<html lang="<?= fleetx_html_lang() ?>" dir="<?= fleetx_html_dir() ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>بوابة الدفع | FleetX</title>
  <link rel="stylesheet" href="<?= fleetx_css_href() ?>">
</head>
<body class="fx-home fx-page-shell">
<?php include 'includes/navbar.php'; ?>
<div class="container fx-page-body" style="padding:48px 0;max-width:520px;">
  <div class="fx-checkout-box" style="text-align:center;">
    <div style="font-size:48px;margin-bottom:16px;">🔒</div>
    <h1 style="font-size:22px;font-weight:900;margin-bottom:8px;">بوابة الدفع الآمنة</h1>
    <p style="color:var(--text-muted);margin-bottom:24px;">FleetX × <?= htmlspecialchars($method_label) ?></p>
    <div class="fx-checkout-summary-card" style="text-align:right;margin-bottom:24px;">
      <div class="fx-checkout-line"><span>المركبة</span><span><?= sanitize($title) ?></span></div>
      <div class="fx-checkout-line"><span>رقم العملية</span><span class="font-en"><?= htmlspecialchars($ref) ?></span></div>
      <div class="fx-checkout-total"><span>المبلغ</span><span class="font-en"><?= number_format((float)$intent['amount']) ?> SAR</span></div>
    </div>
    <form method="POST" action="/payment-return.php">
      <input type="hidden" name="ref" value="<?= htmlspecialchars($ref) ?>">
      <input type="hidden" name="confirm" value="1">
      <button type="submit" class="btn btn-primary fx-checkout-submit" style="width:100%;">تأكيد الدفع</button>
    </form>
    <a href="/checkout.php?id=<?= (int)$intent['auction_id'] ?>&cancelled=1" style="display:block;margin-top:16px;color:var(--text-muted);">إلغاء والعودة</a>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>