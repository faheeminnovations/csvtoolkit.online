<?php
require_once ROOT_PATH . '/services/CsvParser.php';
require_once ROOT_PATH . '/services/CsvConversionService.php';

$tool = getToolBySlug('csv-delimiter-converter') ?? [
    'name'              => 'CSV Delimiter Converter',
    'slug'              => 'csv-delimiter-converter',
    'h1'                => 'CSV Delimiter Converter — Change Delimiters Instantly',
    'short_description' => 'Convert CSV files between comma, semicolon, tab, pipe, and custom delimiters.',
    'meta_title'        => 'CSV Delimiter Converter — Change CSV Delimiter Online Free',
    'meta_description'  => 'Convert CSV files between any delimiter: comma, semicolon, tab, pipe, or custom. Free online delimiter converter with preview.',
    'instructions'      => '<ol><li>Upload your CSV file.</li><li>Select the current (from) delimiter — or use Auto-detect.</li><li>Select the target (to) delimiter.</li><li>Click <strong>Convert</strong> to process.</li><li>Preview the first few rows and download the converted file.</li></ol>',
    'content'           => '<p>Different applications use different CSV delimiters. Excel in many European countries defaults to semicolons, while most English-language tools use commas. Databases often export with tabs or pipes. This tool converts between any of these formats without altering your data.</p>',
];
$faqs         = getToolFaqs($tool['id'] ?? 0);
$relatedTools = getRelatedTools('csv-delimiter-converter');

$result = null;
$errors = [];

$delimOptions = [
    'auto'      => 'Auto-detect',
    'comma'     => 'Comma ( , )',
    'semicolon' => 'Semicolon ( ; )',
    'tab'       => 'Tab ( ⇥ )',
    'pipe'      => 'Pipe ( | )',
    'colon'     => 'Colon ( : )',
    'custom'    => 'Custom…',
];
$delimMap = ['auto'=>'auto','comma'=>',','semicolon'=>';','tab'=>"\t",'pipe'=>'|','colon'=>':'];

if (isPost()) {
    verifyCsrf();
    $file       = $_FILES['csv_file'] ?? null;
    $fromKey    = $_POST['from_delimiter'] ?? 'auto';
    $toKey      = $_POST['to_delimiter']   ?? 'comma';
    $customFrom = trim($_POST['custom_from'] ?? '');
    $customTo   = trim($_POST['custom_to']   ?? '');

    $fromDelim  = $fromKey === 'custom' ? (mb_substr($customFrom, 0, 1) ?: ',') : ($delimMap[$fromKey] ?? 'auto');
    $toDelim    = $toKey   === 'custom' ? (mb_substr($customTo,   0, 1) ?: ',') : ($delimMap[$toKey]   ?? ',');

    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Please select a CSV file.';
    } else {
        $uploadErrors = validateUploadedFile($file);
        if (!empty($uploadErrors)) {
            $errors = $uploadErrors;
        } elseif ($fromDelim !== 'auto' && $fromDelim === $toDelim) {
            $errors[] = 'The source and target delimiters are identical. Please choose different delimiters.';
        } else {
            $rawContent = file_get_contents($file['tmp_name']);
            $result = CsvConversionService::convertDelimiter($rawContent, $fromDelim, $toDelim);

            if ($result['success']) {
                $tmpFile = saveTempFile($result['content'], 'csv');
                $downloadName = sanitizeFilename(pathinfo($file['name'], PATHINFO_FILENAME) . '_converted.csv');
                $_SESSION['delimiter_download'] = ['file' => $tmpFile, 'name' => $downloadName];
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
        <div class="upload-icon"><i class="bi bi-arrow-left-right" aria-hidden="true"></i></div>
        <h3>Drag &amp; drop your CSV here</h3>
        <p>or click to browse — max <?= formatBytes(MAX_FILE_SIZE) ?></p>
        <p class="upload-filename text-primary fw-semibold d-none mt-2"></p>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-sm-5">
            <label for="from_delimiter" class="form-label fw-medium">From Delimiter</label>
            <select id="from_delimiter" name="from_delimiter" class="form-select"
                    onchange="toggleCustom('from', this.value)">
                <?php foreach ($delimOptions as $k => $v): ?>
                <option value="<?= e($k) ?>"><?= e($v) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" id="custom_from" name="custom_from" maxlength="1"
                   class="form-control mt-2 d-none" placeholder="Enter 1 character">
        </div>
        <div class="col-sm-2 d-flex align-items-end justify-content-center pb-1">
            <i class="bi bi-arrow-right fs-4 text-muted" aria-hidden="true"></i>
        </div>
        <div class="col-sm-5">
            <label for="to_delimiter" class="form-label fw-medium">To Delimiter</label>
            <select id="to_delimiter" name="to_delimiter" class="form-select"
                    onchange="toggleCustom('to', this.value)">
                <option value="comma" selected>Comma ( , )</option>
                <option value="semicolon">Semicolon ( ; )</option>
                <option value="tab">Tab ( ⇥ )</option>
                <option value="pipe">Pipe ( | )</option>
                <option value="colon">Colon ( : )</option>
                <option value="custom">Custom…</option>
            </select>
            <input type="text" id="custom_to" name="custom_to" maxlength="1"
                   class="form-control mt-2 d-none" placeholder="Enter 1 character">
        </div>
    </div>

    <button type="submit" class="btn btn-primary px-4">
        <i class="bi bi-arrow-left-right me-2" aria-hidden="true"></i>Convert Delimiter
    </button>
</form>

<?php if ($result): ?>
<hr class="my-4">

<div class="alert alert-success d-flex gap-2 align-items-center mb-3">
    <i class="bi bi-check-circle-fill fs-5"></i>
    <div>
        Converted <strong><?= $result['row_count'] ?> rows</strong> from
        <code><?= e($result['from_delimiter'] === "\t" ? 'Tab' : $result['from_delimiter']) ?></code> to
        <code><?= e($result['to_delimiter']   === "\t" ? 'Tab' : $result['to_delimiter'])   ?></code> delimiter.
    </div>
</div>

<!-- Preview -->
<?php
$prev = CsvParser::parseString($result['content']);
$prevRows = array_slice($prev['rows'], 0, 8);
?>
<?php if (!empty($prevRows)): ?>
<h2 class="h6 fw-bold mb-2"><i class="bi bi-eye me-1 text-primary"></i>Preview (first 8 rows)</h2>
<div class="csv-table-wrapper mb-3">
    <table class="table table-sm table-bordered csv-table">
        <thead>
            <tr><th>#</th><?php foreach ($prev['headers'] as $h): ?><th><?= e($h) ?></th><?php endforeach; ?></tr>
        </thead>
        <tbody>
        <?php foreach ($prevRows as $ri => $row): ?>
            <tr><td class="text-muted font-mono"><?= $ri+1 ?></td>
            <?php foreach ($row as $cell): ?><td><?= e($cell) ?></td><?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<a href="<?= url('download/') ?>?file=<?= urlencode($_SESSION['delimiter_download']['file']) ?>&name=<?= urlencode($_SESSION['delimiter_download']['name']) ?>&type=csv"
   class="btn btn-success btn-lg">
    <i class="bi bi-download me-2"></i>Download Converted CSV
</a>
<?php endif; ?>

<script>
function toggleCustom(side, val) {
    const el = document.getElementById('custom_' + side);
    if (el) el.classList.toggle('d-none', val !== 'custom');
}
</script>

<?php
$toolContent = ob_get_clean();
require_once ROOT_PATH . '/includes/tool-layout.php';
