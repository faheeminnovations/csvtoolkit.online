<?php
setSeoMeta([
    'title'       => 'Terms of Service — CSV Repair Toolkit',
    'description' => 'Read the CSV Repair Toolkit Terms of Service. Understand the rules for using our free online CSV tools.',
    'canonical'   => APP_URL . '/terms/',
    'robots'      => 'noindex, follow',
]);
$bodyClass = 'page-legal';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="container py-5">
    <?php renderBreadcrumbs([['label'=>'Home','url'=>url()],['label'=>'Terms of Service']]); ?>

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <h1 class="fw-bold mb-1">Terms of Service</h1>
            <p class="text-muted mb-4">Last updated: <?= date('F j, Y') ?></p>

            <div class="card border-0 shadow-sm p-4 mb-4">
                <h2 class="h5 fw-bold">1. Acceptance of Terms</h2>
                <p class="text-muted mb-0">By accessing or using CSV Repair Toolkit ("<strong>the Service</strong>"), you agree to be bound by these Terms of Service. If you do not agree, please do not use the Service.</p>
            </div>

            <div class="card border-0 shadow-sm p-4 mb-4">
                <h2 class="h5 fw-bold">2. Description of Service</h2>
                <p class="text-muted mb-0">CSV Repair Toolkit provides free online tools for processing CSV files including repair, validation, conversion, cleaning, merging, splitting, comparison, and viewing. The Service is provided "as is" without warranty of any kind.</p>
            </div>

            <div class="card border-0 shadow-sm p-4 mb-4">
                <h2 class="h5 fw-bold">3. Acceptable Use</h2>
                <p class="text-muted">You agree not to:</p>
                <ul class="text-muted">
                    <li>Upload files containing illegal, harmful, or personally identifiable data you are not authorised to process.</li>
                    <li>Use the Service to distribute malware or conduct cyberattacks.</li>
                    <li>Attempt to circumvent file size limits or rate limits.</li>
                    <li>Scrape, crawl, or automate bulk processing in a way that degrades service for other users.</li>
                    <li>Reverse-engineer, decompile, or attempt to extract the source code of the Service.</li>
                </ul>
            </div>

            <div class="card border-0 shadow-sm p-4 mb-4">
                <h2 class="h5 fw-bold">4. File Processing and Data</h2>
                <p class="text-muted">You are responsible for ensuring you have the legal right to process any file you upload. We do not review the content of your files. Uploaded files are deleted immediately after processing. See our <a href="<?= url('privacy-policy/') ?>">Privacy Policy</a> for details.</p>
            </div>

            <div class="card border-0 shadow-sm p-4 mb-4">
                <h2 class="h5 fw-bold">5. Accuracy of Results</h2>
                <p class="text-muted">Our tools are designed to be accurate and helpful, but results are provided without warranty. Always verify important output before using it in production systems. We are not liable for data loss, incorrect results, or downstream consequences of using the Service.</p>
            </div>

            <div class="card border-0 shadow-sm p-4 mb-4">
                <h2 class="h5 fw-bold">6. Limitation of Liability</h2>
                <p class="text-muted mb-0">To the fullest extent permitted by law, CSV Repair Toolkit and its operators shall not be liable for any direct, indirect, incidental, special, or consequential damages arising from your use of the Service, including loss of data.</p>
            </div>

            <div class="card border-0 shadow-sm p-4 mb-4">
                <h2 class="h5 fw-bold">7. Intellectual Property</h2>
                <p class="text-muted mb-0">The Service, its design, code, and content are owned by CSV Repair Toolkit. You retain ownership of the files you upload. You grant us a temporary, limited licence to process your files solely to provide the Service.</p>
            </div>

            <div class="card border-0 shadow-sm p-4 mb-4">
                <h2 class="h5 fw-bold">8. Service Availability</h2>
                <p class="text-muted mb-0">We aim for high availability but do not guarantee uninterrupted access. We reserve the right to modify, suspend, or discontinue the Service at any time without notice.</p>
            </div>

            <div class="card border-0 shadow-sm p-4 mb-4">
                <h2 class="h5 fw-bold">9. Changes to Terms</h2>
                <p class="text-muted mb-0">We may update these Terms at any time. Continued use of the Service after changes constitutes acceptance of the new Terms.</p>
            </div>

            <div class="card border-0 shadow-sm p-4">
                <h2 class="h5 fw-bold">10. Contact</h2>
                <p class="text-muted mb-0">Questions about these Terms? <a href="<?= url('contact/') ?>">Contact us</a> or email <a href="mailto:hello@csvtoolkit.online">hello@csvtoolkit.online</a>.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
