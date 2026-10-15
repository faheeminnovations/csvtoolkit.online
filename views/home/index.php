<?php
/**
 * Homepage view
 */

setSeoMeta([
    'title'       => APP_NAME . ' — Free Online CSV Repair, Convert & Validate Tools',
    'description' => 'Free online CSV tools: repair broken files, detect encoding, convert delimiters, validate structure, remove duplicates, convert to JSON, merge, split, compare and view CSV files.',
    'canonical'   => APP_URL . '/',
    'schema'      => [
        '@context' => 'https://schema.org',
        '@type'    => 'WebSite',
        'name'     => APP_NAME,
        'url'      => APP_URL . '/',
        'potentialAction' => [
            '@type'       => 'SearchAction',
            'target'      => APP_URL . '/?q={search_term_string}',
            'query-input' => 'required name=search_term_string',
        ],
    ],
]);

// Handle homepage search redirect
if (!empty($_GET['q'])) {
    $q = trim($_GET['q']);
    // redirect to the same page with anchor, or just keep it
}

$allTools   = getTools();
$popTools   = getTools(true);
$categories = getCategories();

$bodyClass = 'page-home';
require_once ROOT_PATH . '/includes/header.php';
?>

<!-- ── Hero ─────────────────────────────────────────────────────────────── -->
<section class="hero-section" aria-labelledby="heroHeading">
    <div class="container text-center">
        <div class="hero-badge">
            <i class="bi bi-stars" aria-hidden="true"></i>
            10 Free CSV Tools — No Registration Required
        </div>
        <h1 class="hero-title mb-3" id="heroHeading">
            Fix, Convert &amp; Analyze<br>CSV Files Online
        </h1>
        <p class="hero-subtitle mx-auto mb-4">
            Repair broken CSV files, detect encoding, convert delimiters,
            remove duplicates, merge, split, validate, and more — free and instant.
        </p>

        <!-- Search -->
        <div class="row justify-content-center mb-4">
            <div class="col-md-6 col-lg-5">
                <div class="hero-search d-flex" role="search" aria-label="Search tools">
                    <input type="search" id="toolSearch" class="form-control"
                           placeholder="Search tools, e.g. repair, convert, merge…"
                           aria-label="Search CSV tools"
                           value="<?= e($_GET['q'] ?? '') ?>" autocomplete="off">
                    <button class="btn" type="button" id="heroSearchBtn" aria-label="Search">
                        <i class="bi bi-search" aria-hidden="true"></i> Search
                    </button>
                </div>
            </div>
        </div>

        <!-- Quick links to popular tools -->
        <div class="d-flex flex-wrap justify-content-center gap-2">
            <?php foreach (array_slice($popTools, 0, 5) as $pt): ?>
            <a href="<?= url($pt['slug'] . '/') ?>"
               class="btn btn-outline-light btn-sm">
                <?= e($pt['name']) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ── Stats bar ─────────────────────────────────────────────────────────── -->
<section class="stats-bar" aria-label="Site statistics">
    <div class="container">
        <div class="row g-2 text-center">
            <div class="col-6 col-md-3 stat-item">
                <div class="stat-value">10</div>
                <div class="stat-label">Free Tools</div>
            </div>
            <div class="col-6 col-md-3 stat-item">
                <div class="stat-value">100%</div>
                <div class="stat-label">Free Forever</div>
            </div>
            <div class="col-6 col-md-3 stat-item">
                <div class="stat-value">500 MB</div>
                <div class="stat-label">Max File Size</div>
            </div>
            <div class="col-6 col-md-3 stat-item">
                <div class="stat-value">0</div>
                <div class="stat-label">Registration Needed</div>
            </div>
        </div>
    </div>
</section>

