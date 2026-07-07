<?php
require_once 'config.php';

$vehicle_id = isset($_GET['id']) ? intval($_GET['id']) : 1;

$vehicle = null;
if ($db_connected) {
    $sql = "SELECT v.*, ir.exterior_score, ir.interior_score, ir.mechanical_score, ir.electronics_score, ir.overall_score, ir.notes AS inspector_notes
            FROM vehicles v
            LEFT JOIN inspections ir ON ir.vehicle_id = v.id
            WHERE v.id = $vehicle_id";
    $res = $conn->query($sql);
    if ($res && $res->num_rows > 0) {
        $vehicle = $res->fetch_assoc();
    }
}

// Fallback mock data
if (!$vehicle) {
    $mocks = getMockAuctions();
    $found = null;
    foreach ($mocks as $m) {
        if ($m['id'] === $vehicle_id) {
            $found = $m;
            break;
        }
    }
    if (!$found) $found = $mocks[0];
    
    $vehicle = [
        'id' => $vehicle_id,
        'make' => $found['make'],
        'model' => $found['model'],
        'year' => $found['year'],
        'mileage' => $found['mileage'],
        'color' => 'أبيض لؤلؤي',
        'transmission' => 'أوتوماتيك',
        'fuel_type' => 'بنزين',
        'city' => $found['city'],
        'image_url' => getCarImage($found['make'], $found['image_url']),
        'exterior_score' => 88,
        'interior_score' => 90,
        'mechanical_score' => 85,
        'electronics_score' => 92,
        'overall_score' => 88,
        'inspector_notes' => 'حالة المركبة العامة ممتازة وخالية من العيوب الهيكلية أو الصدمات المؤثرة. تم رصد بعض الخدوش الخفيفة في الصدام الخلفي وتمت معالجتها تجميلياً.'
    ];
}

