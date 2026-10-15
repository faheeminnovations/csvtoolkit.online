<?php
require_once __DIR__ . '/CsvParser.php';

/**
 * CsvConversionService — converts CSV to/from other formats.
 */
class CsvConversionService {

    // ── CSV → JSON ────────────────────────────────────────────────────────────
    /**
     * @param string $format  'objects' | 'arrays' | 'keyed'
     */
    public static function csvToJson(
        string $content,
        string $delimiter = ',',
        string $format    = 'objects',
        bool   $prettyPrint = true
    ): array {
        $content = CsvParser::toUtf8($content);
        $parsed  = CsvParser::parseString($content, $delimiter, '"', true);

        if (empty($parsed['rows'])) {
            return ['success' => false, 'error' => 'No data rows found in the CSV.'];
        }

        $headers  = $parsed['headers'];
        $rows     = $parsed['rows'];
        $jsonData = [];
        $flags    = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        if ($prettyPrint) $flags |= JSON_PRETTY_PRINT;

        switch ($format) {
            case 'arrays':
                // Array of arrays, first element is headers
                $jsonData = [$headers];
                foreach ($rows as $row) {
                    // Pad/trim to header count
                    $jsonData[] = array_values(array_slice(
                        array_pad($row, count($headers), null), 0, count($headers)
                    ));
                }
                break;

            case 'keyed':
                // First column values become keys
                if (empty($headers)) {
                    return ['success' => false, 'error' => 'Keyed format requires a header row.'];
                }
                $keyCol = $headers[0];
                foreach ($rows as $row) {
                    $obj = [];
                    foreach ($headers as $hi => $h) {
                        $obj[$h] = $row[$hi] ?? null;
                    }
                    $keyVal = $obj[$keyCol] ?? '';
                    $jsonData[$keyVal] = $obj;
                }
                break;

            default: // 'objects'
                foreach ($rows as $row) {
                    $obj = [];
                    foreach ($headers as $hi => $h) {
                        $obj[$h] = $row[$hi] ?? null;
                    }
                    $jsonData[] = $obj;
                }
        }

        $json = json_encode($jsonData, $flags);
        if ($json === false) {
            return ['success' => false, 'error' => 'JSON encoding failed: ' . json_last_error_msg()];
        }

        return [
            'success'    => true,
            'json'       => $json,
            'row_count'  => count($rows),
            'col_count'  => count($headers),
            'byte_size'  => strlen($json),
        ];
    }

    // ── Change delimiter ──────────────────────────────────────────────────────
    public static function convertDelimiter(
        string $content,
        string $fromDelimiter,
        string $toDelimiter,
        string $enclosure = '"'
    ): array {
        $content = CsvParser::toUtf8($content);

        // Auto-detect if from delimiter is 'auto'
        if ($fromDelimiter === 'auto') {
            $fromDelimiter = CsvParser::detectDelimiter($content);
        }

        $parsed = CsvParser::parseString($content, $fromDelimiter, $enclosure, false);
        $all    = $parsed['rows'];

        if (empty($all)) {
            return ['success' => false, 'error' => 'No data found in the CSV file.'];
        }

        // Re-serialise with new delimiter
        $output = CsvParser::toString($all, [], $toDelimiter, $enclosure);

        return [
            'success'        => true,
            'content'        => $output,
            'from_delimiter' => $fromDelimiter,
            'to_delimiter'   => $toDelimiter,
            'row_count'      => count($all),
        ];
    }
}
