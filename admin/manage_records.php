<?php
require_once __DIR__.'/../includes/config.php';
start_admin_session(); require_admin();

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $action=trim($_POST['action']??'');
    $rid=trim($_POST['record_id']??'');
    if ($action==='update_status'&&$rid) {
        $ns=$_POST['new_status']??'Pending';
        $admin=current_admin();
        db_execute('UPDATE records SET status=? WHERE record_id=? AND is_deleted=0',[$ns,$rid]);
        db_execute('INSERT INTO notifications (record_id,message) VALUES (?,?)',[$rid,"Status updated to '{$ns}' by {$admin['full_name']} on ".date('Y-m-d H:i')]);
        flash('success',"Record <strong>{$rid}</strong> updated to <strong>{$ns}</strong>.");
    }
    if ($action==='soft_delete'&&$rid) {
        $admin=current_admin(); $reason=trim($_POST['delete_reason']??'No reason');
        db_execute('UPDATE records SET is_deleted=1,deleted_at=NOW(),deleted_by=?,delete_reason=? WHERE record_id=?',[$admin['full_name'],$reason,$rid]);
        flash('success',"Record <strong>{$rid}</strong> moved to Recently Deleted.");
    }
    header('Location: /BarangayProject/admin/manage_records.php'); exit;
}

