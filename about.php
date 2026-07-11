<?php
require_once 'config.php';
$hero_title = 'كيف يعمل FleetX؟';
$hero_bg = 'https://images.unsplash.com/photo-1573164713988-8665fc963095?w=1600&q=80';
$hero_back_href = '/index.php';
$hero_back_label = '← العودة للرئيسية';
$hero_eyebrow = 'عن FleetX';
$hero_desc = 'منصة مزادات أساطيل ذكية تربط البائعين المعتمدين بالمشترين الموثّقين عبر تجربة آمنة وشفافة.';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>كيف يعمل مزاد FleetX | شروط التداول والبيع</title>
  <meta name="fx-build" content="<?= FLEETX_CSS_VER ?>">
  <link rel="stylesheet" href="<?= fleetx_css_href() ?>">
</head>
<body class="fx-home fx-page-shell fx-page-shell--about fx-page-shell--legal" data-fx-build="<?= FLEETX_CSS_VER ?>">

<?php include 'includes/navbar.php'; ?>
<?php include 'includes/page-hero.inc.php'; ?>

<div class="container fx-about-page fx-page-body fx-page-body--overlap">
  <p class="fx-about-intro reveal">نربط شركات تأجير السيارات بأكبر شبكة من المشترين والتجار المعتمدين بالمملكة — من التوثيق عبر النفاذ الوطني إلى إتمام الصفقة وتسليم المركبة.</p>

  <div class="fx-about-features-panel reveal">
    <div class="fx-about-features">
      <div class="fx-about-feature">
        <i class="ph-fill ph-shield-check"></i>
        <h3>توثيق كامل</h3>
        <p>تكامل مع النفاذ الوطني وتحقق من هوية المشترين والبائعين قبل أي مزايدة.</p>
      </div>
      <div class="fx-about-feature">
        <i class="ph-fill ph-clipboard-text"></i>
        <h3>فحص 100+ نقطة</h3>
        <p>تقارير فنية معتمدة لكل مركبة قبل عرضها في المزاد أو الشراء الفوري.</p>
      </div>
      <div class="fx-about-feature">
        <i class="ph-fill ph-lightning"></i>
        <h3>تنفيذ سريع</h3>
        <p>من المزايدة إلى التحويل البنكي ونقل الملكية — كل شيء رقمي وشفاف.</p>
      </div>
    </div>
  </div>

  <section class="fx-about-journey reveal">
    <div class="fx-about-section-head">
      <div class="title-badge badge-buyer"><i class="ph-fill ph-shopping-bag"></i> للمشترين والتجار المعتمدين</div>
      <h2>رحلة شراء سيارتك القادمة</h2>
      <p>خطوات بسيطة تأخذك لامتلاك سيارتك بأمان.</p>
    </div>

    <div class="fx-about-timeline-panel">
      <div class="vector-timeline">
        <div class="path-container" aria-hidden="true">
          <svg class="path-svg" viewBox="0 0 800 1200" preserveAspectRatio="none">
            <path class="path-line" d="M400,0 C400,100 700,150 700,300 C700,450 100,450 100,600 C100,750 700,750 700,900 C700,1050 400,1100 400,1200"></path>
          </svg>
        </div>

        <div class="vector-step">
          <div class="v-content" data-step="01">
            <h3>إنشاء الحساب والتوثيق</h3>
            <p>أولى خطواتك تبدأ بإنشاء حساب مشتري وتوثيقه بسلاسة فائقة عبر النفاذ الوطني، لنضمن لك مجتمع مزايدة آمن وموثوق تماماً وخالي من التلاعب.</p>
          </div>
          <div class="v-illustration">
            <div class="v-blob"></div>
            <div class="v-ring"></div>
            <div class="v-ring-pulse"></div>
            <div class="layer layer-main"><i class="ph-fill ph-identification-card"></i></div>
            <div class="layer layer-sub1"><i class="ph-fill ph-fingerprint"></i></div>
            <div class="layer layer-sub2"><i class="ph-fill ph-shield-check icon--success"></i></div>
          </div>
        </div>

        <div class="vector-step">
          <div class="v-content" data-step="02">
            <h3>شحن المحفظة (التأمين)</h3>
            <p>قم بشحن محفظتك الرقمية بمبلغ التأمين المسترد بسهولة لتتمكن من رفع قوتك الشرائية والمشاركة الفورية في أقوى المزادات.</p>
          </div>
          <div class="v-illustration">
            <div class="v-blob v-blob--organic-a"></div>
            <div class="layer layer-main"><i class="ph-fill ph-wallet"></i></div>
            <div class="layer layer-sub1 anim-bounce1 layer-sub--coin-lg"><i class="ph-fill ph-coin"></i></div>
            <div class="layer layer-sub1 anim-bounce2 layer-sub--coin-md"><i class="ph-fill ph-coin"></i></div>
            <div class="layer layer-sub1 anim-bounce3 layer-sub--coin-sm"><i class="ph-fill ph-coin"></i></div>
            <div class="layer layer-sub2"><i class="ph-bold ph-arrows-left-right"></i></div>
          </div>
        </div>

        <div class="vector-step">
          <div class="v-content" data-step="03">
            <h3>المزايدة الحية والتلقائية</h3>
            <p>عش أجواء الحماس في قاعات المزاد الحية، وزايد بنقرة واحدة، أو استخدم المزايدة التلقائية لتقوم المنصة برفع السعر عنك حتى الحد الذي تحدده.</p>
          </div>
          <div class="v-illustration">
            <div class="v-blob v-blob--organic-b"></div>
            <div class="v-ring"></div>
            <div class="layer layer-main anim-strike"><i class="ph-fill ph-gavel"></i></div>
            <div class="layer layer-sub1"><i class="ph-bold ph-trend-up"></i></div>
            <div class="layer layer-sub2"><i class="ph-fill ph-users"></i></div>
          </div>
        </div>

        <div class="vector-step">
          <div class="v-content" data-step="04">
            <h3>الفوز واستلام السيارة</h3>
            <p>تهانينا! بعد رسو المزاد وسداد المبلغ، ستتلقى تفاصيل الاستلام مع تقرير الفحص الفني للسيارة لتنطلق بها بكل ثقة.</p>
          </div>
          <div class="v-illustration">
            <div class="v-blob v-blob--gold"></div>
            <div class="v-ring-pulse"></div>
            <div class="layer layer-main layer-main--gold"><i class="ph-fill ph-car-profile"></i></div>
            <div class="layer layer-sub1 layer-sub--gold"><i class="ph-fill ph-key"></i></div>
            <div class="layer layer-sub2"><i class="ph-fill ph-confetti icon--confetti"></i></div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="fx-about-journey fx-about-journey--seller reveal">
    <div class="fx-about-section-head section-title-wrap seller">
      <div class="title-badge badge-seller"><i class="ph-fill ph-buildings"></i> لبائعي الأساطيل والشركات</div>
      <h2>بيع أسطولك بأعلى عائد مالي</h2>
      <p>نظام متكامل يضمن لك تصفية أسطولك بسرعة وموثوقية.</p>
    </div>

    <div class="fx-about-timeline-panel fx-about-timeline-panel--seller">
      <div class="vector-timeline seller">
        <div class="path-container" aria-hidden="true">
          <svg class="path-svg" viewBox="0 0 800 1200" preserveAspectRatio="none">
            <path class="path-line" d="M400,0 C400,100 100,150 100,300 C100,450 700,450 700,600 C700,750 100,750 100,900 C100,1050 400,1100 400,1200"></path>
          </svg>
        </div>

        <div class="vector-step">
          <div class="v-content" data-step="01">
            <h3>تسجيل الشركة والأسطول</h3>
            <p>وثق حساب شركتك وارفع بيانات أسطول مركباتك دفعة واحدة من خلال لوحة تحكم ذكية ومصممة خصيصاً للشركات الكبرى.</p>
          </div>
          <div class="v-illustration">
            <div class="v-blob"></div>
            <div class="v-ring"></div>
            <div class="layer layer-main"><i class="ph-fill ph-buildings"></i></div>
            <div class="layer layer-sub1"><i class="ph-fill ph-files"></i></div>
            <div class="layer layer-sub2"><i class="ph-bold ph-check icon--primary"></i></div>
          </div>
        </div>

        <div class="vector-step">
          <div class="v-content" data-step="02">
            <h3>فحص FleetX الدقيق</h3>
            <p>نوفر فريق تفتيش معتمد يزور موقعك ليفحص السيارات في أكثر من 100 نقطة فنية لضمان شفافية مطلقة تجذب المزيد من المشترين.</p>
          </div>
          <div class="v-illustration">
            <div class="v-blob v-blob--organic-a"></div>
            <div class="v-ring-pulse"></div>
            <div class="layer layer-main"><i class="ph-fill ph-magnifying-glass"></i></div>
            <div class="layer layer-sub1"><i class="ph-fill ph-clipboard-text"></i></div>
            <div class="layer layer-sub2"><i class="ph-fill ph-engine"></i></div>
          </div>
        </div>

        <div class="vector-step">
          <div class="v-content" data-step="03">
            <h3>إطلاق المزاد والترويج</h3>
            <p>نحدد موعد المزاد ونطلقه لمنصة تجمع آلاف التجار والمشترين المتنافسين، مما يضمن لك بيع المركبة بأعلى سعر سوقي ممكن.</p>
          </div>
          <div class="v-illustration">
            <div class="v-blob v-blob--organic-b"></div>
            <div class="layer layer-main"><i class="ph-fill ph-megaphone-simple"></i></div>
            <div class="layer layer-sub1"><i class="ph-bold ph-users-three"></i></div>
            <div class="layer layer-sub2"><i class="ph-fill ph-chart-line-up icon--success"></i></div>
          </div>
        </div>

        <div class="vector-step">
          <div class="v-content" data-step="04">
            <h3>نقل الملكية وإيداع الأرباح</h3>
            <p>نقوم بإتمام المعاملات الورقية ونقل الملكية، ويتم إيداع الأرباح والمبالغ المحصلة في حسابك البنكي بكل أمان وموثوقية.</p>
          </div>
          <div class="v-illustration">
            <div class="v-blob"></div>
            <div class="v-ring"></div>
            <div class="layer layer-main"><i class="ph-fill ph-bank"></i></div>
            <div class="layer layer-sub1 anim-bounce1 layer-sub--gold-coin"><i class="ph-fill ph-currency-dollar"></i></div>
            <div class="layer layer-sub2"><i class="ph-fill ph-handshake"></i></div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>