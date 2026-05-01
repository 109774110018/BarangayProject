<?php
require_once __DIR__ . '/../includes/config.php';
start_resident_session();
require_resident();

$acc    = current_resident();
$flash  = get_flash();

// Quick stats for this resident
$stats = db_fetch_one('
    SELECT COUNT(*) as total,
    SUM(status="Pending") as pending,
    SUM(status="Done") as done
    FROM records
    WHERE resident_id = ?
', [$acc['resident_id'] ?? '']) ?? [];

$recent = db_fetch_all('
    SELECT * FROM records WHERE resident_id = ?
    ORDER BY date_submitted DESC LIMIT 5
', [$acc['resident_id'] ?? '']);

$page_title = 'Home';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/resident_sidebar.php';
?>

<div class="main-content">
  <div class="topbar">
    <div>
      <h5><i class="bi bi-house me-2"></i>Welcome, <?= e($acc['full_name']) ?>!</h5>
      <small><?= date('l, F j, Y') ?></small>
    </div>
    <span class="badge bg-primary">Resident</span>
  </div>

  <div class="p-4">
    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible">
      <?= $flash['msg'] ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Hero Banner -->
    <div class="rounded-3 p-4 mb-4 text-white"
         style="background:linear-gradient(135deg,#0F2460,#1A3A8F);border-bottom:4px solid #E8A800;">
      <div class="row align-items-center">
        <div class="col-auto">
          <img src="/BarangayProject/Logo.jpg" alt="Logo"
               style="width:72px;height:72px;border-radius:50%;border:3px solid #E8A800;box-shadow:0 0 16px rgba(232,168,0,.4);">
        </div>
        <div class="col">
          <h4 class="fw-bold mb-1">Barangay San Rafael — Resident Portal</h4>
          <p class="mb-0 opacity-75">Submit requests, file complaints, and track your records online.</p>
        </div>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="row g-3 mb-4">
      <?php foreach ([
        ['My Records',  $stats['total']   ?? 0, '#1A3A8F', 'folder2-open'],
        ['Pending',     $stats['pending'] ?? 0, '#F59E0B', 'hourglass-split'],
        ['Completed',   $stats['done']    ?? 0, '#43A047', 'patch-check'],
      ] as [$label, $val, $color, $icon]): ?>
      <div class="col-md-4">
        <div class="stat-card" style="border-left-color:<?= $color ?>">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="stat-value" style="color:<?= $color ?>"><?= (int)$val ?></div>
              <div class="stat-label"><?= $label ?></div>
            </div>
            <i class="bi bi-<?= $icon ?> stat-icon" style="color:<?= $color ?>"></i>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Action Cards -->
    <div class="row g-3 mb-4">
      <?php foreach ([
        ['Submit a Request',  'file-earmark-plus',     'submit_request.php',  '#1A3A8F', 'Request official barangay documents like clearance, certificates, and permits.'],
        ['File a Complaint',  'exclamation-triangle',  'file_complaint.php',  '#E53935', 'Report barangay concerns such as noise, illegal structures, or disputes.'],
        ['My Submissions',    'folder2-open',          'my_submissions.php',  '#7B1FA2', 'View all your submitted requests and complaints with their current status.'],
        ['Track Status',      'search',                'track_status.php',    '#1565C0', 'Check the status of any record using its Record ID.'],
      ] as [$title, $icon, $link, $color, $desc]): ?>
      <div class="col-md-6 col-xl-3">
        <a href="<?= $link ?>" class="text-decoration-none">
          <div class="card h-100 text-center p-3"
               style="border-top:4px solid <?= $color ?>;transition:transform .15s;"
               onmouseover="this.style.transform='translateY(-3px)'"
               onmouseout="this.style.transform=''">
            <div class="rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center"
                 style="width:56px;height:56px;background:<?= $color ?>18;">
              <i class="bi bi-<?= $icon ?>" style="font-size:1.5rem;color:<?= $color ?>;"></i>
            </div>
            <h6 class="fw-bold mb-1" style="color:<?= $color ?>"><?= $title ?></h6>
            <p class="text-muted small mb-0"><?= $desc ?></p>
          </div>
        </a>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Recent Submissions -->
    <?php if (!empty($recent)): ?>
    <div class="card">
      <div class="card-header d-flex justify-content-between">
        <span><i class="bi bi-clock-history me-2"></i>Recent Submissions</span>
        <a href="/BarangayProject/resident/my_submissions.php" class="btn btn-sm btn-outline-primary">View All</a>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead><tr>
              <th>Record ID</th><th>Type</th><th>Category</th><th>Status</th><th>Date</th>
            </tr></thead>
            <tbody>
              <?php foreach ($recent as $r): ?>
              <tr>
                <td><span class="record-id-chip"><?= e($r['record_id']) ?></span></td>
                <td><?= $r['record_type'] === 'request'
                  ? "<span class='badge bg-primary'>Request</span>"
                  : "<span class='badge bg-danger'>Complaint</span>" ?></td>
                <td><?= e($r['category']) ?></td>
                <td><?= status_badge($r['status']) ?></td>
                <td><small class="text-muted"><?= substr($r['date_submitted'],0,16) ?></small></td>
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
