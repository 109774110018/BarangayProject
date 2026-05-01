<?php
require_once __DIR__ . '/../includes/config.php';
start_resident_session();
require_resident();
$acc = current_resident();

$filter = $_GET['filter'] ?? 'all';
$sql = 'SELECT * FROM records WHERE resident_id = ? AND (is_deleted IS NULL OR is_deleted = 0) ORDER BY date_submitted DESC';
$records = db_fetch_all($sql, [$acc['resident_id'] ?? '']);

if ($filter === 'request')   $records = array_filter($records, fn($r) => $r['record_type'] === 'request');
if ($filter === 'complaint')  $records = array_filter($records, fn($r) => $r['record_type'] === 'complaint');

$page_title = 'My Submissions';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/resident_sidebar.php';
?>
<div class="main-content">
  <div class="topbar">
    <div>
      <h5><i class="bi bi-folder2-open me-2"></i>My Submissions</h5>
      <small>All your requests and complaints</small>
    </div>
    <span class="badge bg-secondary"><?= count($records) ?> total</span>
  </div>
  <div class="p-4">
    <!-- Filter Tabs -->
    <div class="mb-3">
      <div class="btn-group">
        <?php foreach (['all'=>'All','request'=>'Requests','complaint'=>'Complaints'] as $val=>$label): ?>
        <a href="?filter=<?= $val ?>"
           class="btn btn-sm <?= $filter===$val ? 'btn-primary' : 'btn-outline-primary' ?>">
          <?= $label ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>

    <?php if (empty($records)): ?>
    <div class="card"><div class="card-body text-center py-5 text-muted">
      <i class="bi bi-inbox fs-2 d-block mb-2"></i>
      No submissions yet. <a href="/BarangayProject/resident/submit_request.php">Submit a request</a> or <a href="/BarangayProject/resident/file_complaint.php">file a complaint</a>.
    </div></div>
    <?php else: ?>
    <div class="card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead><tr>
              <th>Record ID</th><th>Type</th><th>Category</th>
              <th>Status</th><th>Date Filed</th><th>Action</th>
            </tr></thead>
            <tbody>
              <?php foreach ($records as $r): ?>
              <tr>
                <td>
                  <span class="record-id-chip" style="cursor:pointer;"
                        onclick="copyId('<?= e($r['record_id']) ?>')"
                        title="Click to copy">
                    <?= e($r['record_id']) ?> <i class="bi bi-copy ms-1" style="font-size:.7rem;"></i>
                  </span>
                </td>
                <td><?= $r['record_type']==='request'
                  ? "<span class='badge bg-primary'>Request</span>"
                  : "<span class='badge bg-danger'>Complaint</span>" ?></td>
                <td><?= e($r['category']) ?></td>
                <td><?= status_badge($r['status']) ?></td>
                <td><small class="text-muted"><?= substr($r['date_submitted'],0,16) ?></small></td>
                <td>
                  <button class="btn btn-sm btn-outline-secondary"
                          data-bs-toggle="modal" data-bs-target="#detailModal"
                          data-rid="<?= e($r['record_id']) ?>"
                          data-type="<?= e($r['record_type']) ?>"
                          data-cat="<?= e($r['category']) ?>"
                          data-details="<?= e($r['details'] ?? '—') ?>"
                          data-status="<?= e($r['status']) ?>"
                          data-date="<?= substr($r['date_submitted'],0,16) ?>">
                    <i class="bi bi-eye"></i> View
                  </button>
                  <a href="/BarangayProject/resident/view_copy.php?id=<?= urlencode($r['record_id']) ?>"
                     target="_blank" class="btn btn-sm btn-gold ms-1" title="View Document Copy">
                    <i class="bi bi-file-earmark-text"></i>
                  </a>
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

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" class="brand-header">
        <h5 class="modal-title"><i class="bi bi-file-text me-2"></i>Submission Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="detail-body"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="copy-btn">
          <i class="bi bi-copy me-1"></i> Copy Record ID
        </button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3">
  <div id="copyToast" class="toast align-items-center text-bg-success border-0" role="alert">
    <div class="d-flex">
      <div class="toast-body"><i class="bi bi-check-circle me-1"></i> Record ID copied!</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<script>
let currentRid = '';
document.getElementById('detailModal').addEventListener('show.bs.modal', e => {
  const b = e.relatedTarget;
  currentRid = b.dataset.rid;
  document.getElementById('detail-body').innerHTML = `
    <div class="row g-3">
      <div class="col-6"><small class="text-muted fw-semibold">Record ID</small>
        <div class="record-id-chip mt-1">${b.dataset.rid}</div></div>
      <div class="col-6"><small class="text-muted fw-semibold">Type</small>
        <div class="mt-1 text-capitalize">${b.dataset.type}</div></div>
      <div class="col-12"><small class="text-muted fw-semibold">Category</small>
        <div class="mt-1">${b.dataset.cat}</div></div>
      <div class="col-12"><small class="text-muted fw-semibold">Details</small>
        <div class="mt-1 text-muted">${b.dataset.details}</div></div>
      <div class="col-6"><small class="text-muted fw-semibold">Status</small>
        <div class="mt-1">${b.dataset.status}</div></div>
      <div class="col-6"><small class="text-muted fw-semibold">Date Filed</small>
        <div class="mt-1 text-muted">${b.dataset.date}</div></div>
    </div>`;
});
document.getElementById('copy-btn').onclick = () => copyId(currentRid);

function copyId(rid) {
  navigator.clipboard.writeText(rid).then(() => {
    new bootstrap.Toast(document.getElementById('copyToast')).show();
  });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
