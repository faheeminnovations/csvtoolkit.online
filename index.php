<?php
/**
 * Front Controller — CSV Repair Toolkit
 * All HTTP requests are routed through this file.
 */

declare(strict_types=1);

define('ROOT_PATH', __DIR__);
define('DS', DIRECTORY_SEPARATOR);

// ── Bootstrap ─────────────────────────────────────────────────────────────────
require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/includes/seo.php';

// ── Session ───────────────────────────────────────────────────────────────────
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', '1');
}
session_name(SESSION_NAME);
session_set_cookie_params(['lifetime' => SESSION_LIFETIME, 'path' => '/']);
session_start();

// Regenerate session ID periodically
if (!isset($_SESSION['_last_regen']) || (time() - $_SESSION['_last_regen']) > 300) {
    session_regenerate_id(true);
    $_SESSION['_last_regen'] = time();
}

// ── Temp file cleanup (probabilistic — 5% of requests) ────────────────────────
if (mt_rand(1, 20) === 1) {
    cleanTempFiles();
}

// ── Route resolution ──────────────────────────────────────────────────────────
$routes = require ROOT_PATH . '/config/app.php';

// Parse the URI, strip base path and query string
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = trim($uri, '/');

// Remove sub-directory prefix if running in a subdirectory
$scriptDir = trim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
if ($scriptDir !== '' && str_starts_with($uri, $scriptDir)) {
    $uri = ltrim(substr($uri, strlen($scriptDir)), '/');
}

// Normalize trailing slash
// BUT skip: download, api, and any URI that has a query string
$hasQueryString   = str_contains($_SERVER['REQUEST_URI'] ?? '', '?');
$isSpecialEndpoint= ($uri === 'download' || str_starts_with($uri, 'download/') ||
                     str_starts_with($uri, 'api/'));

if ($uri !== '' && !$isSpecialEndpoint && !$hasQueryString &&
    !str_ends_with($_SERVER['REQUEST_URI'], '/') && !str_contains($uri, '.')) {
    $scriptBase  = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $redirectUrl = $scriptBase . '/' . $uri . '/';
    header('Location: ' . $redirectUrl, true, 301);
    exit;
}

// ── Admin routing (separate auth boundary) ────────────────────────────────────
if ($uri === 'admin' || str_starts_with($uri, 'admin/')) {
    require_once ROOT_PATH . '/admin/index.php';
    exit;
}

// ── Download endpoint — handles both /download and /download/ ─────────────────
if ($uri === 'download' || $uri === 'download/') {
    require_once ROOT_PATH . '/includes/download.php';
    exit;
}

// ── API / AJAX handlers ───────────────────────────────────────────────────────
if (str_starts_with($uri, 'api/')) {
    $apiRoute = substr($uri, 4);
    $apiFile  = ROOT_PATH . '/api/' . $apiRoute . '.php';
    if (is_file($apiFile)) {
        require_once $apiFile;
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
    }
    exit;
}

// ── Static routes ─────────────────────────────────────────────────────────────
if (!array_key_exists($uri, $routes)) {
    // 404
    http_response_code(404);
    require_once ROOT_PATH . '/errors/404.php';
    exit;
}

$route = $routes[$uri];
$viewFile = ROOT_PATH . '/' . $route['view'];

if (!is_file($viewFile)) {
    http_response_code(500);
    die(APP_DEBUG ? "View file not found: {$viewFile}" : 'A server error occurred.');
}

require_once $viewFile;
