<?php
/**
 * CsvParser — low-level CSV reading/writing with deliberate handling of
 * delimiters, enclosure, escape, BOM, and mixed line endings.
 */
class CsvParser {

    // ── Constants ─────────────────────────────────────────────────────────────
    public const DEFAULT_DELIMITER = ',';
    public const DEFAULT_ENCLOSURE = '"';
    public const DEFAULT_ESCAPE    = '\\';

    // ── Parse a CSV string into a 2-D array ───────────────────────────────────
    /**
     * @return array{headers: string[], rows: array<string[]>, row_count: int}
     */
    public static function parseString(
        string $content,
        string $delimiter = self::DEFAULT_DELIMITER,
        string $enclosure = self::DEFAULT_ENCLOSURE,
        bool   $hasHeader = true
    ): array {
        // Strip BOM
        $content = self::stripBom($content);

        // Normalise line endings to \n
        $content = self::normaliseLineEndings($content);

        // Remove trailing newline so we don't get a phantom empty row
        $content = rtrim($content, "\n");

        if ($content === '') {
            return ['headers' => [], 'rows' => [], 'row_count' => 0];
        }

        // Write to a temp stream and use fgetcsv
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $content);
        rewind($handle);

        $all = [];
        while (($row = fgetcsv($handle, 0, $delimiter, $enclosure)) !== false) {
            $all[] = $row;
        }
        fclose($handle);

        if (empty($all)) {
            return ['headers' => [], 'rows' => [], 'row_count' => 0];
        }

        $headers = [];
        $rows    = $all;

        if ($hasHeader && !empty($all)) {
            $headers = array_map('strval', $all[0]);
            $rows    = array_slice($all, 1);
        }

        return [
            'headers'   => $headers,
            'rows'      => $rows,
            'row_count' => count($rows),
        ];
    }

    // ── Serialise a 2-D array back to a CSV string ────────────────────────────
    public static function toString(
        array  $rows,
        array  $headers    = [],
        string $delimiter  = self::DEFAULT_DELIMITER,
        string $enclosure  = self::DEFAULT_ENCLOSURE,
        string $lineEnding = "\r\n"
    ): string {
        $handle = fopen('php://temp', 'r+');

        if (!empty($headers)) {
            fputcsv($handle, $headers, $delimiter, $enclosure);
        }
        foreach ($rows as $row) {
            fputcsv($handle, $row, $delimiter, $enclosure);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        // fputcsv always uses \n; convert to desired line ending
        if ($lineEnding !== "\n") {
            $csv = str_replace("\n", $lineEnding, $csv);
        }

        return $csv;
    }

    // ── Detect delimiter from raw content ─────────────────────────────────────
    public static function detectDelimiter(string $content): string {
        $content = self::stripBom($content);
        $lines   = explode("\n", self::normaliseLineEndings($content));

        // Take first 5 non-empty lines
        $sample = array_slice(array_filter($lines, fn($l) => trim($l) !== ''), 0, 5);
        if (empty($sample)) return ',';

        $candidates = [',', ';', "\t", '|', ':'];
        $scores     = array_fill_keys($candidates, 0);

        foreach ($sample as $line) {
            foreach ($candidates as $d) {
                // Count occurrences outside quotes
                $scores[$d] += self::countDelimitersInLine($line, $d);
            }
        }

        arsort($scores);
        $top = array_key_first($scores);
        return $scores[$top] > 0 ? $top : ',';
    }

    // ── Count delimiter occurrences outside quoted fields ─────────────────────
    private static function countDelimitersInLine(string $line, string $delim): int {
        $inQuote = false;
        $count   = 0;
        $len     = strlen($line);
        for ($i = 0; $i < $len; $i++) {
            if ($line[$i] === '"') {
                if ($inQuote && isset($line[$i + 1]) && $line[$i + 1] === '"') {
                    $i++; // escaped quote
                } else {
                    $inQuote = !$inQuote;
                }
            } elseif (!$inQuote && $line[$i] === $delim) {
                $count++;
            }
        }
        return $count;
    }

    // ── Strip UTF-8, UTF-16 LE/BE BOMs ────────────────────────────────────────
    public static function stripBom(string $content): string {
        // UTF-8 BOM: EF BB BF
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            return substr($content, 3);
        }
        // UTF-16 LE BOM: FF FE
        if (str_starts_with($content, "\xFF\xFE")) {
            $utf8 = mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16LE');
            return $utf8 !== false ? $utf8 : substr($content, 2);
        }
        // UTF-16 BE BOM: FE FF
        if (str_starts_with($content, "\xFE\xFF")) {
            $utf8 = mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16BE');
            return $utf8 !== false ? $utf8 : substr($content, 2);
        }
        return $content;
    }

    // ── Normalise CRLF/CR to LF ───────────────────────────────────────────────
    public static function normaliseLineEndings(string $content): string {
        return str_replace(["\r\n", "\r"], "\n", $content);
    }

    // ── Read a CSV file from disk ─────────────────────────────────────────────
    public static function parseFile(
        string $path,
        string $delimiter = self::DEFAULT_DELIMITER,
        string $enclosure = self::DEFAULT_ENCLOSURE,
        bool   $hasHeader = true
    ): array {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException("Cannot read file: $path");
        }
        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException("Failed to read file: $path");
        }
        // Auto-convert to UTF-8 if needed
        $content = self::toUtf8($content);
        return self::parseString($content, $delimiter, $enclosure, $hasHeader);
    }

    // ── Convert content to UTF-8 ──────────────────────────────────────────────
    public static function toUtf8(string $content): string {
        $enc = self::detectEncoding($content);
        if (in_array($enc, ['UTF-8', 'ASCII'], true)) {
            return $content;
        }
        $converted = mb_convert_encoding($content, 'UTF-8', $enc);
        return $converted !== false ? $converted : $content;
    }

    // ── Detect encoding using BOM + heuristics ────────────────────────────────
    public static function detectEncoding(string $content): string {
        // BOM detection
        if (str_starts_with($content, "\xEF\xBB\xBF"))  return 'UTF-8-BOM';
        if (str_starts_with($content, "\xFF\xFE"))       return 'UTF-16LE';
        if (str_starts_with($content, "\xFE\xFF"))       return 'UTF-16BE';
        if (str_starts_with($content, "\xFF\xFE\x00\x00")) return 'UTF-32LE';
        if (str_starts_with($content, "\x00\x00\xFE\xFF")) return 'UTF-32BE';

        // mb_detect_encoding
        $detected = mb_detect_encoding($content, ['UTF-8','ISO-8859-1','Windows-1252','ISO-8859-15','UTF-16'], true);
        if ($detected !== false) return $detected;

        // Fallback: if all bytes are ≤ 0x7F it's ASCII
        if (preg_match('/^[\x00-\x7F]*$/', $content)) return 'ASCII';

        return 'ISO-8859-1';
    }

    // ── Get column count consistency ──────────────────────────────────────────
    public static function getColumnCounts(array $rows): array {
        $counts = [];
        foreach ($rows as $i => $row) {
            $counts[$i] = count($row);
        }
        return $counts;
    }
}
