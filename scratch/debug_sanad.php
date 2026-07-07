<?php
$base = 'https://mazadi.bearand.com';
$jar = tempnam(sys_get_temp_dir(), 'san_');
$mobile = '05' . random_int(10000000, 99999999);
$email = 'sanad_' . time() . '@test.local';
$pass = 'Test1234!';

function rq($url, $jar, $opts = []) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => !empty($opts['follow']),
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_TIMEOUT => 30,
    ]);
    if (!empty($opts['post'])) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $opts['post']);
    }
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $body];
}

rq("$base/register.php", $jar, ['post' => http_build_query([
    'register_submit'=>'1','step'=>'3','role'=>'buyer','full_name'=>'Sanad Test',
    'mobile'=>$mobile,'password'=>$pass,'national_id'=>'9876543210','city'=>'الرياض','email'=>$email,
])]);
rq("$base/login.php", $jar, ['post' => http_build_query(['login_type'=>'trader','mobile'=>$mobile,'password'=>$pass]), 'follow'=>true]);

[$c1, $b1] = rq("$base/nafath.php", $jar, ['post' => http_build_query(['verify_nafath'=>'1']), 'follow'=>true]);
echo "Nafath: code=$c1 verified=" . (str_contains($b1, 'تم التحقق') || str_contains($b1, 'success') ? 'maybe' : 'check') . "\n";

[$c2, $b2] = rq("$base/sanad.php", $jar, ['post' => http_build_query(['amount'=>'2000000']), 'follow'=>true]);
echo "Sanad post code=$c2\n";
if (preg_match('/الحد الحالي[^0-9]*([\d,]+)/u', $b2, $m)) echo "Limit shown: {$m[1]}\n";
if (preg_match('/sanad_limit|الحد المالي/ui', $b2)) echo "Has limit text\n";
preg_match_all('/([\d,]+)\s*ر\.س/u', $b2, $amounts);
echo "Amounts on page: " . implode(', ', array_slice($amounts[1] ?? [], 0, 5)) . "\n";

[$c3, $b3] = rq("$base/api/notifications.php?action=list", $jar);
echo "Notifications: $b3\n";

[$c4, $b4] = rq("$base/api/bid.php", $jar, ['post' => null]);
// use json post
$ch = curl_init("$base/api/bid.php");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode(['auction_id'=>1,'amount'=>90500]),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar,
]);
echo "Bid: " . curl_exec($ch) . "\n";
curl_close($ch);

@unlink($jar);