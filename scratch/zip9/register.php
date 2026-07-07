<?php
require_once 'config.php';

if (isLoggedIn()) {
    header("Location: /index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $mobile    = trim($_POST['mobile'] ?? '');
    $password  = trim($_POST['password'] ?? '');
    $role      = in_array($_POST['role'] ?? '', ['buyer','seller']) ? $_POST['role'] : 'buyer';

    if ($db_connected && $full_name && $mobile && $password) {
        // Check if mobile already exists
        $check = $conn->prepare("SELECT id FROM users WHERE mobile = ? LIMIT 1");
        $check->bind_param('s', $mobile);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $error = 'رقم الجوال مسجل مسبقاً، يرجى تسجيل الدخول.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (full_name, mobile, password_hash, role) VALUES (?,?,?,?)");
            $stmt->bind_param('ssss', $full_name, $mobile, $hash, $role);
            if ($stmt->execute()) {
                $_SESSION['user_id']   = $conn->insert_id;
                $_SESSION['user_name'] = $full_name;
                $_SESSION['role']      = $role;
                header("Location: /" . ($role === 'seller' ? 'seller.php' : 'buyer.php'));
                exit;
            } else {
                $error = 'حدث خطأ في إنشاء الحساب، يرجى المحاولة لاحقاً.';
            }
        }
    } elseif (!$db_connected) {
        // Demo mode fallback
        $_SESSION['user_id']   = rand(10, 100);
        $_SESSION['user_name'] = $full_name ?: 'مستخدم جديد';
        $_SESSION['role']      = $role;
        header("Location: /" . ($role === 'seller' ? 'seller.php' : 'buyer.php'));
        exit;
    } else {
        $error = 'يرجى تعبئة جميع الحقول المطلوبة.';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>إنشاء حساب جديد | FleetX</title>
  <link rel="stylesheet" href="/assets/css/fleetx.css">
  <style>
    .role-card {
      border: 1.5px solid var(--border-light);
      border-radius: var(--radius-md);
      padding: 24px 16px;
      text-align: center;
      cursor: pointer;
      transition: var(--transition);
      background: var(--bg-white);
      display: block;
    }
    .role-card:hover {
      border-color: var(--primary);
      background: rgba(15, 180, 139, 0.01);
    }
    .role-card.active {
      border-color: var(--primary);
      background: rgba(15, 180, 139, 0.05);
      box-shadow: 0 0 0 4px rgba(15, 180, 139, 0.1);
    }
    .step-indicator {
      display: flex;
      justify-content: center;
      gap: 12px;
      margin-bottom: 32px;
    }
    .step-dot {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      background: var(--border-light);
      transition: var(--transition);
    }
    .step-dot.active {
      background: var(--primary);
      transform: scale(1.3);
    }
    .step-dot.completed {
      background: var(--primary-emerald);
    }
    .auth-bg-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, rgba(7,11,19,0.95) 0%, rgba(5,8,14,0.97) 100%);
      z-index: 2;
    }
    .auth-bg-img {
      position: absolute;
      inset: 0;
      background: url('https://images.unsplash.com/photo-1502877338535-766e1452684a?w=1000&q=80') center/cover no-repeat;
      opacity: 0.15;
      z-index: 1;
    }
  </style>
</head>
<body>