<!-- ── All Tools ─────────────────────────────────────────────────────────── -->
<section class="py-5" id="tools" aria-labelledby="toolsHeading">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="section-title" id="toolsHeading">All CSV Tools</h2>
            <p class="section-subtitle">Pick any tool below — no sign-up, no limits, no watermarks.</p>
        </div>

        <?php
        $toolMeta = [
            'csv-repair'             => ['icon'=>'bi-wrench-adjustable', 'color'=>'#0d6efd'],
            'csv-encoding-detector'  => ['icon'=>'bi-search',            'color'=>'#6610f2'],
            'csv-delimiter-converter'=> ['icon'=>'bi-arrow-left-right',  'color'=>'#0dcaf0'],
            'csv-validator'          => ['icon'=>'bi-check-circle',      'color'=>'#198754'],
            'remove-duplicates'      => ['icon'=>'bi-funnel',            'color'=>'#fd7e14'],
            'csv-to-json'            => ['icon'=>'bi-braces',            'color'=>'#6f42c1'],
            'csv-merger'             => ['icon'=>'bi-stack',             'color'=>'#d63384'],
            'csv-splitter'           => ['icon'=>'bi-scissors',         'color'=>'#20c997'],
            'csv-file-comparison'    => ['icon'=>'bi-files',             'color'=>'#dc3545'],
            'csv-viewer'             => ['icon'=>'bi-eye',               'color'=>'#0d6efd'],
        ];
        ?>

        <div class="row g-3" id="toolsGrid">
            <?php foreach ($allTools as $t):
                $meta = $toolMeta[$t['slug']] ?? ['icon'=>'bi-tools','color'=>'#0d6efd'];
            ?>
            <div class="col-sm-6 col-lg-4 col-xl-3" data-tool-wrap>
                <a href="<?= url($t['slug'] . '/') ?>"
                   class="tool-card-home d-block"
                   data-tool-name="<?= eAttr($t['name']) ?>"
                   data-tool-desc="<?= eAttr($t['short_description'] ?? '') ?>">
                    <div class="tool-icon-wrap"
                         style="background:<?= $meta['color'] ?>1a; color:<?= $meta['color'] ?>">
                        <i class="<?= $meta['icon'] ?>" aria-hidden="true"></i>
                    </div>
                    <h3><?= e($t['name']) ?></h3>
                    <p><?= e($t['short_description'] ?? '') ?></p>
                    <?php if ($t['is_popular']): ?>
                    <span class="badge badge-popular">
                        <i class="bi bi-star-fill me-1" aria-hidden="true"></i>Popular
                    </span>
                    <?php endif; ?>
                    <div class="mt-auto pt-2">
                        <span class="text-primary small fw-semibold">
                            Use Tool <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </span>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- No results -->
        <div id="noToolsMsg" class="text-center text-muted py-4 d-none">
            <i class="bi bi-search display-6 d-block mb-2" aria-hidden="true"></i>
            No tools match your search. Try a different keyword.
        </div>
    </div>
</section>

<!-- ── Categories ────────────────────────────────────────────────────────── -->
<?php if (!empty($categories)): ?>
<section class="py-4 bg-light" aria-labelledby="catHeading">
    <div class="container">
        <h2 class="h5 fw-bold text-center mb-3" id="catHeading">Browse by Category</h2>
        <div class="d-flex flex-wrap justify-content-center gap-2">
            <?php foreach ($categories as $cat): ?>
            <a href="#tools" class="category-pill"
               onclick="filterByCategory('<?= eAttr($cat['name']) ?>')">
                <i class="<?= e($cat['icon'] ?? 'bi-grid') ?>" aria-hidden="true"></i>
                <?= e($cat['name']) ?>
                <span class="badge bg-secondary ms-1"><?= (int)$cat['tool_count'] ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ── How it works ───────────────────────────────────────────────────────── -->
<section class="py-5" aria-labelledby="howHeading">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="section-title" id="howHeading">How It Works</h2>
            <p class="section-subtitle">Three simple steps — no account needed.</p>
        </div>
        <div class="row g-3 justify-content-center">
            <div class="col-md-4">
                <div class="feature-step fade-in-up">
                    <div class="step-number">1</div>
                    <h3>Choose a Tool</h3>
                    <p>Pick from 10 free CSV tools — repair, convert, validate, clean, merge, split or compare.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-step fade-in-up" style="animation-delay:.1s">
                    <div class="step-number">2</div>
                    <h3>Upload Your File</h3>
                    <p>Drag and drop or browse to select your CSV file. Up to 500 MB supported.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-step fade-in-up" style="animation-delay:.2s">
                    <div class="step-number">3</div>
                    <h3>Download Results</h3>
                    <p>Preview the results instantly and download your fixed, converted or cleaned file.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ── Privacy & Security ─────────────────────────────────────────────────── -->
<section class="py-5 bg-light" aria-labelledby="privacyHeading">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-6">
                <h2 class="section-title" id="privacyHeading">
                    <i class="bi bi-shield-check text-success me-2" aria-hidden="true"></i>
                    Your Files Stay Private
                </h2>
                <p class="text-muted">
                    We take your data privacy seriously. CSV files uploaded for server processing
                    are stored in a protected temporary directory outside the web root,
                    processed immediately, and permanently deleted. We never read, share,
                    log, or retain your file content.
                </p>
                <ul class="list-unstyled">
                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Files deleted automatically after processing</li>
                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>No account or email required</li>
                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>CSV Viewer processes files entirely in your browser</li>
                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>HTTPS encryption in transit</li>
                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>No third-party data sharing</li>
                </ul>
                <a href="<?= url('privacy-policy/') ?>" class="btn btn-outline-primary btn-sm">
                    Read Privacy Policy
                </a>
            </div>
            <div class="col-lg-6">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="card border-0 shadow-sm text-center p-3">
                            <i class="bi bi-lock-fill display-6 text-primary mb-2" aria-hidden="true"></i>
                            <div class="fw-semibold">Encrypted Transfer</div>
                            <div class="small text-muted">HTTPS end-to-end</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card border-0 shadow-sm text-center p-3">
                            <i class="bi bi-trash-fill display-6 text-danger mb-2" aria-hidden="true"></i>
                            <div class="fw-semibold">Auto-Deleted</div>
                            <div class="small text-muted">After processing</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card border-0 shadow-sm text-center p-3">
                            <i class="bi bi-person-slash display-6 text-warning mb-2" aria-hidden="true"></i>
                            <div class="fw-semibold">No Account</div>
                            <div class="small text-muted">Anonymous use</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card border-0 shadow-sm text-center p-3">
                            <i class="bi bi-browser-chrome display-6 text-success mb-2" aria-hidden="true"></i>
                            <div class="fw-semibold">Browser-Based</div>
                            <div class="small text-muted">Viewer never uploads</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ── FAQ ───────────────────────────────────────────────────────────────── -->
