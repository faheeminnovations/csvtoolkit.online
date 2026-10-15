<?php
require_once __DIR__ . '/CsvParser.php';

/**
 * CsvValidationService — structural & encoding validation of CSV content.
 */
class CsvValidationService {

    public const SEVERITY_ERROR   = 'error';
    public const SEVERITY_WARNING = 'warning';

    /**
     * @return array{
     *   valid: bool,
     *   errors: array<array{row:int|string,col:int|null,severity:string,code:string,message:string}>,
     *   stats: array{rows:int,cols:int,empty_cells:int,encoding:string,delimiter:string},
     *   headers: string[]
     * }
     */
    public static function validate(string $content, string $delimiter = ','): array {
        $errors  = [];
        $content = CsvParser::stripBom($content);

        // ── Encoding check ─────────────────────────────────────────────────────
        $encoding = CsvParser::detectEncoding($content);
        if (!mb_check_encoding($content, 'UTF-8')) {
            $errors[] = self::err('n/a', null, self::SEVERITY_WARNING, 'encoding',
                "File encoding detected as {$encoding}. Non-UTF-8 characters may not display correctly.");
        }

        // ── Empty file ─────────────────────────────────────────────────────────
        $trimmed = trim($content);
        if ($trimmed === '') {
            $errors[] = self::err('n/a', null, self::SEVERITY_ERROR, 'empty_file', 'The file is empty.');
            return self::result(false, $errors, [], [], $encoding, $delimiter);
        }

        // ── Parse ──────────────────────────────────────────────────────────────
        $parsed  = CsvParser::parseString($content, $delimiter);
        $headers = $parsed['headers'];
        $rows    = $parsed['rows'];

        // ── Missing header ─────────────────────────────────────────────────────
        if (empty($headers)) {
            $errors[] = self::err(1, null, self::SEVERITY_WARNING, 'no_header',
                'No header row detected. Treating first row as data.');
        }

        // ── Duplicate headers ──────────────────────────────────────────────────
        if (!empty($headers)) {
            $dups = array_filter(array_count_values($headers), fn($c) => $c > 1);
            foreach (array_keys($dups) as $dup) {
                $errors[] = self::err(1, null, self::SEVERITY_WARNING, 'duplicate_header',
                    "Duplicate header name: \"{$dup}\".");
            }
        }

        // ── Empty headers ──────────────────────────────────────────────────────
        foreach ($headers as $ci => $h) {
            if (trim($h) === '') {
                $errors[] = self::err(1, $ci + 1, self::SEVERITY_WARNING, 'empty_header',
                    "Column " . ($ci + 1) . " has an empty header name.");
            }
        }

        // ── Determine expected column count ────────────────────────────────────
        $expectedCols = !empty($headers) ? count($headers) : (isset($rows[0]) ? count($rows[0]) : 0);
        $emptyCells   = 0;

        foreach ($rows as $ri => $row) {
            $rowNum = $ri + 2; // 1-indexed, offset for header
            $actual = count($row);

            // Inconsistent column count
            if ($actual !== $expectedCols) {
                $severity = $actual === 1 ? self::SEVERITY_ERROR : self::SEVERITY_WARNING;
                $errors[] = self::err($rowNum, null, $severity, 'col_mismatch',
                    "Row {$rowNum} has {$actual} column(s); expected {$expectedCols}.");
            }

            // Empty cells
            foreach ($row as $ci => $cell) {
                if ($cell === '' || trim($cell) === '') {
                    $emptyCells++;
                }

                // Formula injection check
                if ($cell !== '' && in_array($cell[0], ['=','+','-','@'], true)) {
                    $errors[] = self::err($rowNum, $ci + 1, self::SEVERITY_WARNING, 'formula_injection',
                        "Row {$rowNum}, col " . ($ci + 1) . ": Cell starts with \"{$cell[0]}\" — possible formula injection.");
                }

                // Invalid UTF-8 sequence
                if (!mb_check_encoding($cell, 'UTF-8')) {
                    $errors[] = self::err($rowNum, $ci + 1, self::SEVERITY_ERROR, 'invalid_utf8',
                        "Row {$rowNum}, col " . ($ci + 1) . " contains invalid UTF-8 bytes.");
                }
            }
        }

        // ── Completely empty rows ──────────────────────────────────────────────
        foreach ($rows as $ri => $row) {
            if (array_filter($row, fn($c) => $c !== '') === []) {
                $errors[] = self::err($ri + 2, null, self::SEVERITY_WARNING, 'empty_row',
                    "Row " . ($ri + 2) . " is completely empty.");
            }
        }

        $hasErrors = !empty(array_filter($errors, fn($e) => $e['severity'] === self::SEVERITY_ERROR));
        $stats = [
            'rows'        => count($rows),
            'cols'        => $expectedCols,
            'empty_cells' => $emptyCells,
            'encoding'    => $encoding,
            'delimiter'   => $delimiter,
        ];

        return self::result(!$hasErrors, $errors, $headers, $rows, $encoding, $delimiter, $stats);
    }

    private static function err(int|string $row, ?int $col, string $severity, string $code, string $message): array {
        return ['row'=>$row, 'col'=>$col, 'severity'=>$severity, 'code'=>$code, 'message'=>$message];
    }

    private static function result(bool $valid, array $errors, array $headers, array $rows,
        string $encoding, string $delimiter, array $extraStats = []): array {
        return [
            'valid'   => $valid,
            'errors'  => $errors,
            'stats'   => array_merge([
                'rows'        => count($rows),
                'cols'        => !empty($headers) ? count($headers) : (isset($rows[0]) ? count($rows[0]) : 0),
                'empty_cells' => 0,
                'encoding'    => $encoding,
                'delimiter'   => $delimiter,
            ], $extraStats),
            'headers' => $headers,
        ];
    }
}
