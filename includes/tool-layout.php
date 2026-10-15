<?php
/**
 * Shared tool page layout wrapper.
 * Variables expected from each tool's index.php:
 *   $tool       — array from DB (or static array)
 *   $faqs       — array of FAQ rows
 *   $toolContent — string of rendered HTML for the tool UI
 *   $relatedTools — array of related tool rows
 */

// Ensure defaults
$tool         = $tool         ?? [];
$faqs         = $faqs         ?? [];
$relatedTools = $relatedTools ?? [];
$toolSlug     = $tool['slug'] ?? '';
$toolName     = $tool['name'] ?? 'Tool';

// Set SEO
setSeoMeta([
    'title'       => $tool['meta_title']       ?? ($toolName . ' — ' . APP_NAME),
    'description' => $tool['meta_description'] ?? ($tool['short_description'] ?? ''),
    'canonical'   => APP_URL . '/' . $toolSlug . '/',
    'schema'      => buildToolSchema($tool),
    'breadcrumbs' => [
        ['label' => 'Home',  'url' => APP_URL . '/'],
        ['label' => $toolName],
    ],
]);

require_once ROOT_PATH . '/includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumbs -->
    <?php renderBreadcrumbs([
        ['label' => 'Home', 'url' => url()],
        ['label' => $toolName],
    ]); ?>

    <div class="row g-4">
        <!-- Main tool column -->
        <div class="col-lg-8">
            <div class="tool-card card shadow-sm border-0">
                <div class="card-body p-4">
                    <h1 class="tool-title h3 mb-1">
                        <?= e($tool['h1'] ?? $toolName) ?>
                    </h1>
                    <p class="text-muted mb-4"><?= e($tool['short_description'] ?? '') ?></p>

                    <!-- Flash messages -->
                    <?php renderFlash(); ?>

                    <!-- Tool UI injected here -->
                    <?= $toolContent ?? '' ?>
                </div>
            </div>

            <!-- Instructions & content -->
            <?php if (!empty($tool['instructions']) || !empty($tool['content'])): ?>
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body p-4">
                    <?php if (!empty($tool['instructions'])): ?>
                    <h2 class="h5 mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>How to Use</h2>
                    <div class="tool-instructions"><?= $tool['instructions'] ?></div>
                    <?php endif; ?>
                    <?php if (!empty($tool['content'])): ?>
                    <div class="tool-content mt-3"><?= $tool['content'] ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- FAQs -->
            <?php if (!empty($faqs)): ?>
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">
                        <i class="bi bi-question-circle me-2 text-primary"></i>
                        Frequently Asked Questions
                    </h2>
                    <div class="accordion accordion-flush" id="toolFaqs">
                        <?php foreach ($faqs as $i => $faq): ?>
                        <div class="accordion-item">
                            <h3 class="accordion-header" id="faqH<?= $i ?>">
                                <button class="accordion-button collapsed"
                                        type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#faqC<?= $i ?>"
                                        aria-expanded="false"
                                        aria-controls="faqC<?= $i ?>">
                                    <?= e($faq['question']) ?>
                                </button>
                            </h3>
                            <div id="faqC<?= $i ?>"
                                 class="accordion-collapse collapse"
                                 aria-labelledby="faqH<?= $i ?>">
                                <!-- NOTE: data-bs-parent removed so multiple FAQs can be open at once -->
                                <div class="accordion-body text-muted">
                                    <?= nl2br(e($faq['answer'])) ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- AdSense slot -->
            <?php if (ADSENSE_ENABLED): ?>
            <div class="ad-slot mb-4" aria-label="Advertisement">
                <ins class="adsbygoogle" style="display:block"
                     data-ad-client="<?= e(ADSENSE_PUBLISHER_ID) ?>"
                     data-ad-slot="AUTO"
                     data-ad-format="auto"
                     data-full-width-responsive="true"></ins>
                <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
            </div>
            <?php endif; ?>

            <!-- Related tools -->
            <?php if (!empty($relatedTools)): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h6 text-uppercase fw-semibold text-muted mb-3">
                        <i class="bi bi-grid me-1"></i>Related Tools
                    </h2>
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($relatedTools as $rt): ?>
                        <li class="mb-2">
                            <a href="<?= url($rt['slug'] . '/') ?>" class="related-tool-link d-flex align-items-start gap-2">
                                <i class="bi bi-arrow-right-circle text-primary mt-1 flex-shrink-0" aria-hidden="true"></i>
                                <div>
                                    <div class="fw-medium"><?= e($rt['name']) ?></div>
                                    <div class="small text-muted"><?= e(truncate($rt['short_description'] ?? '', 60)) ?></div>
                                </div>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>

            <!-- Privacy notice -->
            <div class="card border-0 bg-light">
                <div class="card-body p-3">
                    <h2 class="h6 fw-semibold mb-2">
                        <i class="bi bi-shield-lock me-1 text-success"></i>Your Privacy
                    </h2>
                    <p class="small text-muted mb-0">
                        Files uploaded for server processing are stored in a secure temporary location,
                        processed immediately, and deleted automatically. We do not read, store,
                        or share your data.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
