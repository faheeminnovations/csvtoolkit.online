<?php
/**
 * Custom 404 error page.
 * Called from index.php when no route matches.
 */

// Bootstrap only what's needed (config already loaded by index.php)
setSeoMeta([
    'title'       => '404 — Page Not Found | ' . APP_NAME,
    'description' => 'The page you are looking for does not exist.',
    'canonical'   => APP_URL . '/404/',
    'robots'      => 'noindex, nofollow',
]);

$bodyClass = 'page-error';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="container py-5 text-center">
    <div class="row justify-content-center">
        <div class="col-md-6">

            <!-- Icon -->
            <div class="mb-4">
                <svg width="120" height="120" viewBox="0 0 120 120" fill="none"
                     xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <circle cx="60" cy="60" r="58" fill="#e9f0ff" stroke="#0d6efd" stroke-width="2"/>
                    <text x="50%" y="54%" dominant-baseline="middle" text-anchor="middle"
                          font-size="42" font-weight="800" fill="#0d6efd">404</text>
                    <path d="M30 80 Q60 95 90 80" stroke="#0d6efd" stroke-width="2.5"
                          stroke-linecap="round" fill="none"/>
                    <circle cx="45" cy="65" r="4" fill="#0d6efd"/>
                    <circle cx="75" cy="65" r="4" fill="#0d6efd"/>
                </svg>
            </div>

            <h1 class="fw-bold mb-2">Page Not Found</h1>
            <p class="text-muted mb-4">
                The page you are looking for doesn't exist, has been moved,
                or the URL may be incorrect.
            </p>

            <!-- Quick links -->
            <div class="d-flex flex-wrap justify-content-center gap-2 mb-5">
                <a href="<?= url() ?>" class="btn btn-primary">
                    <i class="bi bi-house me-2" aria-hidden="true"></i>Go Home
                </a>
                <a href="<?= url('csv-repair/') ?>" class="btn btn-outline-primary">
                    <i class="bi bi-wrench me-2" aria-hidden="true"></i>CSV Repair
                </a>
                <a href="<?= url('csv-validator/') ?>" class="btn btn-outline-primary">
                    <i class="bi bi-check-circle me-2" aria-hidden="true"></i>CSV Validator
                </a>
                <a href="<?= url('contact/') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-envelope me-2" aria-hidden="true"></i>Contact Us
                </a>
            </div>

            <!-- All tools -->
            <div class="card border-0 shadow-sm p-4 text-start">
                <h2 class="h6 fw-bold mb-3 text-center">All Available Tools</h2>
                <div class="row g-2">
                    <?php
                    $quickTools = [
                        ['csv-repair',             'CSV Repair',           'bi-wrench'],
                        ['csv-encoding-detector',  'Encoding Detector',    'bi-search'],
                        ['csv-delimiter-converter','Delimiter Converter',  'bi-arrow-left-right'],
                        ['csv-validator',          'CSV Validator',        'bi-check-circle'],
                        ['remove-duplicates',      'Remove Duplicates',    'bi-funnel'],
                        ['csv-to-json',            'CSV to JSON',          'bi-braces'],
                        ['csv-merger',             'CSV Merger',           'bi-stack'],
                        ['csv-splitter',           'CSV Splitter',         'bi-scissors'],
                        ['csv-file-comparison',    'File Comparison',      'bi-files'],
                        ['csv-viewer',             'CSV Viewer',           'bi-eye'],
                    ];
                    foreach ($quickTools as [$slug, $name, $icon]):
                    ?>
                    <div class="col-6">
                        <a href="<?= url($slug . '/') ?>"
                           class="d-flex align-items-center gap-2 text-decoration-none text-dark small py-1">
                            <i class="<?= $icon ?> text-primary" aria-hidden="true"></i>
                            <?= e($name) ?>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
