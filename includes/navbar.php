<?php
// ── Active route detection ────────────────────────────────────────────────────
$_navUri = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
$_scriptDir = trim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
if ($_scriptDir !== '' && str_starts_with($_navUri, $_scriptDir)) {
    $_navUri = ltrim(substr($_navUri, strlen($_scriptDir)), '/');
}
$_navUri = rtrim($_navUri, '/'); // strip trailing slash for comparison

function navActive(string $slug): string {
    global $_navUri;
    $clean = rtrim($slug, '/');
    return ($_navUri === $clean) ? ' active' : '';
}
function navAriaCurrent(string $slug): string {
    global $_navUri;
    $clean = rtrim($slug, '/');
    return ($_navUri === $clean) ? ' aria-current="page"' : '';
}
?>
<a href="#main-content" class="visually-hidden-focusable skip-link">Skip to main content</a>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top" aria-label="Main navigation">
    <div class="container">

        <!-- Brand -->
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?= url() ?>">
            <svg width="28" height="28" viewBox="0 0 28 28" fill="none"
                 xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <rect width="28" height="28" rx="6" fill="white" fill-opacity="0.15"/>
                <path d="M6 8h16M6 14h10M6 20h13" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                <circle cx="21" cy="20" r="4" fill="#FFD700"/>
                <path d="M19.5 20l1 1 2-2" stroke="white" stroke-width="1.8"
                      stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            CSV Repair Toolkit
        </a>

        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false"
                aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                <!-- Home -->
                <li class="nav-item">
                    <a class="nav-link<?= navActive('') ?>"
                       href="<?= url() ?>"<?= navAriaCurrent('') ?>>
                        Home
                    </a>
                </li>

                <!-- Tools dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="javascript:void(0)"
                       role="button" data-bs-toggle="dropdown"
                       aria-expanded="false" id="toolsDropdown">
                        Tools
                    </a>
                    <ul class="dropdown-menu dropdown-menu-tools"
                        aria-labelledby="toolsDropdown">
                        <?php
                        $navTools = [
                            ['slug'=>'csv-repair',             'name'=>'CSV Repair',          'icon'=>'bi-wrench-adjustable'],
                            ['slug'=>'csv-encoding-detector',  'name'=>'Encoding Detector',   'icon'=>'bi-search'],
                            ['slug'=>'csv-delimiter-converter','name'=>'Delimiter Converter', 'icon'=>'bi-arrow-left-right'],
                            ['slug'=>'csv-validator',          'name'=>'CSV Validator',        'icon'=>'bi-check-circle'],
                            ['slug'=>'remove-duplicates',      'name'=>'Remove Duplicates',   'icon'=>'bi-funnel'],
                            ['slug'=>'csv-to-json',            'name'=>'CSV to JSON',         'icon'=>'bi-braces'],
                            ['slug'=>'csv-merger',             'name'=>'CSV Merger',          'icon'=>'bi-stack'],
                            ['slug'=>'csv-splitter',           'name'=>'CSV Splitter',        'icon'=>'bi-scissors'],
                            ['slug'=>'csv-file-comparison',    'name'=>'File Comparison',     'icon'=>'bi-files'],
                            ['slug'=>'csv-viewer',             'name'=>'CSV Viewer',          'icon'=>'bi-eye'],
                        ];
                        foreach ($navTools as $t):
                            $isActive = ($_navUri === $t['slug']);
                        ?>
                        <li>
                            <a class="dropdown-item<?= $isActive ? ' active' : '' ?>"
                               href="<?= url($t['slug'] . '/') ?>"
                               <?= $isActive ? 'aria-current="page"' : '' ?>>
                                <i class="<?= $t['icon'] ?> me-2 text-primary"
                                   aria-hidden="true"></i>
                                <?= e($t['name']) ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </li>

                <li class="nav-item">
                    <a class="nav-link<?= navActive('about') ?>"
                       href="<?= url('about/') ?>"<?= navAriaCurrent('about') ?>>
                        About
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= navActive('contact') ?>"
                       href="<?= url('contact/') ?>"<?= navAriaCurrent('contact') ?>>
                        Contact
                    </a>
                </li>

            </ul>

            <!-- Navbar search -->
            <form class="d-flex" role="search"
                  action="<?= url() ?>" method="get"
                  aria-label="Search tools"
                  id="navSearchForm">
                <div class="input-group input-group-sm">
                    <input class="form-control" type="search" name="q"
                           id="navSearch"
                           placeholder="Search tools…"
                           aria-label="Search tools"
                           value="<?= e($_GET['q'] ?? '') ?>"
                           maxlength="100"
                           autocomplete="off">
                    <button class="btn btn-outline-light" type="submit"
                            aria-label="Search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                    </button>
                </div>
            </form>

            <script>
            // If we're on the homepage, filter in-place instead of reloading
            document.getElementById('navSearchForm')?.addEventListener('submit', function(e) {
                const toolSearch = document.getElementById('toolSearch');
                if (toolSearch && typeof applyToolSearch === 'function') {
                    e.preventDefault();
                    const q = document.getElementById('navSearch').value;
                    toolSearch.value = q;
                    applyToolSearch(q);
                    const toolsSection = document.getElementById('tools');
                    if (toolsSection) toolsSection.scrollIntoView({behavior:'smooth'});
                }
                // else: normal form submit → goes to /?q=... on other pages
            });
            </script>
        </div>

    </div>
</nav>
