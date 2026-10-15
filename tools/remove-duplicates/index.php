<?php
require_once ROOT_PATH . '/services/CsvParser.php';
require_once ROOT_PATH . '/services/CsvCleaningService.php';

$tool = getToolBySlug('remove-duplicates') ?? [
    'name'              => 'Remove Duplicate Rows',
    'slug'              => 'remove-duplicates',
    'h1'                => 'Remove Duplicate Rows from CSV Files Online',
    'short_description' => 'Remove duplicate rows from CSV files based on all columns or selected key columns.',
    'meta_title'        => 'Remove Duplicate Rows from CSV — Free Online Deduplication Tool',
    'meta_description'  => 'Remove duplicate rows from any CSV file online. Choose which columns to compare, preview results, and download the deduplicated file.',
    'instructions'      => '<ol><li>Upload your CSV file.</li><li>Choose whether to compare all columns or only selected key columns.</li><li>Choose whether to keep the first or last occurrence of each duplicate.</li><li>Click <strong>Remove Duplicates</strong>.</li><li>Review the results and download the deduplicated CSV.</li></ol>',
    'content'           => '<p>The deduplication tool compares rows and removes exact duplicates, or near-duplicates based on key columns you choose. This is ideal for cleaning CRM exports, mailing lists, or any dataset with repeated entries.</p>',
];
$faqs         = getToolFaqs($tool['id'] ?? 0);
$relatedTools = getRelatedTools('remove-duplicates');

$result  = null;
$errors  = [];
$headers = [];

// AJAX: get headers from file for column selector
if (isPost() && isset($_POST['action']) && $_POST['action'] === 'get_headers') {
    verifyCsrf();
    $file = $_FILES['csv_file'] ?? null;
    if ($file && $file['error'] === UPLOAD_ERR_OK) {
        $raw  = file_get_contents($file['tmp_name']);
        $delim = CsvParser::detectDelimiter($raw);
        $p = CsvParser::parseString($raw, $delim);
        jsonResponse(['headers' => $p['headers'], 'delimiter' => $delim]);
    }
    jsonResponse(['headers' => [], 'delimiter' => ',']);
}

if (isPost() && !isset($_POST['action'])) {
    verifyCsrf();
    $file      = $_FILES['csv_file'] ?? null;
    $keepMode  = $_POST['keep_mode'] ?? 'first';
    $colMode   = $_POST['col_mode']  ?? 'all';
    $keyCols   = isset($_POST['key_cols']) ? array_map('intval', (array)$_POST['key_cols']) : null;

    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Please select a CSV file.';
    } else {
        $uploadErrors = validateUploadedFile($file);
        if (!empty($uploadErrors)) {
            $errors = $uploadErrors;
        } else {
            $rawContent = file_get_contents($file['tmp_name']);
            $delim      = CsvParser::detectDelimiter($rawContent);

            $keyCols = ($colMode === 'selected' && !empty($keyCols)) ? $keyCols : null;

            $result = CsvCleaningService::removeDuplicates($rawContent, $delim, $keyCols, $keepMode);

            if ($result['success']) {
                $tmpFile = saveTempFile($result['content'], 'csv');
                $downloadName = sanitizeFilename(pathinfo($file['name'], PATHINFO_FILENAME) . '_deduplicated.csv');
                $_SESSION['dedup_download'] = ['file' => $tmpFile, 'name' => $downloadName];
                $headers = $result['headers'];
            } else {
                $errors[] = $result['error'];
                $result   = null;
            }
        }
    }
}

