<?php
$root = dirname(__DIR__);

function extract_styles(string $content): string {
    preg_match_all('/<style>(.*?)<\/style>/is', $content, $m);
    return implode("\n\n/* --- */\n\n", array_map('trim', $m[1]));
}

function strip_styles(string $content): string {
    return preg_replace('/<style>.*?<\/style>\s*/is', '', $content);
}

$files = [
    'buyer.php' => 'BUYER DASHBOARD',
    'seller.php' => 'SELLER DASHBOARD',
    'index.php' => 'HOMEPAGE',
];

$append = '';
foreach ($files as $file => $label) {
    $path = $root . '/' . $file;
    $content = file_get_contents($path);
    $css = extract_styles($content);
    $append .= "\n\n/* ==========================================================================\n   {$label} — migrated from {$file}\n   ========================================================================== */\n";
    $append .= $css . "\n";
    file_put_contents($path, strip_styles($content));
    echo "Stripped styles from {$file} (" . strlen($css) . " bytes)\n";
}

$cssPath = $root . '/assets/css/platform-ui.css';
file_put_contents($cssPath, file_get_contents($cssPath) . $append);
echo "Appended to platform-ui.css\n";