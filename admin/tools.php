<?php
$pageTitle = 'Manage Tools';

// Handle quick status toggle
if (isPost() && isset($_POST['toggle_status'])) {
    verifyCsrf();
    $id     = (int)$_POST['tool_id'];
    $status = (int)$_POST['new_status'];
    dbExecute('UPDATE tools SET status = ? WHERE id = ?', [$status, $id]);
    setFlash('success', 'Tool status updated.');
    redirect(appBase() . '/admin/tools/');
}

$tools = dbFetchAll(
    'SELECT t.*, c.name AS category_name
     FROM tools t
     LEFT JOIN tool_categories c ON t.category_id = c.id
     ORDER BY t.sort_order ASC'
);

ob_start();
?>
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Category</th>
                        <th>Popular</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($tools as $t): ?>
                <tr>
                    <td class="text-muted small"><?= (int)$t['id'] ?></td>
                    <td class="fw-medium"><?= e($t['name']) ?></td>
                    <td class="font-mono small text-muted">/<?= e($t['slug']) ?>/</td>
                    <td class="small"><?= e($t['category_name'] ?? '—') ?></td>
                    <td><?= $t['is_popular'] ? '<i class="bi bi-star-fill text-warning"></i>' : '—' ?></td>
                    <td>
                        <form method="post" class="d-inline">
                            <?= csrfField() ?>
                            <input type="hidden" name="tool_id"    value="<?= (int)$t['id'] ?>">
                            <input type="hidden" name="new_status" value="<?= $t['status'] ? 0 : 1 ?>">
                            <button type="submit" name="toggle_status" value="1"
                                    class="badge border-0 <?= $t['status'] ? 'bg-success' : 'bg-secondary' ?>"
                                    style="cursor:pointer">
                                <?= $t['status'] ? 'Active' : 'Inactive' ?>
                            </button>
                        </form>
                    </td>
                    <td>
                        <a href="<?= url('admin/tools/edit/?id=' . $t['id']) ?>"
                           class="btn btn-xs btn-sm btn-outline-primary py-0 px-2">
                            <i class="bi bi-pencil" aria-hidden="true"></i> Edit
                        </a>
                        <a href="<?= url($t['slug'] . '/') ?>" target="_blank"
                           class="btn btn-xs btn-sm btn-outline-secondary py-0 px-2 ms-1">
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$adminContent = ob_get_clean();
require_once __DIR__ . '/layout.php';
