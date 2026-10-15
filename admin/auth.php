<?php
/**
 * Admin authentication helpers.
 */

/**
 * Root-relative base path (e.g. "/csvtoolkit.online" on localhost subdir,
 * or "" on a real domain root). Used for redirects that survive subdirs.
 */
function appBase(): string {
    static $base = null;
    if ($base === null) {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $base = rtrim(dirname($scriptName), '/');
        // If running from the actual project index.php, dirname gives the subdir.
        // If running from admin/index.php directly, go one more level up.
        if (str_ends_with($base, '/admin')) {
            $base = dirname($base);
        }
    }
    return $base;
}

function adminRequireLogin(): void {
    if (empty($_SESSION['admin_id'])) {
        header('Location: ' . appBase() . '/admin/login/', true, 302);
        exit;
    }
}

function adminLogin(string $username, string $password): bool {
    $row = dbFetch(
        'SELECT id, password_hash, is_active FROM admin_users WHERE username = ? LIMIT 1',
        [trim($username)]
    );

    if (!$row || !$row['is_active']) return false;
    if (!password_verify($password, $row['password_hash'])) return false;

    session_regenerate_id(true);
    $_SESSION['admin_id']       = $row['id'];
    $_SESSION['admin_username'] = $username;
    $_SESSION['admin_login_at'] = time();

    dbExecute('UPDATE admin_users SET last_login = NOW() WHERE id = ?', [$row['id']]);
    return true;
}

function adminLogout(): void {
    $_SESSION = [];
    session_destroy();
    header('Location: ' . appBase() . '/admin/login/', true, 302);
    exit;
}

function isAdminLoggedIn(): bool {
    return !empty($_SESSION['admin_id']);
}

function currentAdmin(): string {
    return $_SESSION['admin_username'] ?? 'Admin';
}
