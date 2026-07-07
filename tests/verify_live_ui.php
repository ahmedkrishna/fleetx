<?php
$base = getenv('FLEETX_BASE') ?: 'https://mazadi.bearand.com';
$pass = 0;
$fail = 0;

function check($label, $ok, $detail = '') {
    global $pass, $fail;
    if ($ok) { $pass++; echo "[PASS] $label\n"; }
    else { $fail++; echo "[FAIL] $label" . ($detail ? " — $detail" : '') . "\n"; }
}

$pages = ['index.php', 'map.php', 'auctions.php', 'about.php', 'terms.php', 'companies.php'];
foreach ($pages as $p) {
    $code = 0;
    $body = @file_get_contents("$base/$p");
    if ($body === false) { check("HTTP $p", false, 'fetch failed'); continue; }
    check("HTTP $p", true);
}

$index = @file_get_contents("$base/index.php");
check('Navbar map link on index', $index && str_contains($index, 'href="/map.php"') && str_contains($index, 'خريطة المزادات'));
check('Homepage map CTA', $index && str_contains($index, 'خريطة المزادات'));

$map = @file_get_contents("$base/map.php");
check('Map page hero title', $map && (str_contains($map, 'خريطة السيارات') || str_contains($map, 'fx-page-hero')));
check('Map leaflet container', $map && str_contains($map, 'id="map"'));

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
]);
$resp = curl_exec($ch);
curl_close($ch);
$data = json_decode($resp, true);
check('Favorites API JSON', is_array($data) && isset($data['success']));
check('Favorites API success', !empty($data['success']));

$ch = curl_init("$base/profile.php?tab=favorites");
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_FOLLOWLOCATION => true]);
$profile = curl_exec($ch);
curl_close($ch);
check('Profile favorites page', $profile && str_contains($profile, 'المفضلة'));
check('Profile fav grid or empty', $profile && (str_contains($profile, 'fav-grid') || str_contains($profile, 'fx-empty-state') || str_contains($profile, 'fx-fav-card')));

@unlink($jar);
echo "\n=== VERIFY: $pass passed, $fail failed ===\n";
exit($fail > 0 ? 1 : 0);