$type_f=$_GET['type']??'All'; $status_f=$_GET['status']??'All';
$search=trim($_GET['q']??''); $page=max(1,(int)($_GET['page']??1));
$date_from=trim($_GET['date_from']??''); $date_to=trim($_GET['date_to']??'');
$sql='SELECT r.*,res.name,res.address,res.contact FROM records r JOIN residents res ON r.resident_id=res.resident_id WHERE r.is_deleted=0';
$params=[];
if ($type_f!=='All')  { $sql.=' AND r.record_type=?'; $params[]=strtolower($type_f); }
if ($status_f!=='All'){ $sql.=' AND r.status=?'; $params[]=$status_f; }
if ($search)          { $sql.=' AND (r.record_id LIKE ? OR res.name LIKE ? OR r.category LIKE ?)'; $like="%{$search}%"; $params=array_merge($params,[$like,$like,$like]); }
if ($date_from)       { $sql.=' AND DATE(r.date_submitted) >= ?'; $params[]=$date_from; }
if ($date_to)         { $sql.=' AND DATE(r.date_submitted) <= ?'; $params[]=$date_to; }
$sql.=' ORDER BY r.date_submitted DESC';
$all=db_fetch_all($sql,$params);
$pg=paginate($all,PER_PAGE,$page);
$records=$pg['items'];
$base='?type='.urlencode($type_f).'&status='.urlencode($status_f).'&q='.urlencode($search).'&date_from='.urlencode($date_from).'&date_to='.urlencode($date_to);
$trash_count=db_fetch_one('SELECT COUNT(*) c FROM records WHERE is_deleted=1')['c']??0;
$flash=get_flash(); $page_title='Manage Records';
include __DIR__.'/../includes/header.php';
include __DIR__.'/../includes/admin_sidebar.php';
?>
<div class="main-content">
  <div class="topbar">
    <div><h5><i class="bi bi-table me-2"></i>Manage Records</h5><small>Filter, update and manage all records</small></div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <span class="badge bg-secondary"><?= $pg['total'] ?> records</span>
      <?php if($trash_count>0): ?>
      <a href="/BarangayProject/admin/trash.php" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Trash <span class="badge bg-danger ms-1"><?= $trash_count ?></span></a>
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
    <div class="card mb-3"><div class="card-body py-3">
      <form method="get" class="row g-2 align-items-end">
        <div class="col-sm-4 col-12">
          <label class="form-label">Search</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" name="q" class="form-control" placeholder="ID, name, category…" value="<?= e($search) ?>" id="liveSearchInput">
          </div>
        </div>
        <div class="col-sm-2 col-6">
          <label class="form-label">Type</label>
          <select name="type" class="form-select form-select-sm">
            <?php foreach(['All','Request','Complaint'] as $o): ?><option <?= $type_f===$o?'selected':'' ?>><?= $o ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-sm-2 col-6">
          <label class="form-label">Status</label>
          <select name="status" class="form-select form-select-sm">
            <?php foreach(['All','Pending','Approved','Done','Rejected'] as $o): ?><option <?= $status_f===$o?'selected':'' ?>><?= $o ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-sm-2 col-6">
          <label class="form-label">Date From</label>
          <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($date_from) ?>">
        </div>
        <div class="col-sm-2 col-6">
          <label class="form-label">Date To</label>
          <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($date_to) ?>">
        </div>
        <div class="col-sm-auto col-12 d-flex gap-1 align-items-end">
          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Filter</button>
          <a href="/BarangayProject/admin/manage_records.php" class="btn btn-outline-secondary btn-sm">Reset</a>
        </div>
      </form>
    </div></div>
    <!-- Table -->
    <div class="card">
      <div class="card-header">
        <span><i class="bi bi-table me-2"></i>Records <?php if($search||$type_f!=='All'||$status_f!=='All'||$date_from||$date_to): ?>
          <span class="badge bg-warning text-dark ms-1">Filtered</span>
          <?php if($date_from||$date_to): ?>
          <span class="badge bg-info text-dark ms-1"><i class="bi bi-calendar3 me-1"></i><?= $date_from?:'...' ?> → <?= $date_to?:'...' ?></span>
          <?php endif; ?>
          <?php endif; ?></span>
        <?= pagination_html($pg['pages'],$pg['current'],$base) ?>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0" id="recordsTable">
            <thead><tr><th>Record ID</th><th>Type</th><th>Category</th><th>Resident</th><th>Contact</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
              <?php if(empty($records)): ?>
              <tr><td colspan="8" class="text-center text-muted py-5"><i class="bi bi-inbox fs-3 d-block mb-2"></i>No records found.</td></tr>
              <?php else: foreach($records as $r): ?>
              <tr id="row-<?= e($r['record_id']) ?>">
                <td><span class="record-id-chip"><?= e($r['record_id']) ?></span></td>
                <td><?= $r['record_type']==='request'?"<span class='badge bg-primary'>Request</span>":"<span class='badge bg-danger'>Complaint</span>" ?></td>
                <td style="max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($r['category']) ?></td>
                <td><div class="fw-semibold" style="font-size:.83rem;"><?= e($r['name']) ?></div><small class="text-muted"><?= e($r['address']) ?></small></td>
                <td><small><?= e($r['contact']) ?></small></td>
                <td><?= status_badge($r['status']) ?></td>
                <td><small class="text-muted"><?= substr($r['date_submitted'],0,10) ?></small></td>
                <td>
                  <div class="d-flex gap-1 flex-wrap">
                    <button class="btn btn-sm btn-outline-primary" title="Update Status"
                      data-bs-toggle="modal" data-bs-target="#updateModal"
                      data-rid="<?= e($r['record_id']) ?>" data-status="<?= e($r['status']) ?>">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" title="View Details"
                      data-bs-toggle="modal" data-bs-target="#viewModal"
                      data-rid="<?= e($r['record_id']) ?>" data-type="<?= e($r['record_type']) ?>"
                      data-category="<?= e($r['category']) ?>" data-name="<?= e($r['name']) ?>"
                      data-address="<?= e($r['address']) ?>" data-contact="<?= e($r['contact']) ?>"
                      data-status="<?= e($r['status']) ?>" data-details="<?= e($r['details']??'—') ?>"
                      data-date="<?= substr($r['date_submitted'],0,16) ?>">
                      <i class="bi bi-eye"></i>
                    </button>
                    <a href="/BarangayProject/admin/print_copy.php?id=<?= urlencode($r['record_id']) ?>" target="_blank" class="btn btn-sm btn-gold" title="Print Copy"><i class="bi bi-printer"></i></a>
                    <button class="btn btn-sm btn-outline-danger" title="Move to Trash"
                      data-bs-toggle="modal" data-bs-target="#deleteModal"
                      data-rid="<?= e($r['record_id']) ?>">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
        <?php if($pg['pages']>1): ?>
        <div class="d-flex align-items-center justify-content-between px-4 py-3 border-top flex-wrap gap-2">
          <small class="text-muted">Showing <?= count($records) ?> of <?= $pg['total'] ?> records</small>
          <?= pagination_html($pg['pages'],$pg['current'],$base) ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<!-- Update Status Modal -->
