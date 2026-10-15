<?php
/**
 * SEO metadata management.
 * Call setSeoMeta() before rendering the header.
 */

$_seo = [
    'title'       => APP_NAME . ' — ' . APP_TAGLINE,
    'description' => 'Free online CSV tools: repair, validate, convert, merge, split, deduplicate and view CSV files instantly.',
    'canonical'   => APP_URL . '/',
    'og_type'     => 'website',
    'og_image'    => APP_URL . '/assets/images/og-image.png',
    'robots'      => 'index, follow',
    'schema'      => [],
    'breadcrumbs' => [],
];

function setSeoMeta(array $meta): void {
    global $_seo;
    foreach ($meta as $k => $v) {
        $_seo[$k] = $v;
    }
    // Always build canonical from APP_URL, never from request headers
    if (!isset($meta['canonical'])) {
        // auto-derive canonical from current route if not set
    }
}

function getSeoMeta(string $key, string $default = ''): string {
    global $_seo;
    return (string)($_seo[$key] ?? $default);
}

function renderSeoHead(): void {
    global $_seo;

    $title       = e($_seo['title']);
    $description = e($_seo['description']);
    $canonical   = e($_seo['canonical']);
    $robots      = e($_seo['robots']);
    $ogType      = e($_seo['og_type']);
    $ogImage     = e($_seo['og_image']);
    $siteName    = e(APP_NAME);

    echo "    <title>{$title}</title>\n";
    echo "    <meta name=\"description\" content=\"{$description}\">\n";
    echo "    <link rel=\"canonical\" href=\"{$canonical}\">\n";
    echo "    <meta name=\"robots\" content=\"{$robots}\">\n";

    // Open Graph
    echo "    <meta property=\"og:title\" content=\"{$title}\">\n";
    echo "    <meta property=\"og:description\" content=\"{$description}\">\n";
    echo "    <meta property=\"og:type\" content=\"{$ogType}\">\n";
    echo "    <meta property=\"og:url\" content=\"{$canonical}\">\n";
    echo "    <meta property=\"og:image\" content=\"{$ogImage}\">\n";
    echo "    <meta property=\"og:site_name\" content=\"{$siteName}\">\n";

    // Twitter Card
    echo "    <meta name=\"twitter:card\" content=\"summary_large_image\">\n";
    echo "    <meta name=\"twitter:title\" content=\"{$title}\">\n";
    echo "    <meta name=\"twitter:description\" content=\"{$description}\">\n";
    echo "    <meta name=\"twitter:image\" content=\"{$ogImage}\">\n";

    // Structured data
    if (!empty($_seo['schema'])) {
        echo '    <script type="application/ld+json">'
            . json_encode($_seo['schema'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            . "</script>\n";
    }

    // Breadcrumb schema
    if (!empty($_seo['breadcrumbs'])) {
        $items = [];
        foreach ($_seo['breadcrumbs'] as $i => $crumb) {
            $item = [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $crumb['label'],
            ];
            if (isset($crumb['url'])) {
                $item['item'] = $crumb['url'];
            }
            $items[] = $item;
        }
        $schema = [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
        echo '    <script type="application/ld+json">'
            . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            . "</script>\n";
    }
}

/**
 * Build WebApplication schema for a tool page.
 */
function buildToolSchema(array $tool): array {
    return [
        '@context'         => 'https://schema.org',
        '@type'            => 'WebApplication',
        'name'             => $tool['name'],
        'url'              => APP_URL . '/' . $tool['slug'] . '/',
        'description'      => $tool['meta_description'] ?? $tool['short_description'],
        'applicationCategory' => 'UtilitiesApplication',
        'operatingSystem'  => 'Any',
        'offers'           => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
        'provider'         => ['@type' => 'Organization', 'name' => APP_NAME, 'url' => APP_URL],
    ];
}

/**
 * Build FAQPage schema.
 */
function buildFaqSchema(array $faqs): array {
    if (empty($faqs)) return [];
    $items = array_map(fn($f) => [
        '@type'          => 'Question',
        'name'           => $f['question'],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['answer']],
    ], $faqs);
    return [
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => $items,
    ];
}
