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
            if (!fleetx_user_is_active($user)) {
                $error = 'حسابك بانتظار موافقة الإدارة. ستتمكن من الدخول بعد التفعيل.';
            } else {
            $login_type = $_POST['login_type'] ?? 'trader';
            if (!fleetx_role_matches_login_type($user['role'], $login_type)) {
                $expected = $login_type === 'company' ? 'بائع (شركة)' : 'مشتري (تاجر)';
                $error = 'نوع الحساب المختار لا يطابق دور المستخدم. يرجى اختيار ' . $expected;
            } else {
            $_SESSION['user_id']          = $user['id'];
            $_SESSION['user_name']        = $user['full_name'];
            $_SESSION['role']             = $user['role'];
            $_SESSION['user_role']        = $user['role'];
            $_SESSION['wallet_balance']   = floatval($user['wallet_balance'] ?? 0);
            $_SESSION['nafath_verified']  = (int)($user['nafath_verified'] ?? 0);
            $_SESSION['sanad_limit']      = floatval($user['sanad_limit'] ?? 0);
            $_SESSION['user_phone']       = $user['mobile'] ?? '';
            $_SESSION['user_city']        = $user['city'] ?? '';

            $redirect = fleetx_safe_redirect($_GET['redirect'] ?? null, getUserRole() === 'buyer' ? getBuyerLandingUrl() : getDashboardUrl());
            header('Location: ' . $redirect);
            exit;
            }
            }
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
  <link rel="stylesheet" href="<?= fleetx_css_href() ?>">
</head>
<body class="fx-auth-body fx-auth-body--light">

<header class="fx-auth-topbar">
  <a href="/index.php" class="fx-auth-topbar__logo">
    <?php $fx_logo_bg = 'light'; $fx_logo_link = ''; include 'includes/fx-logo.inc.php'; ?>
  </a>
  <a href="/index.php" class="fx-auth-topbar__home"><i class="ph ph-house"></i> الرئيسية</a>
</header>

