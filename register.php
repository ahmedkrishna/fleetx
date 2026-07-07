<?php
require_once 'config.php';

if (isLoggedIn()) { header("Location: /index.php"); exit; }

$error   = '';
$step    = intval($_POST['step'] ?? 1);
$success = false;

// Process final submission (step 3 → done)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_submit'])) {
    $full_name    = trim($_POST['full_name'] ?? '');
    $mobile       = trim($_POST['mobile'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $password     = trim($_POST['password'] ?? '');
    $national_id  = trim($_POST['national_id'] ?? '');
    $city         = trim($_POST['city'] ?? '');
    $role         = in_array($_POST['role'] ?? '', ['buyer','seller']) ? $_POST['role'] : 'buyer';
    $company_name = trim($_POST['company_name'] ?? '');
    $cr_number    = trim($_POST['cr_number'] ?? '');
    $fleet_size   = max(0, intval($_POST['fleet_size'] ?? 0));

    // --- Validation ---
    if (!$full_name || !$mobile || !$password) {
        $error = 'يرجى تعبئة جميع الحقول المطلوبة (الاسم، الجوال، كلمة المرور).';
        $step  = 2;
    } elseif (!preg_match('/^05\d{8}$/', $mobile)) {
        $error = 'رقم الجوال يجب أن يبدأ بـ 05 ويتكون من 10 أرقام.';
        $step  = 2;
    } elseif (mb_strlen($password) < 6) {
        $error = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل.';
        $step  = 2;
    } elseif ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'صيغة البريد الإلكتروني غير صحيحة.';
        $step  = 2;
    } elseif ($role === 'seller' && !$company_name) {
        $error = 'يرجى إدخال اسم الشركة.';
        $step  = 2;
    } elseif (!$db_connected) {
        $error = 'تعذّر الاتصال بقاعدة البيانات. يرجى المحاولة لاحقاً.';
        $step  = 2;
    } else {
        // --- Check duplicate mobile ---
        $check = $conn->prepare("SELECT id FROM users WHERE mobile = ? LIMIT 1");
        $check->bind_param('s', $mobile);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $error = 'رقم الجوال مسجل مسبقاً.';
            $step  = 2;
        }
        $check->close();

        // --- Check duplicate email ---
        if (!$error && $email) {
            $chk2 = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $chk2->bind_param('s', $email);
            $chk2->execute();
            $chk2->store_result();
            if ($chk2->num_rows > 0) {
                $error = 'البريد الإلكتروني مسجل مسبقاً.';
                $step  = 2;
            }
            $chk2->close();
        }

        // --- Insert user ---
        if (!$error) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $email_val = $email ?: null;
            $initial_sanad = ($role === 'buyer') ? 500000.00 : 0.00;
            try {
                $conn->query("ALTER TABLE users ADD COLUMN sanad_limit DECIMAL(12,2) DEFAULT 0.00");
            } catch (Throwable $e) { /* column exists */ }
            $stmt = $conn->prepare("INSERT INTO users (full_name, mobile, email, national_id, password_hash, role, city, sanad_limit) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->bind_param('sssssssd', $full_name, $mobile, $email_val, $national_id, $hash, $role, $city, $initial_sanad);
            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                $_SESSION['user_id']         = $user_id;
                $_SESSION['user_name']       = $full_name;
                $_SESSION['role']            = $role;
                $_SESSION['user_role']       = $role;
                $_SESSION['wallet_balance']  = 0;
                $_SESSION['nafath_verified'] = 0;
                $_SESSION['sanad_limit']     = $initial_sanad;
                $_SESSION['user_phone']      = $mobile;
                $_SESSION['user_city']       = $city;

                if ($role === 'seller') {
                    if (!$company_name) $company_name = $full_name . ' للتأجير';
                    if ($fleet_size <= 0) $fleet_size = 10;
                    $ins = $conn->prepare("INSERT INTO seller_companies (user_id, company_name, cr_number, fleet_size, is_verified) VALUES (?, ?, ?, ?, 0)");
                    $ins->bind_param('issi', $user_id, $company_name, $cr_number, $fleet_size);
                    $ins->execute();
                    $ins->close();
                }

                // Welcome notification
                if (function_exists('createNotification')) {
                    createNotification($conn, $user_id, 'system', 'مرحباً بك في FleetX!', 'تم إنشاء حسابك بنجاح. ابدأ بتصفح المزادات الآن.', '/companies.php');
                }

                $success = true;
            } else {
                $error = 'حدث خطأ في إنشاء الحساب.';
                $step  = 2;
            }
            $stmt->close();
        }
    }
}

