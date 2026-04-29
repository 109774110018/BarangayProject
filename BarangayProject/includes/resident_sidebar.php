<?php
// includes/resident_sidebar.php
$racc    = current_resident();
$current = basename($_SERVER['PHP_SELF']);

function res_nav(string $file, string $icon, string $label, string $current): string {
    $active = ($current === $file) ? 'active' : '';
    return "<a href='/BarangayProject/resident/{$file}' class='nav-link {$active}'>
              <i class='bi bi-{$icon}'></i> {$label}
            </a>";
}
$initials = implode('', array_map(fn($w) => strtoupper($w[0]),
    array_slice(explode(' ', $racc['full_name'] ?? 'R'), 0, 2)));
?>
<div class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon"><i class="bi bi-building"></i></div>
    <h6><?= APP_NAME ?></h6>
    <small>Resident Portal</small>
  </div>

  <div class="sidebar-user">
    <div class="avatar"><?= e($initials) ?></div>
    <div class="user-info">
      <span><?= e($racc['full_name'] ?? '') ?></span>
      <small>@<?= e($racc['username'] ?? '') ?></small>
    </div>
  </div>

  <nav class="sidebar-nav">
    <?= res_nav('home.php',           'house',             'Home',            $current) ?>
    <?= res_nav('submit_request.php', 'file-earmark-plus', 'Submit Request',  $current) ?>
    <?= res_nav('file_complaint.php', 'exclamation-triangle','File Complaint', $current) ?>
    <?= res_nav('my_submissions.php', 'folder2-open',      'My Submissions',  $current) ?>
    <?= res_nav('track_status.php',   'search',            'Track Status',    $current) ?>
    <?= res_nav('profile.php',        'person-circle',     'My Profile',      $current) ?>
  </nav>

  <div class="sidebar-footer">
    <form method="post" action="/BarangayProject/resident/logout.php">
      <button type="submit" class="btn-logout">
        <i class="bi bi-box-arrow-left"></i> Logout
      </button>
    </form>
  </div>
</div>
