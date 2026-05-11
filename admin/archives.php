<?php
require_once __DIR__.'/../includes/config.php';
start_admin_session(); require_admin();

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $action=trim($_POST['action']??'');
    $rid=trim($_POST['record_id']??'');

    if ($action==='restore' && $rid) {
        db_execute('UPDATE records SET is_deleted=0,deleted_at=NULL,deleted_by=NULL,delete_reason=NULL WHERE record_id=?',[$rid]);
        flash('success',"Record <strong>{$rid}</strong> restored successfully.");
    }
    if ($action==='permanent_delete' && $rid) {
        // Move to archives table before deleting
        $rec=db_fetch_one('SELECT r.*,res.name,res.contact FROM records r JOIN residents res ON r.resident_id=res.resident_id WHERE r.record_id=?',[$rid]);
        if ($rec) {
            db_execute('INSERT IGNORE INTO archives (record_id,resident_id,resident_name,contact,record_type,category,details,status,date_submitted,deleted_by,delete_reason,archived_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())',
                [$rec['record_id'],$rec['resident_id'],$rec['name'],$rec['contact'],
                 $rec['record_type'],$rec['category'],$rec['details']??'',$rec['status'],
                 $rec['date_submitted'],$rec['deleted_by']??'Admin',$rec['delete_reason']??'']);
        }
        db_execute('DELETE FROM notifications WHERE record_id=?',[$rid]);
        db_execute('DELETE FROM records WHERE record_id=?',[$rid]);
        require_once __DIR__.'/../includes/backup.php'; run_backup('archive');
        flash('success',"Record <strong>{$rid}</strong> permanently deleted and archived. Backup saved.");
    }
    if ($action==='empty_trash') {
        $deleted=db_fetch_all('SELECT r.*,res.name,res.contact FROM records r JOIN residents res ON r.resident_id=res.resident_id WHERE r.is_deleted=1');
        foreach ($deleted as $rec) {
            db_execute('INSERT IGNORE INTO archives (record_id,resident_id,resident_name,contact,record_type,category,details,status,date_submitted,deleted_by,delete_reason,archived_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())',
                [$rec['record_id'],$rec['resident_id'],$rec['name'],$rec['contact'],
                 $rec['record_type'],$rec['category'],$rec['details']??'',$rec['status'],
                 $rec['date_submitted'],$rec['deleted_by']??'Admin',$rec['delete_reason']??'']);
            db_execute('DELETE FROM notifications WHERE record_id=?',[$rec['record_id']]);
        }
        db_execute('DELETE FROM records WHERE is_deleted=1');
        require_once __DIR__.'/../includes/backup.php'; run_backup('empty_trash');
        flash('success','All trash records permanently deleted and archived. Backup saved.');
    }
    header('Location: /BarangayProject/admin/archives.php'); exit;
}

$search=trim($_GET['q']??''); $type_f=$_GET['type']??'All';
$page=max(1,(int)($_GET['page']??1));

