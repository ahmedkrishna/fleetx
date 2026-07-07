<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>كيف يعمل مزاد FleetX | شروط التداول والبيع</title>
  <link rel="stylesheet" href="/assets/css/fleetx.css">
  <style>
    .info-card {
      background: var(--bg-white);
      border-radius: var(--radius-lg);
      padding: 40px;
      margin-bottom: 40px;
      border: 1px solid var(--border-light);
    }
    .step-row {
      display: flex;
      gap: 24px;
      align-items: flex-start;
      margin-bottom: 32px;
      border-bottom: 1px dashed var(--border-light);
      padding-bottom: 24px;
    }
    .step-row:last-child {
      margin-bottom: 0;
      border-bottom: none;
      padding-bottom: 0;
    }
    .step-number {
      width: 48px;
      height: 48px;
      flex-shrink: 0;
      background: var(--primary);
      color: #fff;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      font-weight: 800;
      font-family: var(--font-en);
    }
    .step-content h3 {
      font-size: 18px;
      font-weight: 800;
      margin-bottom: 8px;
      color: var(--text-dark);
    }
    .step-content p {
      font-size: 14px;
      color: var(--text-muted);
      line-height: 1.8;
    }
    @media (max-width: 768px) {
      .step-row { flex-direction: column; gap: 16px; }
      .info-card { padding: 24px 16px; }
    }
  </style>
</head>
<body class="page-inner">

<!-- Navbar template -->
<?php include 'includes/navbar.php'; ?>

<!-- Page Header -->
<header class="page-header" style="padding-bottom: 90px">
  <div class="page-header-bg" style="background-image:url('https://images.unsplash.com/photo-1573164713988-8665fc963095?w=1600&q=80')"></div>
  <div class="container">
    <h1 style="margin:0">كيف يعمل FleetX؟</h1>
    <p style="color:var(--text-light-muted); font-size:16px; margin-top:8px; max-width:620px">نربط شركات تأجير السيارات بأكبر شبكة من المشترين والتجار المعتمدين بالمملكة عبر بيئة مزادات آمنة وسهلة بالكامل.</p>
  </div>
</header>

<div class="container" style="margin-top:-50px; position:relative; z-index:10; margin-bottom:100px;">
    
    <!-- For Buyers -->
    <div class="info-card">
        <div style="text-align:center; margin-bottom:40px">
            <span style="font-size:12px; font-weight:800; color:var(--info); background:var(--info-pale); padding:8px 20px; border-radius:var(--radius-round)">للمشترين والتجار المعتمدين</span>
            <h2 style="font-size:26px; font-weight:900; margin-top:16px; color:var(--text-dark)">رحلة شراء سيارتك القادمة من المزاد</h2>
        </div>
        
        <div class="step-row">
            <div class="step-number" style="background: var(--info)">1</div>
            <div class="step-content">
                <h3>إنشاء الحساب وتوثيقه عبر نفاذ</h3>
                <p>سجل حساب مشتري جديد، وأدخل بياناتك الشخصية ثم قم بتوثيق الهوية عبر تكامل بوابة نفاذ الوطني الموحد لضمان جدية المزايدات على المنصة.</p>
            </div>
        </div>
        
        <div class="step-row">
            <div class="step-number" style="background: var(--info)">2</div>
            <div class="step-content">
                <h3>شحن المحفظة ومبلغ التأمين</h3>
                <p>قم بشحن محفظتك بمبلغ التأمين المالي المسترد لتبني قوتك الشرائية. رصيد التأمين يضمن التزامك بالصفقات وجديتك أمام الموردين.</p>
            </div>
        </div>
        
        <div class="step-row">
            <div class="step-number" style="background: var(--info)">3</div>
            <div class="step-content">
                <h3>المزايدة الحية (يدوية وتلقائية)</h3>
                <p>ادخل قاعات المزاد الحية وزايد على السيارات المفضلة لديك بنقرة زر واحدة، أو اضبط المزايدة التلقائية بتحديد الحد الأقصى المقبول ليقوم النظام بالمزايدة عنك.</p>
            </div>
        </div>
        
        <div class="step-row">
            <div class="step-number" style="background: var(--info)">4</div>
            <div class="step-content">
                <h3>الفوز بالمزاد والتسليم</h3>
                <p>عند رسو المزاد عليك، قم بسداد قيمة السيارة المتبقية عبر طرق الدفع المعتمدة، ثم استلم تقرير الفحص والسيارة مباشرة من موقع البائع المعتمد.</p>
            </div>
        </div>
    </div>
    
    <!-- For Sellers -->
    <div class="info-card">
        <div style="text-align:center; margin-bottom:40px">
            <span style="font-size:12px; font-weight:800; color:var(--primary); background:var(--primary-light); padding:8px 20px; border-radius:var(--radius-round)">لبائعي الأساطيل وشركات التأجير</span>
            <h2 style="font-size:26px; font-weight:900; margin-top:16px; color:var(--text-dark)">بيع أسطولك المستعمل بأعلى عائد مالي</h2>
        </div>
        
        <div class="step-row">
            <div class="step-number">1</div>
            <div class="step-content">
                <h3>تسجيل حساب الشركة ورفع الأسطول</h3>
                <p>سجل شركة تأجير السيارات وقدم السجل التجاري لاعتماد الحساب. يمكنك بعدها رفع أسطول مركباتك دفعة واحدة وبكل سرعة من لوحة التحكم الخاصة بك.</p>
            </div>
        </div>
        
        <div class="step-row">
            <div class="step-number">2</div>
            <div class="step-content">
                <h3>الفحص الفني وتقرير 100+ نقطة</h3>
                <p>يقوم مفتشو FleetX المعتمدون بالتوجه لموقع سياراتك وفحصها فحصاً دقيقاً وتوثيق النتيجة. يساهم التقرير الفني الشامل في كسب ثقة المشترين.</p>
            </div>
        </div>
        
        <div class="step-row">
            <div class="step-number">3</div>
            <div class="step-content">
                <h3>جدولة المزاد واستلام المدفوعات</h3>
                <p>يتم إطلاق مزاد سياراتك وجدولته، وبمجرد فوز أعلى مزايد ودفع ثمن السيارة، نقوم بنقل الملكية للمشتري وإيداع الأرباح بحساب شركتك مباشرة.</p>
            </div>
        </div>
    </div>
    
</div>

<!-- Footer template -->
<?php include 'includes/footer.php'; ?>

</body>
</html>
