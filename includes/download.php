<?php
/**
 * Secure file download endpoint.
 * Usage: GET /download?file=<token>&name=<safe_name>&type=csv
 */

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/includes/functions.php';

$file     = $_GET['file'] ?? '';
$name     = sanitizeFilename($_GET['name'] ?? 'download.csv');
$type     = $_GET['type'] ?? 'csv';

$mimeMap  = [
    'csv'  => 'text/csv',
    'json' => 'application/json',
    'txt'  => 'text/plain',
    'tsv'  => 'text/tab-separated-values',
];
$mime = $mimeMap[$type] ?? 'text/csv';

try {
    $path = getTempFilePath($file);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    die('Invalid download token.');
}

if (!is_file($path)) {
    http_response_code(404);
    die('File not found or has already been deleted.');
}

// Stream the file
header('Content-Type: ' . $mime . '; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $name . '"');
header('Content-Length: ' . filesize($path));
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

readfile($path);

// Delete after download
@unlink($path);
exit;
