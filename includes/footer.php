</main><!-- /#main-content -->

<footer class="site-footer bg-dark text-light mt-5 pt-5 pb-3">
    <div class="container">
        <div class="row g-4">
            <!-- Brand -->
            <div class="col-lg-4">
                <h2 class="h5 text-white fw-bold mb-3">
                    <i class="bi bi-file-earmark-spreadsheet me-2 text-warning" aria-hidden="true"></i>
                    CSV Repair Toolkit
                </h2>
                <p class="text-muted small">
                    Free online tools to repair, validate, convert, clean, and analyze CSV files.
                    No registration required. Your files stay private.
                </p>
                <div class="footer-badge d-inline-flex align-items-center gap-2 mt-2">
                    <span class="badge bg-success"><i class="bi bi-shield-check me-1"></i>Secure</span>
                    <span class="badge bg-info text-dark"><i class="bi bi-lightning me-1"></i>Fast</span>
                    <span class="badge bg-warning text-dark"><i class="bi bi-gift me-1"></i>Free</span>
                </div>
            </div>

            <!-- Tools -->
            <div class="col-sm-6 col-lg-2">
                <h3 class="h6 text-white mb-3 text-uppercase fw-semibold">CSV Tools</h3>
                <ul class="list-unstyled small">
                    <li><a href="<?= url('csv-repair/') ?>" class="footer-link">CSV Repair</a></li>
                    <li><a href="<?= url('csv-encoding-detector/') ?>" class="footer-link">Encoding Detector</a></li>
                    <li><a href="<?= url('csv-delimiter-converter/') ?>" class="footer-link">Delimiter Converter</a></li>
                    <li><a href="<?= url('csv-validator/') ?>" class="footer-link">CSV Validator</a></li>
                    <li><a href="<?= url('remove-duplicates/') ?>" class="footer-link">Remove Duplicates</a></li>
                </ul>
            </div>

            <!-- More Tools -->
            <div class="col-sm-6 col-lg-2">
                <h3 class="h6 text-white mb-3 text-uppercase fw-semibold">More Tools</h3>
                <ul class="list-unstyled small">
                    <li><a href="<?= url('csv-to-json/') ?>" class="footer-link">CSV to JSON</a></li>
                    <li><a href="<?= url('csv-merger/') ?>" class="footer-link">CSV Merger</a></li>
                    <li><a href="<?= url('csv-splitter/') ?>" class="footer-link">CSV Splitter</a></li>
                    <li><a href="<?= url('csv-file-comparison/') ?>" class="footer-link">File Comparison</a></li>
                    <li><a href="<?= url('csv-viewer/') ?>" class="footer-link">CSV Viewer</a></li>
                </ul>
            </div>

            <!-- Company -->
            <div class="col-sm-6 col-lg-2">
                <h3 class="h6 text-white mb-3 text-uppercase fw-semibold">Company</h3>
                <ul class="list-unstyled small">
                    <li><a href="<?= url('about/') ?>" class="footer-link">About Us</a></li>
                    <li><a href="<?= url('contact/') ?>" class="footer-link">Contact</a></li>
                    <li><a href="<?= url('blog/') ?>" class="footer-link">Blog</a></li>
                </ul>
            </div>

            <!-- Legal -->
            <div class="col-sm-6 col-lg-2">
                <h3 class="h6 text-white mb-3 text-uppercase fw-semibold">Legal</h3>
                <ul class="list-unstyled small">
                    <li><a href="<?= url('privacy-policy/') ?>" class="footer-link">Privacy Policy</a></li>
                    <li><a href="<?= url('terms/') ?>" class="footer-link">Terms of Service</a></li>
                </ul>
            </div>
        </div>

        <hr class="border-secondary mt-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="small text-muted mb-0">
                    &copy; <?= date('Y') ?> CSV Repair Toolkit. All rights reserved.
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="small text-muted mb-0">
                    <i class="bi bi-lock me-1"></i>Files processed securely.
                    <span class="ms-2"><i class="bi bi-trash me-1"></i>Auto-deleted after processing.</span>
                </p>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS (local) -->
<script src="<?= url('assets/vendor/bootstrap.bundle.min.js') ?>"></script>
<!-- Main JS -->
<script src="<?= asset('js/main.js') ?>?v=<?= APP_VERSION ?>"></script>
<?php if (isset($extraJs)): ?>
<?php foreach ((array)$extraJs as $js): ?>
<script src="<?= asset($js) ?>?v=<?= APP_VERSION ?>"></script>
<?php endforeach; ?>
<?php endif; ?>
<?php if (isset($inlineJs)): ?>
<script><?= $inlineJs ?></script>
<?php endif; ?>
</body>
</html>
