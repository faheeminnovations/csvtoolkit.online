<?php
setSeoMeta([
    'title'       => 'Privacy Policy — CSV Repair Toolkit',
    'description' => 'Read the CSV Repair Toolkit privacy policy. Learn how we handle uploaded files, what data we collect, and how we protect your privacy.',
    'canonical'   => APP_URL . '/privacy-policy/',
    'robots'      => 'noindex, follow',
]);
$bodyClass = 'page-legal';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="container py-5">
    <?php renderBreadcrumbs([['label'=>'Home','url'=>url()],['label'=>'Privacy Policy']]); ?>

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <h1 class="fw-bold mb-1">Privacy Policy</h1>
            <p class="text-muted mb-4">Last updated: <?= date('F j, Y') ?></p>

            <div class="alert alert-info d-flex gap-2 mb-4" role="note">
                <i class="bi bi-info-circle-fill flex-shrink-0 mt-1" aria-hidden="true"></i>
                <div><strong>Short version:</strong> We process your CSV files to provide the tool result.
                Uploaded files are deleted immediately after processing. We do not store, sell, or share your data.</div>
            </div>

            <div class="card border-0 shadow-sm p-4 mb-4">
                <h2 class="h5 fw-bold">1. Information We Collect</h2>
                <h3 class="h6 fw-semibold mt-3">Files You Upload</h3>
                <p class="text-muted">When you use a server-side tool (CSV Repair, Encoding Detector, Delimiter Converter, Validator, Remove Duplicates, CSV to JSON, CSV Merger, CSV Splitter, File Comparison), your file is temporarily stored in a protected directory outside the public web root. It is processed, the result is made available to you, and the original file is deleted immediately. Temporary result files are deleted within one hour. The CSV Viewer processes files entirely in your browser — no data is sent to our servers.</p>
                <h3 class="h6 fw-semibold mt-3">Contact Form</h3>
                <p class="text-muted">If you use the Contact form, we store your name, email address, subject, and message in our database so we can reply to you.</p>
                <h3 class="h6 fw-semibold mt-3">Server Logs</h3>
                <p class="text-muted">Our web server automatically records standard access logs including your IP address, browser type, referring URL, and pages visited. These logs are used for security monitoring and are not shared.</p>
                <h3 class="h6 fw-semibold mt-3">Cookies and Sessions</h3>
                <p class="text-muted">We use a single session cookie to maintain your session (e.g. to show processing results). This cookie is not used for tracking and expires when you close your browser.</p>
            </div>

            <div class="card border-0 shadow-sm p-4 mb-4">
                <h2 class="h5 fw-bold">2. How We Use Information</h2>
                <ul class="text-muted">
                    <li>To process your CSV files and return results.</li>
                    <li>To respond to contact form messages.</li>
                    <li>To monitor for abuse and security threats.</li>
                    <li>To improve tool performance and reliability.</li>
                </ul>
                <p class="text-muted">We do not use your information for advertising, profiling, or any purpose other than those listed above.</p>
            </div>

            <div class="card border-0 shadow-sm p-4 mb-4">
                <h2 class="h5 fw-bold">3. Data Sharing</h2>
                <p class="text-muted">We do not sell, rent, or share your personal information or file data with third parties, except:</p>
                <ul class="text-muted">
                    <li>When required by law or valid legal process.</li>
                    <li>To protect the safety and rights of our users.</li>
                </ul>
                <p class="text-muted">If Google AdSense is enabled, Google may set cookies for advertising purposes. You can opt out via <a href="https://adssettings.google.com/" rel="noopener noreferrer" target="_blank">Google Ad Settings</a>.</p>
            </div>

            <div class="card border-0 shadow-sm p-4 mb-4">
                <h2 class="h5 fw-bold">4. Data Retention</h2>
                <ul class="text-muted">
                    <li><strong>Uploaded files:</strong> Deleted immediately after processing, or within one hour via automated cleanup.</li>
                    <li><strong>Contact messages:</strong> Retained for up to 12 months and then deleted.</li>
                    <li><strong>Server logs:</strong> Retained for up to 30 days.</li>
                </ul>
            </div>

            <div class="card border-0 shadow-sm p-4 mb-4">
                <h2 class="h5 fw-bold">5. Security</h2>
                <p class="text-muted">We use HTTPS encryption, access controls, and secure coding practices to protect your data. Uploaded files are stored outside the public web root and accessed only through controlled server-side code.</p>
            </div>

            <div class="card border-0 shadow-sm p-4 mb-4">
                <h2 class="h5 fw-bold">6. Your Rights</h2>
                <p class="text-muted">Depending on your location, you may have the right to request access to, correction of, or deletion of any personal data we hold about you. Contact us at <a href="mailto:hello@csvtoolkit.online">hello@csvtoolkit.online</a> to make a request.</p>
            </div>

            <div class="card border-0 shadow-sm p-4 mb-4">
                <h2 class="h5 fw-bold">7. Children's Privacy</h2>
                <p class="text-muted">Our tools are not directed at children under 13. We do not knowingly collect personal information from children.</p>
            </div>

            <div class="card border-0 shadow-sm p-4 mb-4">
                <h2 class="h5 fw-bold">8. Changes to This Policy</h2>
                <p class="text-muted">We may update this policy from time to time. The "Last updated" date at the top of this page will reflect any changes.</p>
            </div>

            <div class="card border-0 shadow-sm p-4">
                <h2 class="h5 fw-bold">9. Contact</h2>
                <p class="text-muted mb-0">Questions about this privacy policy? <a href="<?= url('contact/') ?>">Contact us</a> or email <a href="mailto:hello@csvtoolkit.online">hello@csvtoolkit.online</a>.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
