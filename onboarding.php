<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>التسجيل | FleetX</title>
  <link rel="stylesheet" href="<?= fleetx_css_href() ?>">
</head>
<body class="fx-onboarding-body">

<header class="fx-flow-topbar">
  <a href="/index.php" class="fx-flow-topbar__logo"><img src="<?= fleetx_logo_src() ?>" alt="FleetX"></a>
  <a href="/index.php" class="fx-flow-topbar__link"><i class="ph ph-house"></i> الرئيسية</a>
</header>

<div class="onboarding-container">
  <div>
    <div class="logo-container">
      <p class="fx-onboarding-tagline fx-onboarding-tagline--center">اختر نوع حسابك للبدء في المنصة</p>
    </div>

    <div class="onboarding-grid">
      <a href="/register.php?type=company" class="onboarding-card">
        <div class="oc-icon"><i class="ph ph-buildings"></i></div>
        <h3 class="oc-title">شركة (بائع)</h3>
        <p class="oc-desc">سجل كشركة لبيع وإدراج أسطول سياراتك في المزادات وتحقيق أعلى العوائد بكل سهولة وموثوقية.</p>
        <span class="oc-btn">تسجيل كشركة</span>
      </a>

      <a href="/register.php?type=trader" class="onboarding-card">
        <div class="oc-icon"><i class="ph ph-shopping-cart"></i></div>
        <h3 class="oc-title">تاجر (مشتري)</h3>
        <p class="oc-desc">اشترك كتاجر لتصفح المزادات والمزايدة على سيارات الشركات، واستفد من خدمات نقل الملكية والتوصيل.</p>
        <span class="oc-btn">تسجيل كتاجر</span>
      </a>

      <a href="/index.php?guest=1" class="onboarding-card">
        <div class="oc-icon"><i class="ph ph-user"></i></div>
        <h3 class="oc-title">زائر</h3>
        <p class="oc-desc">تصفح المنصة والمزادات الحالية كزائر بدون صلاحيات المزايدة أو الشراء حتى تقوم بالتسجيل.</p>
        <span class="oc-btn">الدخول كزائر</span>
      </a>
    </div>
  </div>
</div>

</body>
</html>