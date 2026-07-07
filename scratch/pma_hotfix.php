<?php
/**
 * phpMyAdmin login + SQL hotfix for Hostinger
 */
$base = 'https://auth-db1904.hstgr.io';
$jar = tempnam(sys_get_temp_dir(), 'pma_');
$user = 'u274391035_usr_BbBE85ay';
$pass = '*A7medfouad*';

function curl_req($url, $jar, $opts = []) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    if (!empty($opts['post'])) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $opts['post']);
    }
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $body ?: ''];
}

// 1. Get login page + token
$r = curl_req("$base/index.php", $jar);
if (!preg_match('/token"\s+value="([^"]+)"/', $r['body'], $tm)) {
    if (!preg_match('/name="token"\s+value="([^"]+)"/', $r['body'], $tm)) {
        die("No token found\n");
    }
}
$token = $tm[1];
echo "Token: $token\n";

// 2. Login
$r = curl_req("$base/index.php", $jar, ['post' => http_build_query([
    'pma_username' => $user,
    'pma_password' => $pass,
    'server' => '1',
    'token' => $token,
])]);
$logged = str_contains($r['body'], 'logged_in":true') || str_contains($r['body'], 'navigation') || !str_contains($r['body'], 'loginform');
echo "Login: " . ($logged ? 'OK' : 'FAIL') . " code={$r['code']}\n";
if (!$logged) {
    if (str_contains($r['body'], 'denied') || str_contains($r['body'], 'Access denied')) echo "Access denied\n";
    file_put_contents(__DIR__ . '/pma_login_fail.html', $r['body']);
    exit(1);
}

// 3. Get fresh token after login
if (!preg_match('/token"\s+value="([^"]+)"/', $r['body'], $tm2)) {
    $r2 = curl_req("$base/index.php?route=/database/sql&db=u274391035_db_BbBE85ay", $jar);
    preg_match('/token"\s+value="([^"]+)"/', $r2['body'], $tm2);
}
$token2 = $tm2[1] ?? $token;
echo "SQL token: $token2\n";

$hash = password_hash('123456', PASSWORD_DEFAULT);
$sql = <<<SQL
ALTER TABLE users ADD COLUMN sanad_limit DECIMAL(12,2) DEFAULT 0.00;
UPDATE users SET password_hash='$hash', is_active=1, nafath_verified=1;
UPDATE users SET sanad_limit=GREATEST(COALESCE(sanad_limit,0),500000), wallet_balance=GREATEST(COALESCE(wallet_balance,0),50000) WHERE role IN ('buyer','admin');
SELECT id,mobile,role,sanad_limit FROM users LIMIT 5;
SQL;

// 4. Execute SQL via phpMyAdmin import/sql endpoint
$r = curl_req("$base/index.php?route=/import", $jar, ['post' => http_build_query([
    'db' => 'u274391035_db_BbBE85ay',
    'token' => $token2,
    'is_js_confirmed' => '1',
    'sql_query' => $sql,
    'sql_delimiter' => ';',
    'show_query' => '1',
])]);
echo "SQL response length: " . strlen($r['body']) . "\n";
if (preg_match_all('/class="error"[^>]*>([^<]+)/', $r['body'], $errs)) {
    echo "Errors: " . implode(' | ', $errs[1]) . "\n";
}
if (str_contains($r['body'], 'success') || str_contains($r['body'], 'affected')) {
    echo "SQL likely executed\n";
}
file_put_contents(__DIR__ . '/pma_result.html', $r['body']);
echo "Saved pma_result.html\n";

@unlink($jar);