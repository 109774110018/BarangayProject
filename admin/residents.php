<?php
require_once __DIR__.'/../includes/config.php';
start_admin_session(); require_admin();

$errors=[];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $action=$_POST['action']??'';
    if ($action==='add') {
        $name=trim($_POST['full_name']??''); $address=trim($_POST['address']??'');
        $contact=trim($_POST['contact']??''); $uname=trim($_POST['username']??''); $pwd=trim($_POST['password']??'');
        if (!$name)    $errors['full_name']='Full name is required.';
        if (!$address) $errors['address']='Address is required.';
        if (!$contact) $errors['contact']='Contact is required.';
        elseif (!validate_contact($contact)) $errors['contact']='Enter a valid PH mobile number (09XXXXXXXXX).';
        if (!$uname)   $errors['username']='Username is required.';
        if (!$pwd)     $errors['password']='Password is required.';
        elseif ($e=validate_password($pwd)) $errors['password']=$e;
        if (!$errors && db_fetch_one('SELECT id FROM resident_accounts WHERE username=?',[$uname]))
            $errors['username']='Username already taken.';
        if (!$errors) {
            $rid='RES-'.strtoupper(substr(uniqid(),-6));
            db_execute('INSERT INTO residents (resident_id,name,address,contact) VALUES (?,?,?,?)',[$rid,$name,$address,$contact]);
            db_execute('INSERT INTO resident_accounts (username,password,full_name,address,contact,resident_id) VALUES (?,?,?,?,?,?)',[$uname,hash_password($pwd),$name,$address,$contact,$rid]);
            flash('success',"Account created! Resident ID: <strong>{$rid}</strong>");
            header('Location: /BarangayProject/admin/residents.php'); exit;
        }
    }
    if ($action==='delete') {
        $id=(int)($_POST['account_id']??0);
        if ($id) { db_execute('DELETE FROM resident_accounts WHERE id=?',[$id]); flash('success','Resident account deleted.'); }
        header('Location: /BarangayProject/admin/residents.php'); exit;
    }
}