ob_start();
?>
<form method="post" enctype="multipart/form-data" class="tool-form" id="dedupForm" novalidate>
    <?= csrfField() ?>

    <div class="upload-area mb-3" role="button" tabindex="0">
        <input type="file" name="csv_file" id="csv_file" accept=".csv,.txt,.tsv">
        <div class="upload-icon"><i class="bi bi-funnel" aria-hidden="true"></i></div>
        <h3>Drag &amp; drop your CSV here</h3>
        <p>or click to browse — max <?= formatBytes(MAX_FILE_SIZE) ?></p>
        <p class="upload-filename text-primary fw-semibold d-none mt-2"></p>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <div class="row g-3 mb-3">
        <div class="col-sm-6">
            <label class="form-label fw-medium">Compare by</label>
            <div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="col_mode" id="colAll" value="all" checked>
                    <label class="form-check-label" for="colAll">All columns</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="col_mode" id="colSelected" value="selected">
                    <label class="form-check-label" for="colSelected">Selected key columns</label>
                </div>
            </div>
        </div>
        <div class="col-sm-6">
            <label for="keep_mode" class="form-label fw-medium">Keep occurrence</label>
            <select id="keep_mode" name="keep_mode" class="form-select">
                <option value="first">First occurrence</option>
                <option value="last">Last occurrence</option>
            </select>
        </div>
    </div>

    <!-- Column selector — loaded dynamically -->
    <div id="colSelectorWrap" class="mb-3 d-none">
        <label class="form-label fw-medium">Key Columns <span class="text-muted small">(select one or more)</span></label>
        <div id="colSelector" class="d-flex flex-wrap gap-2">
            <span class="text-muted small">Upload a file to load column names</span>
        </div>
    </div>

    <button type="submit" class="btn btn-primary px-4">
        <i class="bi bi-funnel me-2" aria-hidden="true"></i>Remove Duplicates
    </button>
</form>

<?php if ($result): ?>
<hr class="my-4">

<div class="validation-summary mb-3">
    <div class="val-stat"><div class="val-num text-primary"><?= number_format($result['original_rows']) ?></div><div class="val-lbl">Original Rows</div></div>
    <div class="val-stat"><div class="val-num text-success"><?= number_format($result['unique_rows']) ?></div><div class="val-lbl">Unique Rows</div></div>
    <div class="val-stat"><div class="val-num text-danger"><?= number_format($result['removed_rows']) ?></div><div class="val-lbl">Removed</div></div>
    <div class="val-stat">
        <div class="val-num text-info">
            <?= $result['original_rows'] > 0
                ? round(($result['removed_rows'] / $result['original_rows']) * 100, 1) . '%'
                : '0%' ?>
        </div>
        <div class="val-lbl">Duplicate %</div>
    </div>
</div>

<?php if ($result['removed_rows'] === 0): ?>
<div class="alert alert-info">No duplicate rows were found. Your data is already unique.</div>
<?php else: ?>
<div class="alert alert-success">
    <strong><?= number_format($result['removed_rows']) ?> duplicate row(s) removed.</strong>
    <?= number_format($result['unique_rows']) ?> unique rows remain.
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

<a href="<?= url('download/') ?>?file=<?= urlencode($_SESSION['dedup_download']['file']) ?>&name=<?= urlencode($_SESSION['dedup_download']['name']) ?>&type=csv"
   class="btn btn-success btn-lg">
    <i class="bi bi-download me-2"></i>Download Deduplicated CSV
</a>
<?php endif; ?>
<?php endif; ?>

<script>
// Toggle column selector visibility
document.querySelectorAll('input[name="col_mode"]').forEach(r => {
    r.addEventListener('change', () => {
        const wrap = document.getElementById('colSelectorWrap');
        if (wrap) wrap.classList.toggle('d-none', r.value !== 'selected');
    });
});

// Load column names when file is chosen
document.getElementById('csv_file').addEventListener('change', async function() {
    const colMode = document.querySelector('input[name="col_mode"]:checked');
    if (!this.files.length) return;

    const formData = new FormData();
    formData.append('csv_file', this.files[0]);
    formData.append('action', 'get_headers');
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

    try {
        const resp = await fetch('', { method: 'POST', body: formData });
        const data = await resp.json();
        const container = document.getElementById('colSelector');
        if (!container) return;
        container.innerHTML = '';
        data.headers.forEach((h, i) => {
            const id = 'col_' + i;
            const label = document.createElement('label');
            label.className = 'form-check-label d-flex align-items-center gap-1 border rounded px-2 py-1 cursor-pointer';
            label.innerHTML = `<input type="checkbox" class="form-check-input" name="key_cols[]" value="${i}" id="${id}"> ${CsvTk.escHtml(h)}`;
            container.appendChild(label);
        });
    } catch(e) { /* silently ignore */ }
});
</script>

<?php
$toolContent = ob_get_clean();
require_once ROOT_PATH . '/includes/tool-layout.php';
