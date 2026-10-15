<?php
require_once ROOT_PATH . '/services/CsvParser.php';
require_once ROOT_PATH . '/services/CsvCleaningService.php';

$tool = getToolBySlug('csv-splitter') ?? [
    'name'              => 'CSV Splitter',
    'slug'              => 'csv-splitter',
    'h1'                => 'CSV Splitter — Split Large CSV Files into Smaller Parts',
    'short_description' => 'Split large CSV files into smaller chunks by row count. Each chunk keeps the header row.',
    'meta_title'        => 'CSV Splitter — Split Large CSV Files Online Free',
    'meta_description'  => 'Split large CSV files into smaller chunks by number of rows. Each split file keeps the original header row. Free online CSV splitter.',
    'instructions'      => '<ol><li>Upload your large CSV file.</li><li>Set the number of rows per chunk.</li><li>Click <strong>Split CSV</strong>.</li><li>Download each chunk individually.</li></ol>',
    'content'           => '<p>Large CSV files are difficult to open in Excel (which has a 1,048,576-row limit) and slow to process. The CSV Splitter breaks a large file into manageable chunks, each with the original header row preserved, so every chunk is independently valid.</p>',
];
$faqs         = getToolFaqs($tool['id'] ?? 0);
$relatedTools = getRelatedTools('csv-splitter');

$result = null;
$errors = [];

if (isPost()) {
    verifyCsrf();
    $file         = $_FILES['csv_file'] ?? null;
    $rowsPerChunk = max(1, min(1000000, (int)($_POST['rows_per_chunk'] ?? 1000)));
    $delimKey     = $_POST['delimiter'] ?? 'auto';
    $delimMap     = ['auto'=>'auto','comma'=>',','semicolon'=>';','tab'=>"\t",'pipe'=>'|'];
    $delim        = $delimMap[$delimKey] ?? 'auto';

    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Please select a CSV file.';
    } else {
        $uploadErrors = validateUploadedFile($file);
        if (!empty($uploadErrors)) {
            $errors = $uploadErrors;
        } else {
            $rawContent = file_get_contents($file['tmp_name']);
            if ($delim === 'auto') $delim = CsvParser::detectDelimiter($rawContent);

            $result = CsvCleaningService::splitFile($rawContent, $delim, $rowsPerChunk);

            if ($result['success']) {
                // Save each chunk to temp storage
                $baseName = sanitizeFilename(pathinfo($file['name'], PATHINFO_FILENAME));
                $chunkTokens = [];
                foreach ($result['chunks'] as $chunk) {
                    $token = saveTempFile($chunk['content'], 'csv');
                    $chunkTokens[] = [
                        'token' => $token,
                        'name'  => $baseName . '_part' . str_pad($chunk['index'], 3, '0', STR_PAD_LEFT) . '.csv',
                        'rows'  => $chunk['rows'],
                        'index' => $chunk['index'],
                    ];
                }
                $_SESSION['splitter_chunks'] = $chunkTokens;
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
        <div class="upload-icon"><i class="bi bi-scissors" aria-hidden="true"></i></div>
        <h3>Drag &amp; drop your CSV here</h3>
        <p>or click to browse — max <?= formatBytes(MAX_FILE_SIZE) ?></p>
        <p class="upload-filename text-primary fw-semibold d-none mt-2"></p>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-sm-5">
            <label for="rows_per_chunk" class="form-label fw-medium">Rows per chunk</label>
            <input type="number" id="rows_per_chunk" name="rows_per_chunk"
                   class="form-control" value="1000" min="1" max="1000000">
            <div class="form-text">Excluding the header row. Each chunk will also include the header.</div>
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
    </div>

    <button type="submit" class="btn btn-primary px-4">
        <i class="bi bi-scissors me-2" aria-hidden="true"></i>Split CSV
    </button>
</form>

<?php if ($result): ?>
<hr class="my-4">

<div class="validation-summary mb-4">
    <div class="val-stat"><div class="val-num text-primary"><?= number_format($result['total_rows']) ?></div><div class="val-lbl">Total Rows</div></div>
    <div class="val-stat"><div class="val-num text-success"><?= $result['chunk_count'] ?></div><div class="val-lbl">Chunks Created</div></div>
    <div class="val-stat"><div class="val-num text-info"><?= count($result['headers']) ?></div><div class="val-lbl">Columns</div></div>
</div>

<h2 class="h6 fw-bold mb-3">Download Chunks</h2>
<div class="row g-2">
    <?php foreach ($_SESSION['splitter_chunks'] as $chunk): ?>
    <div class="col-sm-6 col-lg-4">
        <div class="card border-0 shadow-sm p-3 d-flex flex-row align-items-center gap-3">
            <i class="bi bi-file-earmark-spreadsheet text-success fs-3 flex-shrink-0" aria-hidden="true"></i>
            <div class="flex-grow-1 overflow-hidden">
                <div class="fw-semibold small text-truncate"><?= e($chunk['name']) ?></div>
                <div class="text-muted small"><?= number_format($chunk['rows']) ?> rows</div>
            </div>
            <a href="<?= url('download/') ?>?file=<?= urlencode($chunk['token']) ?>&name=<?= urlencode($chunk['name']) ?>&type=csv"
               class="btn btn-sm btn-outline-success flex-shrink-0">
                <i class="bi bi-download" aria-hidden="true"></i>
            </a>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="alert alert-info mt-3 small">
    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
    Each chunk includes the original header row. Download links are valid for <?= TEMP_FILE_TTL / 60 ?> minutes.
</div>
<?php endif; ?>

<?php
$toolContent = ob_get_clean();
require_once ROOT_PATH . '/includes/tool-layout.php';
