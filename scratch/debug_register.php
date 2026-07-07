<?php
$base = 'https://mazadi.bearand.com';
$jar = tempnam(sys_get_temp_dir(), 'dbg_');
$mobile = '05' . random_int(10000000, 99999999);
$email = 'e2e_' . time() . '@test.local';
$pass = 'Test1234!';

function rq($url, $jar, $post = null) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_TIMEOUT => 30,
    ]);
    if ($post) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    }
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $body];
}

[$c, $b] = rq("$base/register.php", $jar, http_build_query([
    'register_submit' => '1', 'step' => '3', 'role' => 'buyer',
    'full_name' => 'E2E Test', 'mobile' => $mobile, 'password' => $pass,
    'national_id' => '1234567890', 'city' => 'الرياض', 'email' => $email,
]));
echo "Register mobile=$mobile email=$email\n";
echo "success-screen: " . (str_contains($b, 'success-screen') ? 'YES' : 'NO') . "\n";
echo "تم إنشاء: " . (str_contains($b, 'تم إنشاء حسابك') ? 'YES' : 'NO') . "\n";
if (preg_match('/error-box[^>]*>.*?<p[^>]*>([^<]+)/us', $b, $m)) echo "Error: " . trim(strip_tags($m[1])) . "\n";
if (preg_match('/class="error[^"]*"[^>]*>([^<]+)/u', $b, $m2)) echo "Error2: " . trim($m2[1]) . "\n";

[$c2, $b2] = rq("$base/login.php", $jar, http_build_query([
    'login_type' => 'trader', 'mobile' => $mobile, 'password' => $pass,
]));
echo "Login code=$c2 redirect=" . (str_contains($b2, 'غير صحيحة') ? 'FAIL' : 'OK') . "\n";

// Follow redirect manually
[$c3, $b3] = rq("$base/companies.php", $jar);
echo "Companies after login: code=$c3 logged=" . (!str_contains($b3, 'login.php') ? 'yes' : 'no') . "\n";

rq("$base/nafath.php", $jar, http_build_query(['verify_nafath' => '1']));
rq("$base/sanad.php", $jar, http_build_query(['amount' => '2000000']));

[$c4, $b4] = rq("$base/auction-live.php?id=1", $jar);
preg_match('/currentPrice\s*=\s*(\d+)/', $b4, $pm);
$price = (int)($pm[1] ?? 0);
$bid = $price + 500;
echo "Auction price=$price bid=$bid\n";

$ch = curl_init("$base/api/bid.php");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode(['auction_id' => 1, 'amount' => $bid]),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_COOKIEJAR => $jar,
    CURLOPT_COOKIEFILE => $jar,
]);
$bidBody = curl_exec($ch);
curl_close($ch);
echo "Bid: $bidBody\n";

@unlink($jar);