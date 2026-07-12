<?php
require_once 'config.php';
$hero_title = 'عن FleetX';
$hero_bg = fleetx_subpage_hero_bg('about');
$hero_back_href = '/index.php';
$hero_back_label = '← العودة للرئيسية';
$hero_eyebrow = 'منصة مزادات الأساطيل';
$hero_desc = 'أول منصة مزادات ذكية وموثّقة في المملكة — تربط البائعين المعتمدين بالمشترين عبر تجربة آمنة من التوثيق حتى التسليم.';
$hero_extra_class = 'fx-page-hero--cover fx-page-hero--compact fx-page-hero--about';
$hero_meta_html = '
  <span class="fx-page-hero__chip"><i class="ph-fill ph-shield-check"></i> نفاذ وطني</span>
  <span class="fx-page-hero__chip"><i class="ph-fill ph-clipboard-text"></i> فحص 100+ نقطة</span>
  <span class="fx-page-hero__chip"><i class="ph-fill ph-lightning"></i> تنفيذ رقمي</span>';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>عن FleetX | كيف تعمل المنصة</title>
  <meta name="fx-build" content="<?= FLEETX_CSS_VER ?>">
  <link rel="stylesheet" href="<?= fleetx_css_href() ?>">
</head>
<body class="fx-home fx-page-shell fx-page-shell--about" data-fx-build="<?= FLEETX_CSS_VER ?>">

<?php include 'includes/navbar.php'; ?>
<?php include 'includes/page-hero.inc.php'; ?>

<div class="fx-about-v2 fx-page-body fx-page-body--overlap">
  <section class="fx-about-v2__intro reveal">
    <p>نربط شركات تأجير السيارات بأكبر شبكة من المشترين والتجار المعتمدين — من التوثيق عبر النفاذ الوطني إلى إتمام الصفقة وتسليم المركبة.</p>
  </section>

  <div class="fx-about-v2__pillars reveal">
    <article class="fx-about-v2__pillar">
      <span class="fx-about-v2__pillar-icon"><i class="ph-fill ph-shield-check"></i></span>
      <h3>توثيق كامل</h3>
      <p>تحقق من هوية المشترين والبائعين قبل أي مزايدة عبر النفاذ الوطني.</p>
    </article>
    <article class="fx-about-v2__pillar">
      <span class="fx-about-v2__pillar-icon"><i class="ph-fill ph-clipboard-text"></i></span>
      <h3>فحص 100+ نقطة</h3>
      <p>تقارير فنية معتمدة لكل مركبة قبل عرضها في المزاد أو الشراء الفوري.</p>
    </article>
    <article class="fx-about-v2__pillar">
      <span class="fx-about-v2__pillar-icon"><i class="ph-fill ph-lightning"></i></span>
      <h3>تنفيذ سريع</h3>
      <p>من المزايدة إلى التحويل البنكي ونقل الملكية — كل شيء رقمي وشفاف.</p>
    </article>
  </div>

  <section class="fx-about-v2__track reveal">
    <header class="fx-about-v2__track-head">
      <span class="fx-about-v2__badge fx-about-v2__badge--buyer"><i class="ph-fill ph-shopping-bag"></i> للمشترين</span>
      <h2>رحلة شراء سيارتك القادمة</h2>
    </header>
    <ol class="fx-about-v2__steps">
      <li><span class="fx-about-v2__step-no">01</span><strong>التسجيل والتوثيق</strong><small>حساب موثّق عبر النفاذ الوطني</small></li>
      <li class="fx-about-v2__arrow" aria-hidden="true"><i class="ph ph-arrow-left"></i></li>
      <li><span class="fx-about-v2__step-no">02</span><strong>شحن المحفظة</strong><small>تأمين قابل للاسترداد</small></li>
      <li class="fx-about-v2__arrow" aria-hidden="true"><i class="ph ph-arrow-left"></i></li>
      <li><span class="fx-about-v2__step-no">03</span><strong>المزايدة الحية</strong><small>مزايدة يدوية أو تلقائية</small></li>
      <li class="fx-about-v2__arrow" aria-hidden="true"><i class="ph ph-arrow-left"></i></li>
      <li><span class="fx-about-v2__step-no">04</span><strong>الفوز والتسليم</strong><small>تسوية آمنة وتقرير فحص</small></li>
    </ol>
  </section>

  <section class="fx-about-v2__track fx-about-v2__track--seller reveal">
    <header class="fx-about-v2__track-head">
      <span class="fx-about-v2__badge fx-about-v2__badge--seller"><i class="ph-fill ph-buildings"></i> للبائعين</span>
      <h2>بيع أسطولك بأعلى عائد</h2>
    </header>
    <ol class="fx-about-v2__steps">
      <li><span class="fx-about-v2__step-no">01</span><strong>تسجيل الشركة</strong><small>اعتماد خلال 48 ساعة</small></li>
      <li class="fx-about-v2__arrow" aria-hidden="true"><i class="ph ph-arrow-left"></i></li>
      <li><span class="fx-about-v2__step-no">02</span><strong>فحص FleetX</strong><small>تفتيش معتمد في موقعك</small></li>
      <li class="fx-about-v2__arrow" aria-hidden="true"><i class="ph ph-arrow-left"></i></li>
      <li><span class="fx-about-v2__step-no">03</span><strong>إطلاق المزاد</strong><small>وصول لآلاف المشترين</small></li>
      <li class="fx-about-v2__arrow" aria-hidden="true"><i class="ph ph-arrow-left"></i></li>
      <li><span class="fx-about-v2__step-no">04</span><strong>نقل الملكية</strong><small>إيداع الأرباح بأمان</small></li>
    </ol>
  </section>

  <div class="fx-about-v2__cta reveal">
    <a href="/register.php" class="btn btn-primary">ابدأ الآن</a>
    <a href="/companies.php" class="btn btn-outline-dark">دليل الشركات</a>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>