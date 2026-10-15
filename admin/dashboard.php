<?php
$pageTitle = 'Dashboard';

// Stats
$totalTools      = dbFetch('SELECT COUNT(*) AS n FROM tools WHERE status = 1')['n'] ?? 0;
$totalCategories = dbFetch('SELECT COUNT(*) AS n FROM tool_categories WHERE status = 1')['n'] ?? 0;
$totalFaqs       = dbFetch('SELECT COUNT(*) AS n FROM tool_faqs')['n'] ?? 0;
$unreadMessages  = dbFetch('SELECT COUNT(*) AS n FROM contact_messages WHERE is_read = 0')['n'] ?? 0;

$recentMessages = dbFetchAll(
    'SELECT name, email, subject, created_at FROM contact_messages ORDER BY created_at DESC LIMIT 5'
);

$tools = dbFetchAll(
    'SELECT t.name, t.slug, t.status, c.name AS category FROM tools t
     LEFT JOIN tool_categories c ON t.category_id = c.id
     ORDER BY t.sort_order ASC'
);

ob_start();
?>
<!-- Stat cards -->
<div class="row g-3 mb-4">
    <?php
    $stats = [
        ['label'=>'Active Tools',      'value'=>$totalTools,      'icon'=>'bi-tools',            'bg'=>'bg-primary bg-opacity-10',  'color'=>'text-primary'],
        ['label'=>'Categories',        'value'=>$totalCategories, 'icon'=>'bi-grid',             'bg'=>'bg-success bg-opacity-10',  'color'=>'text-success'],
        ['label'=>'FAQs',              'value'=>$totalFaqs,       'icon'=>'bi-question-circle',  'bg'=>'bg-warning bg-opacity-10',  'color'=>'text-warning'],
        ['label'=>'Unread Messages',   'value'=>$unreadMessages,  'icon'=>'bi-envelope',         'bg'=>'bg-danger bg-opacity-10',   'color'=>'text-danger'],
    ];
    foreach ($stats as $s):
    ?>
    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card">
            <div class="admin-stat-icon <?= $s['bg'] ?> <?= $s['color'] ?>">
                <i class="<?= $s['icon'] ?>" aria-hidden="true"></i>
            </div>
            <div>
                <div class="h3 fw-bold mb-0"><?= $s['value'] ?></div>
                <div class="text-muted small"><?= e($s['label']) ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <!-- Tools list -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h6 fw-bold mb-0">Tools</h2>
                    <a href="<?= url('admin/tools/') ?>" class="btn btn-sm btn-outline-primary">Manage</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Name</th><th>Category</th><th>Status</th><th></th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($tools as $t): ?>
                        <tr>
                            <td class="fw-medium"><?= e($t['name']) ?></td>
                            <td class="small text-muted"><?= e($t['category'] ?? '—') ?></td>
                            <td>
                                <span class="badge <?= $t['status'] ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= $t['status'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?= url($t['slug'] . '/') ?>" target="_blank"
                                   class="btn btn-xs btn-outline-secondary btn-sm py-0 px-1 small">
                                    <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent messages -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h6 fw-bold mb-0">Recent Messages</h2>
                    <a href="<?= url('admin/messages/') ?>" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <?php if (empty($recentMessages)): ?>
                <p class="text-muted small mb-0">No messages yet.</p>
                <?php else: ?>
                <?php foreach ($recentMessages as $msg): ?>
                <div class="border-bottom pb-2 mb-2">
                    <div class="fw-medium small"><?= e($msg['name']) ?>
                        <span class="text-muted">&lt;<?= e($msg['email']) ?>&gt;</span>
                    </div>
                    <div class="small text-truncate text-muted"><?= e($msg['subject']) ?></div>
                    <div class="small text-secondary"><?= e(date('M j, Y', strtotime($msg['created_at']))) ?></div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$adminContent = ob_get_clean();
require_once __DIR__ . '/layout.php';
