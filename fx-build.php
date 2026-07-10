<?php
require_once 'config.php';
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
echo "FleetX build: " . FLEETX_CSS_VER . "\n";
echo "Time: " . date('Y-m-d H:i:s T') . "\n";
echo "Home CSS: " . (function_exists('fleetx_home_live_css_href') ? fleetx_home_live_css_href() : 'n/a') . "\n";
$index = __DIR__ . '/index.php';
$css = __DIR__ . '/assets/css/home-live.css';
echo "index.php modified: " . (is_file($index) ? date('Y-m-d H:i:s', filemtime($index)) : 'missing') . "\n";
echo "home-live.css modified: " . (is_file($css) ? date('Y-m-d H:i:s', filemtime($css)) : 'missing') . "\n";
echo "Has panel markup: " . (is_file($index) && strpos(file_get_contents($index), 'fx-auctions-panel') !== false ? 'yes' : 'no') . "\n";
echo "Has quick-stats HTML: " . (is_file($index) && preg_match('/class="fx-home-quick-stats"/', file_get_contents($index)) ? 'yes' : 'no') . "\n";