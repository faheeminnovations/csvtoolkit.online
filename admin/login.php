<?php
// Redirect if already logged in
if (isAdminLoggedIn()) redirect(appBase() . '/admin/dashboard/');

$error = '';

if (isPost()) {
    verifyCsrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } elseif (!adminLogin($username, $password)) {
        // Delay to slow brute-force
        sleep(1);
        $error = 'Invalid username or password.';
    } else {
        redirect(appBase() . '/admin/dashboard/');
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login — <?= e(APP_NAME) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap-icons.min.css') ?>">
    <style>
        body { background: #f0f2f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .login-card { width: 100%; max-width: 400px; }
    </style>
</head>
<body>
<div class="login-card p-3">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <i class="bi bi-file-earmark-spreadsheet display-5 text-primary" aria-hidden="true"></i>
                <h1 class="h5 fw-bold mt-2 mb-0"><?= e(APP_NAME) ?></h1>
                <p class="text-muted small">Admin Panel</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger alert-sm py-2 small"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= url('admin/login/') ?>">
                <?= csrfField() ?>
                <div class="mb-3">
                    <label for="username" class="form-label fw-medium">Username</label>
                    <input type="text" id="username" name="username" class="form-control"
                           required autocomplete="username" value="<?= e($_POST['username'] ?? '') ?>">
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label fw-medium">Password</label>
                    <input type="password" id="password" name="password" class="form-control"
                           required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-primary w-100 fw-semibold">
                    <i class="bi bi-box-arrow-in-right me-2" aria-hidden="true"></i>Sign In
                </button>
            </form>

            <p class="text-center mt-3 mb-0">
                <a href="<?= url() ?>" class="text-muted small">← Back to site</a>
            </p>
        </div>
    </div>
</div>
<script src="<?= url('assets/vendor/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
