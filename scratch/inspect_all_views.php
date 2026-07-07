<?php
$transcript_path = 'C:\\Users\\ahmed\\.gemini\\antigravity\\brain\\9c0d111c-ac9f-4450-99da-4c07893bfd64\\.system_generated\\logs\\transcript.jsonl';

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
            
            if (strpos($content, '/Mazad%20website/index.php') !== false && strpos($content, 'File Path:') !== false) {
                // Find all line numbers in this content
                $lines = explode("\n", $content);
                $line_numbers = [];
                foreach ($lines as $l) {
                    if (preg_match('/^(\d+):/', $l, $m)) {
                        $line_numbers[] = (int)$m[1];
                    }
                }
                if (count($line_numbers) > 0) {
                    $min = min($line_numbers);
                    $max = max($line_numbers);
                    echo "Step: $step | Lines: $min - $max | Count: " . count($line_numbers) . "\n";
                } else {
                    echo "Step: $step | No line numbers found in content\n";
                }
            }
        }
    }
    fclose($handle);
}
?>
