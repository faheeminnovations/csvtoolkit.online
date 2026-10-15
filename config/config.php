<?php
/**
 * Central application configuration.
 * Copy config.php.example to config.php and fill in values.
 * Never commit this file with real credentials.
 */

// ── Environment ──────────────────────────────────────────────────────────────
define('APP_ENV', getenv('APP_ENV') ?: 'development'); // 'development' | 'production'
define('APP_DEBUG', APP_ENV === 'development');

// ── Site identity ─────────────────────────────────────────────────────────────
define('APP_NAME', 'CSV Repair Toolkit');
define('APP_TAGLINE', 'Free Online CSV Tools — Fix, Convert & Analyze CSV Files');
define('APP_URL', rtrim(getenv('APP_URL') ?: 'http://localhost/csvtoolkit.online', '/'));
define('APP_VERSION', '1.0.0');

// ── Database ─────────────────────────────────────────────────────────────────
define('DB_HOST',     getenv('DB_HOST')     ?: '127.0.0.1');
define('DB_PORT',     getenv('DB_PORT')     ?: '3306');
define('DB_NAME',     getenv('DB_NAME')     ?: 'csvtoolkit');
define('DB_USER',     getenv('DB_USER')     ?: 'root');
define('DB_PASS',     getenv('DB_PASS')     ?: '');
define('DB_CHARSET',  'utf8mb4');

// ── Upload limits ─────────────────────────────────────────────────────────────
define('MAX_FILE_SIZE',    500 * 1024 * 1024); // 500 MB
define('MAX_MERGE_FILES',  10);
define('ALLOWED_MIME_TYPES', ['text/csv', 'text/plain', 'application/csv',
    'application/vnd.ms-excel', 'application/octet-stream']);

// ── Storage ───────────────────────────────────────────────────────────────────
define('STORAGE_PATH', dirname(__DIR__) . '/storage/temp');
define('TEMP_FILE_TTL', 3600); // seconds — clean up files older than this

// ── Sessions ──────────────────────────────────────────────────────────────────
define('SESSION_NAME',     'csvtk_sess');
define('SESSION_LIFETIME', 1800); // 30 minutes

// ── AdSense ───────────────────────────────────────────────────────────────────
define('ADSENSE_PUBLISHER_ID', getenv('ADSENSE_PUBLISHER_ID') ?: '');
define('ADSENSE_ENABLED',      !empty(ADSENSE_PUBLISHER_ID));

// ── Security ──────────────────────────────────────────────────────────────────
define('CSRF_TOKEN_LENGTH', 32);

// ── Error display ─────────────────────────────────────────────────────────────
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', dirname(__DIR__) . '/storage/error.log');
}