<div class="fx-auth-wrap fx-auth-wrap--light">
  <div class="fx-auth-visual fx-auth-visual--light">
    <div class="fx-auth-visual-content">
      <span class="fx-home-eyebrow fx-auth-eyebrow"><i class="ph-fill ph-gavel"></i> FleetX</span>
      <h1 class="fx-auth-tagline fx-auth-tagline--light">منصة مزادات<br><span>أساطيل السيارات</span><br>الأولى في السعودية</h1>
      <p class="fx-auth-sub fx-auth-sub--light">بيع وشراء سيارات الأساطيل المستعملة بشفافية كاملة وتقارير فحص موثّقة</p>
      <div class="fx-auth-stats fx-auth-stats--light">
        <div class="fx-auth-stat"><div class="fx-auth-stat-num"><?= max(1, $auth_companies) ?>+</div><div class="fx-auth-stat-lbl">شركة معتمدة</div></div>
        <div class="fx-auth-stat"><div class="fx-auth-stat-num"><?= max(1, $auth_auctions) ?>+</div><div class="fx-auth-stat-lbl">مزاد منظم</div></div>
        <div class="fx-auth-stat"><div class="fx-auth-stat-num"><?= $auth_revenue_display ?></div><div class="fx-auth-stat-lbl">ريال حجم التداول</div></div>
      </div>
    </div>
  </div>

  <div class="fx-auth-form-panel fx-auth-form-panel--light">
    <div class="fx-auth-box fx-panel-first fx-auth-box--card">
      <h1 class="fx-auth-title">مرحباً بك</h1>
      <p class="fx-auth-subtitle">اختر نوع حسابك للمتابعة</p>

      <?php if ($error): ?>
      <div class="fx-error-box"><i class="ph ph-warning-circle"></i> <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <div class="fx-type-selector" id="typeSelector">
        <div class="fx-type-card <?= ($selected_type==='company') ? 'active' : '' ?>" onclick="selectType('company')" id="card-company">
          <span class="fx-type-icon"><i class="ph-fill ph-buildings"></i></span>
          <div class="fx-type-name">شركة</div>
          <div class="fx-type-desc">بائع / مؤجر</div>
        </div>
        <div class="fx-type-card <?= ($selected_type==='trader') ? 'active' : '' ?>" onclick="selectType('trader')" id="card-trader">
          <span class="fx-type-icon"><i class="ph-fill ph-shopping-cart"></i></span>
          <div class="fx-type-name">تاجر</div>
          <div class="fx-type-desc">مشتري / وكيل</div>
        </div>
        <div class="fx-type-card" onclick="guestBrowse()" id="card-guest">
          <span class="fx-type-icon"><i class="ph-fill ph-eye"></i></span>
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

        <div class="fx-auth-mode-toggle" style="display:flex;gap:8px;margin-bottom:16px;">
          <button type="button" class="btn btn-outline btn-sm" id="mode-password" onclick="setLoginMode('password')" style="flex:1;">كلمة المرور</button>
          <button type="button" class="btn btn-outline btn-sm" id="mode-otp" onclick="setLoginMode('otp')" style="flex:1;">رمز OTP</button>
        </div>
        <div id="otp-panel" style="display:none;">
          <button type="button" class="btn btn-secondary btn-sm" style="width:100%;margin-bottom:12px;" onclick="sendLoginOtp()">إرسال رمز التحقق</button>
          <div class="fx-form-group">
            <label class="fx-form-label">رمز OTP</label>
            <input class="fx-form-input" type="text" id="otp_code" maxlength="6" placeholder="6 أرقام" dir="ltr">
          </div>
          <button type="button" class="fx-btn-auth btn btn-primary" onclick="verifyLoginOtp()">
            <i class="ph-fill ph-shield-check"></i> تحقق ودخول
          </button>
        </div>
        <div id="password-panel">
        <button type="submit" class="fx-btn-auth btn btn-primary">
          <i class="ph-fill ph-sign-in"></i>
          <span id="submit-label">تسجيل الدخول</span>
        </button>
        </div>
      </form>

      <div class="fx-auth-divider"><span>ليس لديك حساب؟</span></div>

      <a href="/register.php" class="btn btn-outline fx-auth-signup-btn">
        إنشاء حساب جديد
      </a>

      <p class="fx-auth-guest-link">أو <a href="/index.php?guest=1">تصفح المنصة بدون تسجيل</a></p>

    </div>
  </div>
</div>

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

let loginMode = 'password';
function setLoginMode(mode) {
  loginMode = mode;
  document.getElementById('password-panel').style.display = mode === 'password' ? '' : 'none';
  document.getElementById('otp-panel').style.display = mode === 'otp' ? '' : 'none';
  document.getElementById('password').required = mode === 'password';
}
async function sendLoginOtp() {
  const mobile = document.getElementById('mobile').value.trim();
  if (!mobile) { if (typeof showToast==='function') showToast('أدخل رقم الجوال','warning'); return; }
  const res = await fetch('/api/otp.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'send', mobile, purpose:'login'}) });
  const data = await res.json();
  if (typeof showToast==='function') showToast(data.message || (data.success?'تم الإرسال':'فشل الإرسال'), data.success?'success':'error');
}
async function verifyLoginOtp() {
  const mobile = document.getElementById('mobile').value.trim();
  const otp = document.getElementById('otp_code').value.trim();
  const res = await fetch('/api/otp.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'verify', mobile, otp, purpose:'login'}) });
  const data = await res.json();
  if (data.success && data.redirect) window.location.href = data.redirect;
  else if (typeof showToast==='function') showToast(data.error || 'رمز غير صحيح', 'error');
}
</script>
<?php include 'includes/toast-snippet.inc.php'; ?>
</body>
</html>
