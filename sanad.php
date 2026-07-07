<?php
require_once 'config.php';
requireLogin();

$user_id = (int)$_SESSION['user_id'];
$success = false;

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
if (!$is_verified) {
    header("Location: /nafath.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['amount'])) {
    $amount = floatval($_POST['amount']);
    if ($amount > 0) {
        if ($db_connected) {
            try {
                $conn->query("ALTER TABLE users ADD COLUMN sanad_limit DECIMAL(12,2) DEFAULT 0.00");
            } catch (Throwable $e) { /* column exists */ }

            $stmt = $conn->prepare("UPDATE users SET sanad_limit = sanad_limit + ? WHERE id = ?");
            $stmt->bind_param('di', $amount, $user_id);
            $stmt->execute();
        }
        $_SESSION['sanad_limit'] = ($_SESSION['sanad_limit'] ?? 0) + $amount;
        $success = true;
    }
}

$current_limit = $_SESSION['sanad_limit'] ?? 0;
if ($db_connected) {
    try {
        $conn->query("ALTER TABLE users ADD COLUMN sanad_limit DECIMAL(12,2) DEFAULT 0.00");
    } catch (Throwable $e) { /* column exists */ }
    $lstmt = $conn->prepare("SELECT sanad_limit FROM users WHERE id = ?");
    $lstmt->bind_param('i', $user_id);
    $lstmt->execute();
    $lres = $lstmt->get_result();
    if ($row = $lres->fetch_assoc()) {
        $current_limit = $row['sanad_limit'] ?? 0;
        $_SESSION['sanad_limit'] = $current_limit;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>منصة نافذ | إصدار سند لأمر | FleetX</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="/assets/css/fleetx.css">
</head>
<body class="fx-sanad-page">

<div class="fx-sanad-header">
  <h2>نـــافـــذ <span>(محاكاة)</span></h2>
</div>

<div class="fx-sanad-container">
  <div class="sanad-card">
    <div class="sanad-title"><i class="ph-fill ph-file-text" style="color: var(--primary);"></i> إصدار سند لأمر (ضمان المزايدة)</div>
    
    <?php if ($success): ?>
      <div class="fx-alert-success">
        <i class="ph-fill ph-check-circle" style="font-size: 24px;"></i>
        تم إصدار السند واعتماده بنجاح. رصيد المزايدة المتاح لديك الآن هو <?= number_format($current_limit) ?> ر.س
      </div>
      <a href="/buyer.php?section=dashboard" class="btn btn-primary fx-btn-block">العودة لمنصة FleetX</a>
    <?php else: ?>
    
      <?php if ($current_limit > 0): ?>
        <div class="limit-badge">
          <i class="ph-fill ph-wallet"></i>
          الحد المالي الحالي: <?= number_format($current_limit) ?> ر.س
        </div>
      <?php endif; ?>

      <p style="color: var(--text-body); margin-bottom: 20px; font-size: 15px; line-height: 1.6;">
        للمشاركة في مزادات FleetX، يجب إصدار "سند لأمر" كضمان مالي للمزايدات. لن يتم سحب أي مبالغ، وإنما يعتبر التزاماً قانونياً بقيمة الحد الأعلى للمزايدة الخاصة بك.
      </p>

      <form method="POST" id="sanadForm">
        <input type="hidden" name="amount" id="amountInput" value="">
        
        <label class="fx-field-label">اختر سقف المزايدة (قيمة السند):</label>
        <div class="preset-grid">
          <div class="preset-btn" onclick="setAmount(50000, this)"><span>50,000</span> ر.س</div>
          <div class="preset-btn" onclick="setAmount(100000, this)"><span>100,000</span> ر.س</div>
          <div class="preset-btn" onclick="setAmount(250000, this)"><span>250,000</span> ر.س</div>
        </div>
        
        <div class="sanad-doc">
          <h4>سند لأمر</h4>
          <p>
            أتعهد أنا الموقع أدناه عبر النفاذ الوطني، بأن أدفع بموجب هذا السند لأمر شركة (FleetX للمزادات) مبلغاً وقدره (<span id="displayAmt" class="fx-amt">_____</span>) ريال سعودي، وذلك في حال رسو المزاد علي وامتناعي عن السداد.
          </p>
        </div>

        <button type="button" onclick="submitSanad()" class="btn btn-primary fx-btn-block" style="font-size: 16px; font-weight: 800;">
          اعتماد وتوقيع السند (محاكاة) <i class="ph-bold ph-signature ph-space-right"></i>
        </button>
      </form>
    <?php endif; ?>
  </div>
</div>

<script src="https://unpkg.com/@phosphor-icons/web"></script>
<script>
  function setAmount(val, el) {
      document.getElementById('amountInput').value = val;
      document.getElementById('displayAmt').innerText = val.toLocaleString();
      document.querySelectorAll('.preset-btn').forEach(btn => btn.classList.remove('active'));
      el.classList.add('active');
  }
  function submitSanad() {
      if (!document.getElementById('amountInput').value) {
          alert('يرجى اختيار قيمة السند أولاً');
          return;
      }
      document.getElementById('sanadForm').submit();
  }
</script>
</body>
</html>