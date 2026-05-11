<?php
require_once __DIR__ . '/../includes/config.php';
start_admin_session(); require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $rid    = trim($_POST['record_id'] ?? '');

    if ($action === 'restore' && $rid) {
        db_execute('UPDATE records SET is_deleted=0,deleted_at=NULL,deleted_by=NULL,delete_reason=NULL WHERE record_id=?', [$rid]);
        flash('success', "Record <strong>{$rid}</strong> restored successfully.");
    }

    if ($action === 'archive_delete' && $rid) {
        require_once __DIR__ . '/../includes/backup.php';
        $rec = db_fetch_one('SELECT r.*,res.name,res.contact FROM records r JOIN residents res ON r.resident_id=res.resident_id WHERE r.record_id=?', [$rid]);
        if ($rec) {
            // Ensure archives table exists
            db()->query('CREATE TABLE IF NOT EXISTS archives (
                id INT AUTO_INCREMENT PRIMARY KEY,
                record_id VARCHAR(50) UNIQUE, resident_id VARCHAR(50),
                resident_name VARCHAR(200), contact VARCHAR(30),
                record_type VARCHAR(20), category VARCHAR(200), details TEXT,
                status VARCHAR(30), date_submitted DATETIME,
                deleted_by VARCHAR(100), delete_reason VARCHAR(255),
                archived_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
            db_execute('INSERT IGNORE INTO archives (record_id,resident_id,resident_name,contact,record_type,category,details,status,date_submitted,deleted_by,delete_reason,archived_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())',
                [$rec['record_id'],$rec['resident_id'],$rec['name'],$rec['contact'],
                 $rec['record_type'],$rec['category'],$rec['details']??'',$rec['status'],
                 $rec['date_submitted'],$rec['deleted_by']??'Admin',$rec['delete_reason']??'']);
        }
        db_execute('DELETE FROM notifications WHERE record_id=?', [$rid]);
        db_execute('DELETE FROM records WHERE record_id=?', [$rid]);
        run_backup('archive');
        flash('success', "Record <strong>{$rid}</strong> permanently archived. Backup saved.");
    }

    if ($action === 'empty_trash') {
        require_once __DIR__ . '/../includes/backup.php';
        db()->query('CREATE TABLE IF NOT EXISTS archives (
            id INT AUTO_INCREMENT PRIMARY KEY,
            record_id VARCHAR(50) UNIQUE, resident_id VARCHAR(50),
            resident_name VARCHAR(200), contact VARCHAR(30),
            record_type VARCHAR(20), category VARCHAR(200), details TEXT,
            status VARCHAR(30), date_submitted DATETIME,
            deleted_by VARCHAR(100), delete_reason VARCHAR(255),
            archived_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $deleted = db_fetch_all('SELECT r.*,res.name,res.contact FROM records r JOIN residents res ON r.resident_id=res.resident_id WHERE r.is_deleted=1');
        foreach ($deleted as $rec) {
            db_execute('INSERT IGNORE INTO archives (record_id,resident_id,resident_name,contact,record_type,category,details,status,date_submitted,deleted_by,delete_reason,archived_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())',
                [$rec['record_id'],$rec['resident_id'],$rec['name'],$rec['contact'],
                 $rec['record_type'],$rec['category'],$rec['details']??'',$rec['status'],
                 $rec['date_submitted'],$rec['deleted_by']??'Admin',$rec['delete_reason']??'']);
            db_execute('DELETE FROM notifications WHERE record_id=?', [$rec['record_id']]);
        }
        db_execute('DELETE FROM records WHERE is_deleted=1');
        run_backup('empty_trash');
        flash('success', 'All trash records archived permanently. Backup saved.');
    }

    header('Location: /BarangayProject/admin/trash.php'); exit;
}

$records = db_fetch_all('SELECT r.*,res.name,res.address,res.contact FROM records r JOIN residents res ON r.resident_id=res.resident_id WHERE r.is_deleted=1 ORDER BY r.deleted_at DESC');
$flash = get_flash();
$page_title = 'Recently Deleted';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admin_sidebar.php';
?>
<div class="main-content">
  <div class="topbar">
    <div><h5><i class="bi bi-trash me-2"></i>Recently Deleted</h5><small>Restore records or move them to Archives permanently</small></div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
      <span class="badge bg-danger"><?= count($records) ?> in trash</span>
      <?php if(!empty($records)): ?>
      <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#emptyTrashModal">
        <i class="bi bi-trash3 me-1"></i>Empty Trash
      </button>
      <?php endif; ?>
      <a href="/BarangayProject/admin/archives.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-archive me-1"></i>View Archives
      </a>
    </div>
  </div>
  <div class="p-4">
    <?php if($flash): ?>
    <div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?> alert-dismissible fade show">
      <?= $flash['msg'] ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="alert alert-info d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-info-circle-fill"></i>
      <span>Records here are <strong>soft-deleted</strong> — not permanently removed. <strong>Restore</strong> to bring them back, or <strong>Archive</strong> to permanently remove and save to Archives.</span>
    </div>

    <?php if(empty($records)): ?>
    <div class="card"><div class="card-body text-center py-5 text-muted">
      <i class="bi bi-trash fs-1 d-block mb-3" style="opacity:.3;"></i>
      <h5>Trash is Empty</h5>
      <a href="/BarangayProject/admin/manage_records.php" class="btn btn-primary mt-2"><i class="bi bi-table me-1"></i>Go to Manage Records</a>
    </div></div>
    <?php else: ?>
    <div class="card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead><tr>
              <th>Record ID</th><th>Type</th><th>Category</th><th>Resident</th>
              <th>Status</th><th>Deleted By</th><th>Deleted At</th><th>Actions</th>
            </tr></thead>
            <tbody>
              <?php foreach($records as $r): ?>
              <tr class="row-deleted">
                <td><span class="record-id-chip"><?= e($r['record_id']) ?></span></td>
                <td><?= $r['record_type']==='request'?"<span class='badge bg-primary'>Request</span>":"<span class='badge bg-danger'>Complaint</span>" ?></td>
                <td style="max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($r['category']) ?></td>
                <td><div class="fw-semibold" style="font-size:.83rem;"><?= e($r['name']) ?></div><small class="text-muted"><?= e($r['address']) ?></small></td>
                <td><?= status_badge($r['status']) ?></td>
                <td><small><?= e($r['deleted_by']??'—') ?></small></td>
                <td><small class="text-muted"><?= $r['deleted_at']?substr($r['deleted_at'],0,10):'—' ?></small></td>
                <td>
                  <div class="d-flex gap-1 flex-wrap">
                    <!-- RESTORE -->
                    <form method="post" style="margin:0;"><?= csrf_field() ?>
                      <input type="hidden" name="action" value="restore">
                      <input type="hidden" name="record_id" value="<?= e($r['record_id']) ?>">
                      <button type="submit" class="btn btn-sm btn-success" title="Restore">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Restore
                      </button>
                    </form>
                    <!-- ARCHIVE (permanent) -->
                    <button class="btn btn-sm btn-outline-danger" title="Archive Permanently"
                      data-bs-toggle="modal" data-bs-target="#archiveModal"
                      data-rid="<?= e($r['record_id']) ?>">
                      <i class="bi bi-archive me-1"></i>Archive
                    </button>
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

<!-- Archive Single Modal -->
<div class="modal fade" id="archiveModal" tabindex="-1"><div class="modal-dialog modal-sm"><div class="modal-content">
  <div class="modal-header" style="background:#dc3545;color:#fff;border-radius:14px 14px 0 0;">
    <h5 class="modal-title" style="font-size:.95rem;"><i class="bi bi-archive me-2"></i>Archive Permanently?</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
  </div>
  <div class="modal-body">
    <p class="mb-2">Record <strong id="archRidDisplay"></strong> will be <strong>permanently archived</strong>.</p>
    <div class="alert alert-warning py-2 mb-0" style="font-size:.8rem;"><i class="bi bi-info-circle me-1"></i>A backup will be saved automatically. Record can be viewed in Archives but cannot be restored.</div>
  </div>
  <div class="modal-footer">
    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
    <form method="post" style="margin:0;"><?= csrf_field() ?>
      <input type="hidden" name="action" value="archive_delete">
      <input type="hidden" name="record_id" id="archRid">
      <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-archive me-1"></i>Archive</button>
    </form>
  </div>
</div></div></div>

<!-- Empty Trash Modal -->
<div class="modal fade" id="emptyTrashModal" tabindex="-1"><div class="modal-dialog modal-sm"><div class="modal-content">
  <div class="modal-header" style="background:#dc3545;color:#fff;border-radius:14px 14px 0 0;">
    <h5 class="modal-title" style="font-size:.95rem;"><i class="bi bi-trash3 me-2"></i>Empty All Trash?</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
  </div>
  <div class="modal-body">
    <p class="mb-2">All <strong><?= count($records) ?> records</strong> in trash will be permanently archived.</p>
    <div class="alert alert-warning py-2 mb-0" style="font-size:.8rem;"><i class="bi bi-info-circle me-1"></i>A backup will be saved automatically before archiving.</div>
  </div>
  <div class="modal-footer">
    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
    <form method="post" style="margin:0;"><?= csrf_field() ?>
      <input type="hidden" name="action" value="empty_trash">
      <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-trash3 me-1"></i>Empty & Archive All</button>
    </form>
  </div>
</div></div></div>

<script>
document.getElementById('archiveModal').addEventListener('show.bs.modal',e=>{
  const b=e.relatedTarget;
  document.getElementById('archRid').value=b.dataset.rid;
  document.getElementById('archRidDisplay').textContent=b.dataset.rid;
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
