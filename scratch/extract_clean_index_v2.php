<?php
$transcript_path = 'C:\\Users\\ahmed\\.gemini\\antigravity\\brain\\9c0d111c-ac9f-4450-99da-4c07893bfd64\\.system_generated\\logs\\transcript.jsonl';
$output_path = 'e:\\Design\\bearand\\Mazad website\\scratch\\rebuilt_index.php';

$file_parts = [];
$step_max = 5620; // Rebuild up to this step

if (!file_exists($transcript_path)) {
    die("Transcript file not found.\n");
}

$handle = fopen($transcript_path, "r");
if ($handle) {
    while (($line = fgets($handle)) !== false) {
        $data = json_decode($line, true);
        if ($data && isset($data['type']) && $data['type'] === 'VIEW_FILE' && isset($data['content'])) {
            $content = $data['content'];
            $step = isset($data['step_index']) ? (int)$data['step_index'] : 0;
            
            if ($step < $step_max) {
                // Verify the file path is exactly index.php
                if (strpos($content, '/Mazad%20website/index.php') !== false && strpos($content, 'File Path:') !== false) {
                    $lines = explode("\n", $content);
                    foreach ($lines as $l) {
                        if (preg_match('/^(\d+):\s(.*)/', $l, $matches)) {
                            $line_num = (int)$matches[1];
                            $line_text = $matches[2];
                            $file_parts[$line_num] = $line_text;
                        }
                    }
                }
            }
        }
    }
    fclose($handle);
}

ksort($file_parts);
echo "Extracted " . count($file_parts) . " lines for index.php.\n";

if (count($file_parts) > 0) {
    $max_line = max(array_keys($file_parts));
    echo "Max line index: $max_line\n";
    
    // Check for gaps
    $gaps = [];
    for ($i = 1; $i <= $max_line; $i++) {
        if (!isset($file_parts[$i])) {
            $gaps[] = $i;
        }
    }
    
    if (count($gaps) > 0) {
        echo "Gaps found in lines: " . implode(", ", $gaps) . "\n";
    } else {
        echo "No gaps found! Perfect reconstruction possible.\n";
    }
    
    // Write the file
    $out_handle = fopen($output_path, "w");
    if ($out_handle) {
        for ($i = 1; $i <= $max_line; $i++) {
            $line_text = isset($file_parts[$i]) ? $file_parts[$i] : "";
            fwrite($out_handle, $line_text . "\n");
        }
        fclose($out_handle);
        echo "Wrote rebuilt file to $output_path\n";
    }
}
?>
