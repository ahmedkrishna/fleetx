<?php
require_once 'config.php';

// Pagination setup
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Filters
$type_filter = isset($_GET['type']) ? sanitize($_GET['type']) : 'live';
$search_query = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : [];
$make_filter = isset($_GET['make']) ? $_GET['make'] : [];
$city_filter = isset($_GET['city']) ? sanitize($_GET['city']) : '';
$fuel_filter = isset($_GET['fuel']) ? $_GET['fuel'] : [];
$year_min = isset($_GET['year_min']) ? intval($_GET['year_min']) : '';
$year_max = isset($_GET['year_max']) ? intval($_GET['year_max']) : '';
$price_min = isset($_GET['price_min']) ? intval($_GET['price_min']) : '';
$price_max = isset($_GET['price_max']) ? intval($_GET['price_max']) : '';
$mileage_min = isset($_GET['mileage_min']) ? intval($_GET['mileage_min']) : '';
$mileage_max = isset($_GET['mileage_max']) ? intval($_GET['mileage_max']) : '';

// Data Fetching
$items = [];
$total_items = 0;

$pill_stats = ['all' => 0, 'active' => 0, 'upcoming' => 0];
if ($db_connected) {
    if ($type_filter === 'live') {
        $res = $conn->query("SELECT status, COUNT(*) as c FROM auction_events GROUP BY status");
        if($res) while($r = $res->fetch_assoc()) {
             if($r['status']=='active') $pill_stats['active'] = intval($r['c']);
             if($r['status']=='upcoming') $pill_stats['upcoming'] = intval($r['c']);
        }
        $ended = $conn->query("SELECT COUNT(*) FROM auction_events WHERE status='ended'")->fetch_row()[0] ?? 0;
        $pill_stats['all'] = $pill_stats['active'] + $pill_stats['upcoming'] + intval($ended);
    } else {
        $pill_stats['all'] = intval($conn->query("SELECT COUNT(*) FROM auctions WHERE type='instant'")->fetch_row()[0] ?? 0);
        $pill_stats['active'] = $pill_stats['all']; 
        $pill_stats['upcoming'] = 0; // representing installments if needed, keeping 0 for now as it's not a real DB col
    }
}


