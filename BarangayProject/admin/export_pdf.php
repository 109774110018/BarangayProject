<?php
require_once __DIR__ . '/../includes/config.php';
require_admin();

$page_title = 'Export PDF';
$flash = get_flash();
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admin_sidebar.php';
?>
<div class="main-content">
  <div class="topbar">
    <div>
      <h5><i class="bi bi-file-earmark-pdf me-2"></i>Export PDF</h5>
      <small>Generate official barangay PDF reports</small>
    </div>
  </div>
  <div class="p-4">
    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible">
      <?= $flash['msg'] ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row g-4">
      <!-- Full List -->
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body text-center p-4">
            <i class="bi bi-clipboard-data" style="font-size:2.5rem;color:#1B5E20;"></i>
            <h6 class="fw-bold mt-3">Full Records List</h6>
            <p class="text-muted small">Export all complaints and requests as a formatted table.</p>
            <a href="/BarangayProject/admin/generate_pdf.php?type=full" target="_blank"
               class="btn btn-primary w-100">
              <i class="bi bi-download me-1"></i> Export Full List
            </a>
          </div>
        </div>
      </div>
      <!-- Dashboard Summary -->
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body text-center p-4">
            <i class="bi bi-bar-chart-fill" style="font-size:2.5rem;color:#1565C0;"></i>
            <h6 class="fw-bold mt-3">Dashboard Summary</h6>
            <p class="text-muted small">Export total counts by status and record type.</p>
            <a href="/BarangayProject/admin/generate_pdf.php?type=dashboard" target="_blank"
               class="btn btn-primary w-100">
              <i class="bi bi-download me-1"></i> Export Summary
            </a>
          </div>
        </div>
      </div>
      <!-- Individual -->
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body p-4">
            <div class="text-center">
              <i class="bi bi-search" style="font-size:2.5rem;color:#7B1FA2;"></i>
              <h6 class="fw-bold mt-3">Individual Record</h6>
              <p class="text-muted small">Export a single record by entering its ID.</p>
            </div>
            <form action="generate_pdf.php" method="get" target="_blank">
              <input type="hidden" name="type" value="individual">
              <input type="text" name="record_id" class="form-control mb-2"
                     placeholder="e.g. REQ-ABC123" required>
              <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-download me-1"></i> Export Record
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
