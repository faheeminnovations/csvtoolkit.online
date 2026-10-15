<?php
require_once ROOT_PATH . '/services/CsvParser.php';
require_once ROOT_PATH . '/services/CsvRepairService.php';

$tool = getToolBySlug('csv-repair') ?? [
    'name'              => 'CSV Repair Tool',
    'slug'              => 'csv-repair',
    'h1'                => 'CSV Repair Tool — Fix Broken CSV Files Instantly',
    'short_description' => 'Automatically detect and fix common CSV formatting errors.',
    'meta_title'        => 'CSV Repair Tool — Fix Broken CSV Files Online Free',
    'meta_description'  => 'Repair malformed CSV files instantly. Auto-detects and fixes broken quotes, mismatched columns, BOM, and encoding issues.',
    'instructions'      => '<ol>
        <li>Upload your broken or malformed CSV file.</li>
        <li>Choose the delimiter (auto-detect is recommended).</li>
        <li>Click <strong>Repair CSV</strong> to analyse and fix the file.</li>
        <li>Review the detected issues below.</li>
        <li><strong>Click any cell</strong> in the preview table to edit it manually.</li>
        <li>Click <strong>Download Edited CSV</strong> to save the final result.</li>
    </ol>',
    'content' => '<p>The CSV Repair Tool automatically fixes the most common CSV problems: BOM characters, mixed line endings, inconsistent column counts, unclosed quoted fields, and encoding issues. After auto-repair you can manually edit any cell in the preview table before downloading.</p>',
];

$faqs         = getToolFaqs($tool['id'] ?? 0);
$relatedTools = getRelatedTools('csv-repair');

// ── POST: save edited CSV from table ─────────────────────────────────────────
if (isPost() && isset($_POST['action']) && $_POST['action'] === 'save_edited') {
    verifyCsrf();

    $headersRaw = $_POST['headers'] ?? [];
    $rowsRaw    = $_POST['rows']    ?? [];
    $delimKey   = $_POST['delim']   ?? 'comma';
    $delimMap   = ['comma'=>',','semicolon'=>';','tab'=>"\t",'pipe'=>'|'];
    $delim      = $delimMap[$delimKey] ?? ',';

    // Sanitise — strip any formula injection
    $headers = array_map('strval', $headersRaw);
    $rows    = [];
    foreach ($rowsRaw as $row) {
        $rows[] = array_map('strval', (array)$row);
    }

    $csvContent = CsvParser::toString($rows, $headers, $delim);
    $tmpFile    = saveTempFile($csvContent, 'csv');
    $dlName     = sanitizeFilename(($_POST['original_name'] ?? 'repaired') . '_edited.csv');

    $_SESSION['repair_download'] = ['file' => $tmpFile, 'name' => $dlName];
    jsonResponse([
        'success'  => true,
        'file'     => $tmpFile,
        'name'     => $dlName,
        'download' => url('download/') . '?file=' . urlencode($tmpFile)
                      . '&name=' . urlencode($dlName) . '&type=csv',
    ]);
}

// ── POST: repair the uploaded file ───────────────────────────────────────────
$result       = null;
$errors       = [];
$downloadFile = null;
$allRows      = [];
$headers      = [];
$delim        = ',';
$originalName = 'repaired';

if (isPost() && !isset($_POST['action'])) {
    verifyCsrf();

    $file      = $_FILES['csv_file'] ?? null;
    $delimiter = $_POST['delimiter'] ?? 'auto';
    $delMap    = ['auto'=>'auto','comma'=>',','semicolon'=>';','tab'=>"\t",'pipe'=>'|'];
    $delim     = $delMap[$delimiter] ?? 'auto';

    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Please select a CSV file to upload.';
    } else {
        $uploadErrors = validateUploadedFile($file);
        if (!empty($uploadErrors)) {
            $errors = $uploadErrors;
        } else {
            $rawContent = file_get_contents($file['tmp_name']);
            if ($rawContent === false) {
                $errors[] = 'Could not read the uploaded file.';
            } else {
                if ($delim === 'auto') {
                    $delim = CsvParser::detectDelimiter($rawContent);
                }

                $result = CsvRepairService::repair($rawContent, $delim);
                $originalName = pathinfo($file['name'], PATHINFO_FILENAME);

                // Parse ALL rows for the editable table (not just first 10)
                $parsed  = CsvParser::parseString($result['repaired_content'], $delim);
                $headers = $parsed['headers'];
                $allRows = $parsed['rows'];

                // Save repaired content to temp (before manual edits)
                if ($result['changed']) {
                    $tmpFile      = saveTempFile($result['repaired_content'], 'csv');
                    $downloadFile = $tmpFile;
                    $_SESSION['repair_download'] = [
                        'file' => $tmpFile,
                        'name' => sanitizeFilename($originalName . '_repaired.csv'),
                    ];
                }
            }
        }
    }
}

