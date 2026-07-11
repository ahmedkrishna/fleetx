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

if (!$vehicle) {
    echo "بيانات الفحص غير متوفرة أو المركبة غير موجودة.";
    exit;
}

$title = $vehicle['make'] . ' ' . $vehicle['model'] . ' ' . $vehicle['year'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>تقرير الفحص المعتمد: <?= sanitize($title) ?> | FleetX</title>
  <link rel="stylesheet" href="<?= fleetx_css_href() ?>">
</head>
<body class="fx-home fx-page-shell fx-page-shell--vehicle-report">

<?php include 'includes/navbar.php'; ?>

<?php
$hero_title = 'تقرير الفحص الموثق الشامل';
$hero_desc = 'مرجع تقرير فني معتمد لأساطيل السيارات المستعملة بالمملكة.';
$hero_bg = $vehicle['image_url'] ?? 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=1600&q=80';
$hero_eyebrow = sanitize($title);
$hero_back_href = '/auctions.php';
$hero_back_label = '← العودة للمزادات';
$hero_actions_html = '<button type="button" class="btn btn-primary no-print" onclick="window.print()"><i class="ph ph-download-simple ph-space-left"></i> Print Report / PDF</button>';
include 'includes/page-hero.inc.php';
?>

<div class="container fx-page-body fx-page-body--overlap">
  
  <div class="fx-vehicle-report">
    
    <div class="fx-report-heading">
      <div>
        <h2><?= sanitize($title) ?></h2>
        <p>ممشى المركبة: <span class="font-en"><?= number_format($vehicle['mileage']) ?></span> كم | الموقع: <?= sanitize($vehicle['city']) ?> | ناقل الحركة: <?= sanitize($vehicle['transmission']) ?></p>
      </div>
      <div class="fx-report-ref">
        <div class="fx-report-ref-label">رقم تقرير الفحص المرجعي</div>
        <div class="fx-report-ref-value">IR-<?= 90000 + $vehicle['id'] ?></div>
      </div>
    </div>

    <div class="fx-score-summary">
      <div style="flex:1">
        <h3 style="font-size:18px; color:var(--text-dark); margin-bottom:8px">نتيجة الفحص الفني المعتمد</h3>
        <p style="color:var(--text-muted); font-size:14px; line-height:1.7; margin:0">لقد اجتازت هذه السيارة بنجاح فحصاً فنيّاً شاملاً شمل فحص هيكل السيارة بالكامل، واختبار كفاءة المحركات والأنظمة الكهربائية وكمبيوتر السيارة بواسطة مفتشينا المعتمدين.</p>
      </div>
      <div class="fx-score-badge"><?= $vehicle['overall_score'] ?>%</div>
    </div>

    <div class="fx-check-grid">
      
      <div class="fx-check-card">
        <div class="fx-check-card-head">
          <span><i class="ph ph-car ph-space-left"></i> الهيكل الخارجي والشاسيه</span>
          <span><?= $vehicle['exterior_score'] ?>%</span>
        </div>
        <div class="fx-progress-wrap">
          <div class="fx-progress-fill" style="width:<?= $vehicle['exterior_score'] ?>%"></div>
        </div>
        <p style="font-size:12px; color:var(--text-muted); line-height:1.6">تم فحص سماكة طلاء الهيكل بالكامل ومفاصل الشاسيه الأساسية. لا توجد أي آثار لحوادث جسيمة، فقط بعض الخدوش الطفيفة التجميلية.</p>
      </div>

      <div class="fx-check-card">
        <div class="fx-check-card-head">
          <span><i class="ph ph-chair ph-space-left"></i> المقصورة والفرش الداخلي</span>
          <span><?= $vehicle['interior_score'] ?>%</span>
        </div>
        <div class="fx-progress-wrap">
          <div class="fx-progress-fill" style="width:<?= $vehicle['interior_score'] ?>%"></div>
        </div>
        <p style="font-size:12px; color:var(--text-muted); line-height:1.6">مقاعد جلدية بحالة جيدة ونظيفة. تم فحص سلامة لوحة التحكم، المقود، شاشات العرض والعدادات، وكفاءة تكييف الهواء.</p>
      </div>

      <div class="fx-check-card">
        <div class="fx-check-card-head">
          <span><i class="ph ph-wrench ph-space-left"></i> المحركات والأجزاء الميكانيكية</span>
          <span><?= $vehicle['mechanical_score'] ?>%</span>
        </div>
        <div class="fx-progress-wrap">
          <div class="fx-progress-fill" style="width:<?= $vehicle['mechanical_score'] ?>%"></div>
        </div>
        <p style="font-size:12px; color:var(--text-muted); line-height:1.6">المحرك وناقل الحركة وعلبة التروس تعمل بسلاسة تامة دون تهريبات أو أصوات غير طبيعية. كفاءة ممتازة لنظام التوجيه والتعليق والفرامل.</p>
      </div>

      <div class="fx-check-card">
        <div class="fx-check-card-head">
          <span><i class="ph ph-cpu ph-space-left"></i> الأنظمة الإلكترونية والحساسات</span>
          <span><?= $vehicle['electronics_score'] ?>%</span>
        </div>
        <div class="fx-progress-wrap">
          <div class="fx-progress-fill" style="width:<?= $vehicle['electronics_score'] ?>%"></div>
        </div>
        <p style="font-size:12px; color:var(--text-muted); line-height:1.6">تم فحص كمبيوتر السيارة (OBD-II Scan) للكشف عن الأعطال. جميع الحساسات الخلفية والأمامية والكاميرات والأنظمة الإلكترونية تعمل.</p>
      </div>

    </div>

    <div class="fx-inspector-notes">
      <h3><i class="ph ph-note-pencil ph-space-left"></i> ملاحظات الفحص الفني المعتمدة</h3>
      <p><?= sanitize($vehicle['inspector_notes']) ?></p>
    </div>

    <div class="fx-report-disclaimer">
      أُعد هذا التقرير الفني ليعبر عن الحالة الفعلية للمركبة وقت الفحص فقط. لا تتحمل منصة FleetX أي مسؤولية قانونية عن التغييرات أو التلفيات اللاحقة لعملية الفحص.
    </div>

  </div>

</div>

<!-- Footer template -->
<?php include 'includes/footer.php'; ?>

</body>
</html>
