<?php
require_once ROOT_PATH . '/services/CsvParser.php';
require_once ROOT_PATH . '/services/CsvCleaningService.php';

$tool = getToolBySlug('csv-merger') ?? [
    'name'              => 'CSV Merger',
    'slug'              => 'csv-merger',
    'h1'                => 'CSV Merger — Combine Multiple CSV Files Online',
    'short_description' => 'Merge up to 10 CSV files into one. Align columns by name or position.',
    'meta_title'        => 'CSV Merger — Merge Multiple CSV Files Online Free',
    'meta_description'  => 'Merge up to 10 CSV files into a single file online. Match columns by name or position, and download the combined CSV.',
    'instructions'      => '<ol><li>Select up to 10 CSV files to merge (hold Ctrl/Cmd for multi-select, or add files one by one).</li><li>Choose whether to match columns by header name or by position.</li><li>Click <strong>Merge Files</strong>.</li><li>Preview and download the merged CSV.</li></ol>',
    'content'           => '<p>The CSV Merger combines multiple CSV files into a single file. When using "match by column name", columns from all files are aligned by their header names — missing columns are filled with empty values. When using "match by position", columns are merged in order and all files should have the same column structure.</p>',
];
$faqs         = getToolFaqs($tool['id'] ?? 0);
$relatedTools = getRelatedTools('csv-merger');

$result = null;
$errors = [];

if (isPost()) {
    verifyCsrf();
    $files    = $_FILES['csv_files'] ?? null;
    $matchBy  = in_array($_POST['match_by'] ?? '', ['name','position']) ? $_POST['match_by'] : 'name';
    $delimKey = $_POST['delimiter'] ?? 'auto';
    $delimMap = ['auto'=>'auto','comma'=>',','semicolon'=>';','tab'=>"\t",'pipe'=>'|'];
    $delim    = $delimMap[$delimKey] ?? 'auto';

    // Reconstruct files array
    $uploadedFiles = [];
    if ($files && is_array($files['name'])) {
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $uploadedFiles[] = [
                    'name'     => $files['name'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'size'     => $files['size'][$i],
                    'error'    => $files['error'][$i],
                ];
            }
        }
    }

    if (count($uploadedFiles) < 2) {
        $errors[] = 'Please select at least 2 CSV files to merge.';
    } elseif (count($uploadedFiles) > MAX_MERGE_FILES) {
        $errors[] = 'Maximum ' . MAX_MERGE_FILES . ' files allowed.';
    } else {
        $contents = [];
        foreach ($uploadedFiles as $uf) {
            $errs = validateUploadedFile($uf);
            if (!empty($errs)) { $errors = array_merge($errors, $errs); break; }
            $raw = file_get_contents($uf['tmp_name']);
            if ($raw === false) { $errors[] = 'Could not read file: ' . e($uf['name']); break; }
            // Auto-detect delimiter from first file
            if ($delim === 'auto' && empty($contents)) {
                $delim = CsvParser::detectDelimiter($raw);
            }
            $contents[] = $raw;
        }

        if (empty($errors)) {
            $result = CsvCleaningService::mergeFiles($contents, $delim, $matchBy);
            if ($result['success']) {
                $tmpFile = saveTempFile($result['content'], 'csv');
                $downloadName = 'merged_' . date('Ymd_His') . '.csv';
                $_SESSION['merger_download'] = ['file' => $tmpFile, 'name' => $downloadName];
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

    <!-- Multi-file upload -->
    <div class="mb-3">
        <label class="form-label fw-medium">CSV Files <span class="text-muted small">(select 2–<?= MAX_MERGE_FILES ?> files)</span></label>
        <div class="upload-area" role="button" tabindex="0" id="mergerUploadArea">
            <input type="file" name="csv_files[]" id="csv_files" accept=".csv,.txt,.tsv" multiple>
            <div class="upload-icon"><i class="bi bi-stack" aria-hidden="true"></i></div>
            <h3>Drag &amp; drop CSV files here</h3>
            <p>or click to browse — max <?= MAX_MERGE_FILES ?> files, <?= formatBytes(MAX_FILE_SIZE) ?> each</p>
        </div>
        <div id="mergerFileList" class="mt-2"></div>
        <div class="text-muted small mt-1" id="csv_files_count"></div>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-sm-6">
            <label for="match_by" class="form-label fw-medium">Column Matching</label>
            <select id="match_by" name="match_by" class="form-select">
                <option value="name" selected>By header name (recommended)</option>
                <option value="position">By column position</option>
            </select>
            <div class="form-text">
                "By name" aligns columns using header names — missing columns get empty values.
                "By position" merges columns in order; all files should have identical columns.
            </div>
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
        <i class="bi bi-stack me-2" aria-hidden="true"></i>Merge Files
    </button>
</form>

<?php if ($result): ?>
<hr class="my-4">

<div class="alert alert-success">
    <strong><?= $result['files_merged'] ?> files merged</strong> —
    <?= number_format($result['total_rows']) ?> total data rows,
    <?= count($result['headers']) ?> columns.
</div>

<!-- Headers -->
<div class="mb-3">
    <h2 class="h6 fw-bold mb-2">Merged Columns</h2>
    <div class="d-flex flex-wrap gap-2">
        <?php foreach ($result['headers'] as $h): ?>
        <span class="badge bg-secondary"><?= e($h) ?></span>
        <?php endforeach; ?>
    </div>
</div>

<!-- Preview -->
<?php
$prev = CsvParser::parseString($result['content']);
$prevRows = array_slice($prev['rows'], 0, 8);
if (!empty($prevRows)):
?>
<h2 class="h6 fw-bold mb-2"><i class="bi bi-eye me-1 text-primary"></i>Preview</h2>
<div class="csv-table-wrapper mb-3">
    <table class="table table-sm table-bordered csv-table">
        <thead><tr><th>#</th><?php foreach ($prev['headers'] as $h): ?><th><?= e($h) ?></th><?php endforeach; ?></tr></thead>
        <tbody>
        <?php foreach ($prevRows as $ri => $row): ?>
            <tr><td class="text-muted font-mono"><?= $ri+1 ?></td><?php foreach ($row as $cell): ?><td><?= e($cell) ?></td><?php endforeach; ?></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<a href="<?= url('download/') ?>?file=<?= urlencode($_SESSION['merger_download']['file']) ?>&name=<?= urlencode($_SESSION['merger_download']['name']) ?>&type=csv"
   class="btn btn-success btn-lg">
    <i class="bi bi-download me-2"></i>Download Merged CSV
</a>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    CsvTk.initMultiFileInput('csv_files', 'mergerFileList');
});
</script>

<?php
$toolContent = ob_get_clean();
require_once ROOT_PATH . '/includes/tool-layout.php';
