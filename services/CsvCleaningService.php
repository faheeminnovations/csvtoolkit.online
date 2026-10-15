<?php
require_once __DIR__ . '/CsvParser.php';

/**
 * CsvCleaningService — deduplication, merging, and splitting.
 */
class CsvCleaningService {

    // ── Remove duplicate rows ─────────────────────────────────────────────────
    /**
     * @param int[]|null $keyCols  0-indexed column indices to compare (null = all)
     * @param string     $keep     'first' | 'last'
     */
    public static function removeDuplicates(
        string $content,
        string $delimiter = ',',
        ?array $keyCols   = null,
        string $keep      = 'first'
    ): array {
        $content = CsvParser::toUtf8($content);
        $parsed  = CsvParser::parseString($content, $delimiter);
        $headers = $parsed['headers'];
        $rows    = $parsed['rows'];

        if (empty($rows)) {
            return ['success' => false, 'error' => 'No data rows to process.'];
        }

        $seen     = [];
        $unique   = [];
        $removed  = 0;
        $allRows  = $keep === 'last' ? array_reverse($rows) : $rows;

        foreach ($allRows as $row) {
            $key = self::makeKey($row, $keyCols);
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[]   = $row;
            } else {
                $removed++;
            }
        }

        if ($keep === 'last') {
            $unique = array_reverse($unique);
        }

        $output = CsvParser::toString($unique, $headers, $delimiter);

        return [
            'success'        => true,
            'content'        => $output,
            'original_rows'  => count($rows),
            'unique_rows'    => count($unique),
            'removed_rows'   => $removed,
            'headers'        => $headers,
        ];
    }

    private static function makeKey(array $row, ?array $keyCols): string {
        if ($keyCols === null) {
            return implode('|||', $row);
        }
        $parts = [];
        foreach ($keyCols as $ci) {
            $parts[] = $row[$ci] ?? '';
        }
        return implode('|||', $parts);
    }

    // ── Merge multiple CSV files ───────────────────────────────────────────────
    /**
     * @param array<string> $contents  raw CSV content of each file
     * @param string $matchBy          'name' | 'position'
     */
    public static function mergeFiles(
        array  $contents,
        string $delimiter = ',',
        string $matchBy   = 'name'
    ): array {
        if (count($contents) < 2) {
            return ['success' => false, 'error' => 'At least 2 files are required for merging.'];
        }
        if (count($contents) > MAX_MERGE_FILES) {
            return ['success' => false, 'error' => 'Maximum ' . MAX_MERGE_FILES . ' files allowed.'];
        }

        $parsedFiles = [];
        foreach ($contents as $i => $c) {
            $c = CsvParser::toUtf8($c);
            $parsedFiles[] = CsvParser::parseString($c, $delimiter);
        }

        if ($matchBy === 'name') {
            return self::mergeByColumnName($parsedFiles, $delimiter);
        }
        return self::mergeByPosition($parsedFiles, $delimiter);
    }

    private static function mergeByColumnName(array $parsedFiles, string $delimiter): array {
        // Collect all unique headers (preserving order)
        $allHeaders = [];
        foreach ($parsedFiles as $p) {
            foreach ($p['headers'] as $h) {
                if (!in_array($h, $allHeaders, true)) {
                    $allHeaders[] = $h;
                }
            }
        }

        $allRows   = [];
        $totalRows = 0;

        foreach ($parsedFiles as $p) {
            $fileHeaders = $p['headers'];
            foreach ($p['rows'] as $row) {
                $mapped = [];
                foreach ($allHeaders as $h) {
                    $idx      = array_search($h, $fileHeaders, true);
                    $mapped[] = $idx !== false ? ($row[$idx] ?? '') : '';
                }
                $allRows[] = $mapped;
                $totalRows++;
            }
        }

        $output = CsvParser::toString($allRows, $allHeaders, $delimiter);
        return ['success'=>true, 'content'=>$output, 'total_rows'=>$totalRows,
                'headers'=>$allHeaders, 'files_merged'=>count($parsedFiles)];
    }

    private static function mergeByPosition(array $parsedFiles, string $delimiter): array {
        $headers   = $parsedFiles[0]['headers'];
        $allRows   = [];
        $totalRows = 0;

        foreach ($parsedFiles as $p) {
            foreach ($p['rows'] as $row) {
                $allRows[] = $row;
                $totalRows++;
            }
        }

        $output = CsvParser::toString($allRows, $headers, $delimiter);
        return ['success'=>true, 'content'=>$output, 'total_rows'=>$totalRows,
                'headers'=>$headers, 'files_merged'=>count($parsedFiles)];
    }

    // ── Split CSV into chunks ─────────────────────────────────────────────────
    /**
     * @return array{success:bool, chunks:array<array{index:int,content:string,rows:int}>, total_rows:int}
     */
    public static function splitFile(
        string $content,
        string $delimiter = ',',
        int    $rowsPerChunk = 1000
    ): array {
        if ($rowsPerChunk < 1) {
            return ['success' => false, 'error' => 'Rows per chunk must be at least 1.'];
        }

        $content = CsvParser::toUtf8($content);
        $parsed  = CsvParser::parseString($content, $delimiter);
        $headers = $parsed['headers'];
        $rows    = $parsed['rows'];

        if (empty($rows)) {
            return ['success' => false, 'error' => 'No data rows to split.'];
        }

        $chunks    = [];
        $total     = count($rows);
        $batches   = array_chunk($rows, $rowsPerChunk);

        foreach ($batches as $i => $batch) {
            $chunkCsv = CsvParser::toString($batch, $headers, $delimiter);
            $chunks[] = [
                'index'   => $i + 1,
                'content' => $chunkCsv,
                'rows'    => count($batch),
            ];
        }

        return [
            'success'    => true,
            'chunks'     => $chunks,
            'total_rows' => $total,
            'chunk_count'=> count($chunks),
            'headers'    => $headers,
        ];
    }
}
