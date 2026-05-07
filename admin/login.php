<?php
require_once __DIR__.'/../includes/config.php';
start_admin_session();
if (is_admin()) { header('Location: /BarangayProject/admin/dashboard.php'); exit; }

$error=''; $errors=[];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $user=trim($_POST['username']??''); $pass=trim($_POST['password']??'');
    if (!$user) $errors['username']='Username is required.';
    if (!$pass) $errors['password']='Password is required.';
    if (!$errors) {
        $admin=db_fetch_one('SELECT * FROM admins WHERE username=?',[$user]);
        if ($admin && verify_password($pass,$admin['password'])) {
            maybe_upgrade_hash($pass,$admin['password'],$admin['id'],'admins');
            $_SESSION['admin_id']=$admin['id']; $_SESSION['admin_name']=$admin['full_name'];
            header('Location: /BarangayProject/admin/dashboard.php'); exit;
        }
        $error='Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Login — Barangay San Rafael</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    :root{--navy:#0B1F4B;--gold:#D4A017;--gold-b:#F0B429;}
    *{box-sizing:border-box;margin:0;padding:0;}
    body{font-family:'DM Sans',sans-serif;min-height:100vh;background:linear-gradient(135deg,#050E24 0%,#0B1F4B 55%,#0D1F45 100%);display:flex;align-items:center;justify-content:center;padding:20px;}
    h1,h2,h3,h4,h5,h6{font-family:'Sora',sans-serif;}
    .card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);border-radius:20px;padding:44px 40px;width:100%;max-width:400px;text-align:center;box-shadow:0 30px 80px rgba(0,0,0,.5);animation:fadeUp .4s ease;}
    @keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
    .logo{width:74px;height:74px;border-radius:50%;border:2px solid var(--gold);object-fit:cover;box-shadow:0 0 28px rgba(212,160,23,.35);margin-bottom:8px;}
    .badge-admin{display:inline-flex;align-items:center;gap:5px;background:rgba(212,160,23,.1);border:1px solid rgba(212,160,23,.22);color:var(--gold-b);font-size:.66rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:4px 12px;border-radius:16px;margin:10px 0 6px;}
    h2{color:#fff;font-size:1.25rem;font-weight:800;margin:0 0 3px;}
    .sub{color:rgba(255,255,255,.32);font-size:.78rem;margin:0 0 22px;}
    .err-box{background:rgba(185,28,28,.14);border:1px solid rgba(185,28,28,.28);border-radius:8px;padding:10px 14px;color:#FCA5A5;font-size:.82rem;display:flex;align-items:center;gap:8px;margin-bottom:18px;text-align:left;}
    .f-group{margin-bottom:14px;text-align:left;}
    .f-label{display:block;font-size:.68rem;font-weight:700;color:rgba(255,255,255,.36);text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px;}
    .f-input{width:100%;background:rgba(255,255,255,.06);border:1.5px solid rgba(255,255,255,.1);border-radius:9px;padding:11px 14px;font-family:'DM Sans',sans-serif;font-size:.88rem;color:#fff;outline:none;transition:border-color .15s;}
    .f-input::placeholder{color:rgba(255,255,255,.22);}
    .f-input:focus{border-color:rgba(212,160,23,.5);box-shadow:0 0 0 3px rgba(212,160,23,.1);}
    .f-input.is-invalid{border-color:#dc3545;}
    .f-error{font-size:.7rem;color:#FCA5A5;margin-top:3px;display:block;}
    .f-wrap{position:relative;}.f-wrap .f-input{padding-right:42px;}
    .pw-toggle{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:rgba(255,255,255,.32);font-size:.95rem;}
    .pw-toggle:hover{color:rgba(255,255,255,.7);}
    .btn-admin{width:100%;padding:12px;border:none;border-radius:9px;margin-top:4px;background:linear-gradient(135deg,var(--gold),#C8890A);color:var(--navy);font-family:'Sora',sans-serif;font-size:.9rem;font-weight:700;cursor:pointer;transition:all .18s;}
    .btn-admin:hover{opacity:.9;transform:translateY(-1px);}
    .hint{font-size:.68rem;color:rgba(255,255,255,.18);margin-top:14px;}
    .back{margin-top:18px;padding-top:16px;border-top:1px solid rgba(255,255,255,.07);}
    .back a{color:rgba(255,255,255,.3);font-size:.75rem;text-decoration:none;}
    .back a:hover{color:rgba(255,255,255,.6);}
  </style>
</head>
<body>
<div class="card">
  <img src="/BarangayProject/Logo.jpg" alt="Logo" class="logo">
  <div class="badge-admin"><i class="bi bi-shield-lock-fill"></i> Admin Access</div>
  <h2>Administrator</h2>
  <p class="sub">Barangay San Rafael Management System</p>
  <?php if($error): ?><div class="err-box"><i class="bi bi-exclamation-circle-fill"></i><?= e($error) ?></div><?php endif; ?>
  <form method="post" novalidate>
    <?= csrf_field() ?>
    <div class="f-group">
      <label class="f-label">Username</label>
      <input type="text" name="username" class="f-input <?= isset($errors['username'])?'is-invalid':'' ?>" placeholder="Enter username" value="<?= e($_POST['username']??'') ?>" required autocomplete="username">
      <?php if(isset($errors['username'])): ?><span class="f-error"><?= e($errors['username']) ?></span><?php endif; ?>
    </div>
    <div class="f-group">
      <label class="f-label">Password</label>
      <div class="f-wrap">
        <input type="password" name="password" id="adminPw" class="f-input <?= isset($errors['password'])?'is-invalid':'' ?>" placeholder="Enter password" required autocomplete="current-password">
        <button type="button" class="pw-toggle" onclick="tp('adminPw',this)"><i class="bi bi-eye"></i></button>
      </div>
      <?php if(isset($errors['password'])): ?><span class="f-error"><?= e($errors['password']) ?></span><?php endif; ?>
    </div>
    <button type="submit" class="btn-admin"><i class="bi bi-shield-check me-1"></i>Access Dashboard</button>
  </form>
  <p class="hint">Default: admin1 / admin123 &nbsp;·&nbsp; admin2 / admin456</p>
  <div class="back"><a href="/BarangayProject/index.php"><i class="bi bi-arrow-left me-1"></i>Back to Resident Portal</a></div>
</div>
<script>function tp(id,btn){const i=document.getElementById(id);i.type=i.type==='password'?'text':'password';btn.innerHTML=i.type==='text'?'<i class="bi bi-eye-slash"></i>':'<i class="bi bi-eye"></i>';}</script>
</body></html>
