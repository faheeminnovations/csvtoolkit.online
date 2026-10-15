<?php
/**
 * Admin router — handles all /admin/* requests.
 * Can be called directly by Apache or via the front controller.
 */

// Bootstrap if called directly (ROOT_PATH not yet defined)
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
    define('DS', DIRECTORY_SEPARATOR);
    require_once ROOT_PATH . '/config/config.php';
    require_once ROOT_PATH . '/config/database.php';
    require_once ROOT_PATH . '/includes/functions.php';
    require_once ROOT_PATH . '/includes/csrf.php';
    require_once ROOT_PATH . '/includes/seo.php';

    // Start session (front controller hasn't done it)
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_name(SESSION_NAME);
    session_set_cookie_params(['lifetime' => SESSION_LIFETIME, 'path' => '/']);
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['_last_regen']) || (time() - $_SESSION['_last_regen']) > 300) {
        session_regenerate_id(true);
        $_SESSION['_last_regen'] = time();
    }
}

require_once ROOT_PATH . '/admin/auth.php';

// Parse sub-route
$uri     = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
$base    = trim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
if ($base && str_starts_with($uri, $base)) $uri = ltrim(substr($uri, strlen($base)), '/');

$subRoute = ltrim(substr($uri, strlen('admin')), '/');
$subRoute = rtrim($subRoute, '/');
if ($subRoute === '') $subRoute = 'dashboard';

// Public admin routes (no auth required)
$publicRoutes = ['login', 'logout'];

if (!in_array($subRoute, $publicRoutes, true)) {
    adminRequireLogin();
}

$adminViewMap = [
    'login'      => __DIR__ . '/login.php',
    'logout'     => __DIR__ . '/logout.php',
    'dashboard'  => __DIR__ . '/dashboard.php',
    'tools'      => __DIR__ . '/tools.php',
    'tools/edit' => __DIR__ . '/tools-edit.php',
    'categories' => __DIR__ . '/categories.php',
    'faqs'       => __DIR__ . '/faqs.php',
    'settings'   => __DIR__ . '/settings.php',
    'messages'   => __DIR__ . '/messages.php',
];

$viewFile = $adminViewMap[$subRoute] ?? null;

if (!$viewFile || !is_file($viewFile)) {
    http_response_code(404);
    adminRequireLogin(); // ensure logged in before showing 404
    require_once __DIR__ . '/dashboard.php';
    exit;
}

require_once $viewFile;