// ── Delimiter key for JS ──────────────────────────────────────────────────────
$delimKeys = [','=>'comma',';'=>'semicolon',"\t"=>'tab','|'=>'pipe'];
$delimKey  = $delimKeys[$delim] ?? 'comma';

ob_start();
?>

<!-- ── Upload form ──────────────────────────────────────────────────────────── -->
<form method="post" enctype="multipart/form-data" class="tool-form" novalidate>
    <?= csrfField() ?>

    <div class="upload-area mb-3" role="button" tabindex="0">
        <input type="file" name="csv_file" id="csv_file" accept=".csv,.txt,.tsv">
        <div class="upload-icon"><i class="bi bi-cloud-upload" aria-hidden="true"></i></div>
        <h3>Drag &amp; drop your CSV here</h3>
        <p>or click to browse — max <?= formatBytes(MAX_FILE_SIZE) ?>, .csv / .txt / .tsv</p>
        <p class="upload-filename text-primary fw-semibold d-none mt-2"></p>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger" role="alert">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="row g-3 mb-3">
        <div class="col-sm-6">
            <label for="delimiter" class="form-label fw-medium">Delimiter</label>
            <select id="delimiter" name="delimiter" class="form-select">
                <option value="auto" selected>Auto-detect (recommended)</option>
                <option value="comma">Comma ( , )</option>
                <option value="semicolon">Semicolon ( ; )</option>
                <option value="tab">Tab ( ⇥ )</option>
                <option value="pipe">Pipe ( | )</option>
            </select>
        </div>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-wrench me-2"></i>Repair CSV
        </button>
        <button type="reset" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
        </button>
    </div>
</form>

<?php if ($result !== null): ?>
<hr class="my-4">

<?php if (!$result['changed']): ?>
<!-- No issues found — still show editable table -->
<div class="alert alert-success d-flex gap-2 align-items-center mb-3" role="alert">
    <i class="bi bi-check-circle-fill fs-5"></i>
    <div><strong>No issues found!</strong> Your CSV looks well-formed. You can still edit cells below and download.</div>
</div>
<?php else: ?>

<!-- Stats -->
<div class="validation-summary mb-3">
    <div class="val-stat">
        <div class="val-num text-primary"><?= $result['stats']['rows_examined'] ?></div>
        <div class="val-lbl">Rows</div>
    </div>
    <div class="val-stat">
        <div class="val-num text-danger"><?= $result['stats']['issues_found'] ?></div>
        <div class="val-lbl">Issues Fixed</div>
    </div>
    <div class="val-stat">
        <div class="val-num text-warning"><?= $result['stats']['rows_repaired'] ?></div>
        <div class="val-lbl">Rows Repaired</div>
    </div>
</div>

<!-- Issues list -->
<h2 class="h6 fw-bold mb-2">
    <i class="bi bi-list-check me-1 text-primary"></i>Issues Detected &amp; Fixed
</h2>
<div class="table-responsive mb-4">
    <table class="table table-sm table-bordered align-middle">
        <thead class="table-light">
            <tr><th>Row</th><th>Type</th><th>Description</th></tr>
        </thead>
        <tbody>
        <?php foreach ($result['issues'] as $issue): ?>
        <tr>
            <td class="font-mono small"><?= e((string)$issue['row']) ?></td>
            <td><span class="badge bg-warning text-dark"><?= e($issue['type']) ?></span></td>
            <td class="small"><?= e($issue['description']) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php endif; // $result['changed'] ?>

<!-- ── EDITABLE PREVIEW TABLE ─────────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
    <h2 class="h6 fw-bold mb-0">
        <i class="bi bi-table me-1 text-primary"></i>
        Editable Preview
        <span class="text-muted fw-normal small ms-1">— click any cell to edit</span>
    </h2>
    <div class="d-flex align-items-center gap-2">
        <!-- Add / remove row controls -->
        <button type="button" class="btn btn-sm btn-outline-success" id="addRowBtn" title="Add empty row at bottom">
            <i class="bi bi-plus-lg me-1"></i>Add Row
        </button>
        <button type="button" class="btn btn-sm btn-outline-danger" id="removeRowBtn" title="Remove last row">
            <i class="bi bi-dash-lg me-1"></i>Remove Row
        </button>
        <div class="vr"></div>
        <span class="text-muted small" id="editHint">
            <i class="bi bi-info-circle me-1"></i><span id="rowColCount"></span>
        </span>
    </div>
</div>

