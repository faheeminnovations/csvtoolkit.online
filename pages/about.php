<?php
setSeoMeta([
    'title'       => 'About CSV Repair Toolkit — Free Online CSV Tools',
    'description' => 'Learn about CSV Repair Toolkit — who we are, why we built these free CSV tools, and our commitment to user privacy and data security.',
    'canonical'   => APP_URL . '/about/',
]);
$bodyClass = 'page-about';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="container py-5">
    <?php renderBreadcrumbs([['label'=>'Home','url'=>url()],['label'=>'About']]); ?>

    <!-- Hero -->
    <div class="row align-items-center g-5 mb-5">
        <div class="col-lg-7">
            <h1 class="fw-bold mb-3">About CSV Repair Toolkit</h1>
            <p class="lead text-muted mb-4">
                We built CSV Repair Toolkit because dealing with broken, malformed, or
                mis-encoded CSV files is one of the most frustrating and time-consuming
                tasks in everyday data work — and every existing solution was either
                too expensive, too complicated, or required uploading sensitive data
                to an untrusted third party.
            </p>
            <p class="text-muted">
                Our goal is simple: give data professionals, developers, analysts,
                and everyday users a fast, free, and private set of tools that
                actually work on real-world CSV problems.
            </p>
        </div>
        <div class="col-lg-5 text-center">
            <div class="bg-primary bg-opacity-10 rounded-4 p-4">
                <i class="bi bi-file-earmark-spreadsheet display-1 text-primary" aria-hidden="true"></i>
                <h2 class="h4 mt-3 fw-bold">10 Free Tools</h2>
                <p class="text-muted mb-0">No account. No watermarks. No limits.</p>
            </div>
        </div>
    </div>

    <!-- Mission -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 p-4 text-center">
                <i class="bi bi-lightning-charge-fill display-5 text-warning mb-3" aria-hidden="true"></i>
                <h2 class="h5 fw-bold">Speed</h2>
                <p class="text-muted mb-0">Every tool is optimised for fast processing. Most operations complete in under a second.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 p-4 text-center">
                <i class="bi bi-shield-check-fill display-5 text-success mb-3" aria-hidden="true"></i>
                <h2 class="h5 fw-bold">Privacy</h2>
                <p class="text-muted mb-0">Your files are never stored permanently. Uploaded files are deleted immediately after processing.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 p-4 text-center">
                <i class="bi bi-gift-fill display-5 text-primary mb-3" aria-hidden="true"></i>
                <h2 class="h5 fw-bold">Free Forever</h2>
                <p class="text-muted mb-0">All tools are free to use. No subscription, no trial period, no hidden charges.</p>
            </div>
        </div>
    </div>

    <!-- Tools summary -->
    <div class="card border-0 shadow-sm mb-5">
        <div class="card-body p-4">
            <h2 class="h4 fw-bold mb-4">What We Offer</h2>
            <div class="row g-3">
                <?php
                $toolList = [
                    ['CSV Repair Tool',        'Automatically fix broken quotes, mismatched columns, BOM, and encoding issues.',       'bi-wrench'],
                    ['Encoding Detector',      'Identify the character encoding of any CSV file — UTF-8, ISO-8859-1, and more.',       'bi-search'],
                    ['Delimiter Converter',    'Convert between comma, semicolon, tab, pipe, or custom delimiters.',                    'bi-arrow-left-right'],
                    ['CSV Validator',          'Detailed structural and encoding validation with row-level error reporting.',           'bi-check-circle'],
                    ['Remove Duplicates',      'Deduplicate rows by all columns or a custom key column set.',                          'bi-funnel'],
                    ['CSV to JSON Converter',  'Convert CSV to JSON as array-of-objects or array-of-arrays.',                          'bi-braces'],
                    ['CSV Merger',             'Combine up to 10 CSV files with column alignment by name or position.',                'bi-stack'],
                    ['CSV Splitter',           'Split large files into smaller chunks by row count, preserving headers.',              'bi-scissors'],
                    ['File Comparison',        'Visual diff between two CSV files — added, removed, and modified rows.',               'bi-files'],
                    ['CSV Viewer',             'Browse and search any CSV file in your browser — no upload required.',                 'bi-eye'],
                ];
                foreach ($toolList as $t):
                ?>
                <div class="col-sm-6">
                    <div class="d-flex gap-3">
                        <i class="<?= $t[2] ?> text-primary mt-1 flex-shrink-0 fs-5" aria-hidden="true"></i>
                        <div>
                            <strong><?= e($t[0]) ?></strong>
                            <p class="small text-muted mb-0"><?= e($t[1]) ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Contact CTA -->
    <div class="text-center bg-light rounded-4 p-5">
        <h2 class="h4 fw-bold mb-2">Questions or Suggestions?</h2>
        <p class="text-muted mb-4">We'd love to hear from you — whether it's a bug report, a feature request, or just a hello.</p>
        <a href="<?= url('contact/') ?>" class="btn btn-primary btn-lg px-5">
            <i class="bi bi-envelope me-2" aria-hidden="true"></i>Get in Touch
        </a>
    </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