// Create archives table if not exists
db()->query('CREATE TABLE IF NOT EXISTS archives (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_id VARCHAR(50) UNIQUE,
    resident_id VARCHAR(50),
    resident_name VARCHAR(200),
    contact VARCHAR(30),
    record_type VARCHAR(20),
    category VARCHAR(200),
    details TEXT,
    status VARCHAR(30),
    date_submitted DATETIME,
    deleted_by VARCHAR(100),
    delete_reason VARCHAR(255),
    archived_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

// Trash (soft-deleted records)
$trash_sql='SELECT r.*,res.name,res.contact FROM records r JOIN residents res ON r.resident_id=res.resident_id WHERE r.is_deleted=1';
$trash_params=[];
if ($search) { $trash_sql.=' AND (r.record_id LIKE ? OR res.name LIKE ? OR r.category LIKE ?)'; $like="%{$search}%"; $trash_params=[$like,$like,$like]; }
if ($type_f!=='All') { $trash_sql.=' AND r.record_type=?'; $trash_params[]=strtolower($type_f); }
$trash_sql.=' ORDER BY r.deleted_at DESC';
$trash_all=db_fetch_all($trash_sql,$trash_params);
$pg_trash=paginate($trash_all,PER_PAGE,$page);

// Archives (permanently deleted)
$arch_sql='SELECT * FROM archives WHERE 1';
$arch_params=[];
if ($search) { $arch_sql.=' AND (record_id LIKE ? OR resident_name LIKE ? OR category LIKE ?)'; $like="%{$search}%"; $arch_params=[$like,$like,$like]; }
$arch_sql.=' ORDER BY archived_at DESC';
$arch_all=db_fetch_all($arch_sql,$arch_params);
$pg_arch=paginate($arch_all,PER_PAGE,max(1,(int)($_GET['apage']??1)));

$base='?q='.urlencode($search).'&type='.urlencode($type_f);
$flash=get_flash(); $page_title='Archives & Trash';
include __DIR__.'/../includes/header.php';
include __DIR__.'/../includes/admin_sidebar.php';
?>
<div class="main-content">
  <div class="topbar">
    <div><h5><i class="bi bi-archive me-2"></i>Archives & Trash</h5><small>Manage deleted records and permanent archives</small></div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
      <span class="badge bg-warning text-dark"><?= count($trash_all) ?> in trash</span>
      <span class="badge bg-secondary"><?= count($arch_all) ?> archived</span>
      <?php if(!empty($trash_all)): ?>
      <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#emptyTrashModal">
        <i class="bi bi-trash3 me-1"></i>Empty Trash
      </button>
      <?php endif; ?>
    </div>
  </div>
  <div class="p-4">
    <?php if($flash): ?>
    <div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?> alert-dismissible fade show">
      <?= $flash['msg'] ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Search & Filter -->
    <div class="card mb-3"><div class="card-body py-2">
      <form method="get" class="d-flex gap-2 flex-wrap align-items-center">
        <div class="input-group input-group-sm" style="max-width:280px;">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input type="text" name="q" class="form-control" placeholder="Search ID, name, category…" value="<?= e($search) ?>">
        </div>
        <select name="type" class="form-select form-select-sm" style="max-width:130px;">
          <?php foreach(['All','Request','Complaint'] as $o): ?><option <?= $type_f===$o?'selected':'' ?>><?= $o ?></option><?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Filter</button>
        <?php if($search||$type_f!=='All'): ?><a href="/BarangayProject/admin/archives.php" class="btn btn-outline-secondary btn-sm">Reset</a><?php endif; ?>
      </form>
    </div></div>

    <!-- TABS -->
    <ul class="nav nav-tabs mb-3" id="archiveTabs">
      <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tabTrash"><i class="bi bi-trash me-1"></i>Trash <span class="badge bg-warning text-dark ms-1"><?= count($trash_all) ?></span></a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabArchives"><i class="bi bi-archive me-1"></i>Archives <span class="badge bg-secondary ms-1"><?= count($arch_all) ?></span></a></li>
    </ul>

    <div class="tab-content">
      <!-- TRASH TAB -->
      <div class="tab-pane fade show active" id="tabTrash">
        <div class="card">
          <div class="card-header">
            <span><i class="bi bi-trash me-2"></i>Recently Deleted — Restorable</span>
            <?= pagination_html($pg_trash['pages'],$pg_trash['current'],$base) ?>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover mb-0" id="trashTable">
                <thead><tr><th>Record ID</th><th>Type</th><th>Category</th><th>Resident</th><th>Status</th><th>Deleted By</th><th>Reason</th><th>Deleted At</th><th>Actions</th></tr></thead>
                <tbody>
                  <?php if(empty($pg_trash['items'])): ?>
                  <tr><td colspan="9" class="text-center text-muted py-5"><i class="bi bi-trash fs-3 d-block mb-2"></i>Trash is empty.</td></tr>
                  <?php else: foreach($pg_trash['items'] as $r): ?>
                  <tr class="row-deleted">
                    <td><span class="record-id-chip"><?= e($r['record_id']) ?></span></td>
                    <td><?= $r['record_type']==='request'?"<span class='badge bg-primary'>Request</span>":"<span class='badge bg-danger'>Complaint</span>" ?></td>
                    <td style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($r['category']) ?></td>
                    <td><div class="fw-semibold" style="font-size:.82rem;"><?= e($r['name']) ?></div><small class="text-muted"><?= e($r['contact']) ?></small></td>
                    <td><?= status_badge($r['status']) ?></td>
                    <td><small><?= e($r['deleted_by']??'—') ?></small></td>
                    <td><small class="text-muted"><?= e($r['delete_reason']??'—') ?></small></td>
                    <td><small class="text-muted"><?= $r['deleted_at']?substr($r['deleted_at'],0,10):'—' ?></small></td>
                    <td>
                      <div class="d-flex gap-1">
                        <form method="post" style="margin:0;"><?= csrf_field() ?>
                          <input type="hidden" name="action" value="restore">
                          <input type="hidden" name="record_id" value="<?= e($r['record_id']) ?>">
                          <button type="submit" class="btn btn-sm btn-outline-success" title="Restore"><i class="bi bi-arrow-counterclockwise"></i></button>
                        </form>
                        <button class="btn btn-sm btn-outline-danger" title="Permanently Delete"
                          data-bs-toggle="modal" data-bs-target="#permDeleteModal"
                          data-rid="<?= e($r['record_id']) ?>">
                          <i class="bi bi-x-circle"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; endif; ?>
                </tbody>
              </table>
            </div>
            <?php if($pg_trash['pages']>1): ?>
            <div class="d-flex justify-content-between align-items-center px-4 py-3 border-top flex-wrap gap-2">
              <small class="text-muted">Showing <?= count($pg_trash['items']) ?> of <?= $pg_trash['total'] ?></small>
              <?= pagination_html($pg_trash['pages'],$pg_trash['current'],$base) ?>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- ARCHIVES TAB -->
      <div class="tab-pane fade" id="tabArchives">
        <div class="card">
          <div class="card-header">
            <span><i class="bi bi-archive me-2"></i>Permanent Archives — Read Only</span>
            <?= pagination_html($pg_arch['pages'],$pg_arch['current'],$base.'&apage=') ?>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover mb-0" id="archTable">
                <thead><tr><th>Record ID</th><th>Type</th><th>Category</th><th>Resident</th><th>Status</th><th>Deleted By</th><th>Reason</th><th>Archived At</th></tr></thead>
                <tbody>
                  <?php if(empty($pg_arch['items'])): ?>
                  <tr><td colspan="8" class="text-center text-muted py-5"><i class="bi bi-archive fs-3 d-block mb-2"></i>No archived records yet.</td></tr>
                  <?php else: foreach($pg_arch['items'] as $r): ?>
                  <tr>
                    <td><span class="record-id-chip"><?= e($r['record_id']) ?></span></td>
                    <td><?= $r['record_type']==='request'?"<span class='badge bg-primary'>Request</span>":"<span class='badge bg-danger'>Complaint</span>" ?></td>
                    <td style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($r['category']) ?></td>
                    <td><div class="fw-semibold" style="font-size:.82rem;"><?= e($r['resident_name']) ?></div><small class="text-muted"><?= e($r['contact']) ?></small></td>
                    <td><?= status_badge($r['status']) ?></td>
                    <td><small><?= e($r['deleted_by']??'—') ?></small></td>
                    <td><small class="text-muted"><?= e($r['delete_reason']??'—') ?></small></td>
                    <td><small class="text-muted"><?= substr($r['archived_at'],0,10) ?></small></td>
                  </tr>
                  <?php endforeach; endif; ?>
                </tbody>
              </table>
            </div>
            <?php if($pg_arch['pages']>1): ?>
            <div class="d-flex justify-content-between align-items-center px-4 py-3 border-top flex-wrap gap-2">
              <small class="text-muted">Showing <?= count($pg_arch['items']) ?> of <?= $pg_arch['total'] ?></small>
              <?= pagination_html($pg_arch['pages'],$pg_arch['current'],$base.'&apage=') ?>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Permanent Delete Modal -->
<div class="modal fade" id="permDeleteModal" tabindex="-1"><div class="modal-dialog modal-sm"><div class="modal-content">
  <div class="modal-header" style="background:#dc3545;color:#fff;border-radius:14px 14px 0 0;">
    <h5 class="modal-title" style="font-size:.95rem;"><i class="bi bi-x-circle me-2"></i>Permanently Delete?</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
  </div>
  <div class="modal-body">
    <p class="mb-0">This will <strong>permanently delete</strong> record <strong id="permRidDisplay"></strong> and save a copy to <strong>Archives</strong>. This cannot be undone.</p>
  </div>
  <div class="modal-footer">
    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
    <form method="post" style="margin:0;"><?= csrf_field() ?>
      <input type="hidden" name="action" value="permanent_delete">
      <input type="hidden" name="record_id" id="permRid">
      <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-x-circle me-1"></i>Delete & Archive</button>
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
    <p class="mb-0">All <strong><?= count($trash_all) ?> records</strong> in trash will be permanently deleted and moved to <strong>Archives</strong>.</p>
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
document.getElementById('permDeleteModal').addEventListener('show.bs.modal',e=>{
  const b=e.relatedTarget;
  document.getElementById('permRid').value=b.dataset.rid;
  document.getElementById('permRidDisplay').textContent=b.dataset.rid;
});
initLiveSearch('trashTable','trashTable');
</script>
<?php include __DIR__.'/../includes/footer.php'; ?>
