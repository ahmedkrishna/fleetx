<?php
$base = 'https://mazadi.bearand.com';
$jar = tempnam(sys_get_temp_dir(), 'dbg_');
$mobile = '05' . random_int(10000000, 99999999);
$pass = 'Test1234!';

function rq($url, $jar, $post = null) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
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
    'national_id' => '1234567890', 'city' => 'الرياض', 'email' => 'e2e@test.com',
]));
echo "Register $mobile code=$c reg=" . (str_contains($b, 'تم إنشاء') ? 'OK' : 'FAIL') . "\n";
if (preg_match('/error-box[^>]*>\s*([^<]+)/u', $b, $m)) echo "Error: " . trim($m[1]) . "\n";

[$c2, $b2] = rq("$base/login.php", $jar, http_build_query([
    'login_type' => 'trader', 'mobile' => $mobile, 'password' => $pass,
]));
echo "Login code=$c2 ok=" . (!str_contains($b2, 'غير صحيحة') ? 'yes' : 'no') . "\n";

[$c3, $b3] = rq("$base/api/notifications.php?action=list", $jar);
echo "Notif: $b3\n";

[$c4, $b4] = rq("$base/event.php?id=1", $jar);
preg_match_all('/href=["\']([^"\']*id=\d+[^"\']*)["\']/', $b4, $links);
echo "Event links: " . implode(', ', array_slice($links[1] ?? [], 0, 8)) . "\n";

[$c5, $b5] = rq("$base/auctions.php?type=instant", $jar);
preg_match_all('/vehicle-details\.php\?id=(\d+)/', $b5, $inst);
echo "Instant auctions: " . implode(',', $inst[1] ?? []) . "\n";

@unlink($jar);