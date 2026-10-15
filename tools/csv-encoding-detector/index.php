<?php
require_once ROOT_PATH . '/services/CsvParser.php';

$tool = getToolBySlug('csv-encoding-detector') ?? [
    'name'              => 'CSV Encoding Detector',
    'slug'              => 'csv-encoding-detector',
    'h1'                => 'CSV Encoding Detector — Detect Character Encoding Online',
    'short_description' => 'Detect the character encoding of any CSV file instantly.',
    'meta_title'        => 'CSV Encoding Detector — Detect File Encoding Online Free',
    'meta_description'  => 'Instantly detect the character encoding of any CSV file. Supports UTF-8, UTF-16, ISO-8859-1, Windows-1252, and more.',
    'instructions'      => '<ol><li>Upload your CSV file.</li><li>The tool analyses the file bytes and detects the encoding.</li><li>Review the encoding information and confidence level.</li><li>Use the encoding information when opening the file in Excel or another application.</li></ol>',
    'content'           => '<p>Character encoding determines how text characters are stored as bytes. CSV files can use many encodings: UTF-8 is the modern standard, but older files often use ISO-8859-1, Windows-1252, or even UTF-16. Using the wrong encoding when opening a file causes "mojibake" — garbled characters. This tool detects the encoding so you can open the file correctly.</p>',
];
$faqs         = getToolFaqs($tool['id'] ?? 0);
$relatedTools = getRelatedTools('csv-encoding-detector');

$result = null;
$errors = [];

if (isPost()) {
    verifyCsrf();
    $file = $_FILES['csv_file'] ?? null;

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
                $encoding  = CsvParser::detectEncoding($rawContent);
                $hasBom    = str_starts_with($rawContent, "\xEF\xBB\xBF")
                          || str_starts_with($rawContent, "\xFF\xFE")
                          || str_starts_with($rawContent, "\xFE\xFF");

                // Assess confidence
                $isUtf8Valid = mb_check_encoding($rawContent, 'UTF-8');
                $confidence  = match(true) {
                    str_starts_with($rawContent, "\xEF\xBB\xBF") => 100,
                    str_starts_with($rawContent, "\xFF\xFE")      => 100,
                    str_starts_with($rawContent, "\xFE\xFF")      => 100,
                    $isUtf8Valid                                  => 95,
                    default                                       => 75,
                };

                // Sample non-ASCII chars
                $sample = [];
                $stripped = CsvParser::stripBom($rawContent);
                for ($i = 0; $i < strlen($stripped) && count($sample) < 20; $i++) {
                    if (ord($stripped[$i]) > 127) {
                        $sample[] = sprintf('0x%02X', ord($stripped[$i]));
                    }
                }

                // Line endings
                $lineEnding = 'LF (Unix)';
                if (str_contains($rawContent, "\r\n")) $lineEnding = 'CRLF (Windows)';
                elseif (str_contains($rawContent, "\r"))   $lineEnding = 'CR (Old Mac)';

                // Delimiter detection
                $delimiter = CsvParser::detectDelimiter($rawContent);
                $delimNames = [','=>'Comma','；'=>'Semicolon',';'=>'Semicolon',"\t"=>'Tab','|'=>'Pipe'];

                $result = [
                    'encoding'      => $encoding,
                    'has_bom'       => $hasBom,
                    'confidence'    => $confidence,
                    'is_utf8_valid' => $isUtf8Valid,
                    'line_ending'   => $lineEnding,
                    'delimiter'     => $delimiter,
                    'delimiter_name'=> $delimNames[$delimiter] ?? 'Unknown',
                    'file_size'     => $file['size'],
                    'file_name'     => $file['name'],
                    'high_bytes'    => $sample,
                    'line_count'    => substr_count($rawContent, "\n") + 1,
                ];
            }
        }
    }
}

// Encoding info table
$encodingInfo = [
    'UTF-8'        => ['desc'=>'Universal encoding supporting all languages. Recommended for modern CSV files.', 'icon'=>'bi-check-circle-fill', 'color'=>'success'],
    'UTF-8-BOM'    => ['desc'=>'UTF-8 with a Byte Order Mark prefix. Compatible but BOM can cause issues in some tools.', 'icon'=>'bi-info-circle-fill', 'color'=>'warning'],
    'UTF-16LE'     => ['desc'=>'UTF-16 Little Endian. Often produced by Windows applications. Two bytes per character.', 'icon'=>'bi-info-circle-fill', 'color'=>'info'],
    'UTF-16BE'     => ['desc'=>'UTF-16 Big Endian. Less common. May cause issues in tools that expect UTF-8.', 'icon'=>'bi-info-circle-fill', 'color'=>'info'],
    'ISO-8859-1'   => ['desc'=>'Latin-1 encoding. Common in older European data files. Limited character support.', 'icon'=>'bi-exclamation-triangle-fill', 'color'=>'warning'],
    'Windows-1252' => ['desc'=>'Windows Western European encoding. Similar to ISO-8859-1 with extra characters.', 'icon'=>'bi-exclamation-triangle-fill', 'color'=>'warning'],
    'ASCII'        => ['desc'=>'Basic ASCII — no non-English characters. Most compatible encoding.', 'icon'=>'bi-check-circle-fill', 'color'=>'success'],
];