if ($type_filter === 'live') {
    // 1. Live Auctions => Display EVENTS
    $all_events = [];
    if ($db_connected) {
        $res = $conn->query("SELECT ae.*, sc.company_name as seller_name FROM auction_events ae LEFT JOIN seller_companies sc ON ae.seller_id = sc.id");
        if ($res) while ($r = $res->fetch_assoc()) $all_events[] = $r;
    }
    
    
    // Simulate VIP/Featured for mock events
    foreach ($all_events as &$ev) {
        $ev['is_featured'] = ($ev['id'] % 2 === 0);
        $ev['is_vip'] = ($ev['is_featured'] && $ev['id'] % 4 === 0);
    }
    unset($ev);
    
    // Filtering logic for events
    $filtered = array_filter($all_events, function($e) use ($search_query, $status_filter, $city_filter) {
        if (!empty($search_query) && mb_strpos(mb_strtolower($e['title']), mb_strtolower($search_query)) === false) return false;
        if (!empty($status_filter) && is_array($status_filter) && !in_array($e['status'] ?? 'active', $status_filter)) return false;
        if (!empty($city_filter) && ($e['city'] ?? '') !== $city_filter) return false;
        return true;
    });
    
    $total_items = count($filtered);
    $items = array_slice($filtered, $offset, $limit);

} else {
    // 2. Instant Purchase => Display VEHICLES
    $all_auctions = [];
    if ($db_connected) {
        $res = $conn->query("SELECT a.*, v.make, v.model, v.year, v.mileage, v.city, v.image_url, v.fuel_type, u.full_name as seller_name 
                             FROM auctions a JOIN vehicles v ON a.vehicle_id = v.id LEFT JOIN users u ON a.seller_id = u.id WHERE a.type='instant'");
        if ($res) while ($r = $res->fetch_assoc()) $all_auctions[] = $r;
    }
    
    
    // Simulate VIP/Featured/Installments
    $temp_auctions = [];
    foreach ($all_auctions as $auc) {
        $auc['is_featured'] = ($auc['id'] % 3 === 0);
        $auc['is_vip'] = ($auc['is_featured'] && $auc['id'] % 2 === 0);
        $auc['has_installments'] = ($auc['id'] % 5 === 0);
        $temp_auctions[] = $auc;
    }
    $all_auctions = $temp_auctions;
    
    // Filtering logic for vehicles
    $filtered = array_filter($all_auctions, function($a) use ($search_query, $make_filter, $city_filter, $fuel_filter, $year_min, $year_max, $price_min, $price_max, $mileage_min, $mileage_max) {
        $title = mb_strtolower($a['title'] ?? ($a['make'].' '.$a['model']));
        if (!empty($search_query) && mb_strpos($title, mb_strtolower($search_query)) === false) return false;
        if (!empty($make_filter) && is_array($make_filter) && !in_array($a['make'] ?? '', $make_filter)) return false;
        if (!empty($city_filter) && ($a['city'] ?? '') !== $city_filter) return false;
        if (!empty($fuel_filter) && is_array($fuel_filter) && !in_array($a['fuel_type'] ?? '', $fuel_filter)) return false;
        if ($year_min && ($a['year'] ?? 0) < $year_min) return false;
        if ($year_max && ($a['year'] ?? 0) > $year_max) return false;
        $price = $a['current_price'] ?? $a['starting_price'] ?? 0;
        if ($price_min && $price < $price_min) return false;
        if ($price_max && $price > $price_max) return false;
        $mileage = (int)($a['mileage'] ?? 0);
        if ($mileage_min && $mileage < $mileage_min) return false;
        if ($mileage_max && $mileage > $mileage_max) return false;
        return true;
    });
    
    $total_items = count($filtered);
    $items = array_slice($filtered, $offset, $limit);
}
$total_pages = ceil($total_items / $limit);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="fx-build" content="<?= FLEETX_CSS_VER ?>">
  <title>المزادات والمركبات المتاحة | FleetX</title>
  <link rel="stylesheet" href="<?= fleetx_css_href() ?>">
</head>
<body class="fx-home fx-page-shell fx-page-shell--search" data-fx-build="<?= FLEETX_CSS_VER ?>">

<?php include 'includes/navbar.php'; ?>

<?php
if ($type_filter === 'instant') {
    $hero_title = 'الشراء الفوري';
    $hero_desc = 'تصفح واشترِ المركبات مباشرة بخاصية الشراء الفوري وبدون مزايدة';
    $hero_bg = 'https://images.unsplash.com/photo-1550355291-bbee04a92027?w=1600&q=80';
    $hero_eyebrow = 'بحث وتصفح';
} else {
    $hero_title = 'المزادات الحية';
    $hero_desc = 'شارك في أحدث المزادات الحية ونافس على أفضل المركبات المعروضة الآن، مزايدات لحظية تنافسية!';
    $hero_bg = 'https://images.unsplash.com/photo-1494976388531-d1058494cdd8?w=1600&q=80';
    $hero_eyebrow = 'بحث وتصفح';
}
$hero_meta_html = '<span class="fx-page-hero__chip"><i class="ph-fill ph-list-magnifying-glass"></i> ' . number_format($total_items) . ' نتيجة</span>';
if ($type_filter === 'live') {
    $hero_meta_html .= '<span class="fx-page-hero__chip"><i class="ph-fill ph-broadcast"></i> ' . $pill_stats['active'] . ' مزاد نشط</span>';
} else {
    $hero_meta_html .= '<span class="fx-page-hero__chip"><i class="ph-fill ph-lightning"></i> ' . $pill_stats['active'] . ' مركبة متاحة</span>';
}
include 'includes/page-hero.inc.php';
?>

<div class="container fx-page-body fx-page-body--overlap fx-search-page">
  <div class="auctions-layout fx-search-layout">
    <aside class="fx-filter-panel filter-sidebar">
    <div class="fx-filter-panel__head">
      <h3><i class="ph-fill ph-funnel"></i> فلاتر البحث</h3>
    </div>
    <button type="button" class="mobile-sidebar-toggle" id="fxFilterToggle" aria-expanded="false" aria-controls="fxFilterPanel">
        <span>خيارات البحث المتقدم</span>
        <i class="ph-fill ph-caret-down shine-arrow" aria-hidden="true"></i>
    </button>
    <div class="filter-sidebar-content" id="fxFilterPanel">
      <form action="auctions.php" method="GET" id="filter-form">
        <input type="hidden" name="type" value="<?= $type_filter ?>">
        
        <div class="filter-group fx-filter-group">
          <label class="filter-title fx-filter-title">بحث <i class="ph ph-magnifying-glass"></i></label>
          <div class="fx-filter-search-wrap">
            <i class="ph ph-magnifying-glass"></i>
            <input type="text" name="search" class="form-control fx-filter-input" placeholder="ابحث بالاسم أو الماركة..." value="<?= sanitize($search_query) ?>">
          </div>
        </div>
        <?php if($type_filter === 'live'): ?>
          <div class="filter-group fx-filter-group">
            <label class="filter-title fx-filter-title">حالة المزاد <i class="ph ph-activity"></i></label>
            <div class="checkbox-list">
              <label class="checkbox-item"><input type="checkbox" name="status[]" value="active" <?= in_array('active', $status_filter) ? 'checked' : '' ?>> جاري الآن</label>
              <label class="checkbox-item"><input type="checkbox" name="status[]" value="upcoming" <?= in_array('upcoming', $status_filter) ? 'checked' : '' ?>> قادم</label>
              <label class="checkbox-item"><input type="checkbox" name="status[]" value="ended" <?= in_array('ended', $status_filter) ? 'checked' : '' ?>> منتهي</label>
            </div>
          </div>
        <?php endif; ?>
        <div class="filter-group fx-filter-group">
          <label class="filter-title fx-filter-title">الماركة <i class="ph ph-car"></i></label>
          <select name="make" id="sidebarMakeSelect" class="form-control fx-filter-select" onchange="updateSidebarModels()">
            <option value="">كل الماركات</option>
            <?php
              $makes = ['تويوتا','هيونداي','نيسان','فورد','شيفروليه','كيا','جمس','مازدا','هوندا','لكزس','مرسيدس','بي ام دبليو','أودي','بورش','جيلي','شانجان','إم جي'];
              foreach($makes as $m):
            ?>
            <option value="<?=$m?>" <?= isset($_GET['make']) && $_GET['make']==$m ? 'selected' : '' ?>><?=$m?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="filter-group fx-filter-group">
          <label class="filter-title fx-filter-title">الموديل <i class="ph ph-car-profile"></i></label>
          <select name="model" id="sidebarModelSelect" class="form-control fx-filter-select">
            <option value="">كل الموديلات</option>
          </select>
        </div>
        
        <div class="filter-group fx-filter-group">
          <label class="filter-title fx-filter-title">المدينة <i class="ph ph-map-pin"></i></label>
          <select name="city" class="form-control fx-filter-select">
            <option value="">كل المدن</option>
            <?php
              $cities = ['الرياض','جدة','الدمام','مكة المكرمة','المدينة المنورة','الخبر','أبها','تبوك','الطائف','بريدة','جازان','نجران','حائل','الجبيل','الأحساء'];
              foreach($cities as $ct):
            ?>
            <option value="<?=$ct?>" <?= isset($_GET['city']) && $_GET['city']==$ct ? 'selected' : '' ?>><?=$ct?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="filter-group fx-filter-group">
          <label class="filter-title fx-filter-title">سنة الصنع <i class="ph ph-calendar"></i></label>
          <div class="fx-filter-range">
            <select name="year_min" class="form-control fx-filter-select fx-filter-select--en" dir="ltr">
              <option value="">من</option>
              <?php for($y=date('Y'); $y>=2000; $y--): ?><option value="<?=$y?>" <?= isset($_GET['year_min']) && $_GET['year_min']==$y?'selected':'' ?>><?=$y?></option><?php endfor; ?>
            </select>
            <span class="fx-filter-range-sep">-</span>
            <select name="year_max" class="form-control fx-filter-select fx-filter-select--en" dir="ltr">
              <option value="">إلى</option>
              <?php for($y=date('Y'); $y>=2000; $y--): ?><option value="<?=$y?>" <?= isset($_GET['year_max']) && $_GET['year_max']==$y?'selected':'' ?>><?=$y?></option><?php endfor; ?>
            </select>
          </div>
        </div>
        
        <div class="filter-group fx-filter-group">
          <label class="filter-title fx-filter-title">نطاق السعر (ر.س) <i class="ph ph-money"></i></label>
          <div class="fx-filter-range">
            <input type="number" name="price_min" class="form-control fx-filter-input fx-filter-input--en" placeholder="الأدنى" value="<?= isset($_GET['price_min']) ? $_GET['price_min'] : '' ?>" dir="ltr">
            <span class="fx-filter-range-sep">-</span>
            <input type="number" name="price_max" class="form-control fx-filter-input fx-filter-input--en" placeholder="الأعلى" value="<?= isset($_GET['price_max']) ? $_GET['price_max'] : '' ?>" dir="ltr">
          </div>
        </div>

        <?php if ($type_filter !== 'live'): ?>
        <div class="filter-group fx-filter-group">
          <label class="filter-title fx-filter-title">الممشى (كم) <i class="ph ph-gauge"></i></label>
          <div class="fx-filter-range">
            <input type="number" name="mileage_min" class="form-control fx-filter-input fx-filter-input--en" placeholder="من" value="<?= $mileage_min ?: '' ?>" dir="ltr">
            <span class="fx-filter-range-sep">-</span>
            <input type="number" name="mileage_max" class="form-control fx-filter-input fx-filter-input--en" placeholder="إلى" value="<?= $mileage_max ?: '' ?>" dir="ltr">
          </div>
        </div>
        <?php endif; ?>

        <?php if (isLoggedIn()): ?>
        <div class="filter-group fx-filter-group">
          <button type="button" class="btn btn-outline btn-sm" style="width:100%;" onclick="saveCurrentSearch()"><i class="ph ph-bookmark-simple"></i> حفظ البحث</button>
          <div id="saved-searches-list" style="margin-top:10px;font-size:13px;"></div>
        </div>
        <?php endif; ?>

        <script>
        const carModels = {
          'تويوتا': ['كامري', 'كورولا', 'لاند كروزر', 'هايلوكس', 'يارس', 'راف فور', 'فورتشنر', 'أفالون'],
          'هيونداي': ['إلنترا', 'سوناتا', 'أكسنت', 'توسان', 'سنتافي', 'أزيرا', 'كريتا'],
          'نيسان': ['ألتيما', 'صني', 'باترول', 'مكسيما', 'باثفايندر', 'إكستريل', 'كيكس'],
          'فورد': ['تورس', 'إكسبلورر', 'إكسبدشن', 'F-150', 'موستانج', 'تيريتوري'],
          'شيفروليه': ['تاهو', 'سوبربان', 'إمبالا', 'سيلفرادو', 'كابتيفا', 'ماليبو'],
          'كيا': ['أوبتيما / K5', 'سبورتاج', 'سورينتو', 'ريو', 'سيراتو', 'تيلورايد'],
          'جمس': ['يوكون', 'سييرا', 'تيرين', 'أكاديا'],
          'مازدا': ['مازدا 6', 'مازدا 3', 'CX-5', 'CX-9', 'CX-30'],
          'هوندا': ['أكورد', 'سيفيك', 'CR-V', 'بايلوت', 'HR-V'],
          'لكزس': ['ES', 'LX', 'RX', 'IS', 'NX', 'LS'],
          'مرسيدس': ['S-Class', 'E-Class', 'C-Class', 'G-Class', 'GLE', 'GLC'],
          'بي ام دبليو': ['الفئة السابعة', 'الفئة الخامسة', 'الفئة الثالثة', 'X5', 'X6', 'X7'],
          'أودي': ['A8', 'A6', 'A4', 'Q7', 'Q8', 'Q5'],
          'بورش': ['كايين', 'باناميرا', 'ماكان', '911'],
          'جيلي': ['كولراي', 'توجيلا', 'إمجراند', 'مونجارو', 'أوكافانجو'],
          'شانجان': ['CS95', 'CS85', 'CS75', 'UNI-T', 'UNI-K', 'ألسڤن'],
          'إم جي': ['MG RX5', 'MG HS', 'MG 6', 'MG ZS', 'MG GT']
        };

        function updateSidebarModels() {
          const makeSelect = document.getElementById('sidebarMakeSelect');
          const modelSelect = document.getElementById('sidebarModelSelect');
          const selectedMake = makeSelect.value;
          
          modelSelect.innerHTML = '<option value="">كل الموديلات</option>';
          
          if (selectedMake && carModels[selectedMake]) {
            carModels[selectedMake].forEach(function(model) {
              const option = document.createElement('option');
              option.value = model;
              option.textContent = model;
              modelSelect.appendChild(option);
            });
          }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            if(document.getElementById('sidebarMakeSelect') && document.getElementById('sidebarMakeSelect').value !== "") {
                updateSidebarModels();
                const urlParams = new URLSearchParams(window.location.search);
                const modelParam = urlParams.get('model');
                if(modelParam) {
                    const modelSelect = document.getElementById('sidebarModelSelect');
                    if(modelSelect.querySelector('option[value="'+modelParam+'"]')) {
                        modelSelect.value = modelParam;
                    }
                }
            }
        });
        </script>
        <div class="fx-filter-actions">
          <button type="submit" class="btn btn-primary fx-filter-submit">تطبيق الفلاتر</button>
          <a href="auctions.php?type=<?= $type_filter ?>" class="btn btn-outline fx-filter-reset">إعادة ضبط</a>
        </div>
      </form>
    </div></aside>
    
    <main class="fx-search-main">
      <div class="fx-auctions-mobile-type" role="navigation" aria-label="نوع العرض">
        <a href="auctions.php?type=live" class="fx-auctions-mobile-type__link<?= $type_filter === 'live' ? ' is-active' : '' ?>">
          <i class="ph-fill ph-broadcast"></i> المزادات الحية
        </a>
        <a href="auctions.php?type=instant" class="fx-auctions-mobile-type__link<?= $type_filter === 'instant' ? ' is-active' : '' ?>">
          <i class="ph-fill ph-lightning"></i> الشراء الفوري
        </a>
      </div>
      <div class="fx-toolbar fx-search-toolbar">
        <div class="fx-pills filter-pills-group">
            <a href="auctions.php?type=<?= $type_filter ?>" class="fx-pill fx-pill-link active">
                الكل <span class="fx-pill-count"><?= $pill_stats['all'] ?></span>
            </a>
            <a href="auctions.php?type=<?= $type_filter ?>&status[]=active" class="fx-pill fx-pill-link">
                <?= $type_filter === 'instant' ? 'مباشر' : 'جاري' ?> <span class="fx-pill-count"><?= $pill_stats['active'] ?></span>
            </a>
            <a href="auctions.php?type=<?= $type_filter ?>&status[]=upcoming" class="fx-pill fx-pill-link">
                <?= $type_filter === 'instant' ? 'تقسيط' : 'قادم' ?> <span class="fx-pill-count"><?= $pill_stats['upcoming'] ?></span>
            </a>
        </div>
        <div class="fx-search-sort">
          <label>ترتيب حسب:</label>
          <select name="sort_by" form="filter-form" onchange="document.getElementById('filter-form').submit();" class="form-select fx-search-sort-select">
            <option value="all" <?= isset($_GET['sort_by']) && $_GET['sort_by'] == 'all' ? 'selected' : '' ?>>الكل</option>
            <option value="newest" <?= isset($_GET['sort_by']) && $_GET['sort_by'] == 'newest' ? 'selected' : '' ?>>الأحدث</option>
            <option value="price_asc" <?= isset($_GET['sort_by']) && $_GET['sort_by'] == 'price_asc' ? 'selected' : '' ?>>الأقل سعراً</option>
            <option value="price_desc" <?= isset($_GET['sort_by']) && $_GET['sort_by'] == 'price_desc' ? 'selected' : '' ?>>الأكثر سعراً</option>
            <option value="views_desc" <?= isset($_GET['sort_by']) && $_GET['sort_by'] == 'views_desc' ? 'selected' : '' ?>>الأكثر مشاهدة</option>
          </select>
        </div>
      </div>

      <?php if(empty($items)): ?>
        <div class="fx-empty-state fx-empty-state--search">
          <i class="ph-fill ph-warning-circle"></i>
          <h3>لا توجد نتائج مطابقة</h3>
          <p>حاول تغيير فلاتر البحث والمحاولة مجدداً.</p>
          <a href="auctions.php?type=<?= $type_filter ?>" class="btn btn-primary btn--pill fx-empty-state__cta">إعادة ضبط الفلاتر</a>
        </div>
      <?php else: ?>
        <div class="fx-listing-grid">
          <?php foreach ($items as $item):
            $is_live = ($type_filter === 'live');
            $link = $is_live ? '/event.php?id=' . $item['id'] : '/vehicle-details.php?id=' . $item['id'];
            $title = $item['title'] ?? ($item['make'] . ' ' . $item['model'] . ' ' . $item['year']);
            $card_status = $item['status'] ?? 'active';
            $fx_card = [
              'id' => $item['id'],
              'href' => $link,
              'title' => $title,
              'image_url' => $item['image_url'] ?? '',
              'make' => trim(($item['make'] ?? '') . ' ' . ($item['model'] ?? '')),
              'type' => $is_live ? 'live' : 'instant',
              'status' => $card_status,
              'city' => $item['city'] ?? 'الرياض',
              'price' => intval($item['current_price'] ?? $item['starting_price'] ?? 50000),
              'price_label' => $is_live ? 'يبدأ من' : 'السعر',
              'is_vip' => !empty($item['is_vip']),
              'is_featured' => !empty($item['is_featured']),
              'show_installment' => !$is_live && !empty($item['has_installments']),
              'extra_class' => 'animate-card',
            ];
            if ($is_live) {
              $fx_card['seller'] = $item['seller_name'] ?? 'الوطنية للتأجير';
              $fx_card['vehicles_count'] = rand(20, 150);
              $fx_card['end_time'] = $item['end_time'] ?? date('Y-m-d H:i:s', strtotime('+2 days'));
              if ($card_status === 'active') {
                $fx_card['timer_data'] = timeLeft($fx_card['end_time']);
              }
            } else {
              $fx_card['mileage'] = intval($item['mileage'] ?? 0);
              $fx_card['year'] = $item['year'] ?? '2023';
              $fx_card['end_time'] = $item['end_time'] ?? date('Y-m-d H:i:s', strtotime('+3 days'));
            }
            include 'includes/fx-auction-card.inc.php';
          endforeach; ?>
        </div>

        <?php if ($total_pages > 1): ?>
        <nav class="fx-pagination" aria-label="صفحات المزادات">
          <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?type=<?= $type_filter ?>&page=<?= $i ?>" class="fx-pagination__link<?= $i === $page ? ' is-active' : '' ?>"><?= $i ?></a>
          <?php endfor; ?>
        </nav>
        <?php endif; ?>
        
      <?php endif; ?>
    </main>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
