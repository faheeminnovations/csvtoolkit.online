<?php
require_once __DIR__ . '/CsvParser.php';

/**
 * CsvRepairService — detects and repairs common CSV formatting problems.
 *
 * Repairs performed:
 *  - Strip BOM characters
 *  - Normalise mixed line endings
 *  - Remove null bytes
 *  - Detect and pad/truncate rows with inconsistent column counts
 *  - Close unclosed quoted fields
 *  - Convert encoding to UTF-8
 *  - Remove CSV formula injection prefixes (=, +, -, @)
 */
class CsvRepairService {

    public const FORMULA_PREFIXES = ['=', '+', '-', '@'];

    /**
     * Analyse and repair a CSV string.
     *
     * @return array{
     *   original_content: string,
     *   repaired_content: string,
     *   issues: array<array{row: int|string, type: string, description: string}>,
     *   stats: array{rows_examined: int, rows_repaired: int, issues_found: int},
     *   changed: bool
     * }
     */
    public static function repair(string $rawContent, string $delimiter = ','): array {
        $issues  = [];
        $changed = false;
        $content = $rawContent;

        // ── 1. Null bytes ─────────────────────────────────────────────────────
        if (str_contains($content, "\x00")) {
            $content = str_replace("\x00", '', $content);
            $issues[] = ['row'=>'n/a', 'type'=>'null_bytes',
                         'description'=>'Removed null (0x00) bytes from the file.'];
            $changed  = true;
        }

        // ── 2. Encoding → UTF-8 ───────────────────────────────────────────────
        $encoding = CsvParser::detectEncoding($content);
        if (!in_array($encoding, ['UTF-8', 'ASCII'], true)) {
            $converted = mb_convert_encoding(
                in_array($encoding, ['UTF-8-BOM','UTF-16LE','UTF-16BE']) ? CsvParser::stripBom($content) : $content,
                'UTF-8',
                $encoding === 'UTF-8-BOM' ? 'UTF-8' : $encoding
            );
            if ($converted !== false) {
                $content  = $converted;
                $issues[] = ['row'=>'n/a', 'type'=>'encoding',
                             'description'=>"Converted file encoding from {$encoding} to UTF-8."];
                $changed  = true;
            }
        }

        // ── 3. Strip BOM ──────────────────────────────────────────────────────
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content  = substr($content, 3);
            $issues[] = ['row'=>'n/a', 'type'=>'bom',
                         'description'=>'Removed UTF-8 BOM (Byte Order Mark) from the start of the file.'];
            $changed  = true;
        }

        // ── 4. Normalise line endings ─────────────────────────────────────────
        $hasWindowsEndings = str_contains($content, "\r\n");
        $hasMacEndings     = preg_match('/\r(?!\n)/', $content);
        if ($hasWindowsEndings || $hasMacEndings) {
            $normalised = CsvParser::normaliseLineEndings($content);
            if ($normalised !== $content) {
                $content  = $normalised;
                $type     = $hasWindowsEndings ? 'CRLF' : 'CR';
                $issues[] = ['row'=>'n/a', 'type'=>'line_endings',
                             'description'=>"Normalised mixed {$type} line endings to LF."];
                $changed  = true;
            }
        }

        // ── 5. Fix unclosed quoted fields ─────────────────────────────────────
        [$content, $quoteIssues, $quoteFixed] = self::fixUnclosedQuotes($content, $delimiter);
        if ($quoteFixed) { $issues = array_merge($issues, $quoteIssues); $changed = true; }

        // ── 6. Detect dominant column count & fix inconsistent rows ───────────
        $parsed = CsvParser::parseString($content, $delimiter);
        $allRows = $parsed['rows'];
        if (!empty($parsed['headers'])) {
            array_unshift($allRows, $parsed['headers']);
        }

