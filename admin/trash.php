<?php
require_once __DIR__ . '/../includes/config.php';
require_admin();

// ── Handle POST actions ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $rid    = trim($_POST['record_id'] ?? '');

    if ($action === 'restore' && $rid) {
        db_execute(
            'UPDATE records SET is_deleted=0, deleted_at=NULL, deleted_by=NULL, delete_reason=NULL WHERE record_id=?',
            [$rid]
        );
        flash('success', "Record <strong>{$rid}</strong> has been <strong>restored</strong> successfully.");
    }

    if ($action === 'permanent_delete' && $rid) {
        db_execute('DELETE FROM notifications WHERE record_id = ?', [$rid]);
        db_execute('DELETE FROM records WHERE record_id = ?', [$rid]);
        flash('danger', "Record <strong>{$rid}</strong> has been permanently deleted.");
    }

    if ($action === 'empty_trash') {
        $deleted = db_fetch_all('SELECT record_id FROM records WHERE is_deleted = 1');
        foreach ($deleted as $d) {
            db_execute('DELETE FROM notifications WHERE record_id = ?', [$d['record_id']]);
        }
        db_execute('DELETE FROM records WHERE is_deleted = 1');
        flash('danger', 'All records in trash have been permanently deleted.');
    }

    header('Location: /BarangayProject/admin/trash.php');
    exit;
}

// ── Fetch deleted records ────────────────────────────────────
$records = db_fetch_all('
    SELECT r.*, res.name, res.address, res.contact
    FROM records r
    JOIN residents res ON r.resident_id = res.resident_id
    WHERE r.is_deleted = 1
    ORDER BY r.deleted_at DESC
');

$flash = get_flash();
$page_title = 'Recently Deleted';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admin_sidebar.php';
?>

<div class="main-content">
  <div class="topbar">
    <div>
      <h5><i class="bi bi-trash me-2"></i>Recently Deleted</h5>
      <small>Records moved to trash — restore or permanently delete</small>
    </div>
    <div class="d-flex gap-2 align-items-center">
      <span class="badge bg-danger"><?= count($records) ?> in trash</span>
      <?php if (!empty($records)): ?>
      <form method="post" onsubmit="return confirm('Permanently delete ALL records in trash? This CANNOT be undone.')">
        <input type="hidden" name="action" value="empty_trash">
        <button type="submit" class="btn btn-sm btn-danger">
          <i class="bi bi-trash3 me-1"></i>Empty Trash
        </button>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <div class="p-4">
    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible">
      <?= $flash['msg'] ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Info Banner -->
    <div class="alert alert-info d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-info-circle-fill fs-5"></i>
      <div>Records here are <strong>soft-deleted</strong> — not permanently removed.
           You can <strong>restore</strong> them anytime, or permanently delete them individually.
           <a href="/BarangayProject/admin/manage_records.php" class="alert-link ms-2">← Back to Active Records</a>
      </div>
    </div>

    <?php if (empty($records)): ?>
    <div class="card">
      <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-trash fs-1 d-block mb-3" style="opacity:.3;"></i>
        <h5>Trash is Empty</h5>
        <p class="mb-3">No records have been deleted recently.</p>
        <a href="/BarangayProject/admin/manage_records.php" class="btn btn-primary">
          <i class="bi bi-table me-1"></i>Go to Manage Records
        </a>
      </div>
    </div>
    <?php else: ?>
    <div class="card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>Record ID</th><th>Type</th><th>Category</th><th>Resident</th>
                <th>Status</th><th>Deleted By</th><th>Deleted At</th><th>Reason</th>
                <th style="width:180px">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($records as $r): ?>
              <tr>
                <td><span class="record-id-chip"><?= e($r['record_id']) ?></span></td>
                <td>
                  <?= $r['record_type'] === 'request'
                    ? "<span class='badge bg-primary'>Request</span>"
                    : "<span class='badge bg-danger'>Complaint</span>" ?>
                </td>
                <td><?= e($r['category']) ?></td>
                <td>
                  <div class="fw-semibold" style="font-size:.85rem;"><?= e($r['name']) ?></div>
                  <small class="text-muted"><?= e($r['address']) ?></small>
                </td>
                <td><?= status_badge($r['status']) ?></td>
                <td><small><?= e($r['deleted_by'] ?? '—') ?></small></td>
                <td><small class="text-muted"><?= $r['deleted_at'] ? substr($r['deleted_at'],0,16) : '—' ?></small></td>
                <td><small class="text-muted fst-italic"><?= e($r['delete_reason'] ?? '—') ?></small></td>
                <td>
                  <div class="d-flex gap-1">
                    <!-- Restore -->
                    <form method="post" class="d-inline">
                      <input type="hidden" name="action" value="restore">
                      <input type="hidden" name="record_id" value="<?= e($r['record_id']) ?>">
                      <button type="submit" class="btn btn-sm btn-success" title="Restore Record">
                        <i class="bi bi-arrow-counterclockwise"></i> Restore
                      </button>
                    </form>
                    <!-- View Copy -->
                    <a href="/BarangayProject/admin/print_copy.php?id=<?= urlencode($r['record_id']) ?>"
                       target="_blank" class="btn btn-sm btn-outline-secondary" title="View Document Copy">
                      <i class="bi bi-file-earmark-text"></i>
                    </a>
                    <!-- Permanent Delete -->
                    <form method="post" class="d-inline"
                          onsubmit="return confirm('PERMANENTLY delete record <?= e($r['record_id']) ?>? This cannot be undone.')">
                      <input type="hidden" name="action" value="permanent_delete">
                      <input type="hidden" name="record_id" value="<?= e($r['record_id']) ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger" title="Permanently Delete">
                        <i class="bi bi-x-circle"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
