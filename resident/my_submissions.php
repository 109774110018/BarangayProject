<?php
require_once __DIR__.'/../includes/config.php';
start_resident_session(); require_resident();
$acc=current_resident();

$filter=$_GET['filter']??'all'; $search=trim($_GET['q']??''); $page=max(1,(int)($_GET['page']??1));
$sql='SELECT * FROM records WHERE resident_id=? AND (is_deleted IS NULL OR is_deleted=0)';
$params=[$acc['resident_id']??''];
if ($filter==='request')   $sql.=" AND record_type='request'";
if ($filter==='complaint')  $sql.=" AND record_type='complaint'";
if ($search) { $sql.=' AND (record_id LIKE ? OR category LIKE ?)'; $like="%{$search}%"; $params[]=$like; $params[]=$like; }
$sql.=' ORDER BY date_submitted DESC';
$all=db_fetch_all($sql,$params);
$pg=paginate($all,PER_PAGE,$page);
$base='?filter='.urlencode($filter).'&q='.urlencode($search);

$page_title='My Submissions';
include __DIR__.'/../includes/header.php';
include __DIR__.'/../includes/resident_sidebar.php';
?>
<div class="main-content">
  <div class="topbar">
    <div><h5><i class="bi bi-folder2-open me-2"></i>My Submissions</h5><small>All your requests and complaints</small></div>
    <div class="d-flex align-items-center gap-2">
      <span class="rt-dot" title="Real-time updates active"></span>
      <span class="badge bg-secondary"><?= $pg['total'] ?> total</span>
    </div>
  </div>
  <div class="p-4">
    <!-- Filter + Search -->
    <div class="card mb-3"><div class="card-body py-2">
      <form method="get" class="d-flex flex-wrap gap-2 align-items-center">
        <div class="btn-group btn-group-sm">
          <?php foreach(['all'=>'All','request'=>'Requests','complaint'=>'Complaints'] as $v=>$l): ?>
          <a href="?filter=<?= $v ?>&q=<?= urlencode($search) ?>" class="btn <?= $filter===$v?'btn-primary':'btn-outline-primary' ?>"><?= $l ?></a>
          <?php endforeach; ?>
        </div>
        <div class="input-group input-group-sm" style="max-width:240px;">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input type="text" name="q" class="form-control" placeholder="Search ID or category…" value="<?= e($search) ?>" id="subSearchInput">
          <input type="hidden" name="filter" value="<?= e($filter) ?>">
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Go</button>
        <?php if($search): ?><a href="?filter=<?= e($filter) ?>" class="btn btn-outline-secondary btn-sm">Clear</a><?php endif; ?>
      </form>
    </div></div>

    <?php if(empty($pg['items'])): ?>
    <div class="card"><div class="card-body text-center py-5 text-muted">
      <i class="bi bi-inbox fs-2 d-block mb-2"></i>No submissions yet.
      <a href="/BarangayProject/resident/submit_request.php">Submit a request</a> or <a href="/BarangayProject/resident/file_complaint.php">file a complaint</a>.
    </div></div>
    <?php else: ?>
    <div class="card">
      <div class="card-header">
        <span><i class="bi bi-folder2-open me-2"></i>Submissions</span>
        <?= pagination_html($pg['pages'],$pg['current'],$base) ?>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0" id="subTable">
            <thead><tr><th>Record ID</th><th>Type</th><th>Category</th><th>Status</th><th>Date Filed</th><th>Actions</th></tr></thead>
            <tbody>
              <?php foreach($pg['items'] as $r): ?>
              <tr data-record-id="<?= e($r['record_id']) ?>">
                <td>
                  <span class="record-id-chip" style="cursor:pointer;" onclick="copyId('<?= e($r['record_id']) ?>')" title="Click to copy">
                    <?= e($r['record_id']) ?> <i class="bi bi-copy ms-1" style="font-size:.62rem;opacity:.5;"></i>
                  </span>
                </td>
                <td><?= $r['record_type']==='request'?"<span class='badge bg-primary'>Request</span>":"<span class='badge bg-danger'>Complaint</span>" ?></td>
                <td style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($r['category']) ?></td>
                <td><span class="badge bg-<?= ['Pending'=>'warning','Approved'=>'primary','Done'=>'success','Rejected'=>'danger'][$r['status']]??'secondary' ?> rt-status-badge"><?= e($r['status']) ?></span></td>
                <td><small class="text-muted"><?= substr($r['date_submitted'],0,10) ?></small></td>
                <td>
                  <div class="d-flex gap-1">
                    <button class="btn btn-sm btn-outline-secondary"
                      data-bs-toggle="modal" data-bs-target="#detailModal"
                      data-rid="<?= e($r['record_id']) ?>" data-type="<?= e($r['record_type']) ?>"
                      data-cat="<?= e($r['category']) ?>" data-details="<?= e($r['details']??'—') ?>"
                      data-status="<?= e($r['status']) ?>" data-date="<?= substr($r['date_submitted'],0,16) ?>">
                      <i class="bi bi-eye"></i>
                    </button>
                    <a href="/BarangayProject/resident/view_copy.php?id=<?= urlencode($r['record_id']) ?>" target="_blank" class="btn btn-sm btn-gold" title="Download / Print Copy">
                      <i class="bi bi-file-earmark-arrow-down"></i>
                    </a>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php if($pg['pages']>1): ?>
        <div class="d-flex align-items-center justify-content-between px-4 py-3 border-top flex-wrap gap-2">
          <small class="text-muted">Showing <?= count($pg['items']) ?> of <?= $pg['total'] ?></small>
          <?= pagination_html($pg['pages'],$pg['current'],$base) ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header brand-header"><h5 class="modal-title"><i class="bi bi-file-text me-2"></i>Submission Details</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
  <div class="modal-body" id="detailBody"></div>
  <div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" id="copyBtn"><i class="bi bi-copy me-1"></i>Copy ID</button>
    <a id="downloadBtn" href="#" target="_blank" class="btn btn-gold"><i class="bi bi-file-earmark-arrow-down me-1"></i>Download Copy</a>
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
  </div>