$title = $vehicle['make'] . ' ' . $vehicle['model'] . ' ' . $vehicle['year'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>تقرير الفحص المعتمد: <?= sanitize($title) ?> | FleetX</title>
  <link rel="stylesheet" href="/assets/css/fleetx.css">
  <style>
    .vehicle-page-container {
      background: var(--bg-white);
      border: 1px solid var(--border-light);
      border-radius: var(--radius-lg);
      padding: 40px;
      box-shadow: var(--shadow-card);
      margin-bottom: 80px;
    }
    .score-summary-card {
      background: var(--bg-light);
      border: 1px solid var(--border-light);
      border-radius: var(--radius-md);
      padding: 30px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 40px;
      gap: 24px;
    }
    .score-badge-circle {
      width: 110px;
      height: 110px;
      border-radius: 50%;
      background: var(--primary-gradient);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 32px;
      font-weight: 800;
      color: #fff;
      font-family: var(--font-en);
      box-shadow: 0 8px 20px rgba(0, 210, 211, 0.2);
    }
    .check-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 24px;
      margin-bottom: 40px;
    }
    .check-card {
      background: var(--bg-white);
      border: 1px solid var(--border-light);
      border-radius: var(--radius-sm);
      padding: 20px;
      transition: var(--transition);
    }
    .check-card:hover {
      border-color: var(--primary);
    }
    .progress-bar-wrap {
      height: 6px;
      background: var(--bg-light);
      border-radius: var(--radius-round);
      overflow: hidden;
      margin-top: 12px;
      margin-bottom: 12px;
    }
    .progress-bar-fill {
      height: 100%;
      background: var(--primary-gradient);
      border-radius: var(--radius-round);
    }
    @media (max-width: 768px) {
      .check-grid { grid-template-columns: 1fr; }
      .score-summary-card { flex-direction: column; text-align: center; }
      .vehicle-page-container { padding: 24px 16px; }
    }
    @media print {
      body { background: #fff; color: #000; }
      .navbar, footer, .btn, .no-print { display: none !important; }
      .vehicle-page-container { border: none; box-shadow: none; padding: 0; margin: 0; }
      .score-summary-card { background: #fff; border: 1.5px solid #000; }
      .score-badge-circle { background: #000; color: #fff; border: 1px solid #000; }
      .check-card { border: 1.5px solid #000; }
      .progress-bar-fill { background: #000 !important; }
    }
  </style>
</head>
<body class="page-inner">

<!-- Navbar template -->
<?php include 'includes/navbar.php'; ?>

<!-- Dark Header -->
<header class="page-header no-print">
  <div class="page-header-bg" style="background-image:url('<?= $vehicle['image_url'] ?>'); filter:blur(10px) brightness(0.25)"></div>
  <div class="container">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:20px">
      <div>
        <a href="/auctions.php" style="color:var(--primary-turquoise); font-size:14px; font-weight:700; display:inline-block; margin-bottom:12px">← العودة للمزادات</a>
        <h1 style="margin:0">تقرير الفحص الموثق الشامل</h1>
        <p style="color:var(--text-light-muted); font-size:16px; margin-top:6px">مرجع تقرير فني معتمد لأساطيل السيارات المستعملة بالمملكة.</p>
      </div>
      <div>
         <button class="btn btn-primary font-en" onclick="window.print()" style="display:inline-flex; align-items:center; gap:8px;"><i class="ph ph-download-simple"></i> Print Report / PDF</button>
      </div>
    </div>
  </div>
</header>

<div class="container" style="margin-top:-30px; position:relative; z-index:10;">
  
  <div class="vehicle-page-container">
    
    <!-- Vehicle Heading specs -->
    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:16px; border-bottom:1px solid var(--border-light); padding-bottom:24px; margin-bottom:32px">
      <div>
        <h2 style="font-size:26px; color:var(--text-dark)"><?= sanitize($title) ?></h2>
        <p style="color:var(--text-muted); font-size:14px; margin-top:6px">ممشى المركبة: <span class="font-en"><?= number_format($vehicle['mileage']) ?></span> كم | الموقع: <?= sanitize($vehicle['city']) ?> | ناقل الحركة: <?= sanitize($vehicle['transmission']) ?></p>
      </div>
      <div style="text-align:left; font-family:var(--font-en)">
        <div style="font-size:12px; color:var(--text-muted)">رقم تقرير الفحص المرجعي</div>
        <div style="font-size:18px; font-weight:800; color:var(--text-dark); margin-top:4px">IR-<?= 90000 + $vehicle['id'] ?></div>
      </div>
    </div>

    <!-- Overall Score summary -->
    <div class="score-summary-card">
      <div style="flex:1">
        <h3 style="font-size:18px; color:var(--text-dark); margin-bottom:8px">نتيجة الفحص الفني المعتمد</h3>
        <p style="color:var(--text-muted); font-size:14px; line-height:1.7">لقد اجتازت هذه السيارة بنجاح فحصاً فنيّاً شاملاً شمل فحص هيكل السيارة بالكامل، واختبار كفاءة المحركات والأنظمة الكهربائية وكمبيوتر السيارة بواسطة مفتشينا المعتمدين.</p>
      </div>
      <div class="score-badge-circle"><?= $vehicle['overall_score'] ?>%</div>
    </div>

    <!-- Checklist Breakdown -->
    <div class="check-grid">
      
      <!-- Exterior check -->
      <div class="check-card">
        <div style="display:flex; justify-content:space-between; align-items:center font-weight:800">
          <span style="font-size:15px; color:var(--text-dark)"><i class="ph ph-car ph-space-left"></i> الهيكل الخارجي والشاسيه</span>
          <span class="font-en" style="color:var(--primary); font-weight:800"><?= $vehicle['exterior_score'] ?>%</span>
        </div>
        <div class="progress-bar-wrap">
          <div class="progress-bar-fill" style="width:<?= $vehicle['exterior_score'] ?>%"></div>
        </div>
        <p style="font-size:12px; color:var(--text-muted); line-height:1.6">تم فحص سماكة طلاء الهيكل بالكامل ومفاصل الشاسيه الأساسية. لا توجد أي آثار لحوادث جسيمة، فقط بعض الخدوش الطفيفة التجميلية.</p>
      </div>

      <!-- Interior check -->
      <div class="check-card">
        <div style="display:flex; justify-content:space-between; align-items:center font-weight:800">
          <span style="font-size:15px; color:var(--text-dark)"><i class="ph ph-chair ph-space-left"></i> المقصورة والفرش الداخلي</span>
          <span class="font-en" style="color:var(--primary); font-weight:800"><?= $vehicle['interior_score'] ?>%</span>
        </div>
        <div class="progress-bar-wrap">
          <div class="progress-bar-fill" style="width:<?= $vehicle['interior_score'] ?>%"></div>
        </div>
        <p style="font-size:12px; color:var(--text-muted); line-height:1.6">مقاعد جلدية بحالة جيدة ونظيفة. تم فحص سلامة لوحة التحكم، المقود، شاشات العرض والعدادات، وكفاءة تكييف الهواء.</p>
      </div>

      <!-- Mechanical check -->
      <div class="check-card">
        <div style="display:flex; justify-content:space-between; align-items:center font-weight:800">
          <span style="font-size:15px; color:var(--text-dark)"><i class="ph ph-wrench ph-space-left"></i> المحركات والأجزاء الميكانيكية</span>
          <span class="font-en" style="color:var(--primary); font-weight:800"><?= $vehicle['mechanical_score'] ?>%</span>
        </div>
        <div class="progress-bar-wrap">
          <div class="progress-bar-fill" style="width:<?= $vehicle['mechanical_score'] ?>%"></div>
        </div>
        <p style="font-size:12px; color:var(--text-muted); line-height:1.6">المحرك وناقل الحركة وعلبة التروس تعمل بسلاسة تامة دون تهريبات أو أصوات غير طبيعية. كفاءة ممتازة لنظام التوجيه والتعليق والفرامل.</p>
      </div>

      <!-- Electrical check -->
      <div class="check-card">
        <div style="display:flex; justify-content:space-between; align-items:center font-weight:800">
          <span style="font-size:15px; color:var(--text-dark)"><i class="ph ph-cpu ph-space-left"></i> الأنظمة الإلكترونية والحساسات</span>
          <span class="font-en" style="color:var(--primary); font-weight:800"><?= $vehicle['electronics_score'] ?>%</span>
        </div>
        <div class="progress-bar-wrap">
          <div class="progress-bar-fill" style="width:<?= $vehicle['electronics_score'] ?>%"></div>
        </div>
        <p style="font-size:12px; color:var(--text-muted); line-height:1.6">تم فحص كمبيوتر السيارة (OBD-II Scan) للكشف عن الأعطال. جميع الحساسات الخلفية والأمامية والكاميرات والأنظمة الإلكترونية تعمل.</p>
      </div>

    </div>

    <!-- Inspector comments -->
    <div style="background:var(--bg-light); border:1px solid var(--border-light); border-radius:var(--radius-md); padding:24px 30px">
      <h3 style="font-size:16px; color:var(--text-dark); margin-bottom:10px"><i class="ph ph-note-pencil ph-space-left"></i> ملاحظات الفحص الفني المعتمدة</h3>
      <p style="color:var(--text-muted); font-size:14px; line-height:1.7"><?= sanitize($vehicle['inspector_notes']) ?></p>
    </div>

    <!-- Print disclaimer -->
    <div style="margin-top:40px; font-size:11px; color:var(--text-muted); border-top:1px solid var(--border-light); padding-top:20px; line-height:1.6">
      أُعد هذا التقرير الفني ليعبر عن الحالة الفعلية للمركبة وقت الفحص فقط. لا تتحمل منصة FleetX أي مسؤولية قانونية عن التغييرات أو التلفيات اللاحقة لعملية الفحص.
    </div>

  </div>

</div>

<!-- Footer template -->
<?php include 'includes/footer.php'; ?>

</body>
</html>
