<?php
$file = 'index.php';
if (!file_exists($file)) {
    die("File not found.\n");
}

$content = file_get_contents($file);

// Find all style blocks
preg_match_all('/<style>(.*?)<\/style>/is', $content, $matches, PREG_OFFSET_CAPTURE);

if (count($matches[0]) > 1) {
    echo "Found " . count($matches[0]) . " style blocks.\n";
    
    // We want to keep the first style block (which is in the <head>)
    // And delete any other style block that contains ".services-flex-container"
    $offset_adjustment = 0;
    
    foreach ($matches[0] as $index => $match_info) {
        $block_text = $match_info[0];
        $block_offset = $match_info[1] - $offset_adjustment;
        
        // Skip the very first style block
        if ($index === 0) {
            continue;
        }
        
        if (strpos($block_text, 'services-flex-container') !== false) {
            echo "Removing duplicate style block at index $index (offset " . $match_info[1] . ")\n";
            // Replace this block with an empty string
            $content = substr_replace($content, '', $block_offset, strlen($block_text));
            $offset_adjustment += strlen($block_text);
        }
    }
    
    file_put_contents($file, $content);
    echo "Duplicate style blocks removed successfully!\n";
} else {
    echo "No duplicate style blocks found.\n";
}
?>
