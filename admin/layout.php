<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Admin') ?> — <?= e(APP_NAME) ?> Admin</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/main.css') ?>?v=<?= APP_VERSION ?>">
</head>
<body class="bg-light">
<div class="d-flex min-vh-100">

    <!-- Sidebar -->
    <nav class="admin-sidebar d-flex flex-column py-3" aria-label="Admin navigation">
        <a href="<?= url('admin/dashboard/') ?>" class="text-white text-decoration-none px-3 mb-4">
            <i class="bi bi-file-earmark-spreadsheet me-2" aria-hidden="true"></i>
            <span class="fw-bold">CSV Toolkit</span>
            <div class="small text-secondary">Admin Panel</div>
        </a>

        <ul class="nav flex-column px-2">
            <?php
            $nav = [
                ['url'=>'admin/dashboard/', 'icon'=>'bi-speedometer2',     'label'=>'Dashboard'],
                ['url'=>'admin/tools/',     'icon'=>'bi-tools',            'label'=>'Tools'],
                ['url'=>'admin/categories/','icon'=>'bi-grid',             'label'=>'Categories'],
                ['url'=>'admin/faqs/',      'icon'=>'bi-question-circle',  'label'=>'FAQs'],
                ['url'=>'admin/messages/',  'icon'=>'bi-envelope',         'label'=>'Messages'],
                ['url'=>'admin/settings/',  'icon'=>'bi-gear',             'label'=>'Settings'],
            ];
            $currentPath = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
            foreach ($nav as $item):
                $isActive = str_contains($currentPath, rtrim($item['url'], '/'));
            ?>
            <li class="nav-item">
                <a href="<?= url($item['url']) ?>"
                   class="nav-link<?= $isActive ? ' active' : '' ?>">
                    <i class="<?= $item['icon'] ?> me-2" aria-hidden="true"></i>
                    <?= e($item['label']) ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>

        <div class="mt-auto px-2">
            <hr class="border-secondary">
            <a href="<?= url() ?>" class="nav-link text-secondary" target="_blank">
                <i class="bi bi-box-arrow-up-right me-2" aria-hidden="true"></i>View Site
            </a>
            <a href="<?= url('admin/logout/') ?>" class="nav-link text-danger"
               onclick="return confirm('Sign out?')">
                <i class="bi bi-box-arrow-left me-2" aria-hidden="true"></i>Sign Out
            </a>
        </div>
    </nav>

    <!-- Main -->
    <div class="admin-main">
        <!-- Top bar -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h4 fw-bold mb-0"><?= e($pageTitle ?? '') ?></h1>
            <div class="text-muted small">
                <i class="bi bi-person-circle me-1" aria-hidden="true"></i>
                <?= e(currentAdmin()) ?>
            </div>
        </div>

        <!-- Flash -->
        <?php renderFlash(); ?>

        <!-- Page content -->
        <?= $adminContent ?? '' ?>
    </div>
</div>

<script src="<?= url('assets/vendor/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
