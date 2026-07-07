<?php
/**
 * Hostinger hPanel login + FTP discovery + file upload attempt
 */
$email = 'ahmedkrishna11@gmail.com';
$pass  = '*A7medfouad*';
$jar   = __DIR__ . '/hpanel_session.txt';
@unlink($jar);

function hcurl($url, $jar, $opts = []) {
    $ch = curl_init($url);
    $headers = $opts['headers'] ?? [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Accept: application/json, text/plain, */*',
    ];
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    if (!empty($opts['post'])) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($opts['post']) ? json_encode($opts['post']) : $opts['post']);
        if (is_array($opts['post'])) {
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
    }
    if (!empty($opts['referer'])) curl_setopt($ch, CURLOPT_REFERER, $opts['referer']);
    $body = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    return ['code' => $info['http_code'], 'url' => $info['url'], 'body' => $body ?: ''];
}

echo "=== Hostinger hPanel Login ===\n\n";

// 1. Auth page
$r = hcurl('https://auth.hostinger.com/api/v1/pub/login', $jar, [
    'post' => ['username' => $email, 'password' => $pass],
    'headers' => [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'Accept: application/json',
        'Content-Type: application/json',
        'Origin: https://auth.hostinger.com',
        'Referer: https://auth.hostinger.com/login',
    ],
]);
echo "Login API: HTTP {$r['code']}\n";
echo substr($r['body'], 0, 500) . "\n\n";

$data = json_decode($r['body'], true);
if ($data) {
    echo "JSON keys: " . implode(', ', array_keys($data)) . "\n";
    if (isset($data['token'])) echo "Got token\n";
    if (isset($data['access_token'])) echo "Got access_token\n";
    if (isset($data['error'])) echo "Error: {$data['error']}\n";
    if (isset($data['message'])) echo "Message: {$data['message']}\n";
}

// 2. Try alternate login endpoints
$endpoints = [
    ['https://auth.hostinger.com/api/v1/auth/login', ['email' => $email, 'password' => $pass]],
    ['https://auth.hostinger.com/api/v1/login', ['email' => $email, 'password' => $pass]],
    ['https://hpanel.hostinger.com/api/login', ['username' => $email, 'password' => $pass]],
];
foreach ($endpoints as [$url, $payload]) {
    $r2 = hcurl($url, $jar, ['post' => $payload]);
    echo "\n$url => {$r2['code']}: " . substr($r2['body'], 0, 200) . "\n";
}

// 3. Try hPanel pages after login
$pages = [
    'https://hpanel.hostinger.com/',
    'https://hpanel.hostinger.com/websites',
    'https://hpanel.hostinger.com/websites/mazadi.bearand.com/files/file-manager',
    'https://hpanel.hostinger.com/hosting/mazadi.bearand.com/files/file-manager',
];
foreach ($pages as $page) {
    $r3 = hcurl($page, $jar);
    $hasLogin = str_contains($r3['body'], 'login') || str_contains($r3['body'], 'Sign in');
    echo "\n$page => {$r3['code']} len=" . strlen($r3['body']) . " login_page=" . ($hasLogin ? 'yes' : 'no') . "\n";
}

// 4. FTP accounts API guesses
$ftpApis = [
    'https://hpanel.hostinger.com/api/hosting/mazadi.bearand.com/ftp',
    'https://hpanel.hostinger.com/api/websites/mazadi.bearand.com/ftp-accounts',
    'https://hpanel.hostinger.com/api/hosting/ftp-accounts',
];
foreach ($ftpApis as $api) {
    $r4 = hcurl($api, $jar);
    echo "\n$api => {$r4['code']}: " . substr($r4['body'], 0, 300) . "\n";
}

// Save cookies
echo "\nCookies saved to hpanel_session.txt\n";
$cookieContent = file_exists($jar) ? file_get_contents($jar) : '';
echo $cookieContent;