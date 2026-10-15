<?php
require_once ROOT_PATH . '/services/CsvParser.php';
require_once ROOT_PATH . '/services/CsvComparisonService.php';

$tool = getToolBySlug('csv-file-comparison') ?? [
    'name'              => 'CSV File Comparison',
    'slug'              => 'csv-file-comparison',
    'h1'                => 'CSV File Comparison — Find Differences Between Two CSV Files',
    'short_description' => 'Compare two CSV files side by side to find added, removed, and modified rows.',
    'meta_title'        => 'CSV File Comparison — Compare Two CSV Files Online Free',
    'meta_description'  => 'Compare two CSV files to find differences: added rows, removed rows, and modified cells. Visual diff with downloadable report.',
    'instructions'      => '<ol><li>Upload your first CSV file (File A — the original).</li><li>Upload your second CSV file (File B — the updated version).</li><li>Optionally choose the key column used to match rows between files.</li><li>Click <strong>Compare Files</strong>.</li><li>Review the colour-coded diff and download the comparison report.</li></ol>',
    'content'           => '<p>The CSV File Comparison tool identifies rows that were added, removed, or modified between two versions of a CSV file. It uses a key column to match rows across files — rows with the same key value are compared cell by cell. Changes are highlighted in a colour-coded table.</p>',
];
$faqs         = getToolFaqs($tool['id'] ?? 0);
$relatedTools = getRelatedTools('csv-file-comparison');

$result = null;
$errors = [];

if (isPost()) {
    verifyCsrf();
    $fileA     = $_FILES['csv_file_a'] ?? null;
    $fileB     = $_FILES['csv_file_b'] ?? null;
    $delimKey  = $_POST['delimiter'] ?? 'auto';
    $delimMap  = ['auto'=>'auto','comma'=>',','semicolon'=>';','tab'=>"\t",'pipe'=>'|'];
    $delim     = $delimMap[$delimKey] ?? 'auto';
    $keyColStr = trim($_POST['key_col'] ?? '0');
    $keyCols   = array_map('intval', preg_split('/\s*,\s*/', $keyColStr));

    if (!$fileA || $fileA['error'] === UPLOAD_ERR_NO_FILE) $errors[] = 'Please upload File A.';
    if (!$fileB || $fileB['error'] === UPLOAD_ERR_NO_FILE) $errors[] = 'Please upload File B.';

    if (empty($errors)) {
        $errA = validateUploadedFile($fileA);
        $errB = validateUploadedFile($fileB);
        $errors = array_merge($errA, $errB);
    }

    if (empty($errors)) {
        $contentA = file_get_contents($fileA['tmp_name']);
        $contentB = file_get_contents($fileB['tmp_name']);

        if ($delim === 'auto') $delim = CsvParser::detectDelimiter($contentA);

        $result = CsvComparisonService::compare($contentA, $contentB, $delim, $keyCols);

        if (!$result['success']) {
            $errors[] = $result['error'] ?? 'Comparison failed.';
            $result   = null;
        } else {
            // Build downloadable report CSV
            $reportRows = [['Status','Key','File A','File B','Changed Columns']];
            foreach ($result['diff'] as $d) {
                if ($d['status'] === CsvComparisonService::STATUS_EQUAL) continue;
                $reportRows[] = [
                    $d['status'],
                    $d['key'],
                    implode(' | ', $d['row_a'] ?? []),
                    implode(' | ', $d['row_b'] ?? []),
                    implode(', ', $d['changed_cols']),
                ];
            }
            $reportCsv  = CsvParser::toString(array_slice($reportRows, 1), $reportRows[0]);
            $reportFile = saveTempFile($reportCsv, 'csv');
            $_SESSION['comparison_download'] = [
                'file' => $reportFile,
                'name' => 'comparison_report_' . date('Ymd_His') . '.csv',
            ];
        }
    }
}

ob_start();
?>
<form method="post" enctype="multipart/form-data" class="tool-form" novalidate>
    <?= csrfField() ?>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label fw-medium">File A <span class="text-muted">(original)</span></label>
            <div class="upload-area" role="button" tabindex="0" style="padding:1.5rem">
                <input type="file" name="csv_file_a" id="csv_file_a" accept=".csv,.txt,.tsv">
                <div class="upload-icon" style="font-size:1.8rem"><i class="bi bi-file-earmark-text text-primary" aria-hidden="true"></i></div>
                <h3 style="font-size:.9rem">File A — Original</h3>
                <p class="upload-filename text-primary fw-semibold d-none mt-1 small"></p>
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-medium">File B <span class="text-muted">(updated)</span></label>
            <div class="upload-area" role="button" tabindex="0" style="padding:1.5rem">
                <input type="file" name="csv_file_b" id="csv_file_b" accept=".csv,.txt,.tsv">
                <div class="upload-icon" style="font-size:1.8rem"><i class="bi bi-file-earmark-diff text-success" aria-hidden="true"></i></div>
                <h3 style="font-size:.9rem">File B — Updated</h3>
                <p class="upload-filename text-primary fw-semibold d-none mt-1 small"></p>
            </div>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-sm-5">
            <label for="key_col" class="form-label fw-medium">Key Column Index(es)</label>
            <input type="text" id="key_col" name="key_col" class="form-control"
                   value="0" placeholder="e.g. 0 or 0,1">
            <div class="form-text">0-based column index used to match rows. Use comma for multiple keys.</div>
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
        <i class="bi bi-files me-2" aria-hidden="true"></i>Compare Files
    </button>
