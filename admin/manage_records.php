<?php
require_once __DIR__ . '/../includes/config.php';
require_admin();

// ── Handle POST actions ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $rid    = trim($_POST['record_id'] ?? '');

    if ($action === 'update_status' && $rid) {
        $new_status = $_POST['new_status'] ?? 'Pending';
        $admin      = current_admin();
        db_execute('UPDATE records SET status = ? WHERE record_id = ?', [$new_status, $rid]);
        $msg = "Status updated to '{$new_status}' by {$admin['full_name']} on "
             . date('Y-m-d H:i');
        db_execute('INSERT INTO notifications (record_id, message) VALUES (?,?)', [$rid, $msg]);
        flash('success', "Record <strong>{$rid}</strong> updated to <strong>{$new_status}</strong>.");
    }

    if ($action === 'delete' && $rid) {
        db_execute('DELETE FROM notifications WHERE record_id = ?', [$rid]);
        db_execute('DELETE FROM records WHERE record_id = ?', [$rid]);
        flash('success', "Record <strong>{$rid}</strong> has been deleted.");
    }

    header('Location: /BarangayProject/admin/manage_records.php');
    exit;
}

// ── Filters ─────────────────────────────────────────────────
$type_f   = $_GET['type']   ?? 'All';
$status_f = $_GET['status'] ?? 'All';
$search   = trim($_GET['q'] ?? '');
$highlight = $_GET['highlight'] ?? '';

$sql = '
    SELECT r.*, res.name, res.address, res.contact
    FROM records r
    JOIN residents res ON r.resident_id = res.resident_id
    WHERE 1=1
';
$params = [];
if ($type_f !== 'All') {
    $sql .= ' AND r.record_type = ?'; $params[] = strtolower($type_f);
}
if ($status_f !== 'All') {
    $sql .= ' AND r.status = ?'; $params[] = $status_f;
}
if ($search) {
    $sql .= ' AND (r.record_id LIKE ? OR res.name LIKE ? OR r.category LIKE ?)';
    $like = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like]);
}
$sql .= ' ORDER BY r.date_submitted DESC';
$records = db_fetch_all($sql, $params);

$flash = get_flash();
$page_title = 'Manage Records';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admin_sidebar.php';
?>

<div class="main-content">
  <div class="topbar">
    <div>
      <h5><i class="bi bi-table me-2"></i>Manage Records</h5>
      <small>Filter, update status, and delete records</small>
    </div>
    <span class="badge bg-secondary"><?= count($records) ?> records</span>
  </div>

  <div class="p-4">
    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible">
      <?= $flash['msg'] ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card mb-3">
      <div class="card-body py-3">
        <form method="get" class="row g-2 align-items-end">
          <div class="col-md-3">
            <label class="form-label mb-1">Search</label>
            <input type="text" name="q" class="form-control form-control-sm"
                   placeholder="ID, name, category…" value="<?= e($search) ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label mb-1">Type</label>
            <select name="type" class="form-select form-select-sm">
              <?php foreach (['All','Request','Complaint'] as $opt): ?>
              <option <?= $type_f === $opt ? 'selected' : '' ?>><?= $opt ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
              <?php foreach (['All','Pending','Approved','Done','Rejected'] as $opt): ?>
              <option <?= $status_f === $opt ? 'selected' : '' ?>><?= $opt ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-auto">
            <button type="submit" class="btn btn-primary btn-sm">
              <i class="bi bi-search me-1"></i>Filter
            </button>
            <a href="/BarangayProject/admin/manage_records.php" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
          </div>
        </form>
      </div>
    </div>

    <!-- Table -->
    <div class="card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0" id="recordsTable">
            <thead>
              <tr>
                <th>Record ID</th><th>Type</th><th>Category</th><th>Resident</th>
                <th>Contact</th><th>Status</th><th>Date</th><th style="width:180px">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($records)): ?>
              <tr><td colspan="8" class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-3 d-block mb-2"></i>No records found.
              </td></tr>
              <?php else: ?>
              <?php foreach ($records as $r):
                $isHL = $highlight === $r['record_id'];
              ?>
              <tr class="<?= $isHL ? 'table-warning' : '' ?>" id="row-<?= e($r['record_id']) ?>">
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
                <td><small><?= e($r['contact']) ?></small></td>
                <td><?= status_badge($r['status']) ?></td>
                <td><small class="text-muted"><?= substr($r['date_submitted'],0,16) ?></small></td>
                <td>
                  <div class="d-flex gap-1">
                    <!-- Update Status -->
                    <button class="btn btn-sm btn-outline-primary"
                            data-bs-toggle="modal"
                            data-bs-target="#updateModal"
                            data-rid="<?= e($r['record_id']) ?>"
                            data-status="<?= e($r['status']) ?>"
                            title="Update Status">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <!-- View Details -->
                    <button class="btn btn-sm btn-outline-secondary"
                            data-bs-toggle="modal"
                            data-bs-target="#viewModal"
                            data-rid="<?= e($r['record_id']) ?>"
                            data-type="<?= e($r['record_type']) ?>"
                            data-category="<?= e($r['category']) ?>"
                            data-name="<?= e($r['name']) ?>"
                            data-address="<?= e($r['address']) ?>"
                            data-contact="<?= e($r['contact']) ?>"
                            data-status="<?= e($r['status']) ?>"
                            data-details="<?= e($r['details'] ?? '—') ?>"
                            data-date="<?= substr($r['date_submitted'],0,16) ?>"
                            title="View Details">
                      <i class="bi bi-eye"></i>
                    </button>
                    <!-- Delete -->
                    <form method="post" class="d-inline"
                          onsubmit="return confirm('Delete record <?= e($r['record_id']) ?>? This cannot be undone.')">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="record_id" value="<?= e($r['record_id']) ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                        <i class="bi bi-trash"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ── Update Status Modal ── -->