</div></div></div>
<script>
let _curRid='';
document.getElementById('detailModal').addEventListener('show.bs.modal',e=>{
  const b=e.relatedTarget; _curRid=b.dataset.rid;
  const sc={Pending:'warning',Approved:'primary',Done:'success',Rejected:'danger'};
  document.getElementById('detailBody').innerHTML=`
    <div class="row g-3">
      <div class="col-6"><small class="text-muted fw-semibold d-block">Record ID</small><span class="record-id-chip mt-1">${b.dataset.rid}</span></div>
      <div class="col-6"><small class="text-muted fw-semibold d-block">Type</small><span class="text-capitalize mt-1 d-block">${b.dataset.type}</span></div>
      <div class="col-12"><small class="text-muted fw-semibold d-block">Category</small><span class="mt-1 d-block">${b.dataset.cat}</span></div>
      <div class="col-12"><small class="text-muted fw-semibold d-block">Details</small><span class="mt-1 d-block text-muted">${b.dataset.details}</span></div>
      <div class="col-6"><small class="text-muted fw-semibold d-block">Status</small><span class="badge bg-${sc[b.dataset.status]||'secondary'} mt-1">${b.dataset.status}</span></div>
      <div class="col-6"><small class="text-muted fw-semibold d-block">Date Filed</small><span class="mt-1 d-block text-muted">${b.dataset.date}</span></div>
    </div>`;
  document.getElementById('downloadBtn').href='/BarangayProject/resident/view_copy.php?id='+encodeURIComponent(b.dataset.rid);
});
document.getElementById('copyBtn').onclick=()=>copyId(_curRid);
initLiveSearch('subSearchInput','subTable');
</script>
<?php include __DIR__.'/../includes/footer.php'; ?>
