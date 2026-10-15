<?php
require_once ROOT_PATH . '/services/CsvParser.php';
require_once ROOT_PATH . '/services/CsvValidationService.php';

$tool = getToolBySlug('csv-validator') ?? [
    'name'              => 'CSV Validator',
    'slug'              => 'csv-validator',
    'h1'                => 'CSV Validator — Check Your CSV File for Errors',
    'short_description' => 'Validate CSV file structure, encoding, and consistency with detailed row-level error reporting.',
    'meta_title'        => 'CSV Validator — Validate CSV File Structure Online Free',
    'meta_description'  => 'Validate CSV files for structural errors, encoding problems, and format inconsistencies. Get a detailed error report with row and column references.',
    'instructions'      => '<ol><li>Upload your CSV file.</li><li>Choose the delimiter (auto-detect recommended).</li><li>Click <strong>Validate CSV</strong> to run all checks.</li><li>Review the error and warning report.</li><li>Use the <a href="/csv-repair/">CSV Repair Tool</a> to fix any errors found.</li></ol>',
    'content'           => '<p>The CSV Validator runs structural and encoding checks on your file. It verifies column count consistency, header uniqueness, UTF-8 validity, empty rows, and formula injection risks. Each issue is reported with the exact row and column number so you can locate and fix it quickly.</p>',
];
$faqs         = getToolFaqs($tool['id'] ?? 0);
$relatedTools = getRelatedTools('csv-validator');

$result = null;
$errors = [];

if (isPost()) {
    verifyCsrf();
    $file      = $_FILES['csv_file'] ?? null;
    $delimKey  = $_POST['delimiter'] ?? 'auto';
    $delimMap  = ['auto'=>null,'comma'=>',','semicolon'=>';','tab'=>"\t",'pipe'=>'|'];
    $delim     = $delimMap[$delimKey] ?? null;

    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Please select a CSV file.';
    } else {
        $uploadErrors = validateUploadedFile($file);
        if (!empty($uploadErrors)) {
            $errors = $uploadErrors;
        } else {
            $rawContent = file_get_contents($file['tmp_name']);
            // Auto-detect delimiter
            if ($delim === null) {
                $delim = CsvParser::detectDelimiter($rawContent);
            }
            $result = CsvValidationService::validate($rawContent, $delim);
            $result['file_name'] = $file['name'];
            $result['file_size'] = $file['size'];
        }
    }
}

ob_start();
?>
<form method="post" enctype="multipart/form-data" class="tool-form" novalidate>
    <?= csrfField() ?>

    <div class="upload-area mb-3" role="button" tabindex="0">
        <input type="file" name="csv_file" id="csv_file" accept=".csv,.txt,.tsv">
        <div class="upload-icon"><i class="bi bi-check-circle" aria-hidden="true"></i></div>
        <h3>Drag &amp; drop your CSV here</h3>
        <p>or click to browse — max <?= formatBytes(MAX_FILE_SIZE) ?></p>
        <p class="upload-filename text-primary fw-semibold d-none mt-2"></p>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <div class="row g-3 mb-3">
        <div class="col-sm-6">
            <label for="delimiter" class="form-label fw-medium">Delimiter</label>
            <select id="delimiter" name="delimiter" class="form-select">
                <option value="auto">Auto-detect</option>
                <option value="comma">Comma ( , )</option>
                <option value="semicolon">Semicolon ( ; )</option>
                <option value="tab">Tab ( ⇥ )</option>
                <option value="pipe">Pipe ( | )</option>
            </select>
        </div>
    </div>

    <button type="submit" class="btn btn-primary px-4">
        <i class="bi bi-check-circle me-2" aria-hidden="true"></i>Validate CSV
    </button>
</form>

<?php if ($result): ?>
<hr class="my-4">

<!-- Pass / Fail banner -->
<?php if ($result['valid']): ?>
<div class="alert alert-success d-flex gap-2 align-items-center mb-4" role="status">
    <i class="bi bi-check-circle-fill fs-4" aria-hidden="true"></i>
    <div><strong>Validation Passed!</strong> No structural errors found.
    <?php if (!empty($result['errors'])): ?> <?= count($result['errors']) ?> warning(s) noted.<?php endif; ?>
    </div>
</div>
<?php else: ?>
<div class="alert alert-danger d-flex gap-2 align-items-center mb-4" role="alert">
    <i class="bi bi-x-circle-fill fs-4" aria-hidden="true"></i>
    <div><strong>Validation Failed.</strong>
    <?= count(array_filter($result['errors'], fn($e)=>$e['severity']==='error')) ?> error(s) and
    <?= count(array_filter($result['errors'], fn($e)=>$e['severity']==='warning')) ?> warning(s) found.
    Consider using the <a href="<?= url('csv-repair/') ?>" class="alert-link">CSV Repair Tool</a> to fix these automatically.</div>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="validation-summary mb-4">
    <div class="val-stat"><div class="val-num text-primary"><?= number_format($result['stats']['rows']) ?></div><div class="val-lbl">Data Rows</div></div>
    <div class="val-stat"><div class="val-num text-success"><?= $result['stats']['cols'] ?></div><div class="val-lbl">Columns</div></div>
    <div class="val-stat"><div class="val-num text-warning"><?= $result['stats']['empty_cells'] ?></div><div class="val-lbl">Empty Cells</div></div>
    <div class="val-stat"><div class="val-num text-info"><?= e($result['stats']['encoding']) ?></div><div class="val-lbl">Encoding</div></div>
    <div class="val-stat"><div class="val-num text-secondary"><?= formatBytes($result['file_size']) ?></div><div class="val-lbl">File Size</div></div>
</div>

<!-- Headers detected -->
<?php if (!empty($result['headers'])): ?>
<div class="mb-3">
    <h2 class="h6 fw-bold mb-2"><i class="bi bi-columns me-1 text-primary"></i>Headers Detected</h2>
    <div class="d-flex flex-wrap gap-2">
        <?php foreach ($result['headers'] as $h): ?>
        <span class="badge bg-secondary"><?= e($h) ?></span>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Issues table -->
<?php if (!empty($result['errors'])): ?>
<h2 class="h6 fw-bold mb-2"><i class="bi bi-list-check me-1 text-primary"></i>Validation Report</h2>
<div class="table-responsive" style="max-height:400px;overflow-y:auto;">
    <table class="table table-sm table-bordered align-middle">
        <thead class="table-light sticky-top">
            <tr><th>Row</th><th>Col</th><th>Severity</th><th>Code</th><th>Message</th></tr>
        </thead>
        <tbody>
        <?php foreach ($result['errors'] as $issue): ?>
            <tr class="<?= $issue['severity']==='error'?'error-row':'warning-row' ?>">
                <td class="font-mono small"><?= e((string)$issue['row']) ?></td>
                <td class="font-mono small"><?= $issue['col'] !== null ? $issue['col'] : '—' ?></td>
                <td>
                    <span class="<?= $issue['severity']==='error'?'error-badge':'warning-badge' ?>">
                        <?= e($issue['severity']) ?>
                    </span>
                </td>
                <td class="small"><code><?= e($issue['code']) ?></code></td>
                <td class="small"><?= e($issue['message']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if (!$result['valid']): ?>
<div class="mt-3">
    <a href="<?= url('csv-repair/') ?>" class="btn btn-warning">
        <i class="bi bi-wrench me-2"></i>Fix These Errors with CSV Repair Tool
    </a>
</div>
<?php endif; ?>

<?php endif; ?>

<?php
$toolContent = ob_get_clean();
require_once ROOT_PATH . '/includes/tool-layout.php';
