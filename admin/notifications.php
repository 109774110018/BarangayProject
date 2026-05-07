<?php
require_once __DIR__ . '/../includes/config.php';
start_admin_session();
start_admin_session();
require_admin();

$notifs = db_fetch_all('
    SELECT n.*, r.record_type, r.status, r.category, res.name
    FROM notifications n
    JOIN records r      ON n.record_id  = r.record_id
    JOIN residents res  ON r.resident_id = res.resident_id
    ORDER BY n.notif_date DESC
');

$page_title = 'Notifications';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admin_sidebar.php';
?>
<div class="main-content">
  <div class="topbar">
    <div>
      <h5><i class="bi bi-bell me-2"></i>Notifications</h5>
      <small>All status update history</small>
    </div>
    <span class="badge bg-secondary"><?= count($notifs) ?> total</span>
  </div>
  <div class="p-4">
    <?php if (empty($notifs)): ?>
    <div class="card"><div class="card-body text-center text-muted py-5">
      <i class="bi bi-bell-slash fs-2 d-block mb-2"></i>No notifications yet.
    </div></div>
    <?php else: ?>
    <div class="d-flex flex-column gap-2">
    <?php foreach ($notifs as $n): ?>
    <div class="card">
      <div class="card-body py-3">
        <div class="d-flex align-items-center justify-content-between mb-1">
          <div class="d-flex align-items-center gap-2">
            <span class="record-id-chip"><?= e($n['record_id']) ?></span>
            <?= $n['record_type'] === 'request'
              ? "<span class='badge bg-primary'>Request</span>"
              : "<span class='badge bg-danger'>Complaint</span>" ?>
            <?= status_badge($n['status']) ?>
          </div>
          <small class="text-muted"><?= substr($n['notif_date'], 0, 16) ?></small>
        </div>
        <div class="fw-semibold text-muted" style="font-size:.82rem;"><?= e($n['name']) ?> — <?= e($n['category']) ?></div>
        <div style="font-size:.875rem;margin-top:3px;">• <?= e($n['message']) ?></div>
      </div>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
