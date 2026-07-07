<?php
require_once 'config.php';
$hero_title = 'الشروط والأحكام';
$hero_bg = 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?w=1600&q=80';
$hero_back_href = '/index.php';
$hero_back_label = '← العودة للرئيسية';
$hero_modifier = 'compact';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>الشروط والأحكام | FleetX</title>
  <link rel="stylesheet" href="/assets/css/fleetx.css">
</head>
<body class="page-inner fx-page-shell fx-page-shell--legal">

<?php include 'includes/navbar.php'; ?>
<?php include 'includes/page-hero.inc.php'; ?>

<div class="container fx-section-pad">
  <p class="fx-subpage-intro">استخدامك لمنصة FleetX يعني موافقتك على الشروط والسياسات التالية. يرجى قراءتها بعناية قبل البدء بالتداول.</p>

  <div class="fx-terms-card">
    <div class="fx-term-section">
      <h2><i class="ph-fill ph-gavel"></i> 1. شروط المزايدة</h2>
      <p>عند اشتراكك في مزاد عبر المنصة، فإن المزايدة تعتبر التزاماً ملزماً بالشراء في حال رسو المزاد عليك. يتطلب الدخول في المزادات وجود مبلغ تأميني في محفظتك لا يقل عن الحد الأدنى المحدد لكل مركبة، ويحق للمنصة مصادرة التأمين في حال التراجع.</p>
    </div>

    <div class="fx-term-section">
      <h2><i class="ph-fill ph-lightning"></i> 2. سياسة الشراء الفوري</h2>
      <p>المشتريات المباشرة تخضع لتوفر المركبة وموافقة البائع النهائية، ويتم حجز المبلغ من بطاقتك الائتمانية أو محفظتك حتى إتمام إجراءات نقل الملكية عبر نظام المرور وأبشر.</p>
    </div>

    <div class="fx-term-section">
      <h2><i class="ph-fill ph-receipt"></i> 3. الرسوم والعمولات</h2>
      <p>تُطبق ضريبة القيمة المضافة 15% على إجمالي قيمة المركبة أو رسوم الخدمات الإدارية وعمولات المنصة. سيتم إيضاح جميع الرسوم بشفافية في صفحة الدفع قبل تأكيد الشراء النهائي، ولا توجد أي رسوم خفية.</p>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>