<script>
(function () {
  var toggle = document.getElementById('fxFilterToggle');
  var panel = document.getElementById('fxFilterPanel');
  var sidebar = document.querySelector('.fx-filter-panel.filter-sidebar');
  if (!toggle || !sidebar) return;
  function setExpanded(open) {
    sidebar.classList.toggle('expanded', open);
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  }
  toggle.addEventListener('click', function () {
    setExpanded(!sidebar.classList.contains('expanded'));
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && sidebar.classList.contains('expanded')) setExpanded(false);
  });
  if (panel) {
    var form = panel.querySelector('form');
    if (form) form.addEventListener('submit', function () { setExpanded(false); });
  }
})();
</script>
<?php if (isLoggedIn()): ?>
<script>
async function loadSavedSearches() {
  const el = document.getElementById('saved-searches-list');
  if (!el) return;
  try {
    const res = await fetch('/api/saved-searches.php');
    const data = await res.json();
    if (!data.success || !data.items.length) { el.innerHTML = '<span style="color:var(--text-muted)">لا توجد عمليات بحث محفوظة</span>'; return; }
    el.innerHTML = data.items.map(s => {
      const q = new URLSearchParams(s.filters || {}).toString();
      return `<div style="margin:6px 0;"><a href="/auctions.php?${q}" style="color:var(--primary);font-weight:700;">${s.name}</a> <button type="button" style="border:none;background:none;color:#999;cursor:pointer" onclick="deleteSavedSearch(${s.id})">×</button></div>`;
    }).join('');
  } catch (e) { el.textContent = ''; }
}
async function saveCurrentSearch() {
  const form = document.getElementById('filter-form');
  const fd = new FormData(form);
  const filters = {};
  fd.forEach((v, k) => { if (v) filters[k] = v; });
  const name = prompt('اسم البحث المحفوظ:', 'بحثي') || 'بحث محفوظ';
  const res = await fetch('/api/saved-searches.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ name, filters }) });
  const data = await res.json();
  if (data.success && typeof showToast === 'function') showToast('تم حفظ البحث', 'success');
  loadSavedSearches();
}
async function deleteSavedSearch(id) {
  await fetch('/api/saved-searches.php', { method: 'DELETE', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ id }) });
  loadSavedSearches();
}
loadSavedSearches();
</script>
<?php endif; ?>