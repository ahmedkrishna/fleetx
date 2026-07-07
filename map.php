<?php
require_once 'config.php';

// Filters
$type_filter = $_GET['type'] ?? '';
$status_filter = isset($_GET['status']) ? (array)$_GET['status'] : [];
$search_query = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$seller_query = isset($_GET['seller']) ? sanitize($_GET['seller']) : '';
$make_filter = isset($_GET['make']) ? $_GET['make'] : [];
$year_min = isset($_GET['year_min']) ? intval($_GET['year_min']) : '';
$year_max = isset($_GET['year_max']) ? intval($_GET['year_max']) : '';
$price_min = isset($_GET['price_min']) ? intval($_GET['price_min']) : '';
$price_max = isset($_GET['price_max']) ? intval($_GET['price_max']) : '';
$city_filter = isset($_GET['city']) ? sanitize($_GET['city']) : '';
$fuel_filter = isset($_GET['fuel']) ? $_GET['fuel'] : [];

// Fetch active auctions/vehicles for map pins
$map_vehicles = [];
if ($db_connected) {
    $res = $conn->query("SELECT a.id, a.title, a.type, a.current_price, v.make, v.model, v.image_url, v.city, v.year, v.fuel_type, u.full_name as seller_name 
                          FROM auctions a 
                          JOIN vehicles v ON a.vehicle_id = v.id 
                          LEFT JOIN users u ON a.seller_id = u.id
                          WHERE a.status='active'");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $map_vehicles[] = $row;
        }
    }
}

// Apply Filters
if (!empty($map_vehicles)) {
    $filtered_map = array_filter($map_vehicles, function($a) use ($search_query, $seller_query, $make_filter, $year_min, $year_max, $price_min, $price_max, $city_filter, $fuel_filter) {
        if (!empty($search_query)) {
            $search_lower = mb_strtolower($search_query);
            $title_lower = mb_strtolower($a['title'] ?? ($a['make'].' '.$a['model']));
            if (mb_strpos($title_lower, $search_lower) === false) return false;
        }
        if (!empty($seller_query)) {
            $seller_name = mb_strtolower($a['seller_name'] ?? '');
            if (mb_strpos($seller_name, mb_strtolower($seller_query)) === false) return false;
        }
        if (!empty($city_filter) && $a['city'] !== $city_filter) return false;
        if (!empty($make_filter) && is_array($make_filter) && !in_array($a['make'], $make_filter)) return false;
        if (!empty($fuel_filter) && is_array($fuel_filter) && !in_array($a['fuel_type'] ?? '', $fuel_filter)) return false;
        
        $year = intval($a['year'] ?? 0);
        if ($year_min && $year < $year_min) return false;
        if ($year_max && $year > $year_max) return false;
        
        $price = intval($a['current_price'] ?? $a['starting_price'] ?? 0);
        if ($price_min && $price < $price_min) return false;
        if ($price_max && $price > $price_max) return false;
        
        return true;
    });
    $map_vehicles = array_values($filtered_map);
}