<div class="modal fade" id="updateModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header brand-header"><h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Update Status</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="update_status">
    <div class="modal-body">
      <p class="text-muted small mb-1">Record ID</p>
      <p class="fw-bold mb-3" id="mRidDisplay"></p>
      <input type="hidden" name="record_id" id="mRid">
      <label class="form-label">New Status</label>
      <select name="new_status" id="mStatus" class="form-select">
        <?php foreach(['Pending','Approved','Done','Rejected'] as $s): ?><option><?= $s ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Update</button></div>
  </form>
</div></div></div>
<!-- View Modal -->
<div class="modal fade" id="viewModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
  <div class="modal-header brand-header"><h5 class="modal-title"><i class="bi bi-file-text me-2"></i>Record Details</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><div class="row g-3" id="viewContent"></div></div>
  <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><a id="viewPrintBtn" href="#" target="_blank" class="btn btn-gold"><i class="bi bi-printer me-1"></i>Print Copy</a></div>
</div></div></div>
<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header" style="background:#dc3545;color:#fff;border-radius:14px 14px 0 0;"><h5 class="modal-title"><i class="bi bi-trash me-2"></i>Move to Trash</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="soft_delete">
    <div class="modal-body">
      <div class="alert alert-warning d-flex gap-2 mb-3"><i class="bi bi-info-circle-fill mt-1"></i><span>This record will be moved to <strong>Recently Deleted</strong> and can be restored anytime.</span></div>
      <p>Record: <strong id="dRidDisplay"></strong></p>
      <input type="hidden" name="record_id" id="dRid">
      <label class="form-label">Reason (optional)</label>
      <input type="text" name="delete_reason" class="form-control" placeholder="e.g., Duplicate entry…">
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i>Move to Trash</button></div>
  </form>
</div></div></div>
<script>
document.getElementById('updateModal').addEventListener('show.bs.modal',e=>{
  const b=e.relatedTarget;
  document.getElementById('mRid').value=b.dataset.rid;
  document.getElementById('mRidDisplay').textContent=b.dataset.rid;
  document.getElementById('mStatus').value=b.dataset.status;
});
document.getElementById('viewModal').addEventListener('show.bs.modal',e=>{
  const b=e.relatedTarget;
  const fields=[['Record ID','<span class="record-id-chip">'+b.dataset.rid+'</span>'],['Type',b.dataset.type],['Category',b.dataset.category],['Resident',b.dataset.name],['Address',b.dataset.address],['Contact',b.dataset.contact],['Status',b.dataset.status],['Details',b.dataset.details],['Date Filed',b.dataset.date]];
  document.getElementById('viewContent').innerHTML=fields.map(([k,v])=>`<div class="col-md-6"><small class="text-muted fw-semibold d-block">${k}</small><span style="font-size:.9rem;">${v}</span></div>`).join('');
  document.getElementById('viewPrintBtn').href='/BarangayProject/admin/print_copy.php?id='+encodeURIComponent(b.dataset.rid);
});
document.getElementById('deleteModal').addEventListener('show.bs.modal',e=>{
  const b=e.relatedTarget;
  document.getElementById('dRid').value=b.dataset.rid;
  document.getElementById('dRidDisplay').textContent=b.dataset.rid;
});
initLiveSearch('liveSearchInput','recordsTable');
</script>
<?php include __DIR__.'/../includes/footer.php'; ?>