$init_role = isset($_GET['type']) && $_GET['type'] === 'company' ? 'seller' : 'buyer';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>إنشاء حساب جديد | FleetX</title>
  <meta name="description" content="سجّل في FleetX وابدأ البيع والشراء في مزادات أساطيل السيارات">
  <link rel="stylesheet" href="/assets/css/fleetx.css">
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">
</head>
<body class="fx-register-body">

<div class="reg-container">
  <div class="reg-logo-wrap">
    <a href="/index.php"><img src="/assets/images/logo.png" alt="FleetX"></a>
  </div>

  <?php if ($success): ?>
  <!-- ═══════ SUCCESS ═══════ -->
  <div class="reg-card">
    <div class="success-screen">
      <div class="success-icon">🎉</div>
      <h2 class="success-title">تم إنشاء حسابك بنجاح!</h2>
      <p class="success-sub">
        <?= ($_SESSION['role'] === 'seller') ? 'مرحباً بك في FleetX. سيقوم فريقنا بمراجعة بيانات شركتك وتفعيل حسابك خلال 24 ساعة.' : 'مرحباً بك في FleetX. يمكنك الآن تصفح الشركات والمشاركة في المزادات.' ?>
      </p>
      <?php if ($_SESSION['role'] === 'seller'): ?>
      <a href="/nafath.php" class="btn-next btn-inline">توثيق الهوية (نفاذ)</a>
      <a href="/seller.php" class="btn-next btn-inline btn-outline">لوحة البائع</a>
      <?php else: ?>
      <a href="/nafath.php" class="btn-next btn-inline">توثيق الهوية (نفاذ)</a>
      <a href="/sanad.php" class="btn-next btn-inline btn-outline">إعداد سند لأمر</a>
      <?php endif; ?>
    </div>
  </div>

  <?php else: ?>
  <!-- ═══════ STEPS BAR ═══════ -->
  <div class="steps-bar" id="stepsBar">
    <?php
    $steps_info = [
      1 => 'نوع الحساب',
      2 => 'البيانات',
      3 => 'التوثيق',
    ];
    $current_step = intval($_POST['step'] ?? 1);
    foreach ($steps_info as $n => $lbl):
      $cls = ($n < $current_step) ? 'done' : (($n === $current_step) ? 'active' : 'pending');
    ?>
      <?php if ($n > 1): ?><div class="step-line <?= ($n <= $current_step) ? 'done' : '' ?>"></div><?php endif; ?>
      <div class="step-dot <?= $cls ?>" style="position:relative;">
        <?= ($n < $current_step) ? '✓' : $n ?>
        <span class="step-label"><?= $lbl ?></span>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- ═══════ FORM CARD ═══════ -->
  <div class="reg-card">

    <?php if ($error): ?>
    <div class="error-box"><i class="ph ph-warning-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="" id="regForm" enctype="multipart/form-data">
      <input type="hidden" name="step" id="step_input" value="<?= $current_step ?>">
      <input type="hidden" name="role" id="role_input" value="<?= htmlspecialchars($_POST['role'] ?? $init_role) ?>">
      <!-- Preserve data across steps -->
      <input type="hidden" name="register_submit" value="1">
      <input type="hidden" name="full_name" id="hd_name" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
      <input type="hidden" name="mobile" id="hd_mobile" value="<?= htmlspecialchars($_POST['mobile'] ?? '') ?>">
      <input type="hidden" name="email" id="hd_email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      <input type="hidden" name="password" id="hd_pass" value="<?= htmlspecialchars($_POST['password'] ?? '') ?>">
      <input type="hidden" name="national_id" id="hd_nid" value="<?= htmlspecialchars($_POST['national_id'] ?? '') ?>">
      <input type="hidden" name="city" id="hd_city" value="<?= htmlspecialchars($_POST['city'] ?? '') ?>">
      <input type="hidden" name="company_name" id="hd_company" value="<?= htmlspecialchars($_POST['company_name'] ?? '') ?>">
      <input type="hidden" name="cr_number" id="hd_cr" value="<?= htmlspecialchars($_POST['cr_number'] ?? '') ?>">
      <input type="hidden" name="fleet_size" id="hd_fleet" value="<?= htmlspecialchars($_POST['fleet_size'] ?? '') ?>">

      <!-- ══ STEP 1: Account Type ══ -->
      <div id="step1" style="display:<?= ($current_step===1)?'block':'none' ?>;">
        <h2 class="reg-card-title">نوع الحساب</h2>
        <p class="reg-card-sub">اختر نوع حسابك في المنصة</p>

        <div class="account-type-grid">
          <div class="account-type-card <?= (($_POST['role']??$init_role)==='seller')?'active':'' ?>" onclick="setRole('seller')" id="atcard-seller">
            <span class="at-icon">🏢</span>
            <div class="at-name">شركة تأجير</div>
            <div class="at-desc">للشركات ومعارض السيارات الراغبة في بيع أساطيلها</div>
            <div class="at-features">
              <div class="at-feat">إضافة وإدارة السيارات</div>
              <div class="at-feat">جدولة المزادات</div>
              <div class="at-feat">تقارير مالية مفصّلة</div>
            </div>
          </div>
          <div class="account-type-card <?= (($_POST['role']??$init_role)==='buyer')?'active':'' ?>" onclick="setRole('buyer')" id="atcard-buyer">
            <span class="at-icon">🛒</span>
            <div class="at-name">تاجر / وكيل</div>
            <div class="at-desc">للتجار والأفراد الراغبين في شراء سيارات الأساطيل</div>
            <div class="at-features">
              <div class="at-feat">المزايدة والشراء</div>
              <div class="at-feat">متابعة المزادات</div>
              <div class="at-feat">تاريخ المشتريات</div>
            </div>
          </div>
        </div>

        <button type="button" class="btn-next" onclick="goStep(2)">التالي <i class="ph ph-arrow-left"></i></button>
        <p class="have-account">لديك حساب؟ <a href="/login.php">سجّل الدخول</a></p>
      </div>

      <!-- ══ STEP 2: Personal Info ══ -->
      <div id="step2" style="display:<?= ($current_step===2)?'block':'none' ?>;">
        <h2 class="reg-card-title" id="step2-title">البيانات الشخصية</h2>
        <p class="reg-card-sub">أدخل بياناتك لإنشاء الحساب</p>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="full_name">الاسم الكامل</label>
            <input class="form-input" type="text" id="full_name" placeholder="محمد أحمد الغامدي" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label" for="mobile_inp">رقم الجوال</label>
            <input class="form-input" type="tel" id="mobile_inp" placeholder="05XXXXXXXX" pattern="05[0-9]{8}" style="direction:ltr;text-align:left;" required value="<?= htmlspecialchars($_POST['mobile'] ?? '') ?>">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="email_inp">البريد الإلكتروني <span style="font-weight:400;color:var(--text-muted);">(اختياري)</span></label>
          <input class="form-input" type="email" id="email_inp" placeholder="example@email.com" style="direction:ltr;text-align:left;" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="national_id_inp">رقم الهوية الوطنية</label>
            <input class="form-input" type="text" id="national_id_inp" placeholder="1XXXXXXXXX" style="direction:ltr;text-align:left;" value="<?= htmlspecialchars($_POST['national_id'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label" for="city_inp">المدينة</label>
            <select class="form-input form-select" id="city_inp">
              <?php foreach (['الرياض','جدة','مكة المكرمة','المدينة المنورة','الدمام','الخبر','الجبيل','ينبع','تبوك','أبها','حائل','نجران'] as $c): ?>
              <option value="<?= $c ?>" <?= (($_POST['city']??'الرياض')===$c)?'selected':'' ?>><?= $c ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="password_inp">كلمة المرور</label>
          <input class="form-input" type="password" id="password_inp" placeholder="8 أحرف على الأقل" required value="<?= htmlspecialchars($_POST['password'] ?? '') ?>">
        </div>

        <!-- Company fields (seller only) -->
        <div id="company-fields" style="display:none;">
          <div class="fx-company-hint"><i class="ph ph-buildings"></i> بيانات الشركة (مطلوبة للبائعين)</div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="company_name_inp">اسم الشركة</label>
              <input class="form-input" type="text" id="company_name_inp" placeholder="شركة الوفاق للتأجير" value="<?= htmlspecialchars($_POST['company_name'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label" for="cr_number_inp">رقم السجل التجاري</label>
              <input class="form-input" type="text" id="cr_number_inp" placeholder="1010XXXXXX" style="direction:ltr;text-align:left;" value="<?= htmlspecialchars($_POST['cr_number'] ?? '') ?>">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label" for="fleet_size_inp">حجم الأسطول (عدد السيارات)</label>
            <input class="form-input" type="number" id="fleet_size_inp" placeholder="50" min="1" style="direction:ltr;text-align:left;" value="<?= htmlspecialchars($_POST['fleet_size'] ?? '') ?>">
          </div>
        </div>

        <button type="button" class="btn-next" onclick="goStep(3)">التالي <i class="ph ph-arrow-left"></i></button>
        <button type="button" class="btn-back" onclick="goStep(1)">← الرجوع</button>
      </div>

      <!-- ══ STEP 3: Verification ══ -->
      <div id="step3" style="display:<?= ($current_step===3)?'block':'none' ?>;">
        <h2 class="reg-card-title">التوثيق</h2>
        <p class="reg-card-sub">خطوة أخيرة للتأكد من هويتك</p>

        <!-- OTP -->
        <div class="fx-otp-panel">
          <p class="fx-field-label" style="margin-bottom:4px;"><i class="ph ph-device-mobile"></i> رمز التحقق</p>
          <p style="font-size:12px; color:var(--text-muted); margin-bottom:12px;">سيُرسل رمز مكون من 4 أرقام إلى رقم جوالك (للتجربة: استخدم <strong>1234</strong>)</p>
          <div class="otp-boxes" id="otpBoxes">
            <input class="otp-box" type="text" maxlength="1" oninput="otpNext(this,0)" id="otp0">
            <input class="otp-box" type="text" maxlength="1" oninput="otpNext(this,1)" id="otp1">
            <input class="otp-box" type="text" maxlength="1" oninput="otpNext(this,2)" id="otp2">
            <input class="otp-box" type="text" maxlength="1" oninput="otpNext(this,3)" id="otp3">
          </div>
          <p class="otp-hint">لم تستلم الرمز؟ <span class="otp-resend" onclick="alert('تم إعادة إرسال الرمز (محاكاة)')">إعادة إرسال</span></p>
        </div>

        <!-- Nafath (optional) -->
        <div class="reg-nafath-box" id="nafathBox" onclick="simulateNafath()">
          <div class="reg-nafath-logo">نفاذ</div>
          <div class="reg-nafath-text">
            <h4>التحقق عبر نفاذ (اختياري)</h4>
            <p>اربط حسابك بالهوية الوطنية الرقمية للحصول على شارة الموثوقية</p>
          </div>
          <div class="reg-nafath-status" id="nafathStatus">
            <i class="ph ph-arrow-left" style="color:var(--text-muted); font-size:18px;"></i>
          </div>
        </div>

        <!-- Promissory Note (buyers only) -->
        <div id="promissorySection" style="display:none;">
          <div class="promissory-box">
            <h4>📋 سند لأمر إلكتروني</h4>
            <p>
              بموجب هذا السند، أتعهد أنا <strong id="buyer-name-ref">المشتري</strong> بالوفاء بجميع الالتزامات المالية الناشئة عن مزاياداتي على منصة FleetX. يُعدّ هذا السند وثيقة قانونية سارية المفعول وفقاً لنظام الأوراق التجارية السعودي.
            </p>
            <label class="promissory-check">
              <input type="checkbox" id="promissoryCheck" required>
              أوافق على بنود سند لأمر وأتعهد بالالتزام بها
            </label>
          </div>
        </div>

        <button type="submit" class="btn-next" id="finalSubmitBtn">
          <i class="ph-fill ph-check-circle" style="margin-left:6px;"></i> إنشاء الحساب
        </button>
        <button type="button" class="btn-back" onclick="goStep(2)">← الرجوع</button>
      </div>

    </form>
  </div>
  <?php endif; ?>
