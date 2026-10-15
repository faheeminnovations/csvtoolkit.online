<?php
$pageTitle = 'Contact Messages';

if (isPost()) {
    verifyCsrf();
    if (isset($_POST['mark_read'])) {
        $id = (int)$_POST['msg_id'];
        dbExecute('UPDATE contact_messages SET is_read = 1 WHERE id = ?', [$id]);
        setFlash('success', 'Message marked as read.');
    } elseif (isset($_POST['delete_msg'])) {
        $id = (int)$_POST['msg_id'];
        dbExecute('DELETE FROM contact_messages WHERE id = ?', [$id]);
        setFlash('success', 'Message deleted.');
    }
    redirect(appBase() . '/admin/messages/');
}

$messages = dbFetchAll('SELECT * FROM contact_messages ORDER BY created_at DESC');

ob_start();
?>
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($messages)): ?>
        <div class="p-4 text-muted">No messages yet.</div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Name</th><th>Email</th><th>Phone</th><th>Subject</th><th>Date</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($messages as $msg): ?>
                <tr class="<?= !$msg['is_read'] ? 'fw-semibold' : '' ?>">
                    <td><?= e($msg['name']) ?></td>
                    <td class="small"><a href="mailto:<?= e($msg['email']) ?>"><?= e($msg['email']) ?></a></td>
                    <td class="small"><?= !empty($msg['phone']) ? e($msg['phone']) : '<span class="text-muted">—</span>' ?></td>
                    <td class="small"><?= e(truncate($msg['subject'], 50)) ?></td>
                    <td class="small text-muted"><?= e(date('M j Y', strtotime($msg['created_at']))) ?></td>
                    <td>
                        <span class="badge <?= $msg['is_read'] ? 'bg-secondary' : 'bg-primary' ?>">
                            <?= $msg['is_read'] ? 'Read' : 'Unread' ?>
                        </span>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2"
                                data-bs-toggle="modal"
                                data-bs-target="#msgModal"
                                data-name="<?= eAttr($msg['name']) ?>"
                                data-email="<?= eAttr($msg['email']) ?>"
                                data-subject="<?= eAttr($msg['subject']) ?>"
                                data-message="<?= eAttr($msg['message']) ?>"
                                data-id="<?= (int)$msg['id'] ?>"
                                data-read="<?= (int)$msg['is_read'] ?>">
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Message modal -->
<div class="modal fade" id="msgModal" tabindex="-1" aria-labelledby="msgModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="msgModalLabel">Message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <dl class="row small mb-0">
                    <dt class="col-sm-3">From</dt>   <dd class="col-sm-9" id="mName"></dd>
                    <dt class="col-sm-3">Email</dt>  <dd class="col-sm-9"><a id="mEmail" href="#"></a></dd>
                    <dt class="col-sm-3">Phone</dt>  <dd class="col-sm-9" id="mPhone"></dd>
                    <dt class="col-sm-3">Subject</dt><dd class="col-sm-9" id="mSubject"></dd>
                    <dt class="col-sm-3">Message</dt><dd class="col-sm-9"><pre id="mMessage" class="small mb-0" style="white-space:pre-wrap"></pre></dd>
                </dl>
            </div>
            <div class="modal-footer gap-2">
                <form method="post" id="markReadForm">
                    <?= csrfField() ?>
                    <input type="hidden" name="msg_id" id="mMarkId">
                    <button type="submit" name="mark_read" class="btn btn-sm btn-outline-success" id="mMarkBtn">
                        Mark as Read
                    </button>
                </form>
                <form method="post" onsubmit="return confirm('Delete this message?')">
                    <?= csrfField() ?>
                    <input type="hidden" name="msg_id" id="mDeleteId">
                    <button type="submit" name="delete_msg" class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('msgModal').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('mName').textContent    = btn.dataset.name;
    document.getElementById('mEmail').textContent   = btn.dataset.email;
    document.getElementById('mEmail').href          = 'mailto:' + btn.dataset.email;
    document.getElementById('mSubject').textContent = btn.dataset.subject;
    document.getElementById('mMessage').textContent = btn.dataset.message;
    document.getElementById('mMarkId').value        = btn.dataset.id;
    document.getElementById('mDeleteId').value      = btn.dataset.id;
    document.getElementById('mMarkBtn').disabled    = btn.dataset.read === '1';
});
</script>

<?php
$adminContent = ob_get_clean();
require_once __DIR__ . '/layout.php';
