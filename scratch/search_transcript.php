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
            
            // Look for File Path line in content
            if (preg_match('/File Path:\s*(.*)/i', $content, $matches)) {
                $file_path = trim($matches[1]);
                if (strpos($file_path, 'index.php') !== false) {
                    // Count how many lines are in this view
                    $lines = explode("\n", $content);
                    $line_count = 0;
                    foreach ($lines as $l) {
                        if (preg_match('/^(\d+):/', $l)) {
                            $line_count++;
                        }
                    }
                    echo "Step: $step | Path: $file_path | Line Count in View: $line_count\n";
                }
            }
        }
    }
    fclose($handle);
}
?>