</div>

<script src="https://unpkg.com/@phosphor-icons/web"></script>
<script>
let currentRole = '<?= htmlspecialchars($_POST['role'] ?? $init_role) ?>';
let nafathVerified = false;

function setRole(role) {
  currentRole = role;
  document.getElementById('role_input').value = role;
  document.querySelectorAll('.account-type-card').forEach(c => c.classList.remove('active'));
  document.getElementById('atcard-' + (role === 'seller' ? 'seller' : 'buyer')).classList.add('active');
}

function goStep(n) {
  if (n === 2) {
    // Collect step 1 data
    document.getElementById('role_input').value = currentRole;
    // Show/hide company fields
    document.getElementById('company-fields').style.display = currentRole === 'seller' ? 'block' : 'none';
    document.getElementById('step2-title').textContent = currentRole === 'seller' ? 'بيانات الشركة' : 'البيانات الشخصية';
    // Show promissory for buyers
    document.getElementById('promissorySection').style.display = 'none';
  }
  if (n === 3) {
    // Collect step 2 data into hidden fields
    document.getElementById('hd_name').value    = document.getElementById('full_name').value;
    document.getElementById('hd_mobile').value  = document.getElementById('mobile_inp').value;
    document.getElementById('hd_email').value   = document.getElementById('email_inp').value;
    document.getElementById('hd_pass').value    = document.getElementById('password_inp').value;
    document.getElementById('hd_nid').value     = document.getElementById('national_id_inp').value;
    document.getElementById('hd_city').value    = document.getElementById('city_inp').value;
    document.getElementById('hd_company').value = document.getElementById('company_name_inp')?.value || '';
    document.getElementById('hd_cr').value      = document.getElementById('cr_number_inp')?.value || '';
    document.getElementById('hd_fleet').value   = document.getElementById('fleet_size_inp')?.value || '';

    // Validate required fields
    const name   = document.getElementById('full_name').value.trim();
    const mobile = document.getElementById('mobile_inp').value.trim();
    const pass   = document.getElementById('password_inp').value.trim();
    if (!name || !mobile || !pass) {
      alert('يرجى تعبئة الاسم ورقم الجوال وكلمة المرور');
      return;
    }
    if (pass.length < 6) {
      alert('كلمة المرور يجب أن تكون 6 أحرف على الأقل');
      return;
    }

    // Show buyer-specific sections
    if (currentRole === 'buyer') {
      document.getElementById('promissorySection').style.display = 'block';
      document.getElementById('buyer-name-ref').textContent = name;
    }
  }

  document.querySelectorAll('[id^="step"]').forEach(s => s.style.display = 'none');
  const el = document.getElementById('step' + n);
  if (el) { el.style.display = 'block'; }
  document.getElementById('step_input').value = n;

  // Update steps bar visual
  document.querySelectorAll('.step-dot').forEach((dot, i) => {
    const dotN = i + 1;
    dot.className = 'step-dot ' + (dotN < n ? 'done' : (dotN === n ? 'active' : 'pending'));
    dot.textContent = dotN < n ? '✓' : dotN;
    if (dotN < n) dot.innerHTML = '✓<span class="step-label">' + ['نوع الحساب','البيانات','التوثيق'][i] + '</span>';
    else dot.innerHTML = dotN + '<span class="step-label">' + ['نوع الحساب','البيانات','التوثيق'][i] + '</span>';
  });
  document.querySelectorAll('.step-line').forEach((line, i) => {
    line.className = 'step-line ' + (i + 2 <= n ? 'done' : '');
  });
}