<div class="auth-layout">
  
  <!-- Right Column: Multi-Step Registration Form -->
  <div class="auth-right" style="position: relative; overflow-y: auto;">
    <a href="/index.php" style="position:absolute; top:32px; right:32px; display:inline-flex; align-items:center; gap:8px; font-size:14px; color:var(--text-muted); font-weight:700">
      <span>→</span> العودة للرئيسية
    </a>

    <div class="auth-form-container">
      <div style="text-align:center; margin-bottom:28px">
        <div class="navbar-logo" style="font-size:32px; display:inline-block; margin-bottom:8px; color:var(--text-dark)">
          <span>Fleet</span><span class="logo-x">X</span>
        </div>
        <h1 style="font-size:22px; font-weight:900; color:var(--text-dark)">إنشاء حساب جديد</h1>
      </div>

      <?php if (!empty($error)): ?>
        <div style="background: rgba(244, 63, 94, 0.1); border: 1px solid var(--danger); color: var(--danger); padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 24px; font-weight: 700; font-size: 14px;">
          <?= sanitize($error) ?>
        </div>
      <?php endif; ?>

      <!-- Step dots -->
      <div class="step-indicator" id="stepIndicator">
        <div class="step-dot active" data-step="1"></div>
        <div class="step-dot" data-step="2"></div>
        <div class="step-dot" data-step="3"></div>
      </div>

      <form id="registerForm" method="POST" action="">
        
        <!-- STEP 1: Select Role -->
        <div id="step1">
          <h2 style="font-size:16px; font-weight:800; margin-bottom:20px; text-align:center; color:var(--text-dark)">اختر نوع الحساب المناسب لك:</h2>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:28px">
            <label class="role-card active" onclick="selectRole('buyer', this)">
              <input type="radio" name="role" value="buyer" checked style="display:none">
              <div style="font-size:32px; margin-bottom:12px"><i class="ph ph-shopping-bag"></i></div>
              <div style="font-size:16px; font-weight:800; color:var(--text-dark)">حساب مشتري</div>
              <div style="font-size:11px; color:var(--text-muted); margin-top:4px">شراء ومزايدة سيارات</div>
            </label>
            <label class="role-card" onclick="selectRole('seller', this)">
              <input type="radio" name="role" value="seller" style="display:none">
              <div style="font-size:32px; margin-bottom:12px"><i class="ph ph-buildings"></i></div>
              <div style="font-size:16px; font-weight:800; color:var(--text-dark)">حساب بائع</div>
              <div style="font-size:11px; color:var(--text-muted); margin-top:4px">شركات ووكالات السيارات</div>
            </label>
          </div>
          <button type="button" class="btn btn-primary" style="width:100%" onclick="nextStep(2)">التالي ومتابعة التسجيل</button>
        </div>

        <!-- STEP 2: General Form Inputs -->
        <div id="step2" style="display:none">
          <h2 style="font-size:16px; font-weight:800; margin-bottom:20px; text-align:center; color:var(--text-dark)">إدخال بيانات الحساب الأساسية</h2>
          
          <div class="form-group">
            <label class="form-label">الاسم الكامل</label>
            <input type="text" name="full_name" class="form-input" placeholder="مثال: محمد عبدالرحمن العتيبي" required>
          </div>
          
          <div class="form-group">
            <label class="form-label">رقم الجوال</label>
            <input type="text" name="mobile" class="form-input font-en" placeholder="05X XXX XXXX" required style="direction:ltr; text-align:right">
          </div>
          
          <div class="form-group">
            <label class="form-label">رقم الهوية الوطنية / الإقامة</label>
            <input type="text" name="national_id" class="form-input font-en" placeholder="10XXXXXXXX" required style="direction:ltr; text-align:right">
          </div>
          
          <div class="form-group">
            <label class="form-label">كلمة المرور</label>
            <input type="password" name="password" class="form-input" placeholder="أدخل كلمة المرور" required>
          </div>

          <!-- Extra Fields (Conditional display if Seller chosen) -->
          <div id="sellerFields" style="display:none">
            <div class="form-group">
              <label class="form-label">اسم الشركة الرسمي</label>
              <input type="text" name="company_name" class="form-input" placeholder="مثال: شركة الوفاق لتأجير السيارات">
            </div>
            <div class="form-group">
              <label class="form-label">رقم السجل التجاري</label>
              <input type="text" name="cr_number" class="form-input font-en" placeholder="1010XXXXXX" style="direction:ltr; text-align:right">
            </div>
          </div>

          <div style="display:flex; gap:12px; margin-top:28px">
            <button type="button" class="btn btn-outline-dark" style="flex:1" onclick="nextStep(1)">رجوع</button>
            <button type="button" class="btn btn-primary" style="flex:2" onclick="nextStep(3)">متابعة للتوثيق ←</button>
          </div>
        </div>

        <!-- STEP 3: Nafath Identity Verification -->
        <div id="step3" style="display:none">
          <h2 style="font-size:16px; font-weight:800; margin-bottom:20px; text-align:center; color:var(--text-dark)">توثيق الهوية عبر نفاذ الوطني</h2>
          
          <div style="text-align:center; padding:32px 24px; border:1.5px dashed var(--border-light); border-radius:var(--radius-lg); margin-bottom:24px; background:var(--bg-light)">
            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/c/cd/Nafath_Logo.svg/1024px-Nafath_Logo.svg.png" alt="Nafath Logo" style="height:36px; margin-bottom:16px; filter:grayscale(1) contrast(0.5)">
            <p style="font-size:13px; color:var(--text-muted); line-height:1.7; margin-bottom:24px">
              لتوفير بيئة مزايدات آمنة ومحمية، يتطلب توثيق هويتك بشكل رسمي عبر منصة النفاذ الوطني الموحد (نفاذ).
            </p>
            <button type="button" class="btn btn-dark" style="width:100%; font-size:14px" onclick="simulateNafath()">
              <i class="ph ph-lightning ph-space-left"></i> توثيق الهوية عبر نفاذ (مُحاكى)
            </button>
          </div>
          
          <div id="nafathSuccess" style="display:none; text-align:center; padding:12px; background:var(--success-pale); border:1px solid var(--success); color:var(--success); border-radius:var(--radius-sm); margin-bottom:24px; font-weight:800; font-size:14px">
            <i class="ph ph-check-circle ph-space-left" style="color: inherit"></i> تم توثيق الهوية الوطنية بنجاح عبر نفاذ
          </div>

          <div style="display:flex; gap:12px">
            <button type="button" class="btn btn-outline-dark" style="flex:1" onclick="nextStep(2)">رجوع</button>
            <button type="submit" class="btn btn-primary" id="submitBtn" style="flex:2" disabled>إتمام تسجيل الحساب</button>
          </div>
        </div>

      </form>

      <!-- Social Registration -->
      <div style="text-align:center; margin:24px 0; position:relative">
        <hr style="border:none; border-top:1px solid var(--border-light)">
        <span style="background:var(--bg-white); padding:0 12px; color:var(--text-muted); font-size:13px; position:absolute; top:-10px; left:50%; transform:translateX(-50%)">أو التسجيل عبر</span>
      </div>

      <button type="button" class="btn" style="width:100%; margin-bottom:16px; padding:14px; font-weight:700; border-radius:var(--radius-md); display:flex; align-items:center; justify-content:center; gap:12px; background:#fff; border:1px solid #e2e8f0; color:#334155; box-shadow:0 1px 2px rgba(0,0,0,0.05); transition:all 0.3s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#fff'">
        <img src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" alt="Google" style="width:20px; height:20px;">
        التسجيل باستخدام Google
      </button>

      <div style="text-align:center; margin-top:24px; font-size:14px; color:var(--text-muted)">
        لديك حساب بالفعل؟ <a href="/login.php" style="color:var(--primary); font-weight:800">تسجيل الدخول</a>
      </div>
    </div>
  </div>

  <!-- Left Column: Trust Graphics -->
  <div class="auth-left">
    <div class="auth-bg-img"></div>
    <div class="auth-bg-overlay"></div>
    
    <div style="position:relative; z-index:10; color:#fff; max-width:480px; text-align:center">
      <div style="font-size:54px; margin-bottom:20px"><i class="ph ph-shield-check"></i></div>
      <h2 style="font-size:30px; font-weight:900; margin-bottom:16px; color:#fff">بيئة مزايدة آمنة ومحمية</h2>
      <p style="font-size:15px; color:var(--text-light-muted); line-height:1.8; margin-bottom:36px">
        نظام توثيق صارم يضمن موثوقية جميع المشترين والبائعين على المنصة. تقارير فحص تقنية شاملة، وحفظ أموال التأمين بشكل مستقل وآمن.
      </p>
    </div>
  </div>

