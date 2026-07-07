<?php
$base = 'https://auth-db1904.hstgr.io';
$jar = tempnam(sys_get_temp_dir(), 'pma2_');
$db = 'u274391035_db_BbBE85ay';
$user = 'u274391035_usr_BbBE85ay';
$pass = '*A7medfouad*';

function req($url, $jar, $post = null) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_TIMEOUT => 60,
    ]);
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    }
    return curl_exec($ch);
}

function token_from($html) {
    if (preg_match('/name="token"\s+value="([^"]+)"/', $html, $m)) return $m[1];
    if (preg_match('/"token":"([^"]+)"/', $html, $m)) return $m[1];
    return null;
}

$html = req("$base/index.php", $jar);
$token = token_from($html);
req("$base/index.php", $jar, http_build_query([
    'pma_username' => $user, 'pma_password' => $pass, 'server' => '1', 'token' => $token,
]));

$hash = password_hash('123456', PASSWORD_DEFAULT);
$queries = [
    "USE `$db`",
    "UPDATE users SET password_hash='$hash', is_active=1, nafath_verified=1",
    "UPDATE users SET sanad_limit=GREATEST(COALESCE(sanad_limit,0),500000), wallet_balance=GREATEST(COALESCE(wallet_balance,0),50000) WHERE role IN ('buyer','admin')",
    "SELECT id,mobile,role,sanad_limit,LEFT(password_hash,20) as ph FROM users ORDER BY id LIMIT 8",
];

foreach ($queries as $i => $sql) {
    $page = req("$base/index.php?route=/server/sql", $jar);
    $tok = token_from($page) ?: $token;
    $result = req("$base/index.php?route=/import", $jar, http_build_query([
        'db' => $db,
        'token' => $tok,
        'is_js_confirmed' => '1',
        'sql_query' => $sql,
        'sql_delimiter' => ';',
        'show_query' => '1',
        'ajax_request' => 'true',
    ]));
    echo "Q$i: " . substr($sql, 0, 60) . "\n";
    if (preg_match('/class="sqlquery"/', $result)) echo "  -> query box found\n";
    if (preg_match('/# rows? affected/i', $result, $m)) echo "  -> {$m[0]}\n";
    if (preg_match_all('/<td[^>]*>([^<]+)</', $result, $cells)) {
        $vals = array_slice($cells[1], 0, 20);
        if (count($vals) > 3) echo "  -> data: " . implode(' | ', $vals) . "\n";
    }
    if (str_contains($result, 'Duplicate column')) echo "  -> duplicate column (ok)\n";
    if (str_contains($result, 'error') && str_contains($result, 'Access denied')) echo "  -> ACCESS DENIED\n";
}

@unlink($jar);