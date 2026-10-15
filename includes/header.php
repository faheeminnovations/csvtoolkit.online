<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php renderSeoHead(); ?>

    <!-- Bootstrap 5.3 CSS (local) -->
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap.min.css') ?>">
    <!-- Bootstrap Icons (local) -->
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap-icons.min.css') ?>">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= asset('css/main.css') ?>?v=<?= APP_VERSION ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= asset('images/favicon.svg') ?>">
    <link rel="icon" type="image/png" href="<?= asset('images/favicon.png') ?>">

    <!-- AdSense placeholder (only rendered when enabled) -->
    <?php if (ADSENSE_ENABLED): ?>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?= e(ADSENSE_PUBLISHER_ID) ?>"
         crossorigin="anonymous"></script>
    <?php endif; ?>
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<?php require_once ROOT_PATH . '/includes/navbar.php'; ?>
<main id="main-content">
