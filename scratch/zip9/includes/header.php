<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$current_page = basename($_SERVER['PHP_SELF']);
$is_logged_in = isset($_SESSION['user_name']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $page_title ?? 'FleetX - Ultimate Edition' ?></title>
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
</head>
<body>

<nav class="navbar glass" id="navbar">
  <div class="container navbar-inner">
    <a href="/index.php" style="display: flex; align-items: center; gap: 8px;">
      <i class="ph-fill ph-car-profile" style="color: var(--primary); font-size: 32px;"></i>
      <span style="font-family: var(--font-en); font-weight: 900; font-size: 24px; letter-spacing: -1px;">FleetX</span>
    </a>

    <ul class="nav-links">
      <li><a href="/index.php" class="<?= $current_page=='index.php'?'active':'' ?>">الرئيسية</a></li>
      <li><a href="/auctions.php" class="<?= $current_page=='auctions.php'?'active':'' ?>">استكشف السيارات</a></li>
      <li><a href="/add-auction.php" class="<?= $current_page=='add-auction.php'?'active':'' ?>">أضف إعلان</a></li>
      <?php if($is_logged_in && ($_SESSION['role'] ?? '') == 'seller'): ?>
      <li><a href="/dashboard.php" class="<?= $current_page=='dashboard.php'?'active':'' ?>">لوحة الشركات</a></li>
      <?php endif; ?>
    </ul>

    <div style="display: flex; gap: 12px; align-items: center;">
      <?php if($is_logged_in): ?>
        <a href="/profile.php" class="btn btn-outline" style="border-radius: var(--radius-pill); padding: 8px 16px;">
          <i class="ph ph-wallet"></i> <span class="font-en"><?= number_format($_SESSION['wallet_balance'] ?? 0) ?></span> ر.س
        </a>
        <a href="/profile.php" class="btn btn-primary" style="padding: 10px 16px;">
          <i class="ph ph-user"></i> حسابي
        </a>
      <?php else: ?>
        <a href="/login.php" class="btn btn-outline" style="padding: 10px 16px;">تسجيل الدخول</a>
        <a href="/register.php" class="btn btn-primary" style="padding: 10px 16px;">إنشاء حساب</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- Space for fixed navbar -->
<div style="height: 80px;"></div>
