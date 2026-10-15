<?php
require_once __DIR__ . '/CsvParser.php';

/**
 * CsvComparisonService — diff two CSV files row by row.
 */
class CsvComparisonService {

    public const STATUS_ADDED    = 'added';
    public const STATUS_REMOVED  = 'removed';
    public const STATUS_MODIFIED = 'modified';
    public const STATUS_EQUAL    = 'equal';

    /**
     * Compare two CSV files.
     *
     * @param int[]|null $keyColIndices  0-indexed columns to use as row key (null = first col)
     * @return array{
     *   success: bool,
     *   diff: array<array{status:string,key:string,row_a:string[]|null,row_b:string[]|null,changed_cols:int[]}>,
     *   stats: array{total_a:int,total_b:int,added:int,removed:int,modified:int,equal:int},
     *   headers: string[]
     * }
     */
    public static function compare(
        string $contentA,
        string $contentB,
        string $delimiter   = ',',
        ?array $keyColIndices = null
    ): array {
        $contentA = CsvParser::toUtf8($contentA);
        $contentB = CsvParser::toUtf8($contentB);

        $parsedA = CsvParser::parseString($contentA, $delimiter);
        $parsedB = CsvParser::parseString($contentB, $delimiter);

        $headersA = $parsedA['headers'];
        $headersB = $parsedB['headers'];
        $rowsA    = $parsedA['rows'];
        $rowsB    = $parsedB['rows'];

        // Merge header lists
        $allHeaders = $headersA;
        foreach ($headersB as $h) {
            if (!in_array($h, $allHeaders, true)) {
                $allHeaders[] = $h;
            }
        }

        // Default key: first column
        if ($keyColIndices === null) {
            $keyColIndices = [0];
        }

        // Index rows by key
        $indexA = self::indexByKey($rowsA, $keyColIndices);
        $indexB = self::indexByKey($rowsB, $keyColIndices);

        $allKeys = array_unique(array_merge(array_keys($indexA), array_keys($indexB)));

        $diff    = [];
        $stats   = ['total_a'=>count($rowsA),'total_b'=>count($rowsB),
                    'added'=>0,'removed'=>0,'modified'=>0,'equal'=>0];

        foreach ($allKeys as $key) {
            $inA = array_key_exists($key, $indexA);
            $inB = array_key_exists($key, $indexB);

            if ($inA && !$inB) {
                $diff[]         = ['status'=>self::STATUS_REMOVED,'key'=>$key,
                                   'row_a'=>$indexA[$key],'row_b'=>null,'changed_cols'=>[]];
                $stats['removed']++;
            } elseif (!$inA && $inB) {
                $diff[]         = ['status'=>self::STATUS_ADDED,'key'=>$key,
                                   'row_a'=>null,'row_b'=>$indexB[$key],'changed_cols'=>[]];
                $stats['added']++;
            } else {
                $changedCols = self::diffRow($indexA[$key], $indexB[$key]);
                if (empty($changedCols)) {
                    $diff[]       = ['status'=>self::STATUS_EQUAL,'key'=>$key,
                                     'row_a'=>$indexA[$key],'row_b'=>$indexB[$key],'changed_cols'=>[]];
                    $stats['equal']++;
                } else {
                    $diff[]       = ['status'=>self::STATUS_MODIFIED,'key'=>$key,
                                     'row_a'=>$indexA[$key],'row_b'=>$indexB[$key],'changed_cols'=>$changedCols];
                    $stats['modified']++;
                }
            }
        }

        return [
            'success' => true,
            'diff'    => $diff,
            'stats'   => $stats,
            'headers' => $allHeaders,
        ];
    }

    private static function indexByKey(array $rows, array $keyColIndices): array {
        $index = [];
        foreach ($rows as $row) {
            $keyParts = [];
            foreach ($keyColIndices as $ci) {
                $keyParts[] = $row[$ci] ?? '';
            }
            $key = implode('||', $keyParts);
            // If duplicate keys exist, keep last occurrence but track both
            $index[$key] = $row;
        }
        return $index;
    }

    private static function diffRow(array $rowA, array $rowB): array {
        $changed = [];
        $max     = max(count($rowA), count($rowB));
        for ($i = 0; $i < $max; $i++) {
            $a = $rowA[$i] ?? '';
            $b = $rowB[$i] ?? '';
            if ($a !== $b) {
                $changed[] = $i;
            }
        }
        return $changed;
    }
}
