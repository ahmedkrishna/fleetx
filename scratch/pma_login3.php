<?php
$base = 'https://auth-db1904.hstgr.io';
$jar = tempnam(sys_get_temp_dir(), 'pma3_');
$db = 'u274391035_db_BbBE85ay';
$user = 'u274391035_usr_BbBE85ay';
$pass = '*A7medfouad*';

function req($url, $jar, $post = null, $headers = []) {
    $ch = curl_init($url);
    $h = array_merge(['User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'], $headers);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_HTTPHEADER => $h,
    ]);
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    }
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $body];
}

[$c, $html] = req("$base/index.php?route=/&db=$db", $jar);
preg_match('/name="token"\s+value="([^"]+)"/', $html, $t);
preg_match('/name="set_session"\s+value="([^"]+)"/', $html, $s);
$token = $t[1] ?? '';
$set_session = $s[1] ?? '';
echo "token=$token set_session=$set_session\n";

[$c2, $html2] = req("$base/index.php?route=/", $jar, http_build_query([
    'set_session' => $set_session,
    'pma_username' => $user,
    'pma_password' => $pass,
    'server' => '1',
    'token' => $token,
    'db' => $db,
]));
$logged = str_contains($html2, 'logged_in":true') || str_contains($html2, 'navigation') && !str_contains($html2, 'login_form');
echo "Login attempt: code=$c2 logged=" . ($logged ? 'YES' : 'NO') . "\n";
echo "Has login_form: " . (str_contains($html2, 'login_form') ? 'yes' : 'no') . "\n";
echo "Has recaptcha: " . (str_contains($html2, 'recaptcha') ? 'yes' : 'no') . "\n";

if (preg_match('/logged_in":(true|false)/', $html2, $li)) echo "logged_in={$li[1]}\n";

file_put_contents(__DIR__.'/pma_login3.html', $html2);
@unlink($jar);