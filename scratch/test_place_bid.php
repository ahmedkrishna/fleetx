<?php
$base = 'https://mazadi.bearand.com';
$jar = tempnam(sys_get_temp_dir(), 'pb_');
$mobile = '05' . random_int(10000000, 99999999);
$email = 'pb_' . time() . '@test.local';
$pass = 'Test1234!';

function go($url, $jar, $post = null, $follow = false) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => $follow,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_TIMEOUT => 30,
    ]);
    if ($post) { curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, $post); }
    curl_exec($ch);
    curl_close($ch);
}

go("$base/register.php", $jar, http_build_query([
    'register_submit'=>'1','step'=>'3','role'=>'buyer','full_name'=>'PB','mobile'=>$mobile,
    'password'=>$pass,'national_id'=>'5555666677','city'=>'الرياض','email'=>$email,
]));
go("$base/login.php", $jar, http_build_query(['login_type'=>'trader','mobile'=>$mobile,'password'=>$pass]), true);
go("$base/nafath.php", $jar, http_build_query(['verify_nafath'=>'1']), true);
go("$base/wallet-topup.php", $jar, http_build_query(['amount'=>'200000']), true);

$ch = curl_init("$base/api/place-bid.php");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode(['auction_id'=>1,'amount'=>90500]),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar,
]);
echo "place-bid: " . curl_exec($ch) . "\n";
curl_close($ch);

$ch = curl_init("$base/api/bid.php");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode(['auction_id'=>1,'amount'=>91000]),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar,
]);
echo "bid: " . curl_exec($ch) . "\n";
curl_close($ch);

@unlink($jar);