$search=trim($_GET['q']??''); $page=max(1,(int)($_GET['page']??1));
$sql='SELECT ra.*,r.resident_id as res_id FROM resident_accounts ra LEFT JOIN residents r ON ra.resident_id=r.resident_id';
$params=[];
if ($search) { $sql.=' WHERE ra.full_name LIKE ? OR ra.username LIKE ? OR ra.contact LIKE ?'; $like="%{$search}%"; $params=[$like,$like,$like]; }
$sql.=' ORDER BY ra.created_at DESC';
$all=db_fetch_all($sql,$params);
$pg=paginate($all,PER_PAGE,$page);
$base='?q='.urlencode($search);
$flash=get_flash(); $page_title='Residents';
include __DIR__.'/../includes/header.php';
include __DIR__.'/../includes/admin_sidebar.php';
?>
<div class="main-content">
  <div class="topbar">
    <div><h5><i class="bi bi-people me-2"></i>Resident Accounts</h5><small>Manage registered resident logins</small></div>
    <div class="d-flex gap-2 align-items-center">
      <span class="badge bg-secondary"><?= $pg['total'] ?> total</span>
      <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-person-plus me-1"></i>Add Resident</button>
    </div>
  </div>
  <div class="p-4">
    <?php if($flash): ?>
    <div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?> alert-dismissible fade show">
      <?= $flash['msg'] ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <!-- Search -->
    <div class="card mb-3"><div class="card-body py-2">
      <form method="get" class="d-flex gap-2 align-items-center flex-wrap">
        <div class="input-group input-group-sm" style="max-width:320px;">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input type="text" name="q" class="form-control" placeholder="Search name, username, contact…" value="<?= e($search) ?>" id="resSearchInput">
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Search</button>
        <?php if($search): ?><a href="/BarangayProject/admin/residents.php" class="btn btn-outline-secondary btn-sm">Clear</a><?php endif; ?>
      </form>
    </div></div>
    <div class="card">
      <div class="card-header">
        <span><i class="bi bi-people me-2"></i>Registered Accounts</span>
        <?= pagination_html($pg['pages'],$pg['current'],$base) ?>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0" id="residentsTable">
            <thead><tr><th>#</th><th>Full Name</th><th>Username</th><th>Contact</th><th>Resident ID</th><th>Joined</th><th>Actions</th></tr></thead>
            <tbody>
              <?php if(empty($pg['items'])): ?>
              <tr><td colspan="7" class="text-center text-muted py-5"><i class="bi bi-people fs-3 d-block mb-2"></i>No residents found.</td></tr>
              <?php else: foreach($pg['items'] as $i=>$r): ?>
              <tr>
                <td><small class="text-muted"><?= ($pg['current']-1)*PER_PAGE+$i+1 ?></small></td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white" style="width:30px;height:30px;background:var(--navy);font-size:.7rem;flex-shrink:0;"><?= strtoupper(substr($r['full_name'],0,2)) ?></div>
                    <div><div class="fw-semibold" style="font-size:.84rem;"><?= e($r['full_name']) ?></div><small class="text-muted"><?= e($r['address']) ?></small></div>
                  </div>
                </td>
                <td><code>@<?= e($r['username']) ?></code></td>
                <td><small><?= e($r['contact']) ?></small></td>
                <td><span class="record-id-chip"><?= e($r['resident_id']??'—') ?></span></td>
                <td><small class="text-muted"><?= substr($r['created_at'],0,10) ?></small></td>
                <td>
                  <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#delResModal"
                    data-id="<?= $r['id'] ?>" data-name="<?= e($r['full_name']) ?>"><i class="bi bi-trash"></i></button>
                </td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
        <?php if($pg['pages']>1): ?>
        <div class="d-flex align-items-center justify-content-between px-4 py-3 border-top flex-wrap gap-2">
          <small class="text-muted">Showing <?= count($pg['items']) ?> of <?= $pg['total'] ?></small>
          <?= pagination_html($pg['pages'],$pg['current'],$base) ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header brand-header"><h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Add Resident Account</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
  <form method="post" novalidate><?= csrf_field() ?><input type="hidden" name="action" value="add">
    <div class="modal-body">
      <?php if($errors): ?><div class="alert alert-danger"><i class="bi bi-exclamation-circle me-1"></i>Please fix the errors below.</div><?php endif; ?>
      <div class="row g-3">
        <div class="col-12">
          <label class="form-label">Full Name <span class="text-danger">*</span></label>
          <input type="text" name="full_name" class="form-control <?= isset($errors['full_name'])?'is-invalid':'' ?>" placeholder="Juan Dela Cruz" value="<?= e($_POST['full_name']??'') ?>" required>
          <?php if(isset($errors['full_name'])): ?><div class="invalid-feedback"><?= e($errors['full_name']) ?></div><?php endif; ?>
        </div>
        <div class="col-12">
          <label class="form-label">Address <span class="text-danger">*</span></label>
          <input type="text" name="address" class="form-control <?= isset($errors['address'])?'is-invalid':'' ?>" placeholder="Purok / Street" value="<?= e($_POST['address']??'') ?>" required>
          <?php if(isset($errors['address'])): ?><div class="invalid-feedback"><?= e($errors['address']) ?></div><?php endif; ?>
        </div>
        <div class="col-md-6">
          <label class="form-label">Contact No. <span class="text-danger">*</span></label>
          <input type="text" name="contact" class="form-control <?= isset($errors['contact'])?'is-invalid':'' ?>" placeholder="09XXXXXXXXX" value="<?= e($_POST['contact']??'') ?>" required>
          <?php if(isset($errors['contact'])): ?><div class="invalid-feedback"><?= e($errors['contact']) ?></div><?php endif; ?>
        </div>
        <div class="col-md-6">
          <label class="form-label">Username <span class="text-danger">*</span></label>
          <input type="text" name="username" class="form-control <?= isset($errors['username'])?'is-invalid':'' ?>" placeholder="username" value="<?= e($_POST['username']??'') ?>" required>
          <?php if(isset($errors['username'])): ?><div class="invalid-feedback"><?= e($errors['username']) ?></div><?php endif; ?>
        </div>
        <div class="col-12">
          <label class="form-label">Password <span class="text-danger">*</span></label>
          <div class="input-group">
            <input type="password" name="password" id="addPass" class="form-control <?= isset($errors['password'])?'is-invalid':'' ?>" placeholder="Min. 6 characters" required>
            <button type="button" class="btn btn-outline-secondary" onclick="togglePw('addPass',this)"><i class="bi bi-eye"></i></button>
            <?php if(isset($errors['password'])): ?><div class="invalid-feedback"><?= e($errors['password']) ?></div><?php endif; ?>
          </div>
          <div class="form-text">Password will be securely hashed (bcrypt).</div>
        </div>
      </div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Create Account</button></div>
  </form>
</div></div></div>
<!-- Delete Confirm Modal -->
<div class="modal fade" id="delResModal" tabindex="-1"><div class="modal-dialog modal-sm"><div class="modal-content">
  <div class="modal-header" style="background:#dc3545;color:#fff;border-radius:14px 14px 0 0;"><h5 class="modal-title" style="font-size:.95rem;"><i class="bi bi-trash me-2"></i>Delete Account?</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><p class="mb-0">Delete account for <strong id="delResName"></strong>? This cannot be undone.</p></div>
  <div class="modal-footer">
    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
    <form method="post" style="margin:0;"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="account_id" id="delResId"><button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-trash me-1"></i>Delete</button></form>
  </div>
</div></div></div>
<script>
document.getElementById('delResModal').addEventListener('show.bs.modal',e=>{
  document.getElementById('delResId').value=e.relatedTarget.dataset.id;
  document.getElementById('delResName').textContent=e.relatedTarget.dataset.name;
});
function togglePw(id,btn){const i=document.getElementById(id);i.type=i.type==='password'?'text':'password';btn.innerHTML=i.type==='text'?'<i class="bi bi-eye-slash"></i>':'<i class="bi bi-eye"></i>';}
initLiveSearch('resSearchInput','residentsTable');
<?php if($errors): ?>document.addEventListener('DOMContentLoaded',()=>new bootstrap.Modal(document.getElementById('addModal')).show());<?php endif; ?>
</script>
<?php include __DIR__.'/../includes/footer.php'; ?>
