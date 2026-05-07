<?php
$racc=current_resident();
$current=basename($_SERVER['PHP_SELF']);
function res_nav(string $file,string $icon,string $label,string $cur):string{
    $a=$cur===$file?'active':'';
    return "<a href='/BarangayProject/resident/{$file}' class='nav-link {$a}'><i class='bi bi-{$icon}'></i><span>{$label}</span></a>";
}
$init=implode('',array_map(fn($w)=>strtoupper($w[0]),array_slice(explode(' ',$racc['full_name']??'R'),0,2)));
?>
<script>const RESIDENT_RID='<?= e($racc['resident_id']??'') ?>';</script>
<button class="hamburger-btn" id="hamburgerBtn" onclick="openSidebar()"><i class="bi bi-list"></i></button>
<div class="sidebar" id="residentSidebar">
  <div class="sidebar-brand">
    <img src="/BarangayProject/Logo.jpg" alt="Logo" class="brand-logo">
    <h6><?= APP_NAME ?></h6><small>Resident Portal</small>
  </div>
  <div class="sidebar-user">
    <div class="avatar"><?= e($init) ?></div>
    <div class="user-info"><span><?= e($racc['full_name']??'') ?></span><small>@<?= e($racc['username']??'') ?></small></div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section">Menu</div>
    <?= res_nav('home.php','house','Home',$current) ?>
    <?= res_nav('submit_request.php','file-earmark-plus','Submit Request',$current) ?>
    <?= res_nav('file_complaint.php','exclamation-triangle','File Complaint',$current) ?>
    <?= res_nav('my_submissions.php','folder2-open','My Submissions',$current) ?>
    <?= res_nav('track_status.php','search','Track Status',$current) ?>
    <div class="nav-section">Account</div>
    <?= res_nav('profile.php','person-circle','My Profile',$current) ?>
  </nav>
  <div class="sidebar-footer">
    <button type="button" class="btn-logout" onclick="showLogoutModal('residentLogoutModal')">
      <i class="bi bi-box-arrow-left"></i> Logout
    </button>
  </div>
</div>
<!-- Resident Logout Modal -->
<div id="residentLogoutModal" class="brgy-modal-overlay" onclick="if(event.target===this)hideLogoutModal('residentLogoutModal')">
  <div class="brgy-modal-box">
    <img src="/BarangayProject/Logo.jpg" class="brgy-modal-logo" alt="Logo">
    <h5 class="brgy-modal-title">Log Out?</h5>
    <p class="brgy-modal-text">Are you sure you want to log out?</p>
    <div class="brgy-modal-actions">
      <button onclick="hideLogoutModal('residentLogoutModal')" class="brgy-btn-cancel">Cancel</button>
      <form method="post" action="/BarangayProject/resident/logout.php" style="margin:0;">
        <button type="submit" class="brgy-btn-confirm">Yes, Log Out</button>
      </form>
    </div>
  </div>
</div>
