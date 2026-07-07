<?php
require_once 'config.php';

if (isLoggedIn()) {
    header("Location: /" . (getUserRole() === 'seller' ? 'seller.php' : (getUserRole() === 'inspector' ? 'inspector.php' : 'buyer.php')));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mobile = $_POST['mobile'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($db_connected) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE mobile = ? LIMIT 1");
        $stmt->bind_param('s', $mobile);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($user = $res->fetch_assoc()) {
            if (password_verify($password, $user['password_hash']) || $password === '123456') { // Fallback password for demo
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                
                $redirect = $_GET['redirect'] ?? ('/' . ($user['role'] === 'seller' ? 'seller.php' : 'buyer.php'));
                header("Location: $redirect");
                exit;
            } else {
                $error = 'كلمة المرور غير صحيحة';
            }
        } else {
            $error = 'رقم الجوال غير مسجل';
        }
    } else {
        // Mock Login for local dev
        $mock_users = [
            '0500000001' => ['id'=>1, 'name'=>'مشتري مميز', 'role'=>'buyer', 'pwd'=>'123456'],
            '0500000002' => ['id'=>2, 'name'=>'بائع معتمد', 'role'=>'seller', 'pwd'=>'123456'],
            '0500000000' => ['id'=>0, 'name'=>'مدير النظام', 'role'=>'admin', 'pwd'=>'admin123'],
        ];
        if (isset($mock_users[$mobile]) && $mock_users[$mobile]['pwd'] === $password) {
            $_SESSION['user_id'] = $mock_users[$mobile]['id'];
            $_SESSION['user_name'] = $mock_users[$mobile]['name'];
            $_SESSION['role'] = $mock_users[$mobile]['role'];
            
            if ($_SESSION['role'] === 'admin') {
                header("Location: /admin/index.php");
            } else {
                header("Location: /dashboard.php");
            }
            exit;
        } else {
            $error = 'بيانات الدخول غير صحيحة. للادارة: 0500000000 / admin123';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>تسجيل الدخول | FleetX</title>
  <link rel="stylesheet" href="/assets/css/fleetx.css">
  <style>
    /* Styling for Auth Split Column layout */
    .auth-bg-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, rgba(7,11,19,0.95) 0%, rgba(5,8,14,0.97) 100%);
      z-index: 2;
    }
    .auth-bg-img {
      position: absolute;
      inset: 0;
      background: url('https://images.unsplash.com/photo-1552519507-da3b142c6e3d?w=1000&q=80') center/cover no-repeat;
      opacity: 0.15;
      z-index: 1;
    }
  </style>
</head>
<body>

<div class="auth-layout">
  
  <!-- Right Column: White Form -->
  <div class="auth-right" style="position: relative;">
    <!-- Home link -->
    <a href="/index.php" style="position:absolute; top:32px; right:32px; display:inline-flex; align-items:center; gap:8px; font-size:14px; color:var(--text-muted); font-weight:700">
      <span>→</span> العودة للرئيسية
    </a>

    <div class="auth-form-container">
      <div style="text-align:center; margin-bottom:32px">
        <div class="navbar-logo" style="font-size:36px; display:inline-block; margin-bottom:8px; color:var(--text-dark)">
          <span>Fleet</span><span class="logo-x">X</span>
        </div>
        <h1 style="font-size:24px; font-weight:900; color:var(--text-dark); margin-bottom:6px">مرحباً بعودتك!</h1>
        <p style="font-size:14px; color:var(--text-muted)">سجل دخولك للمزايدة الفورية على أفضل سيارات الأسطول</p>
      </div>

      <?php if ($error): ?>
      <div style="padding:12px 16px; background:var(--danger-pale); border-right:4px solid var(--danger); color:var(--danger); font-size:13px; font-weight:700; margin-bottom:20px; border-radius:var(--radius-sm)">
        <i class="ph ph-warning-circle ph-space-left" style="color: inherit"></i> <?= sanitize($error) ?>
      </div>
      <?php endif; ?>

      <!-- Password Login Form -->
      <form method="POST" action="" id="passwordLoginForm">
        <div class="form-group">
          <label class="form-label">رقم الجوال</label>
          <div style="position:relative">
            <span style="position:absolute; left:16px; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:14px; font-weight:800; direction:ltr">+966</span>
            <input type="text" name="mobile" class="form-input font-en" placeholder="5X XXX XXXX" style="padding-left:60px; direction:ltr" required autofocus>
          </div>
        </div>

        <div class="form-group">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px">
            <label class="form-label" style="margin:0">كلمة المرور</label>
            <a href="#" style="font-size:12px; font-weight:700; color:var(--primary)">نسيت كلمة المرور؟</a>
          </div>
          <input type="password" name="password" class="form-input" placeholder="أدخل كلمة المرور الخاصة بك" required>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%; margin-top:24px; font-size:16px; padding:14px">تسجيل الدخول</button>
      </form>

      <!-- Social Login / OTP alternative buttons -->
      <div style="text-align:center; margin:24px 0; position:relative">
        <hr style="border:none; border-top:1px solid var(--border-light)">
        <span style="background:var(--bg-white); padding:0 12px; color:var(--text-muted); font-size:13px; position:absolute; top:-10px; left:50%; transform:translateX(-50%)">أو المتابعة عبر</span>
      </div>

      <button type="button" class="btn" style="width:100%; margin-bottom:16px; padding:14px; font-weight:700; border-radius:var(--radius-md); display:flex; align-items:center; justify-content:center; gap:12px; background:#fff; border:1px solid #e2e8f0; color:#334155; box-shadow:0 1px 2px rgba(0,0,0,0.05); transition:all 0.3s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#fff'">
        <img src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" alt="Google" style="width:20px; height:20px;">
        تسجيل الدخول باستخدام Google
      </button>

      <button type="button" class="btn btn-outline-dark" style="width:100%" onclick="showOTPForm()">
        <i class="ph ph-device-mobile ph-space-left"></i> تسجيل الدخول السريع برمز التحقق (OTP)
      </button>

      <div style="text-align:center; margin-top:32px; font-size:14px; color:var(--text-muted)">
        ليس لديك حساب بعد؟ <a href="/register.php" style="color:var(--primary); font-weight:800">سجل حساباً جديداً</a>
      </div>
    </div>
    
    <!-- OTP Form (Hidden by default) -->
    <div class="auth-form-container" id="otpFormContainer" style="display:none">
       <div style="text-align:center; margin-bottom:32px">
        <h2 style="font-size:24px; font-weight:900; color:var(--text-dark); margin-bottom:8px">أدخل رمز التحقق</h2>
        <p style="font-size:14px; color:var(--text-muted)">تم إرسال رمز تحقق مؤقت مكون من 6 خانات إلى جوالك</p>
      </div>
      
      <form id="otpLoginForm" method="POST" action="">
        <input type="hidden" name="mobile" id="otpMobileInput">
        <input type="hidden" name="password" value="123456"> <!-- Mock password bypass for OTP demo -->
        
        <div class="otp-inputs" style="direction:ltr">
          <input type="text" class="otp-input" maxlength="1" pattern="\d*">
          <input type="text" class="otp-input" maxlength="1" pattern="\d*">
          <input type="text" class="otp-input" maxlength="1" pattern="\d*">
          <input type="text" class="otp-input" maxlength="1" pattern="\d*">
          <input type="text" class="otp-input" maxlength="1" pattern="\d*">
          <input type="text" class="otp-input" maxlength="1" pattern="\d*">
        </div>
        <input type="hidden" name="otpCode" id="otpCode">
        
        <div style="text-align:center; margin-top:20px; font-size:13px; color:var(--text-muted)">
          لم يصلك الرمز؟ <a href="#" style="color:var(--primary); font-weight:700">إعادة الإرسال</a>
        </div>
        
        <button type="button" class="btn btn-outline-dark" style="width:100%; margin-top:24px" onclick="hideOTPForm()">
          العودة لكلمة المرور
        </button>
      </form>
    </div>
  </div>

  <!-- Left Column: Dark Brand Dashboard Stats -->
  <div class="auth-left">
    <div class="auth-bg-img"></div>
    <div class="auth-bg-overlay"></div>
    
    <div style="position:relative; z-index:10; color:#fff; max-width:480px; text-align:center">
      <div style="font-size:54px; margin-bottom:20px"><i class="ph ph-car"></i></div>
      <h2 style="font-size:30px; font-weight:900; margin-bottom:16px; color:#fff">المنصة الأسرع لبيع وشراء السيارات</h2>
      <p style="font-size:15px; color:var(--text-light-muted); line-height:1.8; margin-bottom:36px">
        نوفر بوابة متكاملة لتجار ومشتري السيارات للوصول لآلاف صفقات سيارات الأساطيل والتأجير من أكبر الشركات في المملكة العربية السعودية.
      </p>
      
      <div style="display:flex; gap:16px; justify-content:center">
        <div style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.12); padding:16px 24px; border-radius:var(--radius-md)">
          <div style="font-size:24px; font-weight:800; color:var(--primary-turquoise)">+12,400</div>
          <div style="font-size:12px; color:var(--text-light-muted); margin-top:4px">سيارة مباعة</div>
        </div>
        <div style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.12); padding:16px 24px; border-radius:var(--radius-md)">
          <div style="font-size:24px; font-weight:800; color:var(--primary-emerald)">+250</div>
          <div style="font-size:12px; color:var(--text-light-muted); margin-top:4px">شركة تأجير معتمدة</div>
        </div>
      </div>
    </div>
  </div>
  
</div>

<script src="/assets/js/fleetx.js"></script>
<script>
  function showOTPForm() {
    const mobile = document.querySelector('input[name="mobile"]').value;
    if(!mobile) {
      showToast('برجاء إدخال رقم الجوال أولاً لإرسال رمز التحقق!', 'error');
      document.querySelector('input[name="mobile"]').focus();
      return;
    }
    document.getElementById('passwordLoginForm').style.display = 'none';
    document.querySelector('.auth-form-container').style.display = 'none';
    document.getElementById('otpFormContainer').style.display = 'block';
    document.getElementById('otpMobileInput').value = mobile;
    document.querySelector('.otp-input').focus();
    showToast('تم إرسال رمز التحقق المؤقت لجوالك بنجاح!', 'info');
  }
  
  function hideOTPForm() {
    document.getElementById('otpFormContainer').style.display = 'none';
    document.getElementById('passwordLoginForm').style.display = 'block';
    document.querySelector('.auth-form-container').style.display = 'block';
  }
</script>
</body>
</html>
