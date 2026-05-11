<?php
require_once __DIR__.'/../includes/config.php';
start_admin_session(); require_admin();
require_once __DIR__.'/../includes/backup.php';

// Handle actions
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'manual_backup') {
        $ok = run_backup('manual');
        flash($ok ? 'success' : 'danger',
              $ok ? 'Manual backup created successfully!' : 'Backup failed. Check server error log.');
        header('Location: /BarangayProject/admin/backup_history.php'); exit;
    }

    if ($action === 'delete_backup') {
        $file = basename($_POST['filename'] ?? '');
        $path = BACKUP_DIR . $file;
        if ($file && file_exists($path) && str_ends_with($file, '.sql')) {
            unlink($path);
            flash('success', "Backup file <strong>{$file}</strong> deleted.");
        }
        header('Location: /BarangayProject/admin/backup_history.php'); exit;
    }
}

// Handle download
if (isset($_GET['download'])) {
    $file = basename($_GET['download']);
    $path = BACKUP_DIR . $file;
    if ($file && file_exists($path) && str_ends_with($file, '.sql')) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }
}

$backups = get_backup_files();
$history = get_backup_history();
$flash   = get_flash();
$page_title = 'Backup History';
include __DIR__.'/../includes/header.php';
include __DIR__.'/../includes/admin_sidebar.php';
?>
<div class="main-content">
  <div class="topbar">
    <div>
      <h5><i class="bi bi-cloud-arrow-down me-2"></i>Backup History</h5>
      <small>View, download, and manage database backups</small>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
      <span class="badge bg-secondary"><?= count($backups) ?> backup files</span>
      <form method="post" style="margin:0;"><?= csrf_field() ?>
        <input type="hidden" name="action" value="manual_backup">
        <button type="submit" class="btn btn-primary btn-sm">
          <i class="bi bi-cloud-arrow-up me-1"></i>Run Manual Backup
        </button>
      </form>
    </div>
  </div>

  <div class="p-4">
    <?php if($flash): ?>
    <div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?> alert-dismissible fade show">
      <?= $flash['msg'] ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Info Cards -->
    <div class="row g-3 mb-4">
      <div class="col-sm-4">
        <div class="stat-card" style="--accent-color:var(--navy);">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="stat-value"><?= count($backups) ?></div>
              <div class="stat-label">Total Backup Files</div>
            </div>
            <i class="bi bi-server" style="font-size:1.6rem;opacity:.1;"></i>
          </div>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="stat-card" style="--accent-color:var(--gold);">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="stat-value"><?= $backups ? format_size(array_sum(array_map('filesize',$backups))) : '0 B' ?></div>
              <div class="stat-label">Total Backup Size</div>
            </div>
            <i class="bi bi-hdd" style="font-size:1.6rem;opacity:.1;"></i>
          </div>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="stat-card" style="--accent-color:#15803D;">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="stat-value"><?= $backups ? date('M d',filemtime($backups[0])) : '—' ?></div>
              <div class="stat-label">Latest Backup</div>
            </div>
            <i class="bi bi-clock-history" style="font-size:1.6rem;opacity:.1;"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Auto Backup Status -->
    <div class="card mb-4">
      <div class="card-header"><i class="bi bi-info-circle me-2"></i>Backup Configuration</div>
      <div class="card-body">
        <div class="row g-3">
          <?php foreach([
            ['Schedule','Daily (on logout)','bi-calendar-check','text-success'],
            ['Tables Backed Up','records, archives, residents, accounts, notifications + all others','bi-table','text-primary'],
            ['Retention','Last 30 days','bi-clock','text-warning'],
            ['Triggers','Logout · Permanent Delete · Empty Trash · Manual','bi-lightning-charge','text-danger'],
          ] as [$label,$val,$icon,$cls]): ?>
          <div class="col-md-6">
            <div class="d-flex align-items-start gap-3 p-3 rounded" style="background:#F8FAFF;border:1px solid var(--border);">
              <i class="bi <?= $icon ?> <?= $cls ?>" style="font-size:1.2rem;margin-top:2px;flex-shrink:0;"></i>
              <div><div class="fw-semibold" style="font-size:.82rem;color:var(--navy);"><?= $label ?></div><small class="text-muted"><?= $val ?></small></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Backup Files Table -->
    <div class="card mb-4">
      <div class="card-header">
        <span><i class="bi bi-file-earmark-zip me-2"></i>Backup Files on Disk</span>
        <small class="text-muted">Stored in <code>/backups/</code> folder</small>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0" id="backupTable">
            <thead><tr><th>#</th><th>Filename</th><th>Trigger</th><th>Size</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
              <?php if(empty($backups)): ?>
              <tr><td colspan="6" class="text-center text-muted py-5">
                <i class="bi bi-cloud-slash fs-3 d-block mb-2"></i>No backup files yet. Run a manual backup above.
              </td></tr>
              <?php else: foreach($backups as $i=>$file):
                $fname   = basename($file);
                $size    = filesize($file);
                $mtime   = filemtime($file);
                // Detect trigger from filename
                $trigger = 'logout';
                if (str_contains($fname,'_archive'))     $trigger = 'archive';
                if (str_contains($fname,'_empty_trash')) $trigger = 'empty_trash';
                if (str_contains($fname,'_manual'))      $trigger = 'manual';
              ?>
              <tr>
                <td><small class="text-muted"><?= $i+1 ?></small></td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-text text-muted"></i>
                    <code style="font-size:.78rem;"><?= e($fname) ?></code>
                    <?php if($i===0): ?><span class="badge bg-success ms-1" style="font-size:.6rem;">Latest</span><?php endif; ?>
                  </div>
                </td>
                <td><?= trigger_badge($trigger) ?></td>
                <td><small><?= format_size($size) ?></small></td>
                <td><small class="text-muted"><?= date('Y-m-d H:i',$mtime) ?></small></td>
                <td>
                  <div class="d-flex gap-1">
                    <a href="/BarangayProject/admin/backup_history.php?download=<?= urlencode($fname) ?>"
                       class="btn btn-sm btn-outline-primary" title="Download">
                      <i class="bi bi-download"></i>
                    </a>
                    <button class="btn btn-sm btn-outline-danger" title="Delete"
                      data-bs-toggle="modal" data-bs-target="#delBackupModal"
                      data-file="<?= e($fname) ?>">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Backup Event History -->
    <div class="card">
      <div class="card-header"><i class="bi bi-clock-history me-2"></i>Backup Event Log</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead><tr><th>Filename</th><th>Trigger</th><th>Size</th><th>Date & Time</th></tr></thead>
            <tbody>
              <?php if(empty($history)): ?>
              <tr><td colspan="4" class="text-center text-muted py-4">No backup events logged yet.</td></tr>
              <?php else: foreach(array_slice($history,0,20) as $h): ?>
              <tr>
                <td><code style="font-size:.76rem;"><?= e($h['filename']) ?></code></td>
                <td><?= trigger_badge($h['trigger']) ?></td>
                <td><small><?= format_size($h['size_bytes']) ?></small></td>
                <td><small class="text-muted"><?= e($h['created_at']) ?></small></td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Delete Backup Modal -->
<div class="modal fade" id="delBackupModal" tabindex="-1"><div class="modal-dialog modal-sm"><div class="modal-content">
  <div class="modal-header" style="background:#dc3545;color:#fff;border-radius:14px 14px 0 0;">
    <h5 class="modal-title" style="font-size:.95rem;"><i class="bi bi-trash me-2"></i>Delete Backup?</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
  </div>
  <div class="modal-body">
    <p class="mb-0">Delete backup file <strong id="delBkFile"></strong>? <span class="text-danger">This cannot be undone.</span></p>
  </div>
  <div class="modal-footer">
    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
    <form method="post" style="margin:0;"><?= csrf_field() ?>
      <input type="hidden" name="action" value="delete_backup">
      <input type="hidden" name="filename" id="delBkFilename">
      <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-trash me-1"></i>Delete</button>
    </form>
  </div>
</div></div></div>

<script>
document.getElementById('delBackupModal').addEventListener('show.bs.modal',e=>{
  const f=e.relatedTarget.dataset.file;
  document.getElementById('delBkFile').textContent=f;
  document.getElementById('delBkFilename').value=f;
});
</script>
<?php include __DIR__.'/../includes/footer.php'; ?>
