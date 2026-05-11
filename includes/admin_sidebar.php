<?php
$admin=$current_admin=current_admin();
$current=basename($_SERVER['PHP_SELF']);
function nav_link(string $file,string $icon,string $label,string $cur,string $badge=''):string{
    $a=$cur===$file?'active':'';
    $b=$badge?"<span class='ms-auto badge bg-danger' style='font-size:.55rem;padding:2px 5px;min-width:16px;'>$badge</span>":'';
    return "<a href='/BarangayProject/admin/{$file}' class='nav-link {$a}'><i class='bi bi-{$icon}'></i><span>{$label}</span>{$b}</a>";
}
$init=implode('',array_map(fn($w)=>strtoupper($w[0]),array_slice(explode(' ',$admin['full_name']??'Admin'),0,2)));
$trash_c=db_fetch_one('SELECT COUNT(*) c FROM records WHERE is_deleted=1')['c']??0;
$notif_c=0;
try { $notif_c=db_fetch_one('SELECT COUNT(*) c FROM notifications WHERE is_read=0')['c']??0; }
catch(\Throwable $e) { $notif_c=db_fetch_one('SELECT COUNT(*) c FROM notifications')['c']??0; }
?>
<button class="hamburger-btn" id="hamburgerBtn" onclick="openSidebar()"><i class="bi bi-list"></i></button>
<div class="sidebar" id="adminSidebar">

  <!-- BRAND — compact -->
  <div class="sidebar-brand" style="padding:14px 16px 12px;">
    <img src="/BarangayProject/Logo.jpg" alt="Logo" class="brand-logo" style="width:52px;height:52px;margin-bottom:6px;">
    <h6 style="font-size:.7rem;"><?= APP_NAME ?></h6>
    <small>Admin Panel</small>
  </div>

  <!-- USER CHIP — compact -->
  <div class="sidebar-user" style="margin:8px 12px;padding:8px 10px;">
    <div class="avatar" style="width:30px;height:30px;font-size:.76rem;border-radius:7px;"><?= e($init) ?></div>
    <div class="user-info"><span style="font-size:.77rem;"><?= e($admin['full_name']??'') ?></span><small>Administrator</small></div>
  </div>

  <!-- NAV — compact spacing -->
  <nav class="sidebar-nav" style="padding:4px 10px;gap:0;overflow-y:auto;flex:1;">
    <div class="nav-section" style="padding:6px 8px 2px;font-size:.56rem;">Main</div>
    <?= nav_link('dashboard.php','speedometer2','Dashboard',$current) ?>
    <?= nav_link('manage_records.php','table','Manage Records',$current) ?>
    <?= nav_link('residents.php','people','Residents',$current) ?>
    <div class="nav-section" style="padding:6px 8px 2px;font-size:.56rem;">Tools</div>
    <?= nav_link('notifications.php','bell','Notifications',$current,$notif_c>0?(string)$notif_c:'') ?>
    <?= nav_link('export_pdf.php','file-earmark-pdf','Export PDF',$current) ?>
    <?= nav_link('trash.php','trash','Recently Deleted',$current,$trash_c>0?(string)$trash_c:'') ?>
    <?= nav_link('archives.php','archive','Archives',$current) ?>
    <?= nav_link('backup_history.php','cloud-arrow-down','Backup History',$current) ?>
  </nav>

  <!-- LOGOUT — always visible at bottom -->
  <div class="sidebar-footer" style="padding:8px 12px;border-top:1px solid rgba(255,255,255,.07);flex-shrink:0;">
    <button type="button" class="btn-logout" onclick="showLogoutModal('adminLogoutModal')" style="padding:8px 12px;">
      <i class="bi bi-box-arrow-left"></i> Logout
    </button>
  </div>
</div>

<!-- Admin Logout Modal -->
<div id="adminLogoutModal" class="brgy-modal-overlay" onclick="if(event.target===this)hideLogoutModal('adminLogoutModal')">
  <div class="brgy-modal-box">
    <img src="/BarangayProject/Logo.jpg" class="brgy-modal-logo" alt="Logo">
    <h5 class="brgy-modal-title">Log Out?</h5>
    <p class="brgy-modal-text">Are you sure you want to log out?<br><small>A backup will be saved automatically.</small></p>
    <div class="brgy-modal-actions">
      <button onclick="hideLogoutModal('adminLogoutModal')" class="brgy-btn-cancel">Cancel</button>
      <form method="post" action="/BarangayProject/admin/logout.php" style="margin:0;">
        <button type="submit" class="brgy-btn-confirm">Yes, Log Out</button>
      </form>
    </div>
  </div>
</div>
