<?php
require_once ROOT_PATH . '/services/CsvParser.php';
require_once ROOT_PATH . '/services/CsvConversionService.php';

$tool = getToolBySlug('csv-to-json') ?? [
    'name'              => 'CSV to JSON Converter',
    'slug'              => 'csv-to-json',
    'h1'                => 'CSV to JSON Converter — Free Online Tool',
    'short_description' => 'Convert CSV files to JSON format instantly. Supports array of objects, arrays, and keyed output.',
    'meta_title'        => 'CSV to JSON Converter — Convert CSV to JSON Online Free',
    'meta_description'  => 'Convert CSV files to JSON instantly. Choose array of objects, array of arrays, or keyed output. Free online CSV to JSON converter.',
    'instructions'      => '<ol><li>Upload your CSV file.</li><li>Choose a JSON output format.</li><li>Enable pretty-print if you need human-readable JSON.</li><li>Click <strong>Convert to JSON</strong>.</li><li>Copy the result or download the JSON file.</li></ol>',
    'content'           => '<p>Converting CSV to JSON is a common task when integrating data with APIs or web applications. This tool supports three output formats: Array of Objects (most common for APIs), Array of Arrays (compact, no headers), and Keyed Object (using the first column as the object key).</p>',
];
$faqs         = getToolFaqs($tool['id'] ?? 0);
$relatedTools = getRelatedTools('csv-to-json');

$result = null;
$errors = [];

if (isPost()) {
    verifyCsrf();
    $file       = $_FILES['csv_file'] ?? null;
    $format     = in_array($_POST['format'] ?? '', ['objects','arrays','keyed']) ? $_POST['format'] : 'objects';
    $pretty     = isset($_POST['pretty_print']);
    $delimKey   = $_POST['delimiter'] ?? 'auto';
    $delimMap   = ['auto'=>'auto','comma'=>',','semicolon'=>';','tab'=>"\t",'pipe'=>'|'];
    $delim      = $delimMap[$delimKey] ?? 'auto';

    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Please select a CSV file.';
    } else {
        $uploadErrors = validateUploadedFile($file);
        if (!empty($uploadErrors)) {
            $errors = $uploadErrors;
        } else {
            $rawContent = file_get_contents($file['tmp_name']);
            if ($delim === 'auto') $delim = CsvParser::detectDelimiter($rawContent);

            $result = CsvConversionService::csvToJson($rawContent, $delim, $format, $pretty);

            if ($result['success']) {
                $tmpFile = saveTempFile($result['json'], 'json');
                $downloadName = sanitizeFilename(pathinfo($file['name'], PATHINFO_FILENAME) . '.json');
                $_SESSION['json_download'] = ['file' => $tmpFile, 'name' => $downloadName];
            } else {
                $errors[] = $result['error'];
                $result   = null;
            }
        }
    }
}

ob_start();
?>
<form method="post" enctype="multipart/form-data" class="tool-form" novalidate>
    <?= csrfField() ?>

    <div class="upload-area mb-3" role="button" tabindex="0">
        <input type="file" name="csv_file" id="csv_file" accept=".csv,.txt,.tsv">
        <div class="upload-icon"><i class="bi bi-braces" aria-hidden="true"></i></div>
        <h3>Drag &amp; drop your CSV here</h3>
        <p>or click to browse — max <?= formatBytes(MAX_FILE_SIZE) ?></p>
        <p class="upload-filename text-primary fw-semibold d-none mt-2"></p>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-sm-6">
            <label for="format" class="form-label fw-medium">Output Format</label>
            <select id="format" name="format" class="form-select">
                <option value="objects" selected>Array of Objects <small>[ {col: val}, … ]</small></option>
                <option value="arrays">Array of Arrays <small>[ [val, val], … ]</small></option>
                <option value="keyed">Keyed Object <small>{ key: {…}, … }</small></option>
            </select>
        </div>
        <div class="col-sm-4">
            <label for="delimiter" class="form-label fw-medium">Delimiter</label>
            <select id="delimiter" name="delimiter" class="form-select">
                <option value="auto">Auto-detect</option>
                <option value="comma">Comma ( , )</option>
                <option value="semicolon">Semicolon ( ; )</option>
                <option value="tab">Tab</option>
                <option value="pipe">Pipe ( | )</option>
            </select>
        </div>
        <div class="col-sm-2 d-flex align-items-end pb-1">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="pretty_print" id="pretty_print" value="1" checked>
                <label class="form-check-label" for="pretty_print">Pretty-print</label>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary px-4">
        <i class="bi bi-braces me-2" aria-hidden="true"></i>Convert to JSON
    </button>
</form>

<?php if ($result): ?>
<hr class="my-4">

<div class="validation-summary mb-3">
    <div class="val-stat"><div class="val-num text-primary"><?= number_format($result['row_count']) ?></div><div class="val-lbl">Rows</div></div>
    <div class="val-stat"><div class="val-num text-success"><?= $result['col_count'] ?></div><div class="val-lbl">Columns</div></div>
    <div class="val-stat"><div class="val-num text-info"><?= formatBytes($result['byte_size']) ?></div><div class="val-lbl">JSON Size</div></div>
</div>

<div class="d-flex justify-content-between align-items-center mb-2">
    <h2 class="h6 fw-bold mb-0"><i class="bi bi-braces me-1 text-primary"></i>JSON Output</h2>
    <button class="btn btn-sm btn-outline-secondary" data-copy="jsonOutput">
        <i class="bi bi-clipboard me-1"></i>Copy
    </button>
</div>
<div class="result-box mb-3">
    <pre id="jsonOutput" class="font-mono"><?= e(mb_substr($result['json'], 0, 50000)) ?><?php if (strlen($result['json']) > 50000): ?>
<span class="text-muted">… (truncated for preview — download for full output)</span><?php endif; ?></pre>
</div>

<a href="<?= url('download/') ?>?file=<?= urlencode($_SESSION['json_download']['file']) ?>&name=<?= urlencode($_SESSION['json_download']['name']) ?>&type=json"
   class="btn btn-success btn-lg">
    <i class="bi bi-download me-2"></i>Download JSON
</a>
<?php endif; ?>

<?php
$toolContent = ob_get_clean();
require_once ROOT_PATH . '/includes/tool-layout.php';
