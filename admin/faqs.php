<?php
$pageTitle = 'Manage FAQs';

// ── Handle POST actions ───────────────────────────────────────────────────────
if (isPost()) {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $toolId   = (int)$_POST['tool_id'];
        $question = trim($_POST['question'] ?? '');
        $answer   = trim($_POST['answer']   ?? '');
        $order    = (int)($_POST['sort_order'] ?? 0);
        if ($question && $answer && $toolId) {
            dbExecute(
                'INSERT INTO tool_faqs (tool_id, question, answer, sort_order) VALUES (?,?,?,?)',
                [$toolId, $question, $answer, $order]
            );
            setFlash('success', 'FAQ added successfully.');
        } else {
            setFlash('error', 'Question and answer are required.');
        }

    } elseif ($action === 'update') {
        $faqId    = (int)$_POST['faq_id'];
        $question = trim($_POST['question'] ?? '');
        $answer   = trim($_POST['answer']   ?? '');
        $order    = (int)($_POST['sort_order'] ?? 0);
        if ($faqId && $question && $answer) {
            dbExecute(
                'UPDATE tool_faqs SET question=?, answer=?, sort_order=? WHERE id=?',
                [$question, $answer, $order, $faqId]
            );
            setFlash('success', 'FAQ updated successfully.');
        } else {
            setFlash('error', 'Question and answer cannot be empty.');
        }

    } elseif ($action === 'delete') {
        $faqId = (int)$_POST['faq_id'];
        if ($faqId) {
            dbExecute('DELETE FROM tool_faqs WHERE id = ?', [$faqId]);
            setFlash('success', 'FAQ deleted.');
        }
    }

    // Stay on same tool after action
    $toolId = (int)($_POST['tool_id'] ?? $_POST['stay_tool_id'] ?? 0);
    redirect(appBase() . '/admin/faqs/' . ($toolId ? '?tool_id=' . $toolId : ''));
}

// ── Load data ─────────────────────────────────────────────────────────────────
$tools        = dbFetchAll('SELECT id, name FROM tools ORDER BY sort_order ASC');
$selectedTool = (int)($_GET['tool_id'] ?? ($tools[0]['id'] ?? 0));
$faqs         = $selectedTool
    ? dbFetchAll('SELECT * FROM tool_faqs WHERE tool_id = ? ORDER BY sort_order ASC', [$selectedTool])
    : [];

$toolName = '';
foreach ($tools as $t) {
    if ((int)$t['id'] === $selectedTool) { $toolName = $t['name']; break; }
}

ob_start();
?>

