<?php
// includes/admin_sidebar.php
$admin = current_admin();
$current = basename($_SERVER['PHP_SELF']);

function nav_link(string $file, string $icon, string $label, string $current): string {
    $active = ($current === $file) ? 'active' : '';
    return "<a href='/BarangayProject/admin/{$file}' class='nav-link {$active}'>
              <i class='bi bi-{$icon}'></i> {$label}
            </a>";
}
$initials = implode('', array_map(fn($w) => strtoupper($w[0]),
    array_slice(explode(' ', $admin['full_name'] ?? 'Admin'), 0, 2)));
?>
<div class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon"><i class="bi bi-building"></i></div>
    <h6><?= APP_NAME ?></h6>
    <small>Admin Panel</small>
  </div>

  <div class="sidebar-user">
    <div class="avatar"><?= e($initials) ?></div>
    <div class="user-info">
      <span><?= e($admin['full_name'] ?? '') ?></span>
      <small>Administrator</small>
    </div>
  </div>

  <nav class="sidebar-nav">
    <?= nav_link('dashboard.php',     'speedometer2',      'Dashboard',       $current) ?>
    <?= nav_link('manage_records.php','table',             'Manage Records',  $current) ?>
    <?= nav_link('residents.php',     'people',            'Residents',       $current) ?>
    <?= nav_link('notifications.php', 'bell',              'Notifications',   $current) ?>
    <?= nav_link('export_pdf.php',    'file-earmark-pdf',  'Export PDF',      $current) ?>
  </nav>

  <div class="sidebar-footer">
    <div class="d-flex align-items-center gap-2 px-2 pb-2">
      <span class="status-dot"><i class="bi bi-circle-fill"></i></span>
      <small style="color:rgba(255,255,255,.5);font-size:.7rem;">MySQL Connected</small>
    </div>
    <button type="button" class="btn-logout" onclick="confirmAdminLogout()">
      <i class="bi bi-box-arrow-left"></i> Logout
    </button>

    <!-- Logout Confirmation Modal -->
    <div id="adminLogoutModal" style="
        display:none; position:fixed; inset:0; z-index:9999;
        background:rgba(0,0,0,.5); align-items:center; justify-content:center;">
      <div style="
          background:#fff; border-radius:12px; padding:28px 32px;
          max-width:360px; width:90%; text-align:center; box-shadow:0 8px 32px rgba(0,0,0,.2);">
        <div style="font-size:2.5rem; color:#dc3545; margin-bottom:8px;">
          <i class="bi bi-box-arrow-left"></i>
        </div>
        <h5 style="margin:0 0 6px; color:#212529;">Log Out?</h5>
        <p style="color:#6c757d; font-size:.9rem; margin-bottom:22px;">
          Are you sure you want to log out?<br>A backup will be saved automatically.
        </p>
        <div style="display:flex; gap:10px; justify-content:center;">
          <button onclick="document.getElementById('adminLogoutModal').style.display='none'"
                  style="padding:8px 22px; border-radius:8px; border:1px solid #ced4da;
                         background:#f8f9fa; color:#495057; cursor:pointer; font-size:.9rem;">
            Cancel
          </button>
          <form method="post" action="/BarangayProject/admin/logout.php" style="margin:0;">
            <button type="submit"
                    style="padding:8px 22px; border-radius:8px; border:none;
                           background:#dc3545; color:#fff; cursor:pointer; font-size:.9rem;">
              Yes, Log Out
            </button>
          </form>
        </div>
      </div>
    </div>
    <script>
      function confirmAdminLogout() {
        document.getElementById('adminLogoutModal').style.display = 'flex';
      }
    </script>
  </div>
</div>
