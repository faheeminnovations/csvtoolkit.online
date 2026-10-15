<?php
$pageTitle = 'Site Settings';

if (isPost()) {
    verifyCsrf();

    $settings = [
        'site_name'     => trim($_POST['site_name']     ?? ''),
        'site_tagline'  => trim($_POST['site_tagline']  ?? ''),
        'contact_email' => trim($_POST['contact_email'] ?? ''),
        'adsense_id'    => trim($_POST['adsense_id']    ?? ''),
        'ads_enabled'   => isset($_POST['ads_enabled']) ? '1' : '0',
        'analytics_id'  => trim($_POST['analytics_id'] ?? ''),
        'footer_text'   => trim($_POST['footer_text']   ?? ''),
    ];

    foreach ($settings as $key => $val) {
        dbExecute(
            'INSERT INTO site_settings (setting_key, setting_val)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_val = ?',
            [$key, $val, $val]
        );
    }

    // Change admin password
    $newPass  = $_POST['new_password']     ?? '';
    $confPass = $_POST['confirm_password'] ?? '';
    if ($newPass !== '') {
        if ($newPass !== $confPass) {
            setFlash('error', 'Passwords do not match.');
        } elseif (strlen($newPass) < 8) {
            setFlash('error', 'Password must be at least 8 characters.');
        } else {
            $hash = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
            dbExecute('UPDATE admin_users SET password_hash = ? WHERE id = ?',
                [$hash, $_SESSION['admin_id']]);
            setFlash('success', 'Settings and password updated.');
        }
    } else {
        setFlash('success', 'Settings saved.');
    }

    redirect(appBase() . '/admin/settings/');
}

// Load current settings
$s = [];
$rows = dbFetchAll('SELECT setting_key, setting_val FROM site_settings');
foreach ($rows as $row) {
    $s[$row['setting_key']] = $row['setting_val'];
}

ob_start();
?>
<form method="post" action="<?= url('admin/settings/') ?>">
    <?= csrfField() ?>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">General</h2>
                    <div class="mb-3">
                        <label for="site_name" class="form-label fw-medium">Site Name</label>
                        <input type="text" id="site_name" name="site_name" class="form-control"
                               value="<?= e($s['site_name'] ?? APP_NAME) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="site_tagline" class="form-label fw-medium">Tagline</label>
                        <input type="text" id="site_tagline" name="site_tagline" class="form-control"
                               value="<?= e($s['site_tagline'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="contact_email" class="form-label fw-medium">Contact Email</label>
                        <input type="email" id="contact_email" name="contact_email" class="form-control"
                               value="<?= e($s['contact_email'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="footer_text" class="form-label fw-medium">Footer Text</label>
                        <input type="text" id="footer_text" name="footer_text" class="form-control"
                               value="<?= e($s['footer_text'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">Advertising</h2>
                    <div class="mb-3">
                        <label for="adsense_id" class="form-label fw-medium">
                            Google AdSense Publisher ID
                            <span class="text-muted small">(e.g. ca-pub-1234567890)</span>
                        </label>
                        <input type="text" id="adsense_id" name="adsense_id" class="form-control font-mono"
                               value="<?= e($s['adsense_id'] ?? '') ?>"
                               placeholder="ca-pub-XXXXXXXXXXXXXXXXX">
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="ads_enabled" id="ads_enabled"
                               value="1" <?= ($s['ads_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="ads_enabled">Enable AdSense ads</label>
                        <div class="form-text text-warning">Only enable after AdSense approval and with a valid Publisher ID.</div>
                    </div>
                    <div class="mb-3">
                        <label for="analytics_id" class="form-label fw-medium">
                            Google Analytics ID
                            <span class="text-muted small">(e.g. G-XXXXXXXXXX)</span>
                        </label>
                        <input type="text" id="analytics_id" name="analytics_id" class="form-control font-mono"
                               value="<?= e($s['analytics_id'] ?? '') ?>" placeholder="G-XXXXXXXXXX">
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">Change Admin Password</h2>
                    <div class="mb-3">
                        <label for="new_password" class="form-label fw-medium">New Password</label>
                        <input type="password" id="new_password" name="new_password"
                               class="form-control" minlength="8" autocomplete="new-password">
                        <div class="form-text">Leave blank to keep current password. Minimum 8 characters.</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label fw-medium">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password"
                               class="form-control" autocomplete="new-password">
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">Current Configuration</h2>
                    <dl class="row small text-muted mb-0">
                        <dt class="col-sm-5">APP_URL</dt>
                        <dd class="col-sm-7 font-mono text-truncate"><?= e(APP_URL) ?></dd>
                        <dt class="col-sm-5">Environment</dt>
                        <dd class="col-sm-7"><?= e(APP_ENV) ?></dd>
                        <dt class="col-sm-5">PHP Version</dt>
                        <dd class="col-sm-7"><?= phpversion() ?></dd>
                        <dt class="col-sm-5">Max Upload</dt>
                        <dd class="col-sm-7"><?= formatBytes(MAX_FILE_SIZE) ?></dd>
                        <dt class="col-sm-5">Temp TTL</dt>
                        <dd class="col-sm-7"><?= TEMP_FILE_TTL / 60 ?> minutes</dd>
                    </dl>
                </div>
            </div>

            <div class="alert alert-warning small">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <strong>AdSense note:</strong> Only enable ads after receiving AdSense approval.
                Ads must be clearly separated from tool controls. Never click your own ads.
            </div>

            <button type="submit" class="btn btn-primary w-100 btn-lg mt-2">
                <i class="bi bi-check-lg me-1"></i>Save Settings
            </button>
        </div>
    </div>
</form>

<?php
$adminContent = ob_get_clean();
require_once __DIR__ . '/layout.php';
