<?php
mysqli_report(MYSQLI_REPORT_OFF);
$hosts = ['srv1904.hstgr.io', 'mysql.hostinger.com', 'auth-db1904.hstgr.io', 'localhost'];
foreach ($hosts as $host) {
    $c = @new mysqli($host, 'u274391035_usr_BbBE85ay', '*A7medfouad*', 'u274391035_db_BbBE85ay', 3306);
    echo "$host: " . ($c->connect_error ?: 'OK') . "\n";
    if (!$c->connect_error) {
        $r = $c->query('SELECT id, mobile, role, (password_hash IS NOT NULL AND password_hash != "") as has_pw FROM users LIMIT 5');
        while ($row = $r->fetch_assoc()) {
            print_r($row);
        }
        break;
    }
}