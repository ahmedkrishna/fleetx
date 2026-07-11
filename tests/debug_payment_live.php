<?php
$base = 'https://mazadi.bearand.com';
$jar = tempnam(sys_get_temp_dir(), 'fx_dbg_');

function dbg($url, $jar, $opts = []) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HEADER => true,
        CURLOPT_TIMEOUT => 45,
    ]);
    if (!empty($opts['post'])) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $opts['post']);
    }
    $raw = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $parts = explode("\r\n\r\n", $raw, 2);
    return ['code' => $code, 'headers' => $parts[0] ?? '', 'body' => $parts[1] ?? ''];
}

$login = dbg("$base/login.php", $jar, [
    'post' => http_build_query(['login_type' => 'trader', 'mobile' => '0501111111', 'password' => '123456']),
]);
echo "Login code={$login['code']}\n";
if (preg_match('/^Location:\s*(.+)$/mi', $login['headers'], $lm)) {
    echo "Login Location: " . trim($lm[1]) . "\n";
}
echo "Cookies:\n" . file_get_contents($jar) . "\n";

$get = dbg("$base/checkout.php?id=8", $jar);
echo "GET checkout code={$get['code']} body_has_mada=" . (str_contains($get['body'], 'mada') ? 'yes' : 'no') . "\n";

$post = dbg("$base/checkout.php?id=8", $jar, ['post' => http_build_query(['payment_method' => 'mada'])]);
echo "POST mada code={$post['code']}\n";
echo "Headers:\n{$post['headers']}\n";
if (preg_match('/^Location:\s*(.+)$/mi', $post['headers'], $m)) {
    echo "Location: " . trim($m[1]) . "\n";
}
echo "Body snippet: " . substr(strip_tags($post['body']), 0, 300) . "\n";

@unlink($jar);