</div>

<script src="/assets/js/fleetx.js"></script>
<script>
  function selectRole(role, el) {
    document.querySelectorAll('.role-card').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('sellerFields').style.display = role === 'seller' ? 'block' : 'none';
  }

  function nextStep(step) {
    document.getElementById('step1').style.display = 'none';
    document.getElementById('step2').style.display = 'none';
    document.getElementById('step3').style.display = 'none';
    
    document.getElementById('step' + step).style.display = 'block';
    
    // Update step dots classes
    const dots = document.querySelectorAll('.step-dot');
    dots.forEach((d, i) => {
      if (i < step - 1) {
        d.className = 'step-dot completed';
      } else if (i === step - 1) {
        d.className = 'step-dot active';
      } else {
        d.className = 'step-dot';
      }
    });
  }

  function simulateNafath() {
    const btn = document.querySelector('button[onclick="simulateNafath()"]');
    btn.innerHTML = '⏳ جاري الاتصال ببوابة نفاذ...';
    btn.disabled = true;
    
    setTimeout(() => {
      btn.style.display = 'none';
      document.getElementById('nafathSuccess').style.display = 'block';
      document.getElementById('submitBtn').disabled = false;
      showToast('تم توثيق هويتك الوطنية بنجاح!', 'success');
    }, 1500);
  }
</script>
</body>
</html>
