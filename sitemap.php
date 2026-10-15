<?php
/**
 * Dynamic XML Sitemap
 * Only includes published, canonical, indexable pages.
 * Excludes: admin, temp files, private pages.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/seo.php';

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex'); // sitemap itself shouldn't be indexed

$base = APP_URL;
$now  = date('Y-m-d');

// Static pages
$static = [
    ['loc' => $base . '/',                  'priority' => '1.0',  'changefreq' => 'weekly'],
    ['loc' => $base . '/about/',            'priority' => '0.5',  'changefreq' => 'monthly'],
    ['loc' => $base . '/contact/',          'priority' => '0.4',  'changefreq' => 'monthly'],
    ['loc' => $base . '/blog/',             'priority' => '0.6',  'changefreq' => 'weekly'],
];

// Dynamic tool pages from DB
$tools = [];
try {
    $tools = dbFetchAll(
        'SELECT slug, updated_at FROM tools WHERE status = 1 ORDER BY sort_order ASC'
    );
} catch (Exception $e) {
    // DB not yet set up — fall back to static list
    $toolSlugs = [
        'csv-repair','csv-encoding-detector','csv-delimiter-converter','csv-validator',
        'remove-duplicates','csv-to-json','csv-merger','csv-splitter',
        'csv-file-comparison','csv-viewer',
    ];
    foreach ($toolSlugs as $slug) {
        $tools[] = ['slug' => $slug, 'updated_at' => $now];
    }
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
echo '        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n";
echo '        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9' . "\n";
echo '        http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n";

// Static pages
foreach ($static as $page) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($page['loc'], ENT_XML1) . "</loc>\n";
    echo "    <lastmod>{$now}</lastmod>\n";
    echo "    <changefreq>{$page['changefreq']}</changefreq>\n";
    echo "    <priority>{$page['priority']}</priority>\n";
    echo "  </url>\n";
}

// Tool pages
foreach ($tools as $tool) {
    $lastmod = isset($tool['updated_at'])
        ? date('Y-m-d', strtotime($tool['updated_at']))
        : $now;
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($base . '/' . $tool['slug'] . '/', ENT_XML1) . "</loc>\n";
    echo "    <lastmod>{$lastmod}</lastmod>\n";
    echo "    <changefreq>monthly</changefreq>\n";
    echo "    <priority>0.9</priority>\n";
    echo "  </url>\n";
}

echo '</urlset>';