<!-- Editable table -->
<div class="csv-table-wrapper mb-3" id="editableTableWrap">
    <table class="table table-sm table-bordered csv-table mb-0" id="editableTable">
        <thead id="editableHead">
            <tr>
                <th class="text-muted" style="width:42px">#</th>
                <?php foreach ($headers as $hi => $h): ?>
                <th>
                    <input type="text"
                           class="form-control form-control-sm header-input border-0 bg-transparent fw-bold p-0"
                           value="<?= eAttr($h) ?>"
                           data-col="<?= $hi ?>"
                           style="min-width:80px"
                           aria-label="Column <?= $hi+1 ?> header">
                </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody id="editableBody">
            <?php foreach ($allRows as $ri => $row): ?>
            <tr data-row="<?= $ri ?>">
                <td class="text-muted font-mono small text-center row-num"><?= $ri + 1 ?></td>
                <?php foreach ($headers as $ci => $h): ?>
                <td class="p-0">
                    <input type="text"
                           class="form-control form-control-sm cell-input border-0 rounded-0 px-2"
                           value="<?= eAttr($row[$ci] ?? '') ?>"
                           data-row="<?= $ri ?>"
                           data-col="<?= $ci ?>"
                           aria-label="Row <?= $ri+1 ?>, <?= eAttr($h) ?>">
                </td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Pagination info -->
<?php if (count($allRows) > 50): ?>
<div class="alert alert-info small py-2 mb-3">
    <i class="bi bi-info-circle me-1"></i>
    Showing all <strong><?= count($allRows) ?></strong> rows. Scroll horizontally/vertically to navigate.
</div>
<?php endif; ?>

<!-- Download bar -->
<div class="d-flex align-items-center gap-3 flex-wrap p-3 bg-light rounded border">
    <div>
        <label for="downloadDelim" class="form-label fw-medium mb-0 me-2 small">Output Delimiter:</label>
        <select id="downloadDelim" class="form-select form-select-sm d-inline-block w-auto">
            <option value="comma"     <?= $delimKey==='comma'    ?'selected':'' ?>>Comma ( , )</option>
            <option value="semicolon" <?= $delimKey==='semicolon'?'selected':'' ?>>Semicolon ( ; )</option>
            <option value="tab"       <?= $delimKey==='tab'      ?'selected':'' ?>>Tab ( ⇥ )</option>
            <option value="pipe"      <?= $delimKey==='pipe'     ?'selected':'' ?>>Pipe ( | )</option>
        </select>
    </div>
    <button type="button" class="btn btn-success btn-lg px-5" id="downloadEditedBtn">
        <i class="bi bi-download me-2"></i>Download Edited CSV
    </button>
    <span class="text-muted small" id="saveStatus"></span>
</div>

