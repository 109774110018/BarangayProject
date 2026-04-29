<?php
// ─── Database Restore Utility ─
// Access: /BarangayProject/admin/restore_backup.php  (admin-only)
// Allows an admin to select a backup file and restore the database from it.


require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/backup.php';
require_admin();

$backupDir = BACKUP_DIR;
$message   = '';
$msgType   = '';

// Handle Restore POST 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['backup_file'])) {
    $chosen = basename($_POST['backup_file']);           // strip directory traversal
    $path   = $backupDir . $chosen;

    if (!file_exists($path) || !str_ends_with($chosen, '.sql')) {
        $message = 'Invalid backup file selected.';
        $msgType = 'danger';
    } else {
        $sqlContent = file_get_contents($path);
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset('utf8mb4');

        // Split on semicolons that end a statement (outside strings — simple split)
        $statements = array_filter(
            array_map('trim', explode(";\n", $sqlContent)),
            fn($s) => $s !== ''
        );

        $conn->query('SET FOREIGN_KEY_CHECKS=0');
        $errors = 0;
        foreach ($statements as $stmt) {
            if (!$conn->query($stmt)) {
                error_log('[Restore] Query error: ' . $conn->error . ' | SQL: ' . substr($stmt, 0, 120));
                $errors++;
            }
        }
        $conn->query('SET FOREIGN_KEY_CHECKS=1');
        $conn->close();

        if ($errors === 0) {
            $message = "Database restored successfully from <strong>" . htmlspecialchars($chosen) . "</strong>.";
            $msgType = 'success';
        } else {
            $message = "Restore completed with {$errors} error(s). Check the server error log.";
            $msgType = 'warning';
        }
    }
}

//List Available Backups 
$backups = array_reverse(glob($backupDir . 'backup_*.sql') ?: []);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admin_sidebar.php';
?>

<div class="container-fluid py-4">
  <div class="row justify-content-center">
    <div class="col-lg-7">

      <h4 class="mb-3"><i class="bi bi-database-fill-gear me-2"></i>Restore Database Backup</h4>

      <?php if ($message): ?>
        <div class="alert alert-<?= $msgType ?> alert-dismissible fade show" role="alert">
          <?= $message ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <?php if (empty($backups)): ?>
        <div class="alert alert-info">No backup files found in <code><?= htmlspecialchars($backupDir) ?></code>.</div>
      <?php else: ?>
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white">
            <i class="bi bi-archive me-1"></i> Available Backup Files
          </div>
          <div class="card-body">
            <form method="POST">
              <div class="mb-3">
                <label for="backup_file" class="form-label fw-semibold">Select a backup to restore:</label>
                <select name="backup_file" id="backup_file" class="form-select" required>
                  <option value="">— Choose backup file —</option>
                  <?php foreach ($backups as $file): ?>
                    <?php $name = basename($file); ?>
                    <?php $size = round(filesize($file) / 1024, 1); ?>
                    <?php $date = date('F d, Y', filemtime($file)); ?>
                    <option value="<?= htmlspecialchars($name) ?>">
                      <?= htmlspecialchars($name) ?> &nbsp;(<?= $size ?> KB — <?= $date ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="alert alert-warning py-2">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                <strong>Warning:</strong> Restoring will overwrite all current data. This cannot be undone.
              </div>
              <button type="submit"
                      class="btn btn-danger"
                      onclick="return confirm('Are you sure you want to restore this backup? All current data will be replaced.')">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Restore Selected Backup
              </button>
              <a href="/BarangayProject/admin/dashboard.php" class="btn btn-secondary ms-2">Cancel</a>
            </form>
          </div>
        </div>

        <!-- Backup file list table -->
        <div class="card shadow-sm mt-4">
          <div class="card-header">All Backup Files</div>
          <div class="card-body p-0">
            <table class="table table-sm table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>File Name</th>
                  <th>Size</th>
                  <th>Created</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($backups as $file): ?>
                  <tr>
                    <td><i class="bi bi-file-earmark-code me-1 text-muted"></i><?= htmlspecialchars(basename($file)) ?></td>
                    <td><?= round(filesize($file) / 1024, 1) ?> KB</td>
                    <td><?= date('Y-m-d H:i', filemtime($file)) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
