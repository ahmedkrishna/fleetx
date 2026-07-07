<?php
require_once 'config.php';

if (isLoggedIn()) {
    header('Location: ' . getDashboardUrl());
    exit;
}

$error = '';
$selected_type = $_GET['type'] ?? ''; // 'company' | 'trader' | passed from form

$auth_companies = 0;
$auth_auctions = 0;
$auth_revenue = 0;
if ($db_connected) {
    $auth_companies = (int)($conn->query("SELECT COUNT(*) FROM seller_companies")->fetch_row()[0] ?? 0);
    $auth_auctions = (int)($conn->query("SELECT COUNT(*) FROM auctions")->fetch_row()[0] ?? 0);
    $auth_revenue = (float)($conn->query("SELECT COALESCE(SUM(current_price),0) FROM auctions WHERE status='ended'")->fetch_row()[0] ?? 0);
}
$auth_revenue_display = $auth_revenue >= 1000000
    ? number_format($auth_revenue / 1000000, 1) . 'M+'
    : number_format($auth_revenue);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_type = $_POST['login_type'] ?? 'trader';
    $mobile     = trim($_POST['mobile'] ?? '');
    $password   = trim($_POST['password'] ?? '');

    if ($db_connected && $mobile && $password) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE mobile = ? LIMIT 1");
        $stmt->bind_param('s', $mobile);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && !empty($user['password_hash']) && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id']          = $user['id'];
            $_SESSION['user_name']        = $user['full_name'];
            $_SESSION['role']             = $user['role'];
            $_SESSION['user_role']        = $user['role'];
            $_SESSION['wallet_balance']   = floatval($user['wallet_balance'] ?? 0);
            $_SESSION['nafath_verified']  = (int)($user['nafath_verified'] ?? 0);
            $_SESSION['sanad_limit']      = floatval($user['sanad_limit'] ?? 0);
            $_SESSION['user_phone']       = $user['mobile'] ?? '';
            $_SESSION['user_city']        = $user['city'] ?? '';

            $redirect = $_GET['redirect'] ?? getDashboardUrl();
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'رقم الجوال أو كلمة المرور غير صحيحة';
        }
    } elseif (!$db_connected) {
        $error = 'خطأ في الاتصال بقاعدة البيانات';
    } else {
        $error = 'يرجى إدخال رقم الجوال وكلمة المرور';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>تسجيل الدخول | FleetX</title>
  <meta name="description" content="سجّل الدخول إلى منصة FleetX لمزادات أساطيل السيارات">
  <link rel="stylesheet" href="/assets/css/fleetx.css">
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">
</head>
<body class="fx-auth-body">

<div class="fx-auth-wrap">
  <div class="fx-auth-visual">
    <div class="fx-auth-visual-content">
      <img src="/assets/images/logo.png" alt="FleetX" class="fx-auth-logo">
      <h1 class="fx-auth-tagline">منصة مزادات<br><span>أساطيل السيارات</span><br>الأولى في السعودية</h1>
      <p class="fx-auth-sub">بيع وشراء سيارات الأساطيل المستعملة<br>بشفافية كاملة وتقارير فحص موثّقة</p>
      <div class="fx-auth-stats">
        <div class="fx-auth-stat"><div class="fx-auth-stat-num"><?= max(1, $auth_companies) ?>+</div><div class="fx-auth-stat-lbl">شركة معتمدة</div></div>
        <div class="fx-auth-stat"><div class="fx-auth-stat-num"><?= max(1, $auth_auctions) ?>+</div><div class="fx-auth-stat-lbl">مزاد منظم</div></div>
        <div class="fx-auth-stat"><div class="fx-auth-stat-num"><?= $auth_revenue_display ?></div><div class="fx-auth-stat-lbl">ريال حجم التداول</div></div>
      </div>
    </div>
  </div>

  <div class="fx-auth-form-panel">
    <div class="fx-auth-box">
      <h1 class="fx-auth-title">مرحباً بك</h1>
      <p class="fx-auth-subtitle">اختر نوع حسابك للمتابعة</p>

      <?php if ($error): ?>
      <div class="fx-error-box"><i class="ph ph-warning-circle"></i> <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <div class="fx-type-selector" id="typeSelector">
        <div class="fx-type-card <?= ($selected_type==='company') ? 'active' : '' ?>" onclick="selectType('company')" id="card-company">
          <span class="fx-type-icon">🏢</span>
          <div class="fx-type-name">شركة</div>
          <div class="fx-type-desc">بائع / مؤجر</div>
        </div>
        <div class="fx-type-card <?= ($selected_type==='trader') ? 'active' : '' ?>" onclick="selectType('trader')" id="card-trader">
          <span class="fx-type-icon">🛒</span>
          <div class="fx-type-name">تاجر</div>
          <div class="fx-type-desc">مشتري / وكيل</div>
        </div>
        <div class="fx-type-card" onclick="guestBrowse()" id="card-guest">
          <span class="fx-type-icon">👁️</span>
          <div class="fx-type-name">زائر</div>
          <div class="fx-type-desc">تصفح فقط</div>
        </div>
      </div>

      <form id="loginForm" method="POST" action="">
        <input type="hidden" name="login_type" id="login_type_input" value="">

        <div class="fx-form-group">
          <label class="fx-form-label" for="mobile">رقم الجوال</label>
          <input class="fx-form-input fx-input-phone" type="tel" id="mobile" name="mobile"
                 placeholder="05XXXXXXXX" required
                 value="<?= htmlspecialchars($_POST['mobile'] ?? '') ?>">
        </div>

        <div class="fx-form-group">
          <label class="fx-form-label" for="password">كلمة المرور</label>
          <input class="fx-form-input" type="password" id="password" name="password" placeholder="••••••••" required>
        </div>

        <button type="submit" class="fx-btn-auth">
          <i class="ph-fill ph-sign-in" style="margin-left:6px;"></i>
          <span id="submit-label">تسجيل الدخول</span>
        </button>
      </form>

      <div class="fx-auth-divider"><span>ليس لديك حساب؟</span></div>

      <a href="/register.php" class="btn btn-outline" style="width:100%; justify-content:center; border-radius:14px; padding:14px; font-size:15px; box-sizing:border-box;">
        إنشاء حساب جديد
      </a>

      <p class="guest-link">أو <a href="/index.php?guest=1">تصفح المنصة بدون تسجيل</a></p>

    </div>
  </div>
</div>

<script src="https://unpkg.com/@phosphor-icons/web"></script>
<script>
function selectType(type) {
  // Update cards
  document.querySelectorAll('.fx-type-card').forEach(c => c.classList.remove('active'));
  document.getElementById('card-' + type).classList.add('active');

  // Update hidden input
  document.getElementById('login_type_input').value = type;

  // Update submit label
  const labels = { company: 'دخول كشركة (بائع)', trader: 'دخول كتاجر (مشتري)' };
  document.getElementById('submit-label').textContent = labels[type] || 'تسجيل الدخول';

  // Show form
  document.getElementById('loginForm').classList.add('visible');
}

function guestBrowse() {
  document.querySelectorAll('.fx-type-card').forEach(c => c.classList.remove('active'));
  document.getElementById('card-guest').classList.add('active');
  // Animate then redirect
  setTimeout(() => { window.location.href = '/index.php?guest=1'; }, 400);
}

// Auto-show form if type was selected (e.g. after error)
<?php if ($selected_type && $selected_type !== 'guest'): ?>
selectType('<?= htmlspecialchars($selected_type) ?>');
<?php elseif (!empty($_POST['login_type'])): ?>
selectType('<?= htmlspecialchars($_POST['login_type']) ?>');
<?php endif; ?>
</script>
</body>
</html>
