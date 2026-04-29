<?php
require_once __DIR__ . '/../includes/config.php';
require_admin();

$page_title = 'Dashboard';
$admin = current_admin();

// Stats
$stats = db_fetch_one('
    SELECT
        COUNT(*) as total,
        SUM(status="Pending")   as pending,
        SUM(status="Approved")  as approved,
        SUM(status="Done")      as done,
        SUM(record_type="request")   as requests,
        SUM(record_type="complaint") as complaints
    FROM records
') ?? [];

$recent = db_fetch_all('
    SELECT r.*, res.name, res.contact
    FROM records r
    JOIN residents res ON r.resident_id = res.resident_id
    ORDER BY r.date_submitted DESC LIMIT 10
');

$flash = get_flash();
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admin_sidebar.php';
?>

<div class="main-content">
  <div class="topbar">
    <div>
      <div class="d-flex align-items-center gap-2"><img src="/BarangayProject/Logo.jpg" alt="Logo" style="width:36px;height:36px;border-radius:50%;border:2px solid #E8A800;"><h5 class="mb-0"><i class="bi bi-speedometer2 me-2"></i>Dashboard</h5></div>
      <small>Welcome back, <?= e($admin['full_name']) ?>! — <?= date('l, F j, Y') ?></small>
    </div>
    <span class="badge bg-primary">Admin</span>
  </div>

  <div class="p-4">
    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible">
      <?= $flash['msg'] ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
      <?php
      $cards = [
        ['Total Records', $stats['total']     ?? 0, 'primary',   'clipboard-data',    '#1A3A8F'],
        ['Pending',       $stats['pending']   ?? 0, 'warning',   'hourglass-split',   '#F59E0B'],
        ['Approved',      $stats['approved']  ?? 0, 'info',      'check-circle',      '#1E4DB7'],
        ['Done',          $stats['done']      ?? 0, 'success',   'patch-check',       '#E8A800'],
        ['Requests',      $stats['requests']  ?? 0, 'purple',    'file-earmark-text', '#7B1FA2'],
        ['Complaints',    $stats['complaints']?? 0, 'danger',    'exclamation-octagon','#E53935'],
      ];
      foreach ($cards as [$label, $val, $color, $icon, $hex]): ?>
      <div class="col-xl-2 col-md-4 col-6">
        <div class="stat-card" style="border-left-color:<?= $hex ?>">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="stat-value" style="color:<?= $hex ?>"><?= (int)$val ?></div>
              <div class="stat-label"><?= $label ?></div>
            </div>
            <i class="bi bi-<?= $icon ?> stat-icon" style="color:<?= $hex ?>"></i>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Recent Records -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock-history me-2"></i>Recent Records</span>
        <a href="/BarangayProject/admin/manage_records.php" class="btn btn-sm btn-outline-primary">View All</a>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>Record ID</th><th>Type</th><th>Category</th>
                <th>Resident</th><th>Status</th><th>Date</th><th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($recent)): ?>
              <tr><td colspan="7" class="text-center text-muted py-4">No records yet.</td></tr>
              <?php else: ?>
              <?php foreach ($recent as $r): ?>
              <tr>
                <td><span class="record-id-chip"><?= e($r['record_id']) ?></span></td>
                <td>
                  <?php if ($r['record_type'] === 'request'): ?>
                  <span class="badge bg-primary">Request</span>
                  <?php else: ?>
                  <span class="badge bg-danger">Complaint</span>
                  <?php endif; ?>
                </td>
                <td><?= e($r['category']) ?></td>
                <td><?= e($r['name']) ?></td>
                <td><?= status_badge($r['status']) ?></td>
                <td><small class="text-muted"><?= substr($r['date_submitted'], 0, 16) ?></small></td>
                <td>
                  <a href="/BarangayProject/admin/manage_records.php?highlight=<?= urlencode($r['record_id']) ?>"
                     class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye"></i>
                  </a>
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
