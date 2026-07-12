<?php
/**
 * extend_auctions.php — Extend active auction + event end times
 * /extend_auctions.php?key=mazad2026
 */
require_once 'config.php';
header('Content-Type: text/plain; charset=utf-8');
if (!isset($_GET['key']) || $_GET['key'] !== 'mazad2026') die('Unauthorized');
if (!$db_connected) die('DB error');

echo "=== FleetX Extend Auctions & Events ===\n\n";

$result = fleetx_refresh_event_end_times($conn);
echo "Events updated: {$result['events']}\n";
echo "Auctions updated: {$result['auctions']}\n";
echo "Fallback horizon: {$result['fallback']}\n\n";

echo "-- Sample active auctions --\n";
$r = $conn->query("SELECT id, event_id, status, end_time, current_price FROM auctions WHERE status IN ('active','live') ORDER BY id LIMIT 5");
while ($row = $r->fetch_assoc()) {
    echo " auction #{$row['id']} event={$row['event_id']} end={$row['end_time']} price={$row['current_price']}\n";
}

echo "\n-- Sample active events --\n";
$re = $conn->query("SELECT id, title, status, end_time FROM auction_events WHERE status IN ('active','upcoming') ORDER BY id LIMIT 5");
while ($row = $re->fetch_assoc()) {
    $tl = timeLeft($row['end_time']);
    echo " event #{$row['id']} end={$row['end_time']} remaining={$tl['days']}d {$tl['hours']}h {$tl['mins']}m\n";
}
echo "\nDone.\n";