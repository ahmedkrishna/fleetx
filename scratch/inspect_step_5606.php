<?php
$transcript_path = 'C:\\Users\\ahmed\\.gemini\\antigravity\\brain\\9c0d111c-ac9f-4450-99da-4c07893bfd64\\.system_generated\\logs\\transcript.jsonl';

if (!file_exists($transcript_path)) {
    die("Transcript file not found.\n");
}

$handle = fopen($transcript_path, "r");
if ($handle) {
    while (($line = fgets($handle)) !== false) {
        $data = json_decode($line, true);
        if ($data && isset($data['step_index']) && (int)$data['step_index'] === 5606 && isset($data['content'])) {
            $content = $data['content'];
            $lines = explode("\n", $content);
            echo "Total lines in step 5606 content: " . count($lines) . "\n";
            for ($i = 0; $i < min(100, count($lines)); $i++) {
                if (preg_match('/^(155\d|156\d|157\d):/', $lines[$i])) {
                    echo $lines[$i] . "\n";
                }
            }
            break;
        }
    }
    fclose($handle);
}
?>