</form>

<?php if ($result): ?>
<hr class="my-4">

<!-- Stats -->
<div class="validation-summary mb-3">
    <div class="val-stat"><div class="val-num text-primary"><?= $result['stats']['total_a'] ?></div><div class="val-lbl">File A Rows</div></div>
    <div class="val-stat"><div class="val-num text-primary"><?= $result['stats']['total_b'] ?></div><div class="val-lbl">File B Rows</div></div>
    <div class="val-stat"><div class="val-num text-success"><?= $result['stats']['added'] ?></div><div class="val-lbl">Added</div></div>
    <div class="val-stat"><div class="val-num text-danger"><?= $result['stats']['removed'] ?></div><div class="val-lbl">Removed</div></div>
    <div class="val-stat"><div class="val-num text-warning"><?= $result['stats']['modified'] ?></div><div class="val-lbl">Modified</div></div>
    <div class="val-stat"><div class="val-num text-secondary"><?= $result['stats']['equal'] ?></div><div class="val-lbl">Unchanged</div></div>
</div>

<?php if ($result['stats']['added'] + $result['stats']['removed'] + $result['stats']['modified'] === 0): ?>
<div class="alert alert-success">
    <i class="bi bi-check-circle-fill me-2"></i><strong>Files are identical!</strong> No differences found.
</div>
<?php else: ?>

<!-- Legend -->
<div class="diff-legend mb-3">
    <div class="diff-legend-item"><div class="diff-dot bg-success"></div> Added in File B</div>
    <div class="diff-legend-item"><div class="diff-dot bg-danger"></div> Removed from File A</div>
    <div class="diff-legend-item"><div class="diff-dot bg-warning"></div> Modified</div>
</div>

<!-- Diff table — exclude equal rows for clarity, show first 200 diff rows -->
<?php
$diffRows = array_filter($result['diff'], fn($d) => $d['status'] !== CsvComparisonService::STATUS_EQUAL);
$diffRows = array_slice($diffRows, 0, 200);
$headers  = $result['headers'];
?>
<div class="csv-table-wrapper mb-3">
    <table class="table table-sm table-bordered csv-table">
        <thead>
            <tr>
                <th>Status</th>
                <th>Key</th>
                <?php foreach ($headers as $h): ?><th><?= e($h) ?></th><?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($diffRows as $d): ?>
            <?php
            $rowCls = match($d['status']) {
                'added'    => 'row-added',
                'removed'  => 'row-removed',
                'modified' => 'row-modified',
                default    => '',
            };
            ?>
            <!-- File A row -->
            <?php if ($d['row_a'] !== null): ?>
            <tr class="<?= $rowCls ?>">
                <td>
                    <?php if ($d['status'] === 'removed'): ?>
                    <span class="badge bg-danger">Removed</span>
                    <?php elseif ($d['status'] === 'modified'): ?>
                    <span class="badge bg-warning text-dark">Modified A</span>
                    <?php endif; ?>
                </td>
                <td class="font-mono small"><?= e($d['key']) ?></td>
                <?php foreach ($headers as $ci => $h): ?>
                <td><?= e($d['row_a'][$ci] ?? '') ?></td>
                <?php endforeach; ?>
            </tr>
            <?php endif; ?>
            <!-- File B row (for added/modified) -->
            <?php if (in_array($d['status'], ['added','modified']) && $d['row_b'] !== null): ?>
            <tr class="<?= $rowCls ?>">
                <td>
                    <?php if ($d['status'] === 'added'): ?>
                    <span class="badge bg-success">Added</span>
                    <?php else: ?>
                    <span class="badge bg-warning text-dark">Modified B</span>
                    <?php endif; ?>
                </td>
                <td class="font-mono small"><?= e($d['key']) ?></td>
                <?php foreach ($headers as $ci => $h): ?>
                <td class="<?= in_array($ci, $d['changed_cols'], true) ? 'cell-changed' : '' ?>">
                    <?= e($d['row_b'][$ci] ?? '') ?>
                </td>
                <?php endforeach; ?>
            </tr>
            <?php endif; ?>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (count(array_filter($result['diff'], fn($d)=>$d['status']!=='equal')) > 200): ?>
<div class="alert alert-info small">Showing first 200 changed rows. Download the full report for all differences.</div>
<?php endif; ?>

<a href="<?= url('download/') ?>?file=<?= urlencode($_SESSION['comparison_download']['file']) ?>&name=<?= urlencode($_SESSION['comparison_download']['name']) ?>&type=csv"
   class="btn btn-success btn-lg">
    <i class="bi bi-download me-2"></i>Download Comparison Report
</a>
<?php endif; ?>
<?php endif; ?>

<?php
$toolContent = ob_get_clean();
require_once ROOT_PATH . '/includes/tool-layout.php';
