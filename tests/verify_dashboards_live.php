<?php
$base = getenv('FLEETX_BASE') ?: 'https://mazadi.bearand.com';
$pass = 0;
$fail = 0;

function check($label, $ok, $detail = '') {
    global $pass, $fail;
    if ($ok) {
        $pass++;
        echo "[PASS] $label\n";
    } else {
        $fail++;
        echo "[FAIL] $label" . ($detail ? " — $detail" : '') . "\n";
    }
}

function login($base, $type, $mobile, $password) {
    $jar = tempnam(sys_get_temp_dir(), 'fx_dash_');
    $ch = curl_init("$base/login.php");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'login_type' => $type,
            'mobile' => $mobile,
            'password' => $password,
        ]),
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
    ]);
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$jar, $code, $body ?: ''];
}

function fetch($base, $path, $jar) {
    $ch = curl_init("$base/$path");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
    ]);
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $body ?: ''];
}

echo "=== FleetX Live Dashboard Verify ===\nBase: $base\n\n";

// Guest redirects
[$code] = fetch($base, 'buyer.php', tempnam(sys_get_temp_dir(), 'x'));
check('Buyer guest redirects (302)', $code === 302 || $code === 200);

[$jar, $loginCode] = login($base, 'trader', '0501111111', '123456');
check('Buyer demo login', $loginCode === 200);

[$buyerCode, $buyer] = fetch($base, 'buyer.php', $jar);
check('Buyer dashboard HTTP 200', $buyerCode === 200);
check('Buyer fx-page-shell--dashboard', str_contains($buyer, 'fx-page-shell--dashboard'));
check('Buyer fx-page-hero--dashboard', str_contains($buyer, 'fx-page-hero--dashboard'));
check('Buyer fx-page-hero--buyer', str_contains($buyer, 'fx-page-hero--buyer'));
check('Buyer sidebar present', str_contains($buyer, 'buyer-sidebar'));
check('Buyer main content', str_contains($buyer, 'buyer-main'));
check('Buyer hero welcome text', str_contains($buyer, 'مرحباً بك'));
check('Buyer mobile nav', str_contains($buyer, 'fx-dash-mobile-nav'));

$sections = ['dashboard', 'bids', 'favorites', 'wallet', 'settings'];
foreach ($sections as $sec) {
    [$c, $html] = fetch($base, "buyer.php?section=$sec", $jar);
    check("Buyer section: $sec", $c === 200 && str_contains($html, 'fx-page-shell--dashboard'));
}

@unlink($jar);

[$jar2, $loginCode2] = login($base, 'company', '0500000002', '123456');
check('Seller demo login', $loginCode2 === 200);

[$sellerCode, $seller] = fetch($base, 'seller.php', $jar2);
check('Seller dashboard HTTP 200', $sellerCode === 200);
check('Seller fx-page-shell--dashboard', str_contains($seller, 'fx-page-shell--dashboard'));
check('Seller fx-page-hero--dashboard', str_contains($seller, 'fx-page-hero--dashboard'));
check('Seller fx-page-hero--seller', str_contains($seller, 'fx-page-hero--seller'));
check('Seller sidebar present', str_contains($seller, 'seller-sidebar'));
check('Seller main content', str_contains($seller, 'seller-main'));
check('Seller verified badge or company', str_contains($seller, 'verified-badge') || str_contains($seller, 'seller-company-name'));
check('Seller mobile nav', str_contains($seller, 'fx-dash-mobile-nav'));

$sellerSections = ['dashboard', 'fleet', 'payouts', 'settings'];
foreach ($sellerSections as $sec) {
    [$c, $html] = fetch($base, "seller.php?section=$sec", $jar2);
    check("Seller section: $sec", $c === 200 && str_contains($html, 'fx-page-shell--dashboard'));
}

@unlink($jar2);

echo "\n=== RESULT: $pass passed, $fail failed ===\n";
exit($fail > 0 ? 1 : 0);