<div class="modal fade" id="updateModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Update Status</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="post">
        <input type="hidden" name="action" value="update_status">
        <div class="modal-body">
          <p class="mb-1 text-muted">Record ID:</p>
          <p class="fw-bold" id="modal-rid-display"></p>
          <input type="hidden" name="record_id" id="modal-rid">
          <label class="form-label mt-2">New Status</label>
          <select name="new_status" id="modal-status" class="form-select">
            <?php foreach (['Pending','Approved','Done','Rejected'] as $s): ?>
            <option><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Status</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ── View Details Modal ── -->
<div class="modal fade" id="viewModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background:#1B5E20;color:#fff;">
        <h5 class="modal-title"><i class="bi bi-file-text me-2"></i>Record Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3" id="view-content"></div>
      </div>
    </div>
  </div>
</div>

<script>
// Update modal
document.getElementById('updateModal').addEventListener('show.bs.modal', e => {
  const btn = e.relatedTarget;
  document.getElementById('modal-rid').value         = btn.dataset.rid;
  document.getElementById('modal-rid-display').textContent = btn.dataset.rid;
  document.getElementById('modal-status').value      = btn.dataset.status;
});

// View modal
document.getElementById('viewModal').addEventListener('show.bs.modal', e => {
  const b = e.relatedTarget;
  const fields = [
    ['Record ID',  b.dataset.rid,      'record-id-chip'],
    ['Type',       b.dataset.type,     ''],
    ['Category',   b.dataset.category, ''],
    ['Resident',   b.dataset.name,     ''],
    ['Address',    b.dataset.address,  ''],
    ['Contact',    b.dataset.contact,  ''],
    ['Status',     b.dataset.status,   ''],
    ['Details',    b.dataset.details,  ''],
    ['Date Filed', b.dataset.date,     ''],
  ];
  document.getElementById('view-content').innerHTML = fields.map(([k,v,cls]) =>
    `<div class="col-md-6">
       <div class="text-muted small fw-semibold">${k}</div>
       <div class="${cls}" style="font-size:.9rem;margin-top:2px;">${v}</div>
     </div>`
  ).join('');
});

// Scroll to highlighted row
const hl = document.querySelector('.table-warning');
if (hl) hl.scrollIntoView({ behavior: 'smooth', block: 'center' });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