ob_start();
?>
<form method="post" enctype="multipart/form-data" class="tool-form" novalidate>
    <?= csrfField() ?>

    <div class="upload-area mb-3" role="button" tabindex="0">
        <input type="file" name="csv_file" id="csv_file" accept=".csv,.txt,.tsv">
        <div class="upload-icon"><i class="bi bi-search" aria-hidden="true"></i></div>
        <h3>Drag &amp; drop your CSV here</h3>
        <p>or click to browse — max <?= formatBytes(MAX_FILE_SIZE) ?></p>
        <p class="upload-filename text-primary fw-semibold d-none mt-2"></p>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <button type="submit" class="btn btn-primary px-4">
        <i class="bi bi-search me-2" aria-hidden="true"></i>Detect Encoding
    </button>
</form>

<?php if ($result): ?>
<hr class="my-4">

<!-- Primary result -->
<div class="card border-0 bg-light p-4 mb-4">
    <div class="row g-3 align-items-center">
        <div class="col-md-8">
            <div class="d-flex align-items-center gap-3 mb-3">
                <?php $info = $encodingInfo[$result['encoding']] ?? ['icon'=>'bi-question-circle','color'=>'secondary']; ?>
                <i class="<?= $info['icon'] ?> text-<?= $info['color'] ?> fs-2" aria-hidden="true"></i>
                <div>
                    <div class="text-muted small">Detected Encoding</div>
                    <div class="fs-3 fw-bold font-mono"><?= e($result['encoding']) ?></div>
                </div>
            </div>
            <?php if (isset($encodingInfo[$result['encoding']])): ?>
            <p class="text-muted mb-0 small"><?= e($encodingInfo[$result['encoding']]['desc']) ?></p>
            <?php endif; ?>
        </div>
        <div class="col-md-4 text-md-end">
            <div class="text-muted small mb-1">Confidence</div>
            <div class="progress mb-1" style="height:10px" role="progressbar"
                 aria-valuenow="<?= $result['confidence'] ?>" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar bg-success" style="width:<?= $result['confidence'] ?>%"></div>
            </div>
            <div class="small fw-semibold"><?= $result['confidence'] ?>%</div>
        </div>
    </div>
</div>

<!-- Details grid -->
<div class="validation-summary mb-4">
    <div class="val-stat">
        <div class="val-num text-primary"><?= $result['line_count'] ?></div>
        <div class="val-lbl">Lines</div>
    </div>
    <div class="val-stat">
        <div class="val-num <?= $result['has_bom']?'text-warning':'text-success' ?>">
            <?= $result['has_bom'] ? 'Yes' : 'No' ?>
        </div>
        <div class="val-lbl">Has BOM</div>
    </div>
    <div class="val-stat">
        <div class="val-num text-info small fw-bold"><?= e($result['line_ending']) ?></div>
        <div class="val-lbl">Line Endings</div>
    </div>
    <div class="val-stat">
        <div class="val-num text-primary"><?= e($result['delimiter_name']) ?></div>
        <div class="val-lbl">Delimiter</div>
    </div>
    <div class="val-stat">
        <div class="val-num text-secondary"><?= formatBytes($result['file_size']) ?></div>
        <div class="val-lbl">File Size</div>
    </div>
</div>

<!-- Non-ASCII sample -->
<?php if (!empty($result['high_bytes'])): ?>
<div class="alert alert-info small">
    <strong>Non-ASCII bytes detected:</strong> <?= implode(', ', $result['high_bytes']) ?>
    <?php if (count($result['high_bytes']) >= 20): ?> (showing first 20)<?php endif; ?>
</div>
<?php endif; ?>

<!-- Opening recommendation -->
<div class="card border-0 shadow-sm p-3">
    <h2 class="h6 fw-bold mb-2"><i class="bi bi-lightbulb me-1 text-warning" aria-hidden="true"></i>How to Open This File</h2>
    <?php if (in_array($result['encoding'], ['UTF-8','ASCII'], true)): ?>
    <p class="small text-muted mb-0">This file uses <strong><?= e($result['encoding']) ?></strong>. You can open it directly in Excel, Google Sheets, or any modern application without any special steps.</p>
    <?php elseif ($result['encoding'] === 'UTF-8-BOM'): ?>
    <p class="small text-muted mb-0">This file uses UTF-8 with a BOM. It opens correctly in Excel but may show a stray character in some text editors. The BOM can be removed with the <a href="<?= url('csv-repair/') ?>">CSV Repair Tool</a>.</p>
    <?php else: ?>
    <p class="small text-muted mb-0">This file uses <strong><?= e($result['encoding']) ?></strong>. When opening in Excel, use <em>Data → From Text/CSV</em> and select the correct encoding. In Google Sheets, upload the file and it should auto-detect. Consider using the <a href="<?= url('csv-repair/') ?>">CSV Repair Tool</a> to convert it to UTF-8.</p>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php
$toolContent = ob_get_clean();
require_once ROOT_PATH . '/includes/tool-layout.php';
