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
    <form method="post" action="/BarangayProject/admin/logout.php">
      <button type="submit" class="btn-logout">
        <i class="bi bi-box-arrow-left"></i> Logout
      </button>
    </form>
  </div>
</div>
