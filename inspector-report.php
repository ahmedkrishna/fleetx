<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

$role = getUserRole();
if ($role !== 'inspector' && $role !== 'admin') {
    header('Location: /');
    exit;
}

$inspection_id = intval($_GET['id'] ?? 0);
if (!$inspection_id) {
    header('Location: /inspector.php');
    exit;
}

$inspection = null;
$user_id = (int)$_SESSION['user_id'];

if ($db_connected) {
    $stmt = $conn->prepare("
        SELECT i.*, v.make, v.model, v.year, v.mileage, v.city, v.vin, v.plate_number, v.seller_id,
               sc.company_name as seller_name, sc.user_id as seller_user_id
        FROM inspections i
        JOIN vehicles v ON i.vehicle_id = v.id
        JOIN seller_companies sc ON v.seller_id = sc.id
        WHERE i.id = ?
        LIMIT 1
    ");
    $stmt->bind_param('i', $inspection_id);
    $stmt->execute();
    $inspection = $stmt->get_result()->fetch_assoc();
}

if (!$inspection) {
    die('طلب الفحص غير موجود.');
}

if ($role === 'inspector' && (int)$inspection['inspector_id'] !== $user_id) {
    header('Location: /inspector.php');
    exit;
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $exterior   = max(0, min(100, intval($_POST['exterior_score'] ?? 0)));
    $interior   = max(0, min(100, intval($_POST['interior_score'] ?? 0)));
    $mechanical = max(0, min(100, intval($_POST['mechanical_score'] ?? 0)));
    $electronics= max(0, min(100, intval($_POST['electronics_score'] ?? 0)));
    $paint      = sanitize($_POST['paint_condition'] ?? 'good');
    $accident   = isset($_POST['accident_history']) ? 1 : 0;
    $notes      = trim($_POST['notes'] ?? '');
    $tire_cond  = sanitize($_POST['tire_condition'] ?? 'good');
    $trans_notes= trim($_POST['transmission_notes'] ?? '');
    $engine_notes= trim($_POST['engine_notes'] ?? '');
    $mileage_ok = isset($_POST['mileage_verified']) ? 1 : 0;

    $report_pdf = $inspection['report_pdf'] ?? '';
    if (isset($_FILES['report_pdf']) && $_FILES['report_pdf']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/uploads/reports/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $ext = strtolower(pathinfo($_FILES['report_pdf']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
            $fname = 'report_' . $inspection_id . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['report_pdf']['tmp_name'], $upload_dir . $fname)) {
                $report_pdf = '/uploads/reports/' . $fname;
            }
        }
    }

    if ($exterior < 1 || $interior < 1 || $mechanical < 1 || $electronics < 1) {
        $error = 'يرجى إدخال جميع درجات التقييم (1-100)';
    } elseif ($db_connected) {
        $prices = estimateAutoDataPrice($inspection['make'], $inspection['model'], $inspection['year'], $inspection['mileage']);

        $conn->begin_transaction();
        try {
            $upd = $conn->prepare("
                UPDATE inspections SET
                    exterior_score=?, interior_score=?, mechanical_score=?, electronics_score=?,
                    paint_condition=?, accident_history=?, notes=?, report_pdf=?,
                    tire_condition=?, transmission_notes=?, engine_notes=?, mileage_verified=?,
                    status='completed', inspection_date=CURDATE()
                WHERE id=?
            ");
            $upd->bind_param('iiiisisssssii', $exterior, $interior, $mechanical, $electronics, $paint, $accident, $notes, $report_pdf, $tire_cond, $trans_notes, $engine_notes, $mileage_ok, $inspection_id);
            $upd->execute();

            $vstmt = $conn->prepare("
                UPDATE vehicles SET status='awaiting_seller_approval',
                    autodata_price_min=?, autodata_price_max=?
                WHERE id=?
            ");
            $vmin = $prices['min'];
            $vmax = $prices['max'];
            $vid = (int)$inspection['vehicle_id'];
            $vstmt->bind_param('ddi', $vmin, $vmax, $vid);
            $vstmt->execute();

            $conn->query("UPDATE inspections SET seller_approved=NULL WHERE id=$inspection_id");

            $overall = round(($exterior + $interior + $mechanical + $electronics) / 4);
            $car_name = $inspection['make'] . ' ' . $inspection['model'] . ' ' . $inspection['year'];

            notifyUser($conn, (int)$inspection['seller_user_id'], 'system',
                'تقرير الفحص جاهز — بانتظار موافقتك',
                "اكتمل فحص $car_name بدرجة $overall/100. راجع التقرير واعتمد أو ارفض قبل النشر. السعر المقدر: " . number_format($vmin) . ' - ' . number_format($vmax) . ' ر.س',
                '/seller.php?section=reports',
                ['in_app', 'sms', 'whatsapp']
            );
            notifyUser($conn, 1, 'system', 'فحص مكتمل', "اكتمل فحص $car_name للشركة " . $inspection['seller_name'], '/admin/inspections.php');

            $conn->commit();
            $success = true;
            header('Location: /inspector.php?msg=success');
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'حدث خطأ أثناء حفظ التقرير';
        }
    }
}

$estimated = estimateAutoDataPrice($inspection['make'], $inspection['model'], $inspection['year'], $inspection['mileage']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>تقرير الفحص | FleetX</title>
  <link rel="stylesheet" href="<?= fleetx_css_href() ?>">
</head>
<body class="fx-home fx-page-shell fx-page-shell--inspector-report">
<?php include 'includes/navbar.php'; ?>

<?php
$report_vehicle = sanitize($inspection['make'] . ' ' . $inspection['model'] . ' ' . $inspection['year']);
$hero_title = 'تقرير فحص: ' . $report_vehicle;
$hero_desc = 'البائع: ' . sanitize($inspection['seller_name']) . ' | المدينة: ' . sanitize($inspection['city'] ?? '—');
$hero_bg = 'https://images.unsplash.com/photo-1486262715619-67b85e0b08d3?w=1600&q=80';
$hero_eyebrow = 'تقرير الفحص';
$hero_back_href = '/inspector.php';
$hero_back_label = '← العودة لطلبات الفحص';
include 'includes/page-hero.inc.php';
?>

<div class="container fx-page-body fx-page-body--overlap report-wrap">

  <div class="report-card">
    <div class="report-header">
      <h2 class="fx-report-title">
        تفاصيل الفحص الفني
      </h2>
      <p class="fx-report-meta">
        البائع: <?= sanitize($inspection['seller_name']) ?> |
        المدينة: <?= sanitize($inspection['city'] ?? '—') ?> |
        الممشى: <?= number_format($inspection['mileage'] ?? 0) ?> كم
      </p>
    </div>

    <?php if ($error): ?>
    <div class="error-box"><?= sanitize($error) ?></div>
    <?php endif; ?>

    <div class="autodata-box">
      <div class="fx-autodata-title"><i class="ph ph-chart-line-up"></i> تقدير AutoData (آلي)</div>
      <div class="fx-autodata-price">
        <?= number_format($estimated['min']) ?> — <?= number_format($estimated['max']) ?> <span style="font-size:14px;">ر.س</span>
      </div>
      <div class="fx-autodata-note">يُحدَّث تلقائياً عند اعتماد التقرير بناءً على الماركة والموديل والسنة والممشى</div>
    </div>

    <form method="POST" enctype="multipart/form-data">
      <div class="score-grid">
        <?php
        $scores = [
          'exterior_score' => 'الهيكل الخارجي',
          'interior_score' => 'الداخلية',
          'mechanical_score' => 'الميكانيكا',
          'electronics_score' => 'الإلكترونيات',
        ];
        foreach ($scores as $name => $label):
          $val = (int)($inspection[$name] ?? 85);
        ?>
        <div class="score-field">
          <label><?= $label ?> <span class="score-val" id="val-<?= $name ?>"><?= $val ?></span>/100</label>
          <input type="range" name="<?= $name ?>" min="1" max="100" value="<?= $val ?>"
                 oninput="document.getElementById('val-<?= $name ?>').textContent=this.value">
        </div>
        <?php endforeach; ?>
      </div>

      <div class="fx-field-block">
        <label class="fx-field-label">حالة الطلاء</label>
        <select name="paint_condition" class="form-select">
          <?php foreach (['excellent'=>'ممتاز', 'good'=>'جيد', 'fair'=>'مقبول', 'poor'=>'ضعيف'] as $k=>$v): ?>
          <option value="<?= $k ?>" <?= ($inspection['paint_condition']??'good')===$k?'selected':'' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <label class="fx-check-row">
        <input type="checkbox" name="accident_history" value="1" <?= !empty($inspection['accident_history'])?'checked':'' ?>>
        يوجد سجل حوادث سابق
      </label>

      <div class="fx-field-block">
        <label class="fx-field-label">حالة الإطارات</label>
        <select name="tire_condition" class="form-select">
          <?php foreach (['excellent'=>'ممتازة','good'=>'جيدة','fair'=>'مقبولة','poor'=>'تحتاج استبدال'] as $k=>$v): ?>
          <option value="<?= $k ?>" <?= ($inspection['tire_condition']??'good')===$k?'selected':'' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="fx-field-block">
        <label class="fx-field-label">ملاحظات ناقل الحركة</label>
        <textarea name="transmission_notes" class="form-textarea" placeholder="أداء القير، تسريبات..."><?= sanitize($inspection['transmission_notes'] ?? '') ?></textarea>
      </div>
      <div class="fx-field-block">
        <label class="fx-field-label">ملاحظات المحرك</label>
        <textarea name="engine_notes" class="form-textarea" placeholder="الضغط، الصوت، التسريبات..."><?= sanitize($inspection['engine_notes'] ?? '') ?></textarea>
      </div>
      <label class="fx-check-row">
        <input type="checkbox" name="mileage_verified" value="1" <?= !isset($inspection['mileage_verified']) || $inspection['mileage_verified'] ? 'checked' : '' ?>>
        تم التحقق من قراءة العداد ومطابقتها للسجلات
      </label>
      <div class="fx-field-block">
        <label class="fx-field-label">ملاحظات الفاحص</label>
        <textarea name="notes" class="form-textarea" placeholder="تفاصيل الفحص، العيوب، التوصيات..."><?= sanitize($inspection['notes'] ?? '') ?></textarea>
      </div>

      <div class="fx-field-block">
        <label class="fx-field-label">رفع تقرير PDF / صورة</label>
        <input type="file" name="report_pdf" accept=".pdf,.jpg,.jpeg,.png" style="width:100%;">
        <?php if (!empty($inspection['report_pdf'])): ?>
        <a href="<?= sanitize($inspection['report_pdf']) ?>" target="_blank" style="font-size:13px; color:var(--primary); margin-top:8px; display:inline-block;">عرض التقرير الحالي</a>
        <?php endif; ?>
      </div>

      <button type="submit" class="btn btn-primary fx-btn-block" style="font-size:16px; font-weight:900;">
        <i class="ph ph-check-circle"></i> اعتماد التقرير وإشعار البائع
      </button>
    </form>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
<script src="https://unpkg.com/@phosphor-icons/web"></script>
</body>
</html>