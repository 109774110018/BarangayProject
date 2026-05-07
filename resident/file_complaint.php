<?php
require_once __DIR__.'/../includes/config.php';
start_resident_session(); require_resident();
$acc=current_resident();

$categories=['Noise Complaint','Illegal Construction','Garbage / Sanitation','Illegal Parking','Neighbor Dispute','Illegal Business','Peace & Order','Other'];
$errors=[]; $submitted=null;

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $category=trim($_POST['category']??''); $details=trim($_POST['details']??'');
    if (!$category) $errors['category']='Please select a complaint category.';
    if (!$details)  $errors['details']='Please describe the complaint.';
    if (!$errors) {
        $rid=$acc['resident_id'];
        if (!db_fetch_one('SELECT resident_id FROM residents WHERE resident_id=?',[$rid]))
            db_execute('INSERT INTO residents (resident_id,name,address,contact) VALUES (?,?,?,?)',[$rid,$acc['full_name'],$acc['address'],$acc['contact']]);
        $record_id='CMP-'.strtoupper(substr(uniqid(),-6));
        db_execute('INSERT INTO records (record_id,record_type,category,details,resident_id) VALUES (?,?,?,?,?)',[$record_id,'complaint',$category,$details,$rid]);
        $submitted=$record_id;
    }
}
$page_title='File Complaint';
include __DIR__.'/../includes/header.php';
include __DIR__.'/../includes/resident_sidebar.php';
?>
<div class="main-content">
  <div class="topbar"><div><h5><i class="bi bi-exclamation-triangle me-2"></i>File a Complaint</h5><small>Report barangay concerns</small></div></div>
  <div class="p-4">
    <?php if($submitted): ?>
    <div class="card">
      <div class="card-body text-center p-5">
        <div class="rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width:70px;height:70px;background:#FEE2E2;"><i class="bi bi-check-circle-fill" style="font-size:2.5rem;color:#E53935;"></i></div>
        <h4 class="fw-bold mb-2" style="color:#E53935;">Complaint Received!</h4>
        <p class="text-muted mb-3">Your complaint has been received and is now under review.</p>
        <div class="p-3 rounded-3 mb-4 d-inline-block" style="background:#FEE2E2;min-width:300px;">
          <div class="row g-2 text-start">
            <div class="col-6 text-muted small fw-semibold">Record ID</div><div class="col-6"><span class="record-id-chip"><?= e($submitted) ?></span></div>
            <div class="col-6 text-muted small fw-semibold">Category</div><div class="col-6 small"><?= e($_POST['category']??'') ?></div>
            <div class="col-6 text-muted small fw-semibold">Status</div><div class="col-6"><?= status_badge('Pending') ?></div>
          </div>
        </div>
        <div class="alert alert-warning d-inline-block mb-4"><i class="bi bi-exclamation-triangle me-1"></i><strong>Save your Record ID!</strong> You will be notified once the barangay reviews your complaint.</div>
        <div class="d-flex gap-2 justify-content-center flex-wrap">
          <a href="/BarangayProject/resident/my_submissions.php" class="btn btn-danger"><i class="bi bi-folder2-open me-1"></i>View My Submissions</a>
          <a href="/BarangayProject/resident/file_complaint.php" class="btn btn-outline-secondary"><i class="bi bi-plus me-1"></i>File Another</a>
        </div>
      </div>
    </div>
    <?php else: ?>
    <div class="row justify-content-center">
      <div class="col-lg-7">
        <div class="card">
          <div class="card-header" style="color:#E53935;"><i class="bi bi-exclamation-triangle me-2"></i>Complaint Form</div>
          <div class="card-body p-4">
            <?php if($errors): ?><div class="alert alert-danger"><i class="bi bi-exclamation-circle me-1"></i>Please fix the errors below.</div><?php endif; ?>
            <div class="alert alert-light border mb-4">
              <div class="row g-2">
                <div class="col-6"><small class="text-muted d-block">Complainant</small><strong><?= e($acc['full_name']) ?></strong></div>
                <div class="col-6"><small class="text-muted d-block">Contact</small><strong><?= e($acc['contact']) ?></strong></div>
                <div class="col-12"><small class="text-muted d-block">Address</small><strong><?= e($acc['address']) ?></strong></div>
              </div>
            </div>
            <form method="post" novalidate><?= csrf_field() ?>
              <div class="mb-3">
                <label class="form-label">Complaint Category <span class="text-danger">*</span></label>
                <select name="category" class="form-select <?= isset($errors['category'])?'is-invalid':'' ?>" required>
                  <option value="">— Select category —</option>
                  <?php foreach($categories as $cat): ?><option value="<?= e($cat) ?>" <?= ($_POST['category']??'')===$cat?'selected':'' ?>><?= e($cat) ?></option><?php endforeach; ?>
                </select>
                <?php if(isset($errors['category'])): ?><div class="invalid-feedback"><?= e($errors['category']) ?></div><?php endif; ?>
              </div>
              <div class="mb-4">
                <label class="form-label">Complaint Description <span class="text-danger">*</span></label>
                <textarea name="details" class="form-control <?= isset($errors['details'])?'is-invalid':'' ?>" rows="5" placeholder="Describe the complaint in detail — include location, time, and persons involved…" required><?= e($_POST['details']??'') ?></textarea>
                <?php if(isset($errors['details'])): ?><div class="invalid-feedback"><?= e($errors['details']) ?></div><?php endif; ?>
              </div>
              <button type="submit" class="btn w-100 py-2 fw-bold text-white" style="background:#E53935;"><i class="bi bi-send me-1"></i>Submit Complaint</button>
            </form>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__.'/../includes/footer.php'; ?>
