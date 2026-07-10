<?php
$base = 'https://mazadi.bearand.com';
$jar = tempnam(sys_get_temp_dir(), 'dbg_');
$mobile = '05' . random_int(10000000, 99999999);
$email = 'e2e_' . time() . '_' . random_int(1000, 9999) . '@fleetx.test';
$pass = 'Test1234!';

function rq($url, $jar, $opts = []) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_TIMEOUT => 45,
    ]);
    if (!empty($opts['post'])) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $opts['post']);
    }
    if (!empty($opts['json'])) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($opts['json']));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    $body = curl_exec($ch);
    curl_close($ch);
    return $body ?: '';
}

$r = rq("$base/api/otp.php", $jar, ['json' => [
    'action' => 'send', 'mobile' => $mobile, 'purpose' => 'register', 'e2e_key' => 'mazad2026',
]]);
$d = json_decode($r, true);
$otp = $d['debug_otp'] ?? '';
echo "Mobile: $mobile\nOTP response: $r\nOTP: $otp\n\n";

// Register consumes the OTP (same as e2e — do not call verify API first)
$b = rq("$base/register.php", $jar, ['post' => http_build_query([
    'register_submit' => '1', 'step' => '3', 'role' => 'buyer',
    'full_name' => 'E2E', 'mobile' => $mobile, 'password' => $pass,
    'national_id' => '1234567890', 'city' => 'الرياض', 'email' => $email,
    'otp_code' => $otp,
])]);

if (preg_match('/error-box[^>]*>\s*<i[^>]*><\/i>\s*([^<]+)/u', $b, $m)) {
    echo "Error: " . trim($m[1]) . "\n";
}
$reg_ok = str_contains($b, 'success-screen')
    && (str_contains($b, 'تم إنشاء حسابك') || str_contains($b, 'تم استلام'));
echo 'Register: ' . ($reg_ok ? 'PASS' : 'FAIL') . "\n";
@unlink($jar);
exit($reg_ok ? 0 : 1);