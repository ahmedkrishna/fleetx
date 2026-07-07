<?php
$base = 'https://mazadi.bearand.com';
$jar = tempnam(sys_get_temp_dir(), 'wal_');
$mobile = '05' . random_int(10000000, 99999999);
$email = 'wal_' . time() . '@test.local';
$pass = 'Test1234!';

function rq($url, $jar, $post = null, $follow = false) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => $follow,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_TIMEOUT => 30,
    ]);
    if ($post) { curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, $post); }
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $body];
}

rq("$base/register.php", $jar, http_build_query([
    'register_submit'=>'1','step'=>'3','role'=>'buyer','full_name'=>'Wal Test',
    'mobile'=>$mobile,'password'=>$pass,'national_id'=>'1111222233','city'=>'الرياض','email'=>$email,
]));
rq("$base/login.php", $jar, http_build_query(['login_type'=>'trader','mobile'=>$mobile,'password'=>$pass]), true);
[$c, $b] = rq("$base/wallet-topup.php", $jar, http_build_query(['amount'=>'5000']), true);
echo "Topup code=$c balance_found=" . (preg_match('/([\d,]+)\s*ر\.س/u', $b, $m) ? $m[1] : 'none') . "\n";

// Test sanad without ALTER issue - hit buyer.php for sanad display
rq("$base/nafath.php", $jar, http_build_query(['verify_nafath'=>'1']), true);
[$c2, $b2] = rq("$base/sanad.php", $jar, http_build_query(['amount'=>'500000']), true);
echo "Sanad code=$c2 fatal=" . (str_contains($b2, 'Fatal error') ? 'YES' : 'NO') . "\n";
preg_match_all('/([\d,]+)\s*ر\.س/u', $b2, $ams);
echo "Sanad amounts: " . implode(', ', array_unique($ams[1] ?? [])) . "\n";

@unlink($jar);