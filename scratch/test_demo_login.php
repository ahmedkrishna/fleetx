<?php
$base = 'https://mazadi.bearand.com';
$jar = tempnam(sys_get_temp_dir(), 'fx_');

function req($url, $jar, $post = null) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HEADER => true,
    ]);
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    }
    return curl_exec($ch);
}

$users = [
    'buyer' => ['0501111111', 'trader'],
    'seller' => ['0500000002', 'company'],
    'inspector' => ['0503333333', 'trader'],
];

foreach ($users as $role => [$mob, $type]) {
    $resp = req("$base/login.php", $jar, http_build_query([
        'login_type' => $type, 'mobile' => $mob, 'password' => '123456',
    ]));
    $ok = preg_match('/^HTTP\/\d[^ ]* 30[12]/m', $resp) && !str_contains($resp, 'غير صحيحة');
    echo "$role ($mob): " . ($ok ? 'LOGIN OK' : 'FAIL') . "\n";
    if (!$ok) {
        if (preg_match('/Location: (.+)/', $resp, $m)) echo "  Location: " . trim($m[1]) . "\n";
        if (str_contains($resp, 'غير صحيحة')) echo "  Wrong password\n";
        if (str_contains($resp, 'غير مسجل')) echo "  Not registered\n";
    }
}

// Test sanad limit raise
req("$base/login.php", $jar, http_build_query(['login_type'=>'trader','mobile'=>'0501111111','password'=>'123456']));
$resp2 = req("$base/sanad.php", $jar, http_build_query(['action'=>'raise_limit','new_limit'=>500000]));
$body = substr($resp2, strpos($resp2, "\r\n\r\n") + 4);
echo "\nSanad raise: " . (str_contains($body, 'success') || str_contains($body, 'تم') ? 'OK' : 'FAIL') . "\n";
echo substr($body, 0, 300) . "\n";

@unlink($jar);