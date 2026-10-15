<?php
/**
 * Global helper functions — CSV Repair Toolkit
 */

// ── Output escaping ───────────────────────────────────────────────────────────

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function eAttr(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ── URL helpers ───────────────────────────────────────────────────────────────

function url(string $path = ''): string {
    return APP_URL . '/' . ltrim($path, '/');
}

function asset(string $path): string {
    return APP_URL . '/assets/' . ltrim($path, '/');
}

function currentUrl(): string {
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $uri    = $_SERVER['REQUEST_URI'] ?? '/';
    return $scheme . '://' . $host . $uri;
}

// ── Breadcrumbs ───────────────────────────────────────────────────────────────

/**
 * @param array<array{label:string, url?:string}> $crumbs
 */
function renderBreadcrumbs(array $crumbs): void {
    echo '<nav aria-label="breadcrumb" class="tool-breadcrumb">';
    echo '<ol class="breadcrumb" itemscope itemtype="https://schema.org/BreadcrumbList">';
    $i = 1;
    foreach ($crumbs as $crumb) {
        $isLast = ($i === count($crumbs));
        echo '<li class="breadcrumb-item' . ($isLast ? ' active' : '') . '" '
            . 'itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
        if (!$isLast && isset($crumb['url'])) {
            echo '<a itemprop="item" href="' . e($crumb['url']) . '">'
                . '<span itemprop="name">' . e($crumb['label']) . '</span></a>';
        } else {
            echo '<span itemprop="name">' . e($crumb['label']) . '</span>';
        }
        echo '<meta itemprop="position" content="' . $i . '">';
        echo '</li>';
        $i++;
    }
    echo '</ol></nav>';
}

// ── Flash messages ────────────────────────────────────────────────────────────

function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function renderFlash(): void {
    $flash = getFlash();
    if (!$flash) return;
    $map = ['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'];
    $cls = $map[$flash['type']] ?? 'info';
    echo '<div class="alert alert-' . $cls . ' alert-dismissible fade show" role="alert">';
    echo e($flash['message']);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}

// ── File handling ─────────────────────────────────────────────────────────────

function validateUploadedFile(array $file, int $maxSize = MAX_FILE_SIZE): array {
    $errors = [];

    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit.',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds form size limit.',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the upload.',
        ];
        $errors[] = $uploadErrors[$file['error'] ?? -1] ?? 'Unknown upload error.';
        return $errors;
    }

    if ($file['size'] > $maxSize) {
        $errors[] = 'File size (' . formatBytes($file['size']) . ') exceeds the limit of ' . formatBytes($maxSize) . '.';
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['csv', 'txt', 'tsv'], true)) {
        $errors[] = 'Invalid file extension. Only .csv, .txt, and .tsv files are accepted.';
    }

    // MIME check via finfo
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $allowed = ['text/csv', 'text/plain', 'text/tab-separated-values',
                    'application/csv', 'application/octet-stream'];
        if (!in_array($mime, $allowed, true)) {
            $errors[] = 'Invalid file type detected (' . $mime . '). Only CSV/text files are accepted.';
        }
    }

    return $errors;
}

function saveTempFile(string $content, string $ext = 'csv'): string {
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $path     = STORAGE_PATH . '/' . $filename;
    if (file_put_contents($path, $content) === false) {
        throw new RuntimeException('Failed to write temporary file.');
    }
    return $filename;
}

function getTempFilePath(string $filename): string {
    // Prevent path traversal: allow only hex chars + dot + ext
    if (!preg_match('/^[0-9a-f]{32}\.[a-z0-9]{1,5}$/i', $filename)) {
        throw new InvalidArgumentException('Invalid temporary filename.');
    }
    return STORAGE_PATH . '/' . $filename;
}

function cleanTempFiles(): void {
    $files = glob(STORAGE_PATH . '/*');
    if (!$files) return;
    $cutoff = time() - TEMP_FILE_TTL;
    foreach ($files as $file) {
        if (is_file($file) && filemtime($file) < $cutoff) {
            @unlink($file);
        }
    }
}