// OTP input flow
function otpNext(input, idx) {
  input.value = input.value.replace(/\D/g, '');
  if (input.value && idx < 3) {
    document.getElementById('otp' + (idx + 1)).focus();
  }
  // Auto-validate when 4 digits entered
  const full = [0,1,2,3].map(i => document.getElementById('otp'+i).value).join('');
  if (full.length === 4) {
    if (full !== '1234') {
      document.querySelectorAll('.otp-box').forEach(b => b.style.borderColor = '#ef4444');
      setTimeout(() => document.querySelectorAll('.otp-box').forEach(b => b.style.borderColor = ''), 1500);
    } else {
      document.querySelectorAll('.otp-box').forEach(b => { b.style.borderColor = 'var(--primary)'; b.style.background = 'rgba(27,201,118,0.05)'; });
    }
  }
}

// Nafath simulation
function simulateNafath() {
  const box = document.getElementById('nafathBox');
  const status = document.getElementById('nafathStatus');
  box.style.opacity = '0.7';
  status.innerHTML = '<i class="ph ph-spinner" style="animation:spin 1s linear infinite; font-size:18px; color:var(--primary);"></i>';
  setTimeout(() => {
    nafathVerified = true;
    box.classList.add('verified');
    box.style.opacity = '1';
    status.innerHTML = '<i class="ph-fill ph-check-circle" style="color:var(--primary); font-size:22px;"></i>';
  }, 1800);
}

// Init
document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('company-fields').style.display = currentRole === 'seller' ? 'block' : 'none';
  <?php if ($current_step > 1): ?>
  goStep(<?= $current_step ?>);
  <?php endif; ?>
});
</script>
</body>
</html>
