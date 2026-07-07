<?php
/**
 * Run SQL hotfix directly against Hostinger MySQL (remote hosts)
 */
mysqli_report(MYSQLI_REPORT_OFF);
$pass = '*A7medfouad*';
$user = 'u274391035_usr_BbBE85ay';
$db   = 'u274391035_db_BbBE85ay';
$hosts = [
    'srv1904.hstgr.io',
    'auth-db1904.hstgr.io',
    'mysql.hostinger.com',
    'localhost',
];

foreach ($hosts as $host) {
    $c = @new mysqli($host, $user, $pass, $db, 3306);
    echo "$host: " . ($c->connect_error ?: 'CONNECTED') . "\n";
    if ($c->connect_error) continue;

    try { $c->query("ALTER TABLE users ADD COLUMN sanad_limit DECIMAL(12,2) DEFAULT 0.00"); echo "  sanad column ok/skip\n"; }
    catch (Throwable $e) { echo "  sanad skip: {$e->getMessage()}\n"; }

    $hash = password_hash('123456', PASSWORD_DEFAULT);
    $c->query("UPDATE users SET password_hash='$hash', is_active=1, nafath_verified=1");
    echo "  passwords reset\n";

    $c->query("UPDATE users SET sanad_limit=GREATEST(COALESCE(sanad_limit,0),500000), wallet_balance=GREATEST(COALESCE(wallet_balance,0),50000) WHERE role IN ('buyer','admin')");
    echo "  sanad/wallet set\n";

    $r = $c->query('SELECT id,mobile,role,sanad_limit,(password_hash IS NOT NULL) as has_pw FROM users LIMIT 5');
    while ($row = $r->fetch_assoc()) print_r($row);
    break;
}