// Fallback mock vehicles with coordinates if database empty/not connected
if (empty($map_vehicles)) {
    $map_vehicles = [
        [
            'id' => 1,
            'title' => 'تويوتا كامري 2.5L Prestige',
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2023,
            'current_price' => 85000,
            'city' => 'الرياض',
            'lat' => 24.7136,
            'lng' => 46.6753,
            'image_url' => 'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?w=400&q=80'
        ],
        [
            'id' => 2,
            'title' => 'هيونداي توسان 2.0 AWD',
            'make' => 'Hyundai',
            'model' => 'Tucson',
            'year' => 2023,
            'current_price' => 94000,
            'city' => 'جدة',
            'lat' => 21.5433,
            'lng' => 39.1728,
            'image_url' => 'https://images.unsplash.com/photo-1568844293986-ca9c5c6f8b8a?w=400&q=80'
        ],
        [
            'id' => 3,
            'title' => 'كيا سبورتاج 1.6T',
            'make' => 'Kia',
            'model' => 'Sportage',
            'year' => 2022,
            'current_price' => 78500,
            'city' => 'الدمام',
            'lat' => 26.4207,
            'lng' => 50.0888,
            'image_url' => 'https://images.unsplash.com/photo-1606664515524-ed2f786a0bd6?w=400&q=80'
        ],
        [
            'id' => 5,
            'title' => 'تويوتا راف 4 2.5L',
            'make' => 'Toyota',
            'model' => 'RAV4',
            'year' => 2023,
            'current_price' => 112000,
            'city' => 'الرياض',
            'lat' => 24.7936,
            'lng' => 46.7253,
            'image_url' => 'https://images.unsplash.com/photo-1584345604476-8ec5e12e42dd?w=400&q=80'
        ],
        [
            'id' => 8,
            'title' => 'هوندا أكورد 1.5T',
            'make' => 'Honda',
            'model' => 'Accord',
            'year' => 2022,
            'current_price' => 68000,
            'city' => 'المدينة المنورة',
            'lat' => 24.4672,
            'lng' => 39.6112,
            'image_url' => 'https://images.unsplash.com/photo-1580273916550-e323be2ae537?w=400&q=80'
        ]
    ];
} else {
    // Map cities to coordinates
    $coords = [
        'الرياض' => [24.7136, 46.6753],
        'جدة' => [21.5433, 39.1728],
        'الدمام' => [26.4207, 50.0888],
        'مكة المكرمة' => [21.3891, 39.8579],
        'المدينة المنورة' => [24.4672, 39.6112],
    ];
    foreach ($map_vehicles as &$mv) {
        $c = $mv['city'] ?? 'الرياض';
        // Add random tiny offset to prevent overlapping pins in same city
        $offset_lat = (rand(-100, 100) / 3000);
        $offset_lng = (rand(-100, 100) / 3000);
        if (isset($coords[$c])) {
            $mv['lat'] = $coords[$c][0] + $offset_lat;
            $mv['lng'] = $coords[$c][1] + $offset_lng;
        } else {
            $mv['lat'] = 24.7136 + $offset_lat;
            $mv['lng'] = 46.6753 + $offset_lng;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>خريطة المزايدات التفاعلية | FleetX</title>
  <link rel="stylesheet" href="/assets/css/fleetx.css">
  
  <!-- Leaflet CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
  
</head>
<body class="page-inner fx-page-shell fx-page-shell--listing">

<?php include 'includes/navbar.php'; ?>

<?php
$hero_title = 'خريطة السيارات التفاعلية';
$hero_eyebrow = 'مواقع السيارات المتاحة';
$hero_desc = 'تصفح السيارات المتاحة للمزايدة الفورية والشراء المباشر حسب موقعها الجغرافي في مدن المملكة.';
$hero_bg = 'https://images.unsplash.com/photo-1508962914676-134849a727f0?w=1600&q=80';
$hero_modifier = 'overlap';
$hero_bottom_html = '<div class="fx-page-hero__toolbar"><div class="stat-item"><div class="stat-num">' . count($map_vehicles) . '</div><div class="stat-lbl">مركبة على الخريطة</div></div></div>';
include 'includes/page-hero.inc.php';
?>

<div class="container fx-page-body fx-page-body--overlap-lg">
  
  <div class="fx-map-layout">
    <!-- Sidebar Filters -->
    <div>
      <aside class="fx-map-sidebar">
        <form id="sideFilterForm" method="GET" action="">
          <input type="hidden" name="type" value="<?= $type_filter ?? '' ?>">
          
          <div class="fx-map-filter-head">
            <h3><i class="ph ph-faders"></i> تصفية متقدمة</h3>
            <a href="?type=<?= $type_filter ?? '' ?>" class="fx-map-filter-reset" title="إعادة ضبط"><i class="ph ph-trash"></i></a>
          </div>

          <div class="filter-group">
            <h4 class="filter-title">البحث المباشر</h4>
            <input type="text" name="search" value="<?= htmlspecialchars($search_query ?? '') ?>" placeholder="كلمة البحث..." class="form-control-light fx-map-filter-input-gap" onchange="document.getElementById('sideFilterForm').submit()">
            <input type="text" name="seller" value="<?= htmlspecialchars($seller_query ?? '') ?>" placeholder="اسم البائع أو الشركة..." class="form-control-light" onchange="document.getElementById('sideFilterForm').submit()">
          </div>

          <div class="filter-group">
            <h4 class="filter-title">المدينة</h4>
            <select name="city" class="form-control-light" onchange="document.getElementById('sideFilterForm').submit()">
              <option value="">جميع المدن</option>
              <?php foreach(['الرياض', 'جدة', 'الدمام', 'مكة المكرمة', 'المدينة المنورة'] as $c): ?>
              <option value="<?= $c ?>" <?= (($city_filter??'') === $c)?'selected':'' ?>><?= $c ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <?php if (($type_filter ?? '') === 'live'): ?>
            <div class="filter-group">
              <h4 class="filter-title">فترة المزاد</h4>
              <select name="period" class="form-control-light" onchange="document.getElementById('sideFilterForm').submit()">
                <option value="">الكل</option>
                <option value="today" <?= (isset($_GET['period']) && $_GET['period'] === 'today')?'selected':'' ?>>اليوم</option>
                <option value="tomorrow" <?= (isset($_GET['period']) && $_GET['period'] === 'tomorrow')?'selected':'' ?>>غداً</option>
                <option value="this_week" <?= (isset($_GET['period']) && $_GET['period'] === 'this_week')?'selected':'' ?>>هذا الأسبوع</option>
              </select>
            </div>
            <div class="filter-group">
              <h4 class="filter-title">حالة المزاد</h4>
              <?php 
                $statuses = ['active' => 'جاري حالياً', 'upcoming' => 'قادم', 'ended' => 'منتهي'];
                $sf = $status_filter ?? [];
                foreach($statuses as $k => $l):
              ?>
              <label class="filter-label">
                <input type="checkbox" name="status[]" value="<?= $k ?>" class="fx-map-filter-checkbox" <?= (is_array($sf) && in_array($k, $sf))?'checked':'' ?> onchange="document.getElementById('sideFilterForm').submit()">
                <span><?= $l ?></span>
              </label>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="filter-group">
              <h4 class="filter-title">الشركة الصانعة</h4>
              <select name="make[]" class="form-control-light" onchange="document.getElementById('sideFilterForm').submit()">
                <option value="">جميع الماركات</option>
                <?php foreach(['Toyota', 'Hyundai', 'Kia', 'Nissan', 'Ford', 'BMW', 'Mercedes'] as $m): ?>
                <option value="<?= $m ?>" <?= (isset($make_filter) && is_array($make_filter) && in_array($m, $make_filter))?'selected':'' ?>><?= $m ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="filter-group">
              <h4 class="filter-title">نوع الوقود</h4>
              <?php 
                $fuels = ['بنزين', 'ديزل', 'هايبرد', 'كهرباء'];
                $ff = $fuel_filter ?? [];
                foreach($fuels as $f):
              ?>
              <label class="filter-label">
                <input type="checkbox" name="fuel[]" value="<?= $f ?>" class="fx-map-filter-checkbox" <?= (is_array($ff) && in_array($f, $ff))?'checked':'' ?> onchange="document.getElementById('sideFilterForm').submit()">
                <span><?= $f ?></span>
              </label>
              <?php endforeach; ?>
            </div>

            <div class="filter-group">
              <h4 class="filter-title">سنة الصنع</h4>
              <div class="fx-range-dual">
                <input type="number" name="year_min" value="<?= htmlspecialchars($year_min ?? '') ?>" placeholder="من" class="form-control-light" onchange="document.getElementById('sideFilterForm').submit()">
                <input type="number" name="year_max" value="<?= htmlspecialchars($year_max ?? '') ?>" placeholder="إلى" class="form-control-light" onchange="document.getElementById('sideFilterForm').submit()">
              </div>
            </div>

            <div class="filter-group">
              <h4 class="filter-title">نطاق السعر (ر.س)</h4>
              <div class="fx-price-range-labels">
                  <span id="priceRangeValMin"><?= !empty($price_min) ? $price_min : '0' ?></span>
                  <span id="priceRangeValMax"><?= !empty($price_max) ? $price_max : '500,000+' ?></span>
              </div>
              <input type="range" name="price_max" min="0" max="500000" step="5000" value="<?= !empty($price_max) ? $price_max : 500000 ?>" class="fx-price-range-input" onchange="document.getElementById('sideFilterForm').submit()" oninput="document.getElementById('priceRangeValMax').innerText = this.value">
            </div>
          <?php endif; ?>
          <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px; justify-content:center;">تطبيق الفلاتر</button>
        </form>
      </aside>
    </div>

    <!-- Map Column -->
    <div>
      <div class="fx-map-container">
        <div id="map"></div>
      </div>
    </div>

  </div>

</div>

<!-- Footer -->
<?php include 'includes/footer.php'; ?>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script>
  document.addEventListener("DOMContentLoaded", function() {
    // Center map on KSA
    const map = L.map('map', {
      center: [23.8859, 45.0792],
      zoom: 6,
      zoomControl: true
    });

    // Beautiful light grayscale tile provider from CartoDB
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
      subdomains: 'abcd',
      maxZoom: 20
    }).addTo(map);

    // Dynamic marker configuration
    const vehicles = <?= json_encode($map_vehicles) ?>;

    // Green custom icon for pins matching primary color
    const customIcon = L.divIcon({
      html: `<div style="background-color: var(--primary); width: 14px; height: 14px; border: 3px solid #fff; border-radius: 50%; box-shadow: 0 0 10px rgba(27, 201, 118, 0.8);"></div>`,
      className: 'custom-pin-icon',
      iconSize: [20, 20],
      iconAnchor: [10, 10]
    });

    vehicles.forEach(v => {
      if (v.lat && v.lng) {
        const marker = L.marker([v.lat, v.lng], { icon: customIcon }).addTo(map);
        
        // Popup layout matching design system
        const popupContent = `
          <div class="fx-map-popup-card">
            <img class="fx-map-popup-img" src="${v.image_url}" alt="${v.title}">
            <div class="fx-map-popup-info">
              <div class="fx-map-popup-title">${v.title}</div>
              <div class="fx-map-popup-city"><i class="ph ph-map-pin"></i> ${v.city}</div>
              <div class="fx-map-popup-price">${Number(v.current_price).toLocaleString('ar-SA')} ر.س</div>
              <a href="/auction-live.php?id=${v.id}" class="btn btn-primary btn-sm" style="width:100%; display:inline-flex; align-items:center; justify-content:center; gap:6px;">دخول المزاد <i class="ph ph-arrow-up-right"></i></a>
            </div>
          </div>
        `;
        
        marker.bindPopup(popupContent);
      }
    });

    // Auto fit bounds if coordinates are present
    if (vehicles.length > 0) {
      const group = new L.featureGroup(vehicles.filter(v => v.lat && v.lng).map(v => L.marker([v.lat, v.lng])));
      map.fitBounds(group.getBounds().pad(0.15));
    }
  });
</script>

</body>
</html>