<section class="py-5" aria-labelledby="faqHeading">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="section-title" id="faqHeading">Frequently Asked Questions</h2>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion accordion-flush" id="homeFaqs">
                    <?php
                    $faqs = [
                        ['q'=>'Are these tools really free?',
                         'a'=>'Yes. All 10 tools are completely free to use, with no account, subscription, or hidden fees.'],
                        ['q'=>'What file size limit is supported?',
                         'a'=>'Each tool supports files up to 500 MB. For very large files, use the CSV Splitter to break your file into chunks first.'],
                        ['q'=>'Is my data safe?',
                         'a'=>'Yes. Files uploaded for processing are stored temporarily in a secure directory, processed, and deleted immediately. The CSV Viewer never uploads your file at all — it runs entirely in your browser.'],
                        ['q'=>'What CSV formats are supported?',
                         'a'=>'Our tools support UTF-8, UTF-8 BOM, UTF-16, ISO-8859-1, and Windows-1252 encoded files. Common delimiters — comma, semicolon, tab, pipe — are all supported.'],
                        ['q'=>'Can I use these tools on mobile?',
                         'a'=>'Yes. The website is fully responsive and works on any modern smartphone or tablet.'],
                    ];
                    foreach ($faqs as $i => $faq):
                    ?>
                    <div class="accordion-item">
                        <h3 class="accordion-header" id="hfaqH<?= $i ?>">
                            <button class="accordion-button collapsed"
                                    type="button" data-bs-toggle="collapse"
                                    data-bs-target="#hfaqC<?= $i ?>"
                                    aria-expanded="false"
                                    aria-controls="hfaqC<?= $i ?>">
                                <?= e($faq['q']) ?>
                            </button>
                        </h3>
                        <div id="hfaqC<?= $i ?>"
                             class="accordion-collapse collapse"
                             aria-labelledby="hfaqH<?= $i ?>">
                            <div class="accordion-body text-muted"><?= e($faq['a']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ── CTA ───────────────────────────────────────────────────────────────── -->
<section class="py-5 bg-gradient-primary text-white text-center">
    <div class="container">
        <h2 class="fw-bold mb-2">Ready to Fix Your CSV?</h2>
        <p class="mb-4 opacity-75">Pick a tool and get started in seconds — completely free.</p>
        <a href="#tools" class="btn btn-warning btn-lg fw-semibold px-5">
            <i class="bi bi-tools me-2" aria-hidden="true"></i>Browse All Tools
        </a>
    </div>
</section>

<script>
// ── Search filter ─────────────────────────────────────────────────────────────
function applyToolSearch(q) {
    q = (q || '').toLowerCase().trim();
    let visibleCount = 0;
    document.querySelectorAll('[data-tool-wrap]').forEach(function(wrap) {
        const name = (wrap.querySelector('[data-tool-name]')?.dataset.toolName || '').toLowerCase();
        const desc = (wrap.querySelector('[data-tool-name]')?.dataset.toolDesc || '').toLowerCase();
        const show = q === '' || name.includes(q) || desc.includes(q);
        wrap.style.display = show ? '' : 'none';
        if (show) visibleCount++;
    });
    const msg = document.getElementById('noToolsMsg');
    if (msg) msg.classList.toggle('d-none', visibleCount > 0);
}

// Apply on page load if ?q= is in URL
(function() {
    const urlQ = new URLSearchParams(window.location.search).get('q') || '';
    const searchInput = document.getElementById('toolSearch');
    if (searchInput) {
        if (urlQ) {
            searchInput.value = urlQ;
            applyToolSearch(urlQ);
            // Scroll to tools section
            const toolsSection = document.getElementById('tools');
            if (toolsSection) setTimeout(() => toolsSection.scrollIntoView({behavior:'smooth'}), 200);
        }
        // Live filter as user types
        searchInput.addEventListener('input', function() {
            applyToolSearch(this.value);
        });
        // Prevent full page reload — filter in place instead
        searchInput.closest('form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            applyToolSearch(searchInput.value);
            const toolsSection = document.getElementById('tools');
            if (toolsSection) toolsSection.scrollIntoView({behavior:'smooth'});
        });
    }
})();

// ── Category filter ───────────────────────────────────────────────────────────
function filterByCategory(catName) {
    const q = document.getElementById('toolSearch');
    if (q) {
        q.value = catName;
        applyToolSearch(catName);
        const toolsSection = document.getElementById('tools');
        if (toolsSection) toolsSection.scrollIntoView({behavior:'smooth'});
    }
}
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
