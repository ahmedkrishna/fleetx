<?php
$base = 'https://mazadi.bearand.com';
$jar = tempnam(sys_get_temp_dir(), 'sanad_');
$mobile = '05' . random_int(10000000, 99999999);
$pass = 'Test1234!';

function req($url, $jar, $post = null, $follow = false) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => $follow,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_TIMEOUT => 30,
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

// Register + login + nafath
req("$base/register.php", $jar, http_build_query([
    'register_submit'=>'1','step'=>'3','role'=>'buyer',
    'full_name'=>'Sanad Test','mobile'=>$mobile,'password'=>$pass,
    'national_id'=>'1234567890','city'=>'الرياض','email'=>"t$mobile@test.com",
]));
req("$base/login.php", $jar, http_build_query(['login_type'=>'trader','mobile'=>$mobile,'password'=>$pass]), true);
req("$base/nafath.php", $jar, http_build_query(['verify'=>'1']), true);

[$code, $body] = req("$base/sanad.php", $jar, http_build_query(['amount'=>'2000000']), true);
echo "HTTP $code\n";
if (str_contains($body, 'Duplicate column')) echo "ISSUE: Duplicate column fatal (old sanad.php)\n";
elseif (str_contains($body, 'Fatal error')) echo "ISSUE: Fatal error\n";
else echo "sanad.php POST looks OK\n";

@unlink($jar);