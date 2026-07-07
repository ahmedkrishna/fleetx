<?php
require_once 'config.php';

// Fetch active auctions/vehicles for map pins
$map_vehicles = [];
if ($db_connected) {
    $res = $conn->query("SELECT a.id, a.title, a.type, a.current_price, v.make, v.model, v.image_url, v.city, v.year FROM auctions a JOIN vehicles v ON a.vehicle_id = v.id WHERE a.status='active'");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $map_vehicles[] = $row;
        }
    }
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
  
  <style>
    .map-container-box {
      background: var(--bg-white);
      border: 1px solid var(--border-light);
      border-radius: var(--radius-lg);
      overflow: hidden;
      box-shadow: var(--shadow-card);
      margin-bottom: 80px;
    }
    #map {
      width: 100%;
      height: 600px;
      z-index: 1;
    }
    .leaflet-popup-content-wrapper {
      border-radius: var(--radius-md) !important;
      padding: 0 !important;
      overflow: hidden;
      direction: rtl;
      text-align: right;
      font-family: var(--font-ar);
    }
    .leaflet-popup-content {
      margin: 0 !important;
      width: 280px !important;
    }
    .map-popup-card {
      display: flex;
      flex-direction: column;
      background: #fff;
    }
    .map-popup-img {
      width: 100%;
      height: 140px;
      object-fit: cover;
    }
    .map-popup-info {
      padding: 16px;
    }
    .map-popup-title {
      font-size: 15px;
      font-weight: 800;
      color: var(--text-dark);
      margin-bottom: 4px;
    }
    .map-popup-price {
      font-size: 16px;
      font-weight: 800;
      color: var(--primary);
      margin-bottom: 12px;
      font-family: var(--font-en);
    }
    .leaflet-container a.leaflet-popup-close-button {
      color: #fff !important;
      padding: 6px !important;
      z-index: 100;
    }
  </style>
</head>
<body class="page-inner">

<!-- Navbar -->
<?php include 'includes/navbar.php'; ?>

<!-- Page Header -->
<header class="page-header">
  <div class="page-header-bg" style="background-image:url('https://images.unsplash.com/photo-1508962914676-134849a727f0?w=1600&q=80')"></div>
  <div class="container">
    <div style="font-size:14px; color:var(--primary); font-weight:800; text-transform:uppercase; margin-bottom:8px">مواقع السيارات المتاحة</div>
    <h1 style="margin:0">خريطة السيارات التفاعلية</h1>
    <p style="color:var(--text-light-muted); font-size:16px; margin-top:6px; max-width:600px">تصفح السيارات المتاحة للمزايدة الفورية والشراء المباشر حسب موقعها الجغرافي في مدن المملكة.</p>
  </div>
</header>

<div class="container" style="margin-top:-50px; position:relative; z-index:10;">
  
  <div class="map-container-box">
    <div id="map"></div>
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
          <div class="map-popup-card">
            <img class="map-popup-img" src="${v.image_url}" alt="${v.title}">
            <div class="map-popup-info">
              <div class="map-popup-title">${v.title}</div>
              <div style="font-size:12px; color:var(--text-muted); margin-bottom:6px; display:inline-flex; align-items:center; gap:4px;"><i class="ph ph-map-pin"></i> ${v.city}</div>
              <div class="map-popup-price">${Number(v.current_price).toLocaleString('ar-SA')} ر.س</div>
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
