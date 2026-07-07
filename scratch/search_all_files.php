<?php
$files = glob('*.php');
$queries = ['hero-title-main', 'hero-subtitle-main', 'auctions-hero', 'page-header'];

foreach ($files as $file) {
    $content = file_get_contents($file);
    foreach ($queries as $query) {
        if (strpos($content, $query) !== false) {
            echo "File: $file | Match: $query\n";
        }
    }
}
?>