        if (!empty($allRows)) {
            $colCounts = array_map('count', $allRows);
            $freq      = array_count_values($colCounts);
            arsort($freq);
            $dominant  = (int) array_key_first($freq);

            $repairedRows    = [];
            $rowsRepaired    = 0;

            foreach ($allRows as $rowIdx => $row) {
                $actual = count($row);
                if ($actual !== $dominant) {
                    if ($actual < $dominant) {
                        // Pad with empty strings
                        $row = array_pad($row, $dominant, '');
                        $issues[] = ['row' => $rowIdx + 1, 'type' => 'short_row',
                            'description' => "Row {$rowIdx} had {$actual} column(s); expected {$dominant}. Padded with empty values."];
                    } else {
                        // Truncate extra columns
                        $extra = array_splice($row, $dominant);
                        $issues[] = ['row' => $rowIdx + 1, 'type' => 'long_row',
                            'description' => "Row {$rowIdx} had {$actual} column(s); expected {$dominant}. Removed " . count($extra) . " extra column(s)."];
                    }
                    $rowsRepaired++;
                    $changed = true;
                }
                $repairedRows[] = $row;
            }

            if ($rowsRepaired > 0) {
                $headers = [];
                $dataRows = $repairedRows;
                if (!empty($parsed['headers'])) {
                    $headers  = array_shift($dataRows);
                }
                $content = CsvParser::toString($dataRows, $headers, $delimiter);
            }
        }

        // ── 7. Strip formula injection prefixes ───────────────────────────────
        $hasParsed = CsvParser::parseString($content, $delimiter);
        $formulaFixed = false;
        $fRows = [];
        foreach ($hasParsed['rows'] as $rIdx => $row) {
            $fRow = [];
            foreach ($row as $cIdx => $cell) {
                if ($cell !== '' && in_array($cell[0], self::FORMULA_PREFIXES, true)) {
                    $fRow[] = "'" . $cell; // prefix with single quote (safe in spreadsheets)
                    if (!$formulaFixed) {
                        $issues[] = ['row' => $rIdx + 1, 'type' => 'formula_injection',
                            'description' => 'One or more cells start with =, +, -, or @ (formula injection risk). Prefixed with a single quote.'];
                        $formulaFixed = true;
                        $changed = true;
                    }
                } else {
                    $fRow[] = $cell;
                }
            }
            $fRows[] = $fRow;
        }
        if ($formulaFixed) {
            $content = CsvParser::toString($fRows, $hasParsed['headers'], $delimiter);
        }

        // Recount final rows
        $finalParsed = CsvParser::parseString($content, $delimiter);
        $rowCount = $finalParsed['row_count'] + (empty($finalParsed['headers']) ? 0 : 1);

        return [
            'original_content' => $rawContent,
            'repaired_content' => $content,
            'issues'           => $issues,
            'stats'            => [
                'rows_examined' => $rowCount,
                'rows_repaired' => count(array_filter($issues, fn($i) => in_array($i['type'],['short_row','long_row']))),
                'issues_found'  => count($issues),
            ],
            'changed' => $changed,
        ];
    }

    // ── Fix unclosed quoted fields ─────────────────────────────────────────────
    private static function fixUnclosedQuotes(string $content, string $delimiter): array {
        $lines   = explode("\n", $content);
        $issues  = [];
        $fixed   = false;
        $out     = [];
        $i       = 0;
        $total   = count($lines);

        while ($i < $total) {
            $line     = $lines[$i];
            $inQuote  = false;
            $j        = 0;
            $len      = strlen($line);

            while ($j < $len) {
                if ($line[$j] === '"') {
                    if ($inQuote && isset($line[$j+1]) && $line[$j+1] === '"') {
                        $j += 2;
                    } else {
                        $inQuote = !$inQuote;
                        $j++;
                    }
                } else {
                    $j++;
                }
            }

            if ($inQuote) {
                // Quote spans to next line — merge
                $merged = $line;
                $i++;
                while ($i < $total) {
                    $nextLine = $lines[$i];
                    $merged .= "\n" . $nextLine;
                    // Re-check if quote is now closed
                    $inQ2 = false;
                    for ($k = 0; $k < strlen($merged); $k++) {
                        if ($merged[$k] === '"') {
                            if ($inQ2 && isset($merged[$k+1]) && $merged[$k+1] === '"') {
                                $k++;
                            } else {
                                $inQ2 = !$inQ2;
                            }
                        }
                    }
                    if (!$inQ2) break;
                    $i++;
                }
                $out[]   = $merged;
                $issues[] = ['row' => count($out), 'type' => 'unclosed_quote',
                    'description' => 'Joined multi-line quoted field at row ' . count($out) . '.'];
                $fixed = true;
            } else {
                $out[] = $line;
            }
            $i++;
        }

        return [implode("\n", $out), $issues, $fixed];
    }
}
