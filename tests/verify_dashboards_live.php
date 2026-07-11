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

function login($base, $type, $mobile, $password, $attempts = 3) {
    $jar = tempnam(sys_get_temp_dir(), 'fx_dash_');
    $lastCode = 0;
    $lastBody = '';
    for ($i = 0; $i < $attempts; $i++) {
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
            CURLOPT_TIMEOUT => 45,
            CURLOPT_CONNECTTIMEOUT => 15,
        ]);
        $lastBody = curl_exec($ch) ?: '';
        $lastCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($lastCode === 200) {
            return [$jar, $lastCode, $lastBody];
        }
        if ($i < $attempts - 1) {
            usleep(750000);
        }
    }
    return [$jar, $lastCode, $lastBody];
}

function fetch($base, $path, $jar, $timeout = 45) {
    $sep = str_contains($path, '?') ? '&' : '?';
    $url = "$base/$path{$sep}_t=" . time();
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_HTTPHEADER => ['Cache-Control: no-cache'],
    ]);
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    return [$code, $body ?: '', $err];
}

function fetchSectionWithRetry($base, $path, $jar, $shellMarker, $contentMarkers = [], $opts = []) {
    $attempts = $opts['attempts'] ?? 3;
    $timeout = $opts['timeout'] ?? 45;
    $last = [0, '', ''];

    for ($i = 0; $i < $attempts; $i++) {
        $last = fetch($base, $path, $jar, $timeout);
        [$code, $html] = $last;

        $shellOk = $code === 200 && str_contains($html, $shellMarker);
        $contentOk = true;
        foreach ($contentMarkers as $marker) {
            if (!str_contains($html, $marker)) {
                $contentOk = false;
                break;
            }
        }

        if ($shellOk && $contentOk && $html !== '') {
            return $last;
        }

        if ($i < $attempts - 1) {
            usleep($opts['backoff_us'] ?? 750000);
        }
    }

    return $last;
}

function checkSection($label, $base, $path, $jar, $shellMarker, $contentMarkers = [], $opts = []) {
    [$c, $html, $err] = fetchSectionWithRetry($base, $path, $jar, $shellMarker, $contentMarkers, $opts);
    $shellOk = $c === 200 && str_contains($html, $shellMarker);
    $contentOk = true;
    $missing = [];
    foreach ($contentMarkers as $marker) {
        if (!str_contains($html, $marker)) {
            $contentOk = false;
            $missing[] = $marker;
        }
    }
    $detail = '';
    if (!$shellOk) {
        $detail = "HTTP $c" . ($err ? ", curl: $err" : '');
    } elseif (!$contentOk) {
        $detail = 'missing: ' . implode(', ', $missing);
    }
    check($label, $shellOk && $contentOk, $detail);
}

echo "=== FleetX Live Dashboard Verify ===\nBase: $base\n\n";

// Brief pause when run after heavy browser suites (set FX_DASH_COOLDOWN=1)
if (getenv('FX_DASH_COOLDOWN')) {
    sleep(4);
}

[$code] = fetch($base, 'buyer.php', tempnam(sys_get_temp_dir(), 'x'));
check('Buyer guest redirects (302)', $code === 302 || $code === 200);

[$jar, $loginCode] = login($base, 'trader', '0501111111', '123456');
check('Buyer demo login', $loginCode === 200);

[$buyerCode, $buyer] = fetchSectionWithRetry($base, 'buyer.php', $jar, 'fx-page-shell--buyer');
check('Buyer dashboard HTTP 200', $buyerCode === 200);
check('Buyer fx-home shell', str_contains($buyer, 'fx-home') && str_contains($buyer, 'fx-page-shell--buyer'));
check('Buyer dark hero', str_contains($buyer, 'fx-page-hero') && !str_contains($buyer, 'fx-page-hero--light'));
check('Buyer sidebar present', str_contains($buyer, 'fx-buyer-sidebar'));
check('Buyer main content', str_contains($buyer, 'fx-buyer-main'));
check('Buyer hero welcome text', str_contains($buyer, 'مرحباً بك'));
check('Buyer mobile nav', str_contains($buyer, 'fx-dash-mobile-nav'));

$buyerSections = ['dashboard', 'bids', 'favorites', 'wallet', 'settings'];
foreach ($buyerSections as $sec) {
    checkSection(
        "Buyer section: $sec",
        $base,
        "buyer.php?section=$sec",
        $jar,
        'fx-page-shell--buyer'
    );
}

@unlink($jar);

usleep(750000);
[$jar2, $loginCode2] = login($base, 'company', '0500000002', '123456', 5);
check('Seller demo login', $loginCode2 === 200);

if ($loginCode2 !== 200) {
    echo "[SKIP] Seller dashboard checks — login failed\n";
    @unlink($jar2);
    echo "\n=== RESULT: $pass passed, $fail failed ===\n";
    exit($fail > 0 ? 1 : 0);
}

// Let live host recover after buyer section burst
usleep(500000);

[$sellerCode, $seller] = fetchSectionWithRetry($base, 'seller.php', $jar2, 'fx-page-shell--seller');
check('Seller dashboard HTTP 200', $sellerCode === 200);
check('Seller fx-home shell', str_contains($seller, 'fx-home') && str_contains($seller, 'fx-page-shell--seller'));
check('Seller dark hero', str_contains($seller, 'fx-page-hero') && !str_contains($seller, 'fx-page-hero--light'));
check('Seller sidebar present', str_contains($seller, 'fx-seller-sidebar'));
check('Seller main content', str_contains($seller, 'fx-seller-main'));
check('Seller verified badge or company', str_contains($seller, 'verified-badge') || str_contains($seller, 'fx-seller-company-name'));
check('Seller mobile nav', str_contains($seller, 'fx-dash-mobile-nav'));

$sellerSectionConfig = [
    'dashboard' => ['attempts' => 3, 'timeout' => 45],
    'fleet'     => ['attempts' => 3, 'timeout' => 45],
    'payouts'   => [
        'attempts' => 5,
        'timeout' => 60,
        'backoff_us' => 1000000,
        'markers' => ['fx-seller-payout-hero', 'payout-table'],
    ],
    'settings'  => ['attempts' => 3, 'timeout' => 45],
];

foreach ($sellerSectionConfig as $sec => $cfg) {
    checkSection(
        "Seller section: $sec",
        $base,
        "seller.php?section=$sec",
        $jar2,
        'fx-page-shell--seller',
        $cfg['markers'] ?? [],
        [
            'attempts' => $cfg['attempts'],
            'timeout' => $cfg['timeout'],
            'backoff_us' => $cfg['backoff_us'] ?? 750000,
        ]
    );
}

@unlink($jar2);

echo "\n=== RESULT: $pass passed, $fail failed ===\n";
exit($fail > 0 ? 1 : 0);