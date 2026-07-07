<?php
$query = isset($argv[1]) ? $argv[1] : 'heroParticles';
$file = isset($argv[2]) ? $argv[2] : 'index.php';

if (!file_exists($file)) {
    die("File not found.\n");
}

$lines = file($file);
foreach ($lines as $i => $line) {
    if (strpos($line, $query) !== false) {
        echo ($i + 1) . ": " . trim($line) . "\n";
    }
}
?>
