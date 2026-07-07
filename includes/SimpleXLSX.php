<?php
/**
 * includes/SimpleXLSX.php
 * FleetX — Lightweight zero-dependency XLSX parser
 * Natively parses Excel .xlsx files using PHP ZipArchive and SimpleXMLElement
 */

class SimpleXLSX {
    public static function parse($filepath) {
        if (!file_exists($filepath) || !class_exists('ZipArchive')) {
            return false;
        }
        $zip = new ZipArchive();
        if ($zip->open($filepath) !== true) {
            return false;
        }

        // 1. Read shared strings
        $strings = [];
        if (($index = $zip->locateName('xl/sharedStrings.xml')) !== false) {
            $xmlContent = $zip->getFromIndex($index);
            if ($xmlContent) {
                $xml = simplexml_load_string($xmlContent);
                if ($xml && isset($xml->si)) {
                    foreach ($xml->si as $val) {
                        if (isset($val->t)) {
                            $strings[] = (string)$val->t;
                        } elseif (isset($val->r)) {
                            $str = '';
                            foreach ($val->r as $r) {
                                $str .= (string)$r->t;
                            }
                            $strings[] = $str;
                        } else {
                            $strings[] = '';
                        }
                    }
                }
            }
        }

        // 2. Read first worksheet
        $sheetName = 'xl/worksheets/sheet1.xml';
        if (($index = $zip->locateName($sheetName)) === false) {
            // Try finding any worksheet in xl/worksheets/
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if (preg_match('/^xl\/worksheets\/sheet\d+\.xml$/i', $stat['name'])) {
                    $sheetName = $stat['name'];
                    break;
                }
            }
            if (($index = $zip->locateName($sheetName)) === false) {
                $zip->close();
                return false;
            }
        }

        $xmlContent = $zip->getFromIndex($index);
        $zip->close();
        if (!$xmlContent) {
            return false;
        }

        $xml = simplexml_load_string($xmlContent);
        if (!$xml || !isset($xml->sheetData->row)) {
            return false;
        }

        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $r = [];
            $colIndex = 0;
            foreach ($row->c as $c) {
                $rAttr = (string)$c['r']; // e.g., "A1", "B1", "C1"
                // Convert column letters (A, B, C...) to 0-based index
                $colLetter = preg_replace('/[^A-Z]/i', '', $rAttr);
                $targetColIndex = 0;
                $len = strlen($colLetter);
                for ($i = 0; $i < $len; $i++) {
                    $targetColIndex = $targetColIndex * 26 + (ord(strtoupper($colLetter[$i])) - ord('A') + 1);
                }
                $targetColIndex--; // 0-indexed

                // Fill empty cells if any were skipped
                while ($colIndex < $targetColIndex) {
                    $r[] = '';
                    $colIndex++;
                }

                $val = '';
                $type = (string)$c['t'];
                if ($type === 's') {
                    $idx = (int)$c->v;
                    $val = $strings[$idx] ?? '';
                } elseif ($type === 'inlineStr' && isset($c->is->t)) {
                    $val = (string)$c->is->t;
                } elseif (isset($c->v)) {
                    $val = (string)$c->v;
                }
                $r[] = trim($val);
                $colIndex++;
            }
            if (!empty(array_filter($r, 'strlen'))) {
                $rows[] = $r;
            }
        }
        return $rows;
    }
}
