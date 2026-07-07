<?php
/**
 * extend_auctions.php — Extend active auction end times
 * /extend_auctions.php?key=mazad2026
 */
require_once 'config.php';
header('Content-Type: text/plain; charset=utf-8');
if (!isset($_GET['key']) || $_GET['key'] !== 'mazad2026') die('Unauthorized');
if (!$db_connected) die('DB error');

$future = date('Y-m-d H:i:s', time() + 86400 * 7);
$conn->query("UPDATE auctions SET status='active', end_time='$future' WHERE status IN ('active','live') AND (end_time IS NULL OR end_time < NOW())");
$conn->query("UPDATE auctions SET status='active', end_time='$future' WHERE id IN (SELECT id FROM (SELECT id FROM auctions WHERE type='live' LIMIT 10) t)");
$affected = $conn->affected_rows;
echo "Extended auctions to $future\n";
echo "Rows updated: $affected\n";
$r = $conn->query("SELECT id, status, end_time, current_price FROM auctions WHERE status IN ('active','live') LIMIT 5");
while ($row = $r->fetch_assoc()) {
    echo " #{$row['id']} status={$row['status']} end={$row['end_time']} price={$row['current_price']}\n";
}