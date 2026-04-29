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
    <button type="button" class="btn-logout" onclick="confirmResidentLogout()">
      <i class="bi bi-box-arrow-left"></i> Logout
    </button>

    <!-- Logout Confirmation Modal -->
    <div id="residentLogoutModal" style="
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
          Are you sure you want to log out?
        </p>
        <div style="display:flex; gap:10px; justify-content:center;">
          <button onclick="document.getElementById('residentLogoutModal').style.display='none'"
                  style="padding:8px 22px; border-radius:8px; border:1px solid #ced4da;
                         background:#f8f9fa; color:#495057; cursor:pointer; font-size:.9rem;">
            Cancel
          </button>
          <form method="post" action="/BarangayProject/resident/logout.php" style="margin:0;">
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
      function confirmResidentLogout() {
        document.getElementById('residentLogoutModal').style.display = 'flex';
      }
    </script>
  </div>
</div>
