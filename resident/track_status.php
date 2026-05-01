<?php
require_once __DIR__ . '/../includes/config.php';
start_resident_session();
require_resident();

$record   = null;
$notifs   = [];
$error    = '';
$searched = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['id'])) {
    $rid      = strtoupper(trim($_POST['record_id'] ?? $_GET['id'] ?? ''));
    $searched = true;
    if (!$rid) {
        $error = 'Please enter a Record ID.';
    } else {
        $record = db_fetch_one('
            SELECT r.*, res.name, res.address, res.contact
            FROM records r
            JOIN residents res ON r.resident_id = res.resident_id
            WHERE r.record_id = ?
        ', [$rid]);
        if (!$record) {
            $error = "No record found for ID: <strong>" . e($rid) . "</strong>";
        } else {
            $notifs = db_fetch_all(
                'SELECT * FROM notifications WHERE record_id = ? ORDER BY notif_date DESC',
                [$rid]
            );
        }
    }
}

$page_title = 'Track Status';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/resident_sidebar.php';
?>

<div class="main-content">
  <div class="topbar">
    <div>
      <h5><i class="bi bi-search me-2"></i>Track Status</h5>
      <small>Check the status of any record using its ID</small>
    </div>
  </div>
  <div class="p-4">

    <!-- Search Form -->
    <div class="row justify-content-center mb-4">
      <div class="col-lg-6">
        <div class="card">
          <div class="card-body p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-search me-2"></i>Enter Record ID</h6>
            <form method="post" class="d-flex gap-2">
              <input type="text" name="record_id" class="form-control"
                     placeholder="e.g. REQ-ABC123 or CMP-XYZ789"
                     value="<?= e($_POST['record_id'] ?? $_GET['id'] ?? '') ?>" required>
              <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-search"></i>
              </button>
            </form>
            <small class="text-muted mt-2 d-block">
              You can find your Record ID in <a href="/BarangayProject/resident/my_submissions.php">My Submissions</a>.
            </small>
          </div>
        </div>
      </div>
    </div>

    <?php if ($searched && $error): ?>
    <div class="row justify-content-center">
      <div class="col-lg-6">
        <div class="alert alert-danger text-center">
          <i class="bi bi-exclamation-circle me-1"></i> <?= $error ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($record):
      $status = $record['status'];
      $steps  = ['Pending', 'Approved', 'Done'];
      $cur    = array_search($status, $steps);
      if ($status === 'Rejected') $cur = -1;
    ?>
    <div class="row justify-content-center">
      <div class="col-lg-7">

        <!-- Status Card -->
        <div class="card mb-3">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-file-text me-2"></i>Record Details</span>
            <?= status_badge($status) ?>
          </div>
          <div class="card-body">
            <div class="row g-3 mb-3">
              <?php foreach ([
                ['Record ID',  '<span class="record-id-chip">' . e($record['record_id']) . '</span>'],
                ['Type',       ucfirst($record['record_type'])],
                ['Category',   e($record['category'])],
                ['Resident',   e($record['name'])],
                ['Contact',    e($record['contact'])],
                ['Date Filed', substr($record['date_submitted'], 0, 16)],
              ] as [$k, $v]): ?>
              <div class="col-md-6">
                <small class="text-muted fw-semibold d-block"><?= $k ?></small>
                <span style="font-size:.9rem;"><?= $v ?></span>
              </div>
              <?php endforeach; ?>
              <?php if ($record['details']): ?>
              <div class="col-12">
                <small class="text-muted fw-semibold d-block">Details</small>
                <span style="font-size:.9rem;"><?= e($record['details']) ?></span>
              </div>
              <?php endif; ?>
            </div>

            <!-- Progress Steps -->
            <?php if ($status !== 'Rejected'): ?>
            <div class="d-flex align-items-center justify-content-between my-3 position-relative">
              <div class="position-absolute" style="height:3px;background:#E0E0E0;left:10%;right:10%;top:20px;z-index:0;"></div>
              <div class="position-absolute" style="height:3px;background:#1B5E20;
                   left:10%;width:<?= $cur === 0 ? '0' : ($cur === 1 ? '40%' : '80%') ?>;top:20px;z-index:1;transition:width .5s;"></div>
              <?php foreach ($steps as $i => $step):
                $done   = $i <= $cur;
                $active = $i === $cur;
              ?>
              <div class="text-center" style="z-index:2;flex:1;">
                <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center fw-bold"
                     style="width:40px;height:40px;
                            background:<?= $done ? '#1B5E20' : '#E0E0E0' ?>;
                            color:<?= $done ? '#fff' : '#999' ?>;
                            border:3px solid <?= $active ? '#1B5E20' : ($done ? '#1B5E20' : '#E0E0E0') ?>;
                            font-size:.85rem;">
                  <?= $done ? '<i class="bi bi-check-lg"></i>' : ($i + 1) ?>
                </div>
                <small class="d-block mt-1 fw-<?= $active ? 'bold' : 'normal' ?>"
                       style="color:<?= $done ? '#1B5E20' : '#999' ?>;font-size:.75rem;">
                  <?= $step ?>
                </small>
              </div>
              <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="alert alert-danger mt-3">
              <i class="bi bi-x-circle me-1"></i> This record has been <strong>Rejected</strong>.
              Please contact the barangay office for more information.
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Notifications -->
        <?php if (!empty($notifs)): ?>
        <div class="card">
          <div class="card-header"><i class="bi bi-bell me-2"></i>Update History</div>
          <div class="card-body p-0">
            <?php foreach ($notifs as $n): ?>
            <div class="d-flex gap-3 p-3 border-bottom">
              <div class="rounded-circle bg-success d-flex align-items-center justify-content-center flex-shrink-0"
                   style="width:32px;height:32px;">
                <i class="bi bi-bell-fill text-white" style="font-size:.75rem;"></i>
              </div>
              <div>
                <div style="font-size:.875rem;"><?= e($n['message']) ?></div>
                <small class="text-muted"><?= substr($n['notif_date'], 0, 16) ?></small>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

      </div>
    </div>
    <?php endif; ?>

  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
