<?php
$id   = (int)($_GET['id'] ?? 0);
$tool = $id ? dbFetch('SELECT * FROM tools WHERE id = ?', [$id]) : null;
if (!$tool) { setFlash('error', 'Tool not found.'); redirect(appBase() . '/admin/tools/'); }

$pageTitle  = 'Edit Tool: ' . $tool['name'];
$categories = dbFetchAll('SELECT id, name FROM tool_categories ORDER BY sort_order ASC');
$errors     = [];

if (isPost()) {
    verifyCsrf();
    $f = [
        'name'              => trim($_POST['name']              ?? ''),
        'slug'              => trim($_POST['slug']              ?? ''),
        'short_description' => trim($_POST['short_description'] ?? ''),
        'meta_title'        => trim($_POST['meta_title']        ?? ''),
        'meta_description'  => trim($_POST['meta_description']  ?? ''),
        'h1'                => trim($_POST['h1']                ?? ''),
        'instructions'      => trim($_POST['instructions']      ?? ''),
        'content'           => trim($_POST['content']           ?? ''),
        'category_id'       => (int)($_POST['category_id']     ?? 0) ?: null,
        'is_popular'        => isset($_POST['is_popular']) ? 1 : 0,
        'status'            => isset($_POST['status'])    ? 1 : 0,
    ];

    if ($f['name'] === '')  $errors[] = 'Name is required.';
    if ($f['slug'] === '')  $errors[] = 'Slug is required.';
    if (!preg_match('/^[a-z0-9-]+$/', $f['slug'])) $errors[] = 'Slug may only contain lowercase letters, numbers, and hyphens.';

    // Check slug uniqueness (excluding current)
    $existing = dbFetch('SELECT id FROM tools WHERE slug = ? AND id != ?', [$f['slug'], $id]);
    if ($existing) $errors[] = 'This slug is already used by another tool.';

    if (empty($errors)) {
        dbExecute(
            'UPDATE tools SET name=?, slug=?, short_description=?, meta_title=?, meta_description=?,
             h1=?, instructions=?, content=?, category_id=?, is_popular=?, status=?, updated_at=NOW()
             WHERE id=?',
            [$f['name'], $f['slug'], $f['short_description'], $f['meta_title'], $f['meta_description'],
             $f['h1'], $f['instructions'], $f['content'], $f['category_id'], $f['is_popular'], $f['status'], $id]
        );
        setFlash('success', 'Tool "' . $f['name'] . '" updated successfully.');
        redirect(appBase() . '/admin/tools/');
    }
    $tool = array_merge($tool, $f); // repopulate form
}

ob_start();
?>
<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" action="<?= url('admin/tools/edit/?id=' . $id) ?>">
    <?= csrfField() ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">Basic Information</h2>
                    <div class="mb-3">
                        <label for="name" class="form-label fw-medium">Tool Name</label>
                        <input type="text" id="name" name="name" class="form-control"
                               value="<?= e($tool['name']) ?>" required maxlength="120">
                    </div>
                    <div class="mb-3">
                        <label for="slug" class="form-label fw-medium">URL Slug</label>
                        <div class="input-group">
                            <span class="input-group-text text-muted">/</span>
                            <input type="text" id="slug" name="slug" class="form-control font-mono"
                                   value="<?= e($tool['slug']) ?>" required maxlength="120">
                            <span class="input-group-text text-muted">/</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="short_description" class="form-label fw-medium">Short Description</label>
                        <input type="text" id="short_description" name="short_description"
                               class="form-control" value="<?= e($tool['short_description'] ?? '') ?>" maxlength="300">
                    </div>
                    <div class="mb-3">
                        <label for="h1" class="form-label fw-medium">H1 Heading</label>
                        <input type="text" id="h1" name="h1" class="form-control"
                               value="<?= e($tool['h1'] ?? '') ?>" maxlength="200">
                    </div>
                    <div class="mb-3">
                        <label for="instructions" class="form-label fw-medium">Instructions (HTML allowed)</label>
                        <textarea id="instructions" name="instructions" rows="5" class="form-control font-mono"><?= e($tool['instructions'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="content" class="form-label fw-medium">Page Content (HTML allowed)</label>
                        <textarea id="content" name="content" rows="6" class="form-control font-mono"><?= e($tool['content'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">SEO Metadata</h2>
                    <div class="mb-3">
                        <label for="meta_title" class="form-label fw-medium">
                            Meta Title <span class="text-muted small" id="metaTitleCount"></span>
                        </label>
                        <input type="text" id="meta_title" name="meta_title" class="form-control"
                               value="<?= e($tool['meta_title'] ?? '') ?>" maxlength="160"
                               oninput="updateCount('meta_title','metaTitleCount',160)">
                    </div>
                    <div class="mb-3">
                        <label for="meta_description" class="form-label fw-medium">
                            Meta Description <span class="text-muted small" id="metaDescCount"></span>
                        </label>
                        <textarea id="meta_description" name="meta_description" rows="3" class="form-control"
                                  maxlength="320"
                                  oninput="updateCount('meta_description','metaDescCount',320)"><?= e($tool['meta_description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">Settings</h2>
                    <div class="mb-3">
                        <label for="category_id" class="form-label fw-medium">Category</label>
                        <select id="category_id" name="category_id" class="form-select">
                            <option value="">— No category —</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int)$cat['id'] ?>"
                                <?= ($tool['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="is_popular" id="is_popular"
                               value="1" <?= $tool['is_popular'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_popular">Mark as Popular</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="status" id="status"
                               value="1" <?= $tool['status'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="status">Published (Active)</label>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">Actions</h2>
                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="bi bi-check-lg me-1"></i>Save Changes
                    </button>
                    <a href="<?= url($tool['slug'] . '/') ?>" target="_blank"
                       class="btn btn-outline-secondary w-100 mb-2">
                        <i class="bi bi-eye me-1"></i>Preview Page
                    </a>
                    <a href="<?= url('admin/tools/') ?>" class="btn btn-outline-danger w-100">
                        <i class="bi bi-x me-1"></i>Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
function updateCount(fieldId, countId, max) {
    const len = document.getElementById(fieldId).value.length;
    const el  = document.getElementById(countId);
    el.textContent = len + ' / ' + max;
    el.className = len > max * 0.9 ? 'text-warning small' : 'text-muted small';
}
// Init counts on load
['meta_title', 'meta_description'].forEach(id => {
    const map = { meta_title: ['metaTitleCount', 160], meta_description: ['metaDescCount', 320] };
    updateCount(id, map[id][0], map[id][1]);
});
</script>

<?php
$adminContent = ob_get_clean();
require_once __DIR__ . '/layout.php';