<div class="row g-4">

    <!-- ── Left: tool selector ─────────────────────────────────────────────── -->
    <div class="col-lg-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-2">
                <div class="fw-bold small text-uppercase text-muted px-2 py-2 mb-1">Select Tool</div>
                <?php foreach ($tools as $t): ?>
                <a href="<?= url('admin/faqs/?tool_id=' . $t['id']) ?>"
                   class="d-flex align-items-center gap-2 px-3 py-2 rounded text-decoration-none small mb-1
                          <?= (int)$t['id'] === $selectedTool ? 'bg-primary text-white fw-semibold' : 'text-dark hover-bg' ?>">
                    <i class="bi bi-question-circle <?= (int)$t['id'] === $selectedTool ? 'text-white' : 'text-primary' ?>"></i>
                    <?= e($t['name']) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ── Right: FAQ list + add form ─────────────────────────────────────── -->
    <div class="col-lg-9">
        <?php if (!$selectedTool): ?>
        <div class="alert alert-info">Please select a tool from the left panel.</div>
        <?php else: ?>

        <!-- FAQ table -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-3">
                <h2 class="h6 fw-bold mb-0">
                    <i class="bi bi-question-circle me-2 text-primary"></i>
                    FAQs for: <span class="text-primary"><?= e($toolName) ?></span>
                </h2>
                <span class="badge bg-secondary"><?= count($faqs) ?> FAQ<?= count($faqs) !== 1 ? 's' : '' ?></span>
            </div>
            <div class="card-body p-0">

                <?php if (empty($faqs)): ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inbox display-6 d-block mb-2"></i>
                    No FAQs yet for this tool. Add one below.
                </div>
                <?php else: ?>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0" id="faqTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40px">#</th>
                                <th style="width:30%">Question</th>
                                <th>Answer</th>
                                <th style="width:60px" class="text-center">Order</th>
                                <th style="width:120px" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($faqs as $i => $faq): ?>
                        <tr id="faq-row-<?= (int)$faq['id'] ?>">

                            <!-- Row number -->
                            <td class="text-muted small text-center"><?= $i + 1 ?></td>

                            <!-- Question — read mode -->
                            <td>
                                <div class="faq-view-question fw-medium small"><?= e($faq['question']) ?></div>
                                <input type="text"
                                       class="faq-edit-question form-control form-control-sm d-none"
                                       value="<?= eAttr($faq['question']) ?>"
                                       placeholder="Question" maxlength="500">
                            </td>

                            <!-- Answer — read mode -->
                            <td>
                                <div class="faq-view-answer text-muted small"
                                     style="white-space:pre-wrap;max-height:80px;overflow:hidden"><?= e($faq['answer']) ?></div>
                                <textarea class="faq-edit-answer form-control form-control-sm d-none"
                                          rows="3" placeholder="Answer"><?= e($faq['answer']) ?></textarea>
                            </td>

                            <!-- Sort order -->
                            <td class="text-center">
                                <span class="faq-view-order badge bg-light text-dark border"><?= (int)$faq['sort_order'] ?></span>
                                <input type="number"
                                       class="faq-edit-order form-control form-control-sm d-none text-center"
                                       value="<?= (int)$faq['sort_order'] ?>" min="0" max="999" style="width:60px">
                            </td>

                            <!-- Actions -->
                            <td class="text-center">
                                <!-- VIEW mode buttons -->
                                <div class="faq-actions-view d-flex gap-1 justify-content-center">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary py-0 px-2 faq-edit-btn"
                                            data-id="<?= (int)$faq['id'] ?>"
                                            title="Edit this FAQ">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                    <!-- Delete -->
                                    <form method="post" class="d-inline"
                                          onsubmit="return confirm('Delete this FAQ?')">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action"      value="delete">
                                        <input type="hidden" name="faq_id"      value="<?= (int)$faq['id'] ?>">
                                        <input type="hidden" name="stay_tool_id" value="<?= $selectedTool ?>">
                                        <button type="submit"
                                                class="btn btn-sm btn-outline-danger py-0 px-2"
                                                title="Delete this FAQ">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>

                                <!-- EDIT mode buttons (hidden initially) -->
                                <div class="faq-actions-edit d-none d-flex gap-1 justify-content-center">
                                    <!-- Save -->
                                    <form method="post" class="faq-save-form d-inline">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action"       value="update">
                                        <input type="hidden" name="faq_id"       value="<?= (int)$faq['id'] ?>">
                                        <input type="hidden" name="stay_tool_id" value="<?= $selectedTool ?>">
                                        <input type="hidden" name="question"     class="faq-hidden-question">
                                        <input type="hidden" name="answer"       class="faq-hidden-answer">
                                        <input type="hidden" name="sort_order"   class="faq-hidden-order">
                                        <button type="submit"
                                                class="btn btn-sm btn-success py-0 px-2"
                                                title="Save changes">
                                            <i class="bi bi-check-lg"></i> Save
                                        </button>
                                    </form>
                                    <!-- Cancel -->
                                    <button type="button"
                                            class="btn btn-sm btn-outline-secondary py-0 px-2 faq-cancel-btn"
                                            data-id="<?= (int)$faq['id'] ?>"
                                            title="Cancel editing">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php endif; ?>
            </div>
        </div>

        <!-- ── Add new FAQ ──────────────────────────────────────────────────── -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h2 class="h6 fw-bold mb-0">
                    <i class="bi bi-plus-circle me-2 text-success"></i>Add New FAQ
                </h2>
            </div>
            <div class="card-body">
                <form method="post" id="addFaqForm">
                    <?= csrfField() ?>
                    <input type="hidden" name="action"      value="add">
                    <input type="hidden" name="tool_id"     value="<?= $selectedTool ?>">
                    <input type="hidden" name="stay_tool_id" value="<?= $selectedTool ?>">

                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label fw-medium">Question <span class="text-danger">*</span></label>
                            <input type="text" name="question" id="newQuestion"
                                   class="form-control" placeholder="e.g. What file formats are supported?"
                                   required maxlength="500">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-medium">Answer <span class="text-danger">*</span></label>
                            <textarea name="answer" id="newAnswer" rows="3"
                                      class="form-control" placeholder="Write the answer here…"
                                      required></textarea>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label fw-medium">Order</label>
                            <input type="number" name="sort_order" class="form-control"
                                   value="<?= count($faqs) ?>" min="0" max="999">
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php endif; ?>
    </div><!-- /col-lg-9 -->
