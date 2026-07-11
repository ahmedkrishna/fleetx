<?php
/**
 * Live payment gateway flow — php tests/verify_live_payment.php
 */
$base = rtrim(getenv('FLEETX_BASE_URL') ?: 'https://mazadi.bearand.com', '/');
$jar = tempnam(sys_get_temp_dir(), 'fx_pay_');
$pass = 0;
$fail = 0;

function pay_req($url, $jar, $opts = []) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_FOLLOWLOCATION => !empty($opts['follow']),
        CURLOPT_HEADER => !empty($opts['header']),
    ]);
    if (!empty($opts['post'])) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $opts['post']);
    }
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $redirect = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    $effective = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    return [
        'code' => $code,
        'body' => $body ?: '',
        'redirect' => $redirect ?: '',
        'effective' => $effective ?: '',
        'headers' => !empty($opts['header']) ? ($body ?: '') : '',
    ];
}

function pay_check($ok, $label, $detail = '') {
    global $pass, $fail;
    echo ($ok ? '[PASS]' : '[FAIL]') . " $label" . ($detail ? " — $detail" : '') . "\n";
    $ok ? $pass++ : $fail++;
}

function find_instant_auction_id($base, $jar) {
    $r = pay_req("$base/auctions.php?type=instant", $jar, ['follow' => true]);
    if (preg_match_all('/checkout\.php\?id=(\d+)/', $r['body'], $m)) {
        return (int)$m[1][0];
    }
    if (preg_match_all('/vehicle-details\.php\?id=(\d+)/', $r['body'], $m)) {
        return (int)$m[1][0];
    }
    if (preg_match_all('/data-id="(\d+)"/', $r['body'], $m)) {
        foreach ($m[1] as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $c = pay_req("$base/checkout.php?id=$id", $jar, ['follow' => false]);
                if ($c['code'] === 200 && str_contains($c['body'], 'payment_method')) {
                    return $id;
                }
            }
        }
    }
    foreach ([8, 104, 12, 15, 20, 3, 5] as $probe) {
        $c = pay_req("$base/checkout.php?id=$probe", $jar, ['follow' => false]);
        if ($c['code'] === 200 && str_contains($c['body'], 'payment_method')) {
            return $probe;
        }
    }
    return 0;
}

function extract_redirect_location($headerBlock, $base) {
    if (preg_match('/^Location:\s*(\S+)/mi', $headerBlock, $m)) {
        $loc = trim($m[1]);
        if (str_starts_with($loc, '/')) {
            return rtrim($base, '/') . $loc;
        }
        return $loc;
    }
    return '';
}

echo "=== FleetX Live Payment Flow ===\nBase: $base\n\n";

// Login as test buyer
$login = pay_req("$base/login.php", $jar, [
    'post' => http_build_query(['login_type' => 'trader', 'mobile' => '0501111111', 'password' => '123456']),
    'follow' => true,
]);
pay_check($login['code'] === 200, 'Buyer login', 'code=' . $login['code']);

$auction_id = find_instant_auction_id($base, $jar);
pay_check($auction_id > 0, 'Find checkout-eligible instant auction', 'id=' . $auction_id);
if ($auction_id <= 0) {
    echo "\nResult: cannot continue without auction id\n";
    exit(1);
}

$checkout_get = pay_req("$base/checkout.php?id=$auction_id", $jar, ['follow' => true]);
pay_check(
    $checkout_get['code'] === 200 && str_contains($checkout_get['body'], 'payment_method'),
    'Checkout page loads with payment options',
    'auction_id=' . $auction_id
);
pay_check(str_contains($checkout_get['body'], 'mada') || str_contains($checkout_get['body'], 'مدى'), 'Checkout shows Mada option');

// Initiate gateway payment (capture redirect, do not follow)
$init = pay_req("$base/checkout.php?id=$auction_id", $jar, [
    'post' => http_build_query(['payment_method' => 'mada']),
    'header' => true,
]);
$loc = extract_redirect_location($init['headers'], $base);
pay_check($init['code'] === 302 && $loc !== '', 'Checkout POST returns redirect', 'code=' . $init['code']);

if (!$loc && $init['code'] >= 300 && $init['code'] < 400) {
    $loc = extract_redirect_location($init['headers'], $base);
}
if (!$loc) {
    pay_check(false, 'Gateway redirect URL present');
    echo "\nResult: $fail failed\n";
    @unlink($jar);
    exit(1);
}

pay_check(str_contains($loc, 'payment-gateway.php') && str_contains($loc, 'ref='), 'Redirect targets payment-gateway.php with ref', $loc);

// Gateway page
$gateway = pay_req($loc, $jar, ['follow' => true]);
pay_check($gateway['code'] === 200, 'Payment gateway page loads', 'code=' . $gateway['code']);
pay_check(str_contains($gateway['body'], 'بوابة الدفع') || str_contains($gateway['body'], 'تأكيد الدفع'), 'Gateway shows confirm UI');

preg_match('/name="ref"\s+value="([^"]+)"/', $gateway['body'], $refMatch);
$ref = $refMatch[1] ?? '';
if (!$ref && preg_match('/ref=([A-Za-z0-9\-]+)/', $loc, $rm)) {
    $ref = $rm[1];
}
pay_check($ref !== '', 'Payment reference extracted', $ref);

// Complete payment
$complete = pay_req("$base/payment-return.php", $jar, [
    'post' => http_build_query(['ref' => $ref, 'confirm' => '1']),
    'header' => true,
]);
$complete_loc = extract_redirect_location($complete['headers'], $base);
pay_check(
    ($complete['code'] === 302 || $complete['code'] === 303) && str_contains($complete_loc, 'buyer.php'),
    'Payment confirm redirects to buyer purchases',
    'code=' . $complete['code'] . ' loc=' . ($complete_loc ?: 'none')
);

$purchases = pay_req($complete_loc ?: "$base/buyer.php?section=purchases", $jar, ['follow' => true]);
pay_check($purchases['code'] === 200, 'Purchases page loads after payment', 'code=' . $purchases['code']);

// Second gateway visit should fail (intent consumed)
if ($ref) {
    $replay = pay_req("$base/payment-gateway.php?ref=" . urlencode($ref), $jar, ['follow' => false]);
    pay_check($replay['code'] === 302, 'Replay gateway with used ref redirects away', 'code=' . $replay['code']);
}

@unlink($jar);

echo "\nResult: $pass passed, $fail failed\n";
exit($fail > 0 ? 1 : 0);