<?php
setSeoMeta([
    'title'       => 'CSV Tips & Guides — CSV Repair Toolkit Blog',
    'description' => 'Guides, tips, and tutorials on working with CSV files: encoding issues, delimiter problems, data cleaning, and more.',
    'canonical'   => APP_URL . '/blog/',
]);
$bodyClass = 'page-blog';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="container py-5">
    <?php renderBreadcrumbs([['label'=>'Home','url'=>url()],['label'=>'Blog']]); ?>

    <div class="text-center mb-5">
        <h1 class="fw-bold mb-2">CSV Tips &amp; Guides</h1>
        <p class="text-muted">Practical guides for working with CSV files.</p>
    </div>

    <div class="row g-4 justify-content-center">
        <?php
        $posts = [
            ['title'=>'How to Fix a Broken CSV File',
             'excerpt'=>'Common causes of CSV corruption — from unmatched quotes to encoding mismatches — and how to fix them.',
             'icon'=>'bi-wrench','color'=>'#0d6efd','slug'=>'csv-repair'],
            ['title'=>'UTF-8 vs ISO-8859-1: Which Encoding Does My CSV Use?',
             'excerpt'=>'A practical guide to understanding CSV character encodings and how to detect and convert them.',
             'icon'=>'bi-search','color'=>'#6610f2','slug'=>'csv-encoding-detector'],
            ['title'=>'Comma vs Semicolon: CSV Delimiter Best Practices',
             'excerpt'=>'Why different software uses different delimiters, and how to convert between them without data loss.',
             'icon'=>'bi-arrow-left-right','color'=>'#0dcaf0','slug'=>'csv-delimiter-converter'],
            ['title'=>'How to Merge Multiple CSV Files Into One',
             'excerpt'=>'Step-by-step guide to combining CSV files with matching or mismatched columns.',
             'icon'=>'bi-stack','color'=>'#d63384','slug'=>'csv-merger'],
        ];
        foreach ($posts as $post):
        ?>
        <div class="col-md-6 col-lg-5">
            <div class="card border-0 shadow-sm h-100 card-hover">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="tool-icon-wrap" style="background:<?= $post['color'] ?>1a;color:<?= $post['color'] ?>">
                            <i class="<?= $post['icon'] ?>" aria-hidden="true"></i>
                        </div>
                        <div>
                            <h2 class="h6 fw-bold mb-0"><?= e($post['title']) ?></h2>
                        </div>
                    </div>
                    <p class="text-muted small mb-3"><?= e($post['excerpt']) ?></p>
                    <a href="<?= url($post['slug'] . '/') ?>" class="btn btn-outline-primary btn-sm">
                        Try the <?= e($post['title']) ?> Tool
                        <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="text-center mt-5 p-5 bg-light rounded-4">
        <h2 class="h5 fw-bold mb-2">More guides coming soon</h2>
        <p class="text-muted mb-0">We're working on in-depth articles about CSV best practices, data cleaning, and format conversion.</p>
    </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
