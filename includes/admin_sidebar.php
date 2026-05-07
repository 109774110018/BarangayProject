<?php
$admin=$current_admin=current_admin();
$current=basename($_SERVER['PHP_SELF']);
function nav_link(string $file,string $icon,string $label,string $cur,string $badge=''):string{
    $a=$cur===$file?'active':'';
    $b=$badge?"<span class='ms-auto badge bg-danger' style='font-size:.58rem;padding:2px 5px;'>$badge</span>":'';
    return "<a href='/BarangayProject/admin/{$file}' class='nav-link {$a}'><i class='bi bi-{$icon}'></i><span>{$label}</span>{$b}</a>";
}
$init=implode('',array_map(fn($w)=>strtoupper($w[0]),array_slice(explode(' ',$admin['full_name']??'Admin'),0,2)));
$trash_c=db_fetch_one('SELECT COUNT(*) c FROM records WHERE is_deleted=1')['c']??0;
$notif_c=0; try { $notif_c=db_fetch_one('SELECT COUNT(*) c FROM notifications WHERE is_read=0')['c']??0; } catch(\Throwable $e) { $notif_c=db_fetch_one('SELECT COUNT(*) c FROM notifications')['c']??0; }
?>
<button class="hamburger-btn" id="hamburgerBtn" onclick="openSidebar()"><i class="bi bi-list"></i></button>
<div class="sidebar" id="adminSidebar">
  <div class="sidebar-brand">
    <img src="/BarangayProject/Logo.jpg" alt="Logo" class="brand-logo">
    <h6><?= APP_NAME ?></h6><small>Admin Panel</small>
  </div>
  <div class="sidebar-user">
    <div class="avatar"><?= e($init) ?></div>
    <div class="user-info"><span><?= e($admin['full_name']??'') ?></span><small>Administrator</small></div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section">Main</div>
    <?= nav_link('dashboard.php','speedometer2','Dashboard',$current) ?>
    <?= nav_link('manage_records.php','table','Manage Records',$current) ?>
    <?= nav_link('residents.php','people','Residents',$current) ?>
    <div class="nav-section">Tools</div>
    <?= nav_link('notifications.php','bell','Notifications',$current,$notif_c>0?(string)$notif_c:'') ?>
    <?= nav_link('export_pdf.php','file-earmark-pdf','Export PDF',$current) ?>
    <?= nav_link('trash.php','trash','Recently Deleted',$current,$trash_c>0?(string)$trash_c:'') ?>
  </nav>
  <div class="sidebar-footer">
    <button type="button" class="btn-logout" onclick="showLogoutModal('adminLogoutModal')">
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