</div><!-- /row -->

<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Enter edit mode ───────────────────────────────────────────────────────
    document.querySelectorAll('.faq-edit-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id  = this.dataset.id;
            var row = document.getElementById('faq-row-' + id);
            if (!row) return;

            // Show editable fields
            row.querySelector('.faq-view-question').classList.add('d-none');
            row.querySelector('.faq-view-answer').classList.add('d-none');
            row.querySelector('.faq-view-order').classList.add('d-none');
            row.querySelector('.faq-edit-question').classList.remove('d-none');
            row.querySelector('.faq-edit-answer').classList.remove('d-none');
            row.querySelector('.faq-edit-order').classList.remove('d-none');

            // Swap action buttons
            row.querySelector('.faq-actions-view').classList.add('d-none');
            row.querySelector('.faq-actions-edit').classList.remove('d-none');

            // Highlight row
            row.classList.add('table-warning');

            // Focus question field
            row.querySelector('.faq-edit-question').focus();
        });
    });

    // ── Cancel edit mode ──────────────────────────────────────────────────────
    document.querySelectorAll('.faq-cancel-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id  = this.dataset.id;
            var row = document.getElementById('faq-row-' + id);
            if (!row) return;
            exitEditMode(row);
        });
    });

    function exitEditMode(row) {
        // Restore display fields
        row.querySelector('.faq-view-question').classList.remove('d-none');
        row.querySelector('.faq-view-answer').classList.remove('d-none');
        row.querySelector('.faq-view-order').classList.remove('d-none');
        row.querySelector('.faq-edit-question').classList.add('d-none');
        row.querySelector('.faq-edit-answer').classList.add('d-none');
        row.querySelector('.faq-edit-order').classList.add('d-none');

        // Swap buttons back
        row.querySelector('.faq-actions-view').classList.remove('d-none');
        row.querySelector('.faq-actions-edit').classList.add('d-none');

        // Remove highlight
        row.classList.remove('table-warning');
    }

    // ── Copy editable values to hidden inputs before submit ───────────────────
    document.querySelectorAll('.faq-save-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var row = form.closest('tr');
            if (!row) return;

            var question = row.querySelector('.faq-edit-question').value.trim();
            var answer   = row.querySelector('.faq-edit-answer').value.trim();
            var order    = row.querySelector('.faq-edit-order').value;

            if (!question || !answer) {
                e.preventDefault();
                alert('Question and answer cannot be empty.');
                return;
            }

            form.querySelector('.faq-hidden-question').value = question;
            form.querySelector('.faq-hidden-answer').value   = answer;
            form.querySelector('.faq-hidden-order').value    = order;
        });
    });

    // ── Clear add form after submit ───────────────────────────────────────────
    var addForm = document.getElementById('addFaqForm');
    if (addForm) {
        addForm.addEventListener('submit', function () {
            // Browser will reload — nothing needed
        });
    }

});
</script>

<style>
/* Hover background for tool list links */
a.hover-bg:hover { background: #f0f4ff; }

/* Make edit inputs match cell height nicely */
#faqTable td { vertical-align: middle; padding: .5rem .75rem; }
#faqTable .faq-edit-question,
#faqTable .faq-edit-answer  { min-width: 120px; }
#faqTable .faq-edit-order   { width: 60px; margin: 0 auto; }
</style>

<?php
$adminContent = ob_get_clean();
require_once __DIR__ . '/layout.php';
