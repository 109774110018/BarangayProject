<?php
require_once __DIR__ . '/../includes/config.php';
start_resident_session();
require_resident();

$acc = current_resident();

$doc_types = [
    'Barangay Clearance',
    'Certificate of Indigency',
    'Certificate of Residency',
    'Business Permit',
    'Community Tax Certificate (CEDULA)',
    'Barangay ID',
    'Other Document',
];

$submitted = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doc_type = $_POST['doc_type'] ?? '';
    $details  = trim($_POST['details'] ?? '');

    if (!$doc_type) {
        $error = 'Please select a document type.';
    } else {
        // Ensure resident row exists
        $rid = $acc['resident_id'];
        if (!db_fetch_one('SELECT resident_id FROM residents WHERE resident_id = ?', [$rid])) {
            db_execute('INSERT INTO residents (resident_id, name, address, contact) VALUES (?,?,?,?)',
                       [$rid, $acc['full_name'], $acc['address'], $acc['contact']]);
        }
        $record_id = 'REQ-' . strtoupper(substr(uniqid(), -6));
        db_execute('INSERT INTO records (record_id, record_type, category, details, resident_id)
                    VALUES (?,?,?,?,?)',
                   [$record_id, 'request', $doc_type, $details, $rid]);
        $submitted = $record_id;
    }
}

$page_title = 'Submit Request';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/resident_sidebar.php';
?>

<div class="main-content">
  <div class="topbar">
    <div>
      <h5><i class="bi bi-file-earmark-plus me-2"></i>Submit a Request</h5>
      <small>Request official barangay documents</small>
    </div>
  </div>
  <div class="p-4">

    <?php if ($submitted): ?>
    <!-- Success Card -->
    <div class="card border-0" style="border-top:4px solid #1B5E20 !important;border-top-style:solid !important;">
      <div class="card-body text-center p-5">
        <div class="rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center"
             style="width:70px;height:70px;background:#E8F5E9;">
          <i class="bi bi-check-circle-fill" style="font-size:2.5rem;color:#1B5E20;"></i>
        </div>
        <h4 class="fw-bold text-success mb-2">Request Submitted!</h4>
        <p class="text-muted mb-3">Your document request has been received by the barangay office.</p>

        <div class="p-3 rounded-3 mb-4 d-inline-block" style="background:#E8F5E9;min-width:320px;">
          <div class="row g-2 text-start">
            <div class="col-6 text-muted small fw-semibold">Record ID</div>
            <div class="col-6"><span class="record-id-chip"><?= e($submitted) ?></span></div>
            <div class="col-6 text-muted small fw-semibold">Document</div>
            <div class="col-6 small"><?= e($_POST['doc_type'] ?? '') ?></div>
            <div class="col-6 text-muted small fw-semibold">Status</div>
            <div class="col-6"><?= status_badge('Pending') ?></div>
          </div>
        </div>

        <div class="alert alert-warning d-inline-block mb-4">
          <i class="bi bi-exclamation-triangle me-1"></i>
          <strong>Save your Record ID!</strong> Use it to track your request status.
        </div>

        <div class="d-flex gap-2 justify-content-center flex-wrap">
          <a href="/BarangayProject/resident/my_submissions.php" class="btn btn-primary">
            <i class="bi bi-folder2-open me-1"></i> View My Submissions
          </a>
          <a href="/BarangayProject/resident/submit_request.php" class="btn btn-outline-secondary">
            <i class="bi bi-plus me-1"></i> Submit Another
          </a>
        </div>
      </div>
    </div>

    <?php else: ?>
    <div class="row justify-content-center">
      <div class="col-lg-7">
        <div class="card">
          <div class="card-header">
            <i class="bi bi-file-earmark-plus me-2"></i>Document Request Form
          </div>
          <div class="card-body p-4">

            <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-1"></i><?= e($error) ?></div>
            <?php endif; ?>

            <!-- Resident Info (pre-filled, read-only) -->
            <div class="alert alert-light border mb-4">
              <div class="row g-2">
                <div class="col-6">
                  <small class="text-muted d-block">Name</small>
                  <strong><?= e($acc['full_name']) ?></strong>
                </div>
                <div class="col-6">
                  <small class="text-muted d-block">Contact</small>
                  <strong><?= e($acc['contact']) ?></strong>
                </div>
                <div class="col-12">
                  <small class="text-muted d-block">Address</small>
                  <strong><?= e($acc['address']) ?></strong>
                </div>
              </div>
            </div>

            <form method="post">
              <div class="mb-4">
                <label class="form-label">Document Type <span class="text-danger">*</span></label>
                <div class="row g-2">
                  <?php foreach ($doc_types as $dt): ?>
                  <div class="col-md-6">
                    <div class="form-check border rounded p-3 h-100"
                         style="cursor:pointer;"
                         onclick="document.getElementById('dt_<?= md5($dt) ?>').click()">
                      <input class="form-check-input" type="radio"
                             name="doc_type" id="dt_<?= md5($dt) ?>"
                             value="<?= e($dt) ?>"
                             <?= ($_POST['doc_type'] ?? '') === $dt ? 'checked' : '' ?>>
                      <label class="form-check-label w-100" for="dt_<?= md5($dt) ?>"
                             style="cursor:pointer;">
                        <?= e($dt) ?>
                      </label>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>

              <div class="mb-4">
                <label class="form-label">Purpose / Details <small class="text-muted">(optional)</small></label>
                <textarea name="details" class="form-control" rows="3"
                          placeholder="State the purpose of your request…"><?= e($_POST['details'] ?? '') ?></textarea>
              </div>

              <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                <i class="bi bi-send me-1"></i> Submit Request
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
