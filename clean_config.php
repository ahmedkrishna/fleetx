<?php
$content = file_get_contents('config.php');
$pos = strpos($content, '// ── Mock data for local dev');
if ($pos !== false) {
    $new_content = substr($content, 0, $pos) . "// ── Mock data for local dev (if DB not connected) ─────────\nfunction getMockEvents() { return []; }\nfunction countMockAuctions() { return ['live' => 0, 'instant' => 0, 'sealed' => 0, 'upcoming' => 0]; }\nfunction getMockAuctions(\$limit = 9) { return []; }\n?>\n";
    file_put_contents('config.php', $new_content);
    echo "Success\n";
} else {
    echo "Marker not found\n";
}