<!-- Hidden data for JS -->
<script>
(function () {
    'use strict';

    var CSRF      = <?= json_encode(csrfToken()) ?>;
    var ORIG_NAME = <?= json_encode($originalName) ?>;
    var SAVE_URL  = <?= json_encode(url('csv-repair/')) ?>;

    var table     = document.getElementById('editableTable');
    var body      = document.getElementById('editableBody');
    var addBtn    = document.getElementById('addRowBtn');
    var removeBtn = document.getElementById('removeRowBtn');
    var dlBtn     = document.getElementById('downloadEditedBtn');
    var dlDelim   = document.getElementById('downloadDelim');
    var saveStatus= document.getElementById('saveStatus');
    var rowColCount = document.getElementById('rowColCount');

    // ── Count columns from header inputs ──────────────────────────────────────
    function colCount() {
        return document.querySelectorAll('#editableHead .header-input').length;
    }

    function updateCount() {
        var rows = body.querySelectorAll('tr').length;
        var cols = colCount();
        if (rowColCount) rowColCount.textContent = rows + ' rows × ' + cols + ' columns';
    }
    updateCount();

    // ── Cell styling: highlight on focus ─────────────────────────────────────
    document.addEventListener('focusin', function (e) {
        if (e.target.classList.contains('cell-input') ||
            e.target.classList.contains('header-input')) {
            e.target.classList.add('cell-editing');
        }
    });
    document.addEventListener('focusout', function (e) {
        if (e.target.classList.contains('cell-input') ||
            e.target.classList.contains('header-input')) {
            e.target.classList.remove('cell-editing');
        }
    });

    // ── Tab navigation through cells ─────────────────────────────────────────
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Tab' && e.key !== 'Enter') return;
        var el = e.target;
        if (!el.classList.contains('cell-input') && !el.classList.contains('header-input')) return;

        var allInputs = Array.from(
            table.querySelectorAll('.header-input, .cell-input')
        );
        var idx = allInputs.indexOf(el);
        if (idx === -1) return;

        if (e.key === 'Enter') {
            e.preventDefault();
            // Move to same column in next row
            var col  = +el.dataset.col;
            var row  = el.dataset.row !== undefined ? +el.dataset.row : -1;
            var next = allInputs.find(function (i) {
                return +i.dataset.col === col && +i.dataset.row === row + 1;
            });
            if (next) next.focus();
        }
    });

    // ── Add row ───────────────────────────────────────────────────────────────
    addBtn.addEventListener('click', function () {
        var cols    = colCount();
        var rowCount= body.querySelectorAll('tr').length;
        var tr      = document.createElement('tr');
        tr.dataset.row = rowCount;

        var tdNum   = document.createElement('td');
        tdNum.className = 'text-muted font-mono small text-center row-num';
        tdNum.textContent = rowCount + 1;
        tr.appendChild(tdNum);

        for (var c = 0; c < cols; c++) {
            var td  = document.createElement('td');
            td.className = 'p-0';
            var inp = document.createElement('input');
            inp.type      = 'text';
            inp.className = 'form-control form-control-sm cell-input border-0 rounded-0 px-2';
            inp.value     = '';
            inp.dataset.row = rowCount;
            inp.dataset.col = c;
            inp.setAttribute('aria-label', 'Row ' + (rowCount+1) + ', col ' + (c+1));
            td.appendChild(inp);
            tr.appendChild(td);
        }
        body.appendChild(tr);
        renumberRows();
        updateCount();
        tr.querySelector('.cell-input').focus();
    });

    // ── Remove last row ───────────────────────────────────────────────────────
    removeBtn.addEventListener('click', function () {
        var rows = body.querySelectorAll('tr');
        if (rows.length > 1) {
            rows[rows.length - 1].remove();
            updateCount();
        }
    });

    // ── Renumber row index numbers ─────────────────────────────────────────────
    function renumberRows() {
        var rows = body.querySelectorAll('tr');
        rows.forEach(function (tr, i) {
            tr.dataset.row = i;
            var numCell = tr.querySelector('.row-num');
            if (numCell) numCell.textContent = i + 1;
            tr.querySelectorAll('.cell-input').forEach(function (inp) {
                inp.dataset.row = i;
            });
        });
    }

    // ── Collect all data from table ───────────────────────────────────────────
    function collectData() {
        var headers = [];
        document.querySelectorAll('#editableHead .header-input').forEach(function (inp) {
            headers.push(inp.value);
        });

        var rows = [];
        body.querySelectorAll('tr').forEach(function (tr) {
            var row = [];
            tr.querySelectorAll('.cell-input').forEach(function (inp) {
                row.push(inp.value);
            });
            rows.push(row);
        });

        return { headers: headers, rows: rows };
    }

    // ── Download button ───────────────────────────────────────────────────────
    dlBtn.addEventListener('click', function () {
        var data    = collectData();
        var delimKey= dlDelim.value;

        saveStatus.innerHTML = '<span class="text-muted"><i class="bi bi-hourglass-split me-1"></i>Saving…</span>';
        dlBtn.disabled = true;

        // Build form data
        var fd = new FormData();
        fd.append('action',        'save_edited');
        fd.append('csrf_token',    CSRF);
        fd.append('original_name', ORIG_NAME);
        fd.append('delim',         delimKey);

        data.headers.forEach(function (h, i) {
            fd.append('headers[]', h);
        });
        data.rows.forEach(function (row, ri) {
            row.forEach(function (cell) {
                fd.append('rows[' + ri + '][]', cell);
            });
        });

        fetch(SAVE_URL, { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (json) {
                if (json.success) {
                    saveStatus.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Ready!</span>';
                    // Trigger download
                    var a   = document.createElement('a');
                    a.href  = json.download;
                    a.click();
                } else {
                    saveStatus.innerHTML = '<span class="text-danger">Save failed. Try again.</span>';
                }
                dlBtn.disabled = false;
            })
            .catch(function () {
                saveStatus.innerHTML = '<span class="text-danger">Network error. Try again.</span>';
                dlBtn.disabled = false;
            });
    });

})();
</script>

<style>
/* Editable cell styles */
.cell-input, .header-input {
    background: transparent;
    transition: background .15s, box-shadow .15s;
}
.cell-input:hover {
    background: #f0f7ff !important;
    cursor: text;
}
.cell-input.cell-editing,
.header-input.cell-editing {
    background: #fff !important;
    box-shadow: inset 0 0 0 2px #0d6efd !important;
    border-radius: 3px !important;
    z-index: 2;
    position: relative;
}
#editableHead th {
    background: #0d6efd;
    color: #fff;
    padding: 4px 6px;
}
#editableHead .header-input {
    color: #fff !important;
    font-weight: 600;
}
#editableHead .header-input:hover,
#editableHead .header-input.cell-editing {
    background: rgba(255,255,255,.15) !important;
    box-shadow: inset 0 0 0 2px #fff !important;
    color: #fff !important;
}
#editableTable td { padding: 0 !important; }
#editableTableWrap { max-height: 480px; overflow: auto; }
</style>

<?php endif; // $result !== null ?>

<?php
$toolContent = ob_get_clean();
require_once ROOT_PATH . '/includes/tool-layout.php';
