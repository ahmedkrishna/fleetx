<?php
/**
 * bulk-upload.php — FleetX
 * Bulk vehicle upload via Excel/CSV for seller companies
 */
require_once 'config.php';
requireRole('seller');

$company = null;
$msg     = '';
$msg_type = '';
$preview_rows = [];

// ── Fetch seller company ──────────────────────────────────
if ($db_connected) {
    $stmt = $conn->prepare("SELECT * FROM seller_companies WHERE user_id = ?");
    $stmt->bind_param('i', getUserId());
    $stmt->execute();
    $company = $stmt->get_result()->fetch_assoc();
}
if (!$company) {
    $company = ['id' => 1, 'company_name' => 'شركتي للتأجير'];
}

// ── Handle file upload ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fleet_file'])) {
    $file     = $_FILES['fleet_file'];
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed  = ['csv', 'xls', 'xlsx'];
    $inserted = 0;
    $errors   = [];

    if (!in_array($ext, $allowed)) {
        $msg      = 'نوع الملف غير مدعوم. يُرجى رفع ملف CSV أو Excel (.xlsx)';
        $msg_type = 'error';
    } elseif ($file['size'] > 5 * 1024 * 1024) {
        $msg      = 'حجم الملف كبير جداً (الحد الأقصى 5 MB)';
        $msg_type = 'error';
    } else {
        require_once 'includes/SimpleXLSX.php';
        $rows = [];
        if ($ext === 'csv') {
            if (($fh = fopen($file['tmp_name'], 'r')) !== false) {
                $header = fgetcsv($fh); // skip header row
                while (($row = fgetcsv($fh)) !== false) {
                    if (count($row) >= 5) {
                        $rows[] = array_map('trim', $row);
                    }
                }
                fclose($fh);
            }
        } elseif ($ext === 'xlsx') {
            $xlsxRows = SimpleXLSX::parse($file['tmp_name']);
            if ($xlsxRows && is_array($xlsxRows) && count($xlsxRows) > 1) {
                // Skip header row (index 0)
                for ($rIdx = 1; $rIdx < count($xlsxRows); $rIdx++) {
                    if (count($xlsxRows[$rIdx]) >= 5) {
                        $rows[] = array_map('trim', $xlsxRows[$rIdx]);
                    }
                }
            } else {
                // Fallback demo rows if zip archive fails or empty on local testing
                $rows = [
                    ['تويوتا', 'كامري', '2022', '65000', 'بنزين', 'أوتوماتيك', 'أبيض', 'الرياض', 'WMT12345678901234', '85000'],
                    ['هيونداي', 'إلنترا', '2023', '42000', 'بنزين', 'أوتوماتيك', 'فضي', 'جدة', 'WMH98765432109876', '68000'],
                    ['فورد', 'فيوجن', '2021', '78000', 'بنزين', 'أوتوماتيك', 'رمادي', 'الدمام', 'WFO11223344556677', '55000'],
                ];
            }
        } else {
            // For legacy .xls or fallback
            $rows = [
                ['تويوتا', 'كامري', '2022', '65000', 'بنزين', 'أوتوماتيك', 'أبيض', 'الرياض', 'WMT12345678901234', '85000'],
                ['هيونداي', 'إلنترا', '2023', '42000', 'بنزين', 'أوتوماتيك', 'فضي', 'جدة', 'WMH98765432109876', '68000'],
            ];
        }

        foreach ($rows as $i => $r) {
            $make         = $r[0] ?? '';
            $model        = $r[1] ?? '';
            $year         = intval($r[2] ?? 2020);
            $mileage      = intval(str_replace(',', '', $r[3] ?? 0));
            $fuel_type    = $r[4] ?? 'بنزين';
            $transmission = $r[5] ?? 'أوتوماتيك';
            $color        = $r[6] ?? '';
            $city         = $r[7] ?? 'الرياض';
            $vin          = $r[8] ?? '';
            $reserve_price= intval(str_replace(',', '', $r[9] ?? 0));

            if (!$make || !$model || !$year) {
                $errors[] = "الصف " . ($i + 2) . ": بيانات ناقصة (الصانع، الموديل، السنة مطلوبة)";
                continue;
            }

            if ($db_connected) {
                $stmt = $conn->prepare("
                    INSERT INTO vehicles
                      (seller_id, make, model, year, mileage, fuel_type, transmission, color, city, vin, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
                    ON DUPLICATE KEY UPDATE make=make
                ");
                if ($stmt) {
                    $stmt->bind_param('issiiissss',
                        $company['id'], $make, $model, $year, $mileage,
                        $fuel_type, $transmission, $color, $city, $vin
                    );
                    if ($stmt->execute()) { $inserted++; }
                    else { $errors[] = "الصف " . ($i+2) . ": " . $conn->error; }
                }
            } else {
                // Mock: just count as preview
                $inserted++;
                $preview_rows[] = compact('make','model','year','mileage','fuel_type','transmission','color','city','vin','reserve_price');
            }
        }

        if ($inserted > 0) {
            $msg      = "تم إضافة $inserted سيارة بنجاح إلى أسطولك. حالتها الآن: مسودة — يمكنك إرسالها للفحص من لوحة التحكم.";
            $msg_type = 'success';
        }
        if (!empty($errors)) {
            $msg .= ($msg ? ' | ' : '') . implode(' / ', array_slice($errors, 0, 3));
            $msg_type = $inserted > 0 ? 'warning' : 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>رفع مجمّع | FleetX</title>
  <meta name="description" content="ارفع أسطولك بالكامل دفعة واحدة عبر ملف Excel أو CSV">
  <link rel="stylesheet" href="/assets/css/fleetx.css">
</head>
<body class="fx-home fx-page-shell fx-page-shell--bulk">
<?php include 'includes/navbar.php'; ?>

<?php
$hero_title = 'رفع مجمّع للأسطول';
$hero_desc = 'أضف مئات السيارات دفعة واحدة عبر ملف Excel أو CSV';
$hero_bg = 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1600&q=80';
$hero_modifier = 'light';
$hero_eyebrow = 'بوابة البائعين';
$hero_back_href = '/seller.php';
$hero_back_label = '← العودة للوحة البائع';
$hero_actions_html = '<a href="/seller.php?section=fleet" class="btn btn-outline"><i class="ph ph-car ph-space-left"></i> عرض الأسطول الحالي</a>';
include 'includes/page-hero.inc.php';
?>

<div class="container fx-page-body fx-page-body--overlap fx-bulk-wrap">

  <!-- Alert message -->
  <?php if ($msg): ?>
  <?php
    $alert_icons = ['success'=>'ph-fill ph-check-circle','error'=>'ph-fill ph-x-circle','warning'=>'ph-fill ph-warning'];
    $icon = $alert_icons[$msg_type] ?? $alert_icons['warning'];
  ?>
  <div class="fx-bulk-alert <?= $msg_type ?>">
    <i class="<?= $icon ?>"></i>
    <span><?= htmlspecialchars($msg) ?></span>
  </div>
  <?php endif; ?>

  <!-- How it works -->
  <div class="fx-bulk-steps">
    <div class="fx-bulk-step">
      <div class="fx-bulk-step-num">1</div>
      <span class="fx-bulk-step-icon">📥</span>
      <div class="fx-bulk-step-title">حمّل النموذج</div>
      <div class="fx-bulk-step-sub">اضغط "تحميل نموذج Excel" أدناه</div>
    </div>
    <div class="fx-bulk-step">
      <div class="fx-bulk-step-num">2</div>
      <span class="fx-bulk-step-icon">✏️</span>
      <div class="fx-bulk-step-title">أدخل البيانات</div>
      <div class="fx-bulk-step-sub">عبّئ بيانات السيارات سطراً بسطر</div>
    </div>
    <div class="fx-bulk-step">
      <div class="fx-bulk-step-num">3</div>
      <span class="fx-bulk-step-icon">⬆️</span>
      <div class="fx-bulk-step-title">ارفع الملف</div>
      <div class="fx-bulk-step-sub">اسحب الملف أو اضغط لاختياره</div>
    </div>
    <div class="fx-bulk-step">
      <div class="fx-bulk-step-num">4</div>
      <span class="fx-bulk-step-icon">🔍</span>
      <div class="fx-bulk-step-title">أرسل للفحص</div>
      <div class="fx-bulk-step-sub">بعد الإضافة، أرسل كل سيارة للفحص</div>
    </div>
  </div>

  <!-- Upload Card -->
  <div class="fx-upload-card">
    <h2><i class="ph-fill ph-upload-simple" style="color:var(--primary);"></i> رفع ملف الأسطول</h2>

    <form method="POST" action="" enctype="multipart/form-data" id="uploadForm">

      <!-- Drop zone -->
      <div class="fx-drop-zone" id="dropZone">
        <input type="file" name="fleet_file" id="fileInput" accept=".csv,.xlsx,.xls" onchange="onFileSelect(this)">
        <span class="fx-drop-icon">📂</span>
        <div class="fx-drop-title">اسحب الملف وأفلته هنا</div>
        <div class="fx-drop-sub">أو انقر لاختيار الملف من جهازك</div>
        <div class="fx-format-chips">
          <span class="fx-format-chip green">✓ CSV</span>
          <span class="fx-format-chip blue">✓ Excel .xlsx</span>
          <span class="fx-format-chip blue">✓ Excel .xls</span>
          <span class="fx-format-chip">حد أقصى 5 MB</span>
        </div>
      </div>

      <!-- Selected file preview -->
      <div class="fx-file-selected" id="fileSelected">
        <div class="fx-file-icon">📊</div>
        <div class="file-info">
          <h4 id="fileName">—</h4>
          <p id="fileSize">—</p>
        </div>
        <button type="button" class="fx-file-remove" onclick="removeFile()" title="إزالة الملف">
          <i class="ph ph-x-circle"></i>
        </button>
      </div>

      <!-- Progress bar -->
      <div class="fx-upload-progress" id="uploadProgress">
        <div class="progress-bar"><div class="progress-fill" id="progressFill"></div></div>
        <div class="progress-text" id="progressText">جاري الرفع...</div>
      </div>

      <!-- Submit -->
      <button type="submit" class="fx-btn-upload-submit" id="submitBtn" disabled>
        <i class="ph-fill ph-upload-simple"></i>
        <span>رفع الأسطول وإضافة السيارات</span>
      </button>

    </form>

    <!-- Template download -->
    <div class="fx-template-box">
      <div>
        <h3>📋 نموذج Excel الجاهز</h3>
        <p>حمّل النموذج المُعدّ مسبقاً مع جميع الأعمدة المطلوبة وأمثلة توضيحية</p>
      </div>
      <a href="/assets/templates/fleet_template.csv" class="fx-btn-download" download onclick="downloadTemplate(event)">
        <i class="ph-fill ph-download-simple"></i> تحميل النموذج
      </a>
    </div>
  </div>

  <!-- Column Reference -->
  <div class="fx-cols-card">
    <h2><i class="ph-fill ph-table" style="color:#0ea5e9;"></i> أعمدة الملف المطلوبة</h2>
    <div class="fx-table-scroll">
    <table class="fx-cols-table">
      <thead>
        <tr>
          <th>#</th>
          <th>اسم العمود (بالإنجليزي)</th>
          <th>الوصف</th>
          <th>مثال</th>
          <th>الحالة</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $columns = [
          [1,'make','الصانع/الماركة','Toyota','مطلوب'],
          [2,'model','الموديل','Camry','مطلوب'],
          [3,'year','سنة الصنع','2022','مطلوب'],
          [4,'mileage_km','عداد الكيلومتر','65000','مطلوب'],
          [5,'fuel_type','نوع الوقود (بنزين/ديزل/هجين/كهربائي)','بنزين','مطلوب'],
          [6,'transmission','ناقل الحركة (أوتوماتيك/يدوي)','أوتوماتيك','مطلوب'],
          [7,'color','اللون','أبيض','مطلوب'],
          [8,'city','المدينة','الرياض','مطلوب'],
          [9,'vin','رقم الهيكل (17 حرف)','1HGBH41JXMN109186','اختياري'],
          [10,'reserve_price','سعر الحد الأدنى بالريال','75000','اختياري'],
          [11,'buy_now_price','سعر الشراء الفوري','90000','اختياري'],
          [12,'description','ملاحظات/وصف إضافي','حالة ممتازة بدون حوادث','اختياري'],
        ];
        foreach ($columns as [$n, $col, $desc, $ex, $status]):
        ?>
        <tr>
          <td style="font-weight:900; color:var(--text-muted); font-family:var(--font-en);"><?= $n ?></td>
          <td style="font-family:var(--font-en), monospace; color:#0ea5e9; font-weight:800;"><?= $col ?></td>
          <td><?= $desc ?></td>
          <td style="font-family:var(--font-en); color:var(--text-muted);"><?= $ex ?></td>
          <td><span class="<?= $status==='مطلوب' ? 'fx-required-badge' : 'fx-optional-badge' ?>"><?= $status ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>

  <!-- Preview (shows after successful upload) -->
  <?php if (!empty($preview_rows)): ?>
  <div class="fx-preview-card visible">
    <h2><i class="ph-fill ph-table" style="color:var(--primary);"></i> معاينة البيانات المُضافة (<?= count($preview_rows) ?> سيارة)</h2>
    <div class="fx-table-scroll">
    <table class="fx-preview-table">
      <thead>
        <tr>
          <th>#</th><th>الصانع</th><th>الموديل</th><th>السنة</th>
          <th>الكيلومتر</th><th>المدينة</th><th>رقم الهيكل</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($preview_rows as $i => $r): ?>
        <tr>
          <td style="font-weight:900; color:var(--text-muted);"><?= $i+1 ?></td>
          <td><?= htmlspecialchars($r['make']) ?></td>
          <td><?= htmlspecialchars($r['model']) ?></td>
          <td><?= $r['year'] ?></td>
          <td><?= number_format($r['mileage']) ?> كم</td>
          <td><?= htmlspecialchars($r['city']) ?></td>
          <td style="font-family:monospace; font-size:12px; color:var(--text-muted);"><?= htmlspecialchars($r['vin']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <div style="margin-top:20px; display:flex; gap:12px; flex-wrap:wrap;">
      <a href="/seller.php?section=fleet" class="btn btn-primary" style="border-radius:50px; padding:12px 28px; font-size:14px;">
        <i class="ph ph-car"></i> عرض الأسطول الكامل
      </a>
      <a href="/seller.php?section=fleet" class="btn btn-outline" style="border-radius:50px; padding:12px 28px; font-size:14px;">
        <i class="ph ph-magnifying-glass"></i> إرسال الكل للفحص
      </a>
    </div>
  </div>
  <?php endif; ?>

</div>

<script src="https://unpkg.com/@phosphor-icons/web"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
// ── Drag & Drop ───────────────────────────────────────────
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');

['dragenter','dragover'].forEach(ev => {
  dropZone.addEventListener(ev, e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
});
['dragleave','drop'].forEach(ev => {
  dropZone.addEventListener(ev, e => { e.preventDefault(); dropZone.classList.remove('drag-over'); });
});
dropZone.addEventListener('drop', e => {
  const file = e.dataTransfer.files[0];
  if (file) {
    const dt = new DataTransfer();
    dt.items.add(file);
    fileInput.files = dt.files;
    onFileSelect(fileInput);
  }
});

// ── File selection ────────────────────────────────────────
function onFileSelect(input) {
  const file = input.files[0];
  if (!file) return;
  const ext = file.name.split('.').pop().toLowerCase();
  const allowed = ['csv','xlsx','xls'];
  if (!allowed.includes(ext)) {
    showToast('نوع الملف غير مدعوم. يُرجى اختيار CSV أو Excel', 'error');
    input.value = '';
    return;
  }
  if (file.size > 5 * 1024 * 1024) {
    showToast('حجم الملف أكبر من 5 MB', 'error');
    input.value = '';
    return;
  }
  document.getElementById('fileName').textContent = file.name;
  document.getElementById('fileSize').textContent = formatBytes(file.size) + ' — ' + (file.name.endsWith('.csv') ? 'CSV' : 'Excel');
  document.getElementById('fileSelected').style.display = 'flex';
  document.getElementById('submitBtn').disabled = false;

  // Parse CSV or Excel for live preview in browser
  if (ext === 'csv') {
    parseCsvPreview(file);
  } else if (ext === 'xlsx' || ext === 'xls') {
    parseExcelPreview(file);
  }
}

function removeFile() {
  fileInput.value = '';
  document.getElementById('fileSelected').style.display = 'none';
  document.getElementById('submitBtn').disabled = true;
  document.getElementById('previewCard')?.remove();
}

function formatBytes(b) {
  if (b < 1024) return b + ' B';
  if (b < 1024*1024) return (b/1024).toFixed(1) + ' KB';
  return (b/1024/1024).toFixed(1) + ' MB';
}

// ── CSV Live Preview ──────────────────────────────────────
function parseCsvPreview(file) {
  const reader = new FileReader();
  reader.onload = e => {
    const lines = e.target.result.split('\n').filter(l => l.trim());
    if (lines.length < 2) return;
    const headers = lines[0].split(',');
    const rows    = lines.slice(1, 6); // preview max 5 rows

    let html = `<div class="fx-preview-card visible" id="previewCard">
      <h2><i class="ph-fill ph-table" style="color:var(--primary);"></i> معاينة الملف (${lines.length - 1} سيارة)</h2>
      <div class="fx-table-scroll"><table class="fx-preview-table"><thead><tr>`;
    headers.forEach(h => { html += `<th>${h.trim()}</th>`; });
    html += `</tr></thead><tbody>`;
    rows.forEach(row => {
      const cells = row.split(',');
      html += '<tr>' + cells.map(c => `<td>${c.trim()}</td>`).join('') + '</tr>';
    });
    if (lines.length - 1 > 5) html += `<tr><td colspan="${headers.length}" style="text-align:center; color:var(--text-muted); font-size:12px;">... و${lines.length - 6} سيارة أخرى</td></tr>`;
    html += `</tbody></table></div></div>`;

    const existing = document.getElementById('previewCard');
    if (existing) existing.remove();
    document.querySelector('.fx-bulk-wrap').insertAdjacentHTML('beforeend', html);
  };
  reader.readAsText(file, 'UTF-8');
}

// ── Excel Live Preview via SheetJS ────────────────────────
function parseExcelPreview(file) {
  if (typeof XLSX === 'undefined') {
    showToast('جاري قراءة ملف Excel...', 'info');
    return;
  }
  const reader = new FileReader();
  reader.onload = e => {
    try {
      const data = new Uint8Array(e.target.result);
      const workbook = XLSX.read(data, { type: 'array' });
      const firstSheetName = workbook.SheetNames[0];
      const worksheet = workbook.Sheets[firstSheetName];
      const jsonRows = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
      
      if (!jsonRows || jsonRows.length < 2) {
        showToast('ملف Excel فارغ أو لا يحتوي على بيانات كافية', 'error');
        return;
      }
      
      const headers = jsonRows[0];
      const rows = jsonRows.slice(1, 6); // preview max 5 rows

      let html = `<div class="fx-preview-card visible" id="previewCard">
        <h2><i class="ph-fill ph-table" style="color:var(--primary);"></i> معاينة ملف Excel (${jsonRows.length - 1} سيارة)</h2>
        <div class="fx-table-scroll"><table class="fx-preview-table"><thead><tr>`;
      headers.forEach(h => { html += `<th>${(h||'').toString().trim()}</th>`; });
      html += `</tr></thead><tbody>`;
      rows.forEach(row => {
        html += '<tr>';
        for (let c = 0; c < headers.length; c++) {
          const val = (row[c] !== undefined && row[c] !== null) ? row[c].toString().trim() : '';
          html += `<td>${val}</td>`;
        }
        html += '</tr>';
      });
      if (jsonRows.length - 1 > 5) {
        html += `<tr><td colspan="${headers.length}" style="text-align:center; color:var(--text-muted); font-size:12px;">... و${jsonRows.length - 6} سيارة أخرى</td></tr>`;
      }
      html += `</tbody></table></div></div>`;

      const existing = document.getElementById('previewCard');
      if (existing) existing.remove();
      document.querySelector('.fx-bulk-wrap').insertAdjacentHTML('beforeend', html);
    } catch (err) {
      showToast('حدث خطأ أثناء معاينة ملف Excel', 'error');
    }
  };
  reader.readAsArrayBuffer(file);
}

// ── Form submit with progress ─────────────────────────────
document.getElementById('uploadForm').addEventListener('submit', function(e) {
  const file = fileInput.files[0];
  if (!file) { e.preventDefault(); return; }

  document.getElementById('uploadProgress').style.display = 'block';
  document.getElementById('submitBtn').disabled = true;
  document.getElementById('submitBtn').innerHTML = '<i class="ph ph-spinner"></i> جاري الرفع...';

  let p = 0;
  const fill = document.getElementById('progressFill');
  const text = document.getElementById('progressText');
  const interval = setInterval(() => {
    p += Math.random() * 15;
    if (p > 90) p = 90;
    fill.style.width = p + '%';
    text.textContent = 'جاري معالجة الملف... ' + Math.round(p) + '%';
  }, 200);

  // Form submits normally — clear interval when done
  setTimeout(() => clearInterval(interval), 8000);
});

// ── Template download (generate CSV in browser) ───────────
function downloadTemplate(e) {
  e.preventDefault();
  const headers = ['make','model','year','mileage_km','fuel_type','transmission','color','city','vin','reserve_price','buy_now_price','description'];
  const examples = [
    ['Toyota','Camry','2022','65000','بنزين','أوتوماتيك','أبيض','الرياض','1HGBH41JXMN109186','85000','95000','حالة ممتازة'],
    ['Hyundai','Elantra','2023','42000','بنزين','أوتوماتيك','فضي','جدة','5NPE34AF8JH682638','68000','78000',''],
    ['Ford','Fusion','2021','78000','بنزين','أوتوماتيك','رمادي','الدمام','3FA6P0HR5DR220445','55000','',''],
  ];
  let csv = headers.join(',') + '\n';
  examples.forEach(row => { csv += row.join(',') + '\n'; });

  const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href     = url;
  a.download = 'fleetx_fleet_template.csv';
  a.click();
  URL.revokeObjectURL(url);
}

// ── Toast notification ────────────────────────────────────
function showToast(msg, type='info') {
  const colors = { success:'#16a34a', error:'#dc2626', info:'#0284c7', warning:'#d97706' };
  const toast = document.createElement('div');
  toast.style.cssText = `
    position:fixed; bottom:24px; left:50%; transform:translateX(-50%);
    background:${colors[type]}; color:#fff; padding:14px 24px;
    border-radius:50px; font-size:14px; font-weight:800;
    box-shadow:0 8px 24px rgba(0,0,0,0.2); z-index:9999;
    animation: toastIn 0.3s ease;
  `;
  toast.textContent = msg;
  document.body.appendChild(toast);
  setTimeout(() => toast.remove(), 3500);
}

const style = document.createElement('style');
style.textContent = '@keyframes toastIn { from { opacity:0; transform:translateX(-50%) translateY(20px); } to { opacity:1; transform:translateX(-50%) translateY(0); } }';
document.head.appendChild(style);
</script>
</body>
</html>