function deleteTempFile(string $filename): void {
    try {
        $path = getTempFilePath($filename);
        if (is_file($path)) {
            @unlink($path);
        }
    } catch (InvalidArgumentException $e) {
        // Invalid name — do nothing
    }
}

// ── Formatting ────────────────────────────────────────────────────────────────

function formatBytes(int $bytes, int $precision = 2): string {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow   = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow   = min($pow, count($units) - 1);
    return round($bytes / (1024 ** $pow), $precision) . ' ' . $units[$pow];
}

function slugify(string $text): string {
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^\p{L}\p{N}\s-]/u', '', $text);
    $text = preg_replace('/[\s-]+/', '-', trim($text));
    return $text;
}

function truncate(string $text, int $length = 150, string $suffix = '…'): string {
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . $suffix;
}

// ── Database helpers ──────────────────────────────────────────────────────────

function dbFetch(string $sql, array $params = []): ?array {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ?: null;
}

function dbFetchAll(string $sql, array $params = []): array {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function dbExecute(string $sql, array $params = []): int {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

function getSetting(string $key, string $default = ''): string {
    static $cache = [];
    if (!isset($cache[$key])) {
        $row = dbFetch('SELECT setting_val FROM site_settings WHERE setting_key = ?', [$key]);
        $cache[$key] = $row ? (string)$row['setting_val'] : $default;
    }
    return $cache[$key];
}

function getTools(bool $popularOnly = false): array {
    $sql = 'SELECT t.*, c.name AS category_name, c.slug AS category_slug
            FROM tools t
            LEFT JOIN tool_categories c ON t.category_id = c.id
            WHERE t.status = 1'
        . ($popularOnly ? ' AND t.is_popular = 1' : '')
        . ' ORDER BY t.sort_order ASC';
    return dbFetchAll($sql);
}

function getToolBySlug(string $slug): ?array {
    return dbFetch(
        'SELECT t.*, c.name AS category_name, c.slug AS category_slug
         FROM tools t
         LEFT JOIN tool_categories c ON t.category_id = c.id
         WHERE t.slug = ? AND t.status = 1',
        [$slug]
    );
}

function getToolFaqs(int $toolId): array {
    return dbFetchAll(
        'SELECT * FROM tool_faqs WHERE tool_id = ? ORDER BY sort_order ASC',
        [$toolId]
    );
}

function getCategories(): array {
    return dbFetchAll(
        'SELECT c.*, COUNT(t.id) AS tool_count
         FROM tool_categories c
         LEFT JOIN tools t ON t.category_id = c.id AND t.status = 1
         WHERE c.status = 1
         GROUP BY c.id
         ORDER BY c.sort_order ASC'
    );
}

// ── Security ──────────────────────────────────────────────────────────────────

function redirect(string $url, int $code = 302): void {
    // Allow absolute URLs to same origin, root-relative, or APP_URL-prefixed
    if (!str_starts_with($url, '/') && !str_starts_with($url, APP_URL) && !str_starts_with($url, 'http')) {
        $url = '/' . $url;
    }
    header('Location: ' . $url, true, $code);
    exit;
}

function isPost(): bool {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function isAjax(): bool {
    return (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function sanitizeFilename(string $name): string {
    $name = preg_replace('/[^a-zA-Z0-9_\-.]/', '_', basename($name));
    return substr($name, 0, 100);
}

// ── Related tools helper ──────────────────────────────────────────────────────

function getRelatedTools(string $currentSlug, int $limit = 4): array {
    return dbFetchAll(
        'SELECT name, slug, short_description FROM tools
         WHERE status = 1 AND slug != ?
         ORDER BY is_popular DESC, sort_order ASC
         LIMIT ' . (int)$limit,
        [$currentSlug]
    );
}

// ── Pagination ────────────────────────────────────────────────────────────────

function paginate(int $total, int $perPage, int $currentPage, string $baseUrl): array {
    $totalPages = max(1, (int)ceil($total / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    return [
        'total'       => $total,
        'per_page'    => $perPage,
        'current'     => $currentPage,
        'total_pages' => $totalPages,
        'offset'      => $offset,
        'base_url'    => $baseUrl,
    ];
}
