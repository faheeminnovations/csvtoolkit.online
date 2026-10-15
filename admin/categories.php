<?php
$pageTitle = 'Manage Categories';

if (isPost()) {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name  = trim($_POST['name']        ?? '');
        $slug  = trim($_POST['slug']        ?? '') ?: slugify($name);
        $desc  = trim($_POST['description'] ?? '');
        $order = (int)($_POST['sort_order'] ?? 0);
        if ($name) {
            try {
                dbExecute(
                    'INSERT INTO tool_categories (name, slug, description, sort_order) VALUES (?,?,?,?)',
                    [$name, $slug, $desc, $order]
                );
                setFlash('success', 'Category added.');
            } catch (PDOException $e) {
                setFlash('error', 'Slug already exists. Try a different slug.');
            }
        }
    } elseif ($action === 'toggle') {
        $id  = (int)$_POST['cat_id'];
        $s   = (int)$_POST['new_status'];
        dbExecute('UPDATE tool_categories SET status=? WHERE id=?', [$s, $id]);
        setFlash('success', 'Status updated.');
    } elseif ($action === 'delete') {
        $id = (int)$_POST['cat_id'];
        dbExecute('UPDATE tools SET category_id = NULL WHERE category_id = ?', [$id]);
        dbExecute('DELETE FROM tool_categories WHERE id = ?', [$id]);
        setFlash('success', 'Category deleted.');
    }
    redirect(appBase() . '/admin/categories/');
}

$categories = dbFetchAll(
    'SELECT c.*, COUNT(t.id) AS tool_count
     FROM tool_categories c
     LEFT JOIN tools t ON t.category_id = c.id
     GROUP BY c.id ORDER BY c.sort_order ASC'
);

ob_start();
?>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>Name</th><th>Slug</th><th>Tools</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td class="fw-medium"><?= e($cat['name']) ?></td>
                        <td class="font-mono small text-muted"><?= e($cat['slug']) ?></td>
                        <td><?= (int)$cat['tool_count'] ?></td>
                        <td>
                            <form method="post" class="d-inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="action"     value="toggle">
                                <input type="hidden" name="cat_id"     value="<?= (int)$cat['id'] ?>">
                                <input type="hidden" name="new_status" value="<?= $cat['status'] ? 0 : 1 ?>">
                                <button type="submit"
                                        class="badge border-0 <?= $cat['status'] ? 'bg-success' : 'bg-secondary' ?>"
                                        style="cursor:pointer">
                                    <?= $cat['status'] ? 'Active' : 'Inactive' ?>
                                </button>
                            </form>
                        </td>
                        <td>
                            <form method="post" class="d-inline"
                                  onsubmit="return confirm('Delete category? Tools will be uncategorised.')">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="cat_id" value="<?= (int)$cat['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2">
                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h6 fw-bold mb-3">Add Category</h2>
                <form method="post">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="add">
                    <div class="mb-2">
                        <input type="text" name="name" class="form-control" placeholder="Category name" required>
                    </div>
                    <div class="mb-2">
                        <input type="text" name="slug" class="form-control font-mono" placeholder="slug (auto if empty)">
                    </div>
                    <div class="mb-2">
                        <textarea name="description" class="form-control" rows="2" placeholder="Description (optional)"></textarea>
                    </div>
                    <div class="mb-3">
                        <input type="number" name="sort_order" class="form-control" value="0" placeholder="Sort order">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-plus-lg me-1"></i>Add Category
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$adminContent = ob_get_clean();
require_once __DIR__ . '/layout.php';
