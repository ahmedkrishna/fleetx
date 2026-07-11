<?php
$base = rtrim(getenv('FLEETX_BASE') ?: 'https://mazadi.bearand.com', '/');
$pass = 0;
$fail = 0;

function check($label, $ok, $detail = '') {
    global $pass, $fail;
    if ($ok) { $pass++; echo "[PASS] $label\n"; }
    else { $fail++; echo "[FAIL] $label" . ($detail ? " — $detail" : '') . "\n"; }
}

/**
 * Reliable HTTP fetch for live verification (retries, timeout, status code).
 */
function fx_fetch(string $url, int $retries = 3): array {
    for ($attempt = 1; $attempt <= $retries; $attempt++) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'FleetX-Verify/1.0',
        ]);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($body !== false && $code >= 200 && $code < 400) {
            return ['ok' => true, 'code' => $code, 'body' => $body];
        }

        if ($attempt < $retries) {
            usleep(400000 * $attempt);
        } else {
            return ['ok' => false, 'code' => $code, 'body' => $body ?: '', 'error' => $err ?: 'fetch failed'];
        }
    }

    return ['ok' => false, 'code' => 0, 'body' => '', 'error' => 'fetch failed'];
}

$pages = ['index.php', 'map.php', 'auctions.php', 'about.php', 'terms.php', 'companies.php'];
$bodies = [];

foreach ($pages as $p) {
    $res = fx_fetch("$base/$p");
    $bodies[$p] = $res['body'] ?? '';
    if (!$res['ok']) {
        check("HTTP $p", false, ($res['error'] ?? '') . ($res['code'] ? " HTTP {$res['code']}" : ''));
        continue;
    }
    check("HTTP $p", true);
}

$index = $bodies['index.php'] ?? '';
check('Navbar map link on index', $index !== '' && str_contains($index, 'href="/map.php"') && str_contains($index, 'خريطة المزادات'));
check('Homepage navbar map link', $index !== '' && str_contains($index, 'href="/map.php"'));

$map = $bodies['map.php'] ?? '';
$map_hero_ok = $map !== '' && (
    str_contains($map, 'خريطة السيارات التفاعلية')
    || str_contains($map, 'خريطة السيارات')
    || str_contains($map, 'fx-page-hero__title')
    || str_contains($map, 'fx-page-hero')
);
check('Map page hero title', $map_hero_ok, $map === '' ? 'empty body' : 'hero markers missing');

$map_leaflet_ok = $map !== '' && (
    str_contains($map, 'id="map"')
    || str_contains($map, "id='map'")
) && (
    str_contains($map, 'leaflet')
    || str_contains($map, 'L.map(')
);
check('Map leaflet container', $map_leaflet_ok, $map === '' ? 'empty body' : 'map/leaflet markers missing');

// Login + favorites API
$jar = tempnam(sys_get_temp_dir(), 'fx');
$ch = curl_init("$base/login.php");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query(['login_type' => 'trader', 'mobile' => '0501111111', 'password' => '123456']),
    CURLOPT_COOKIEJAR => $jar,
    CURLOPT_COOKIEFILE => $jar,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 45,
]);
curl_exec($ch);
curl_close($ch);

$ch = curl_init("$base/api/toggle_favorite.php");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode(['id' => 1]),
    CURLOPT_COOKIEJAR => $jar,
    CURLOPT_COOKIEFILE => $jar,
    CURLOPT_TIMEOUT => 30,
]);
$resp = curl_exec($ch);
curl_close($ch);
$data = json_decode($resp, true);
check('Favorites API JSON', is_array($data) && isset($data['success']));
check('Favorites API success', !empty($data['success']));

$ch = curl_init("$base/profile.php?tab=favorites");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEJAR => $jar,
    CURLOPT_COOKIEFILE => $jar,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 45,
]);
$profile = curl_exec($ch);
curl_close($ch);
check('Profile favorites page', $profile && str_contains($profile, 'المفضلة'));
check('Profile fav grid or empty', $profile && (str_contains($profile, 'fav-grid') || str_contains($profile, 'fx-empty-state') || str_contains($profile, 'fx-fav-card')));

@unlink($jar);
echo "\n=== VERIFY: $pass passed, $fail failed ===\n";
exit($fail > 0 ? 1 : 0);