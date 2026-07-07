<?php
/**
 * FleetX End-to-End Test Runner
 * CLI: php tests/e2e.php
 * Web: /tests/e2e.php?key=mazad2026
 */
$is_cli = (php_sapi_name() === 'cli');
if (!$is_cli && (!isset($_GET['key']) || $_GET['key'] !== 'mazad2026')) {
    http_response_code(403);
    die('Forbidden');
}

$base = getenv('FLEETX_BASE_URL') ?: 'https://mazadi.bearand.com';
$results = [];
$cookie_jar = tempnam(sys_get_temp_dir(), 'fleetx_e2e_');

function e2e_log(&$results, $name, $ok, $detail = '') {
    $results[] = ['test' => $name, 'ok' => $ok, 'detail' => $detail];
    $mark = $ok ? 'PASS' : 'FAIL';
    echo "[$mark] $name" . ($detail ? " — $detail" : '') . "\n";
}

function e2e_request($url, $opts = []) {
    global $cookie_jar;
    $ch = curl_init($url);
    $headers = $opts['headers'] ?? [];
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_COOKIEJAR => $cookie_jar,
        CURLOPT_COOKIEFILE => $cookie_jar,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    if (!empty($opts['post'])) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $opts['post']);
    }
    if (!empty($opts['json'])) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($opts['json']));
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    return ['code' => $code, 'body' => $body, 'error' => $err];
}

echo "=== FleetX E2E Test ===\nBase: $base\n\n";

// 1. Public pages
foreach ([
    '/' => 'index.php',
    '/login.php' => 'login',
    '/auctions.php' => 'auctions',
    '/companies.php' => 'companies',
    '/add-auction.php' => 'add-auction',
] as $path => $label) {
    $r = e2e_request(rtrim($base, '/') . $path);
    e2e_log($results, "Public: $label", $r['code'] === 200, "HTTP {$r['code']}");
}

// 2. DB check (if deployed)
$r = e2e_request(rtrim($base, '/') . '/check_db.php');
$has_db = ($r['code'] === 200 && str_contains($r['body'] ?? '', 'Users:'));
e2e_log($results, 'Database connected', $has_db, trim($r['body'] ?? $r['error']));

// 3. Buyer login
$login_url = rtrim($base, '/') . '/login.php';
$r = e2e_request($login_url, [
    'post' => http_build_query(['login_type' => 'trader', 'mobile' => '0501111111', 'password' => '123456']),
]);
$buyer_logged = ($r['code'] === 200 && !str_contains($r['body'] ?? '', 'غير صحيحة'));
e2e_log($results, 'Buyer login (0501111111)', $buyer_logged, 'HTTP ' . $r['code']);

// 4. Buyer pages after login
foreach (['/companies.php', '/buyer.php?section=dashboard', '/nafath.php', '/sanad.php'] as $p) {
    $r = e2e_request(rtrim($base, '/') . $p);
    $ok = $r['code'] === 200 && !str_contains($r['body'] ?? '', 'login.php');
    e2e_log($results, 'Buyer page ' . basename($p), $ok, 'HTTP ' . $r['code']);
}

// 5. Fetch an active auction id
$r = e2e_request(rtrim($base, '/') . '/auctions.php');
preg_match('/auction-live\.php\?id=(\d+)/', $r['body'] ?? '', $m);
if (!$m) preg_match('/auction-room\.php\?id=(\d+)/', $r['body'] ?? '', $m);
$auction_id = (int)($m[1] ?? 0);
e2e_log($results, 'Find active auction', $auction_id > 0, $auction_id ? "id=$auction_id" : 'none found');

// 6. Bid API (may fail if nafath/sanad required — that's expected behavior)
if ($auction_id) {
    $r = e2e_request(rtrim($base, '/') . '/api/bid.php', [
        'json' => ['auction_id' => $auction_id, 'amount' => 999999999],
    ]);
    $data = json_decode($r['body'] ?? '', true);
    $bid_responded = is_array($data) && isset($data['success']);
    e2e_log($results, 'Bid API responds JSON', $bid_responded, substr($r['body'] ?? '', 0, 120));
    if ($bid_responded) {
        e2e_log($results, 'Bid API auth/validation', !$data['success'] || isset($data['message']), $data['message'] ?? '');
    }
}

// 7. Seller login (new session)
@unlink($cookie_jar);
$cookie_jar = tempnam(sys_get_temp_dir(), 'fleetx_seller_');
$r = e2e_request($login_url, [
    'post' => http_build_query(['login_type' => 'company', 'mobile' => '0500000002', 'password' => '123456']),
]);
$seller_logged = ($r['code'] === 200 && !str_contains($r['body'] ?? '', 'غير صحيحة'));
e2e_log($results, 'Seller login (0500000002)', $seller_logged, 'HTTP ' . $r['code']);

$r = e2e_request(rtrim($base, '/') . '/seller.php?section=fleet');
e2e_log($results, 'Seller fleet dashboard', $r['code'] === 200, 'HTTP ' . $r['code']);

$r = e2e_request(rtrim($base, '/') . '/add-auction.php');
e2e_log($results, 'Seller add auction page', $r['code'] === 200, 'HTTP ' . $r['code']);

// 8. Inspector login
@unlink($cookie_jar);
$cookie_jar = tempnam(sys_get_temp_dir(), 'fleetx_insp_');
$r = e2e_request($login_url, [
    'post' => http_build_query(['login_type' => 'trader', 'mobile' => '0503333333', 'password' => '123456']),
]);
$insp_logged = ($r['code'] === 200 && !str_contains($r['body'] ?? '', 'غير صحيحة'));
e2e_log($results, 'Inspector login (0503333333)', $insp_logged, 'HTTP ' . $r['code']);

$r = e2e_request(rtrim($base, '/') . '/inspector.php');
e2e_log($results, 'Inspector panel', $r['code'] === 200, 'HTTP ' . $r['code']);

$r = e2e_request(rtrim($base, '/') . '/inspector-report.php?id=1');
$report_ok = ($r['code'] === 200);
e2e_log($results, 'Inspector report form', $report_ok, 'HTTP ' . $r['code']);

// 9. Notifications API
@unlink($cookie_jar);
$cookie_jar = tempnam(sys_get_temp_dir(), 'fleetx_buyer2_');
e2e_request($login_url, ['post' => http_build_query(['login_type' => 'trader', 'mobile' => '0501111111', 'password' => '123456'])]);
$r = e2e_request(rtrim($base, '/') . '/api/notifications.php?action=list');
$notif = json_decode($r['body'] ?? '', true);
e2e_log($results, 'Notifications API', is_array($notif) && ($notif['success'] ?? false), 'HTTP ' . $r['code']);

@unlink($cookie_jar);

$passed = count(array_filter($results, fn($x) => $x['ok']));
$failed = count($results) - $passed;
echo "\n=== SUMMARY: $passed passed, $failed failed / " . count($results) . " total ===\n";

if (!$is_cli) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['summary' => ['passed' => $passed, 'failed' => $failed], 'results' => $results], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

exit($failed > 0 ? 1 : 0);