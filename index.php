<?php
require_once __DIR__.'/includes/config.php';
start_resident_session();
if (is_resident()) { header('Location: /BarangayProject/resident/home.php'); exit; }

$error=''; $errors=[]; $tab=$_GET['tab']??'login'; $old=[];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $tab=$_POST['tab']??'login'; $old=$_POST;

    if ($tab==='login') {
        $user=trim($_POST['username']??''); $pass=trim($_POST['password']??'');
        if (!$user) $errors['username']='Username is required.';
        if (!$pass) $errors['password']='Password is required.';
        if (!$errors) {
            $acc=db_fetch_one('SELECT * FROM resident_accounts WHERE username=?',[$user]);
            if ($acc && verify_password($pass,$acc['password'])) {
                maybe_upgrade_hash($pass,$acc['password'],$acc['id']);
                $_SESSION['resident_account_id']=$acc['id'];
                $_SESSION['resident_name']=$acc['full_name'];
                header('Location: /BarangayProject/resident/home.php'); exit;
            }
            $error='Invalid username or password.';
        }
    } elseif ($tab==='register') {
        $fname=trim($_POST['full_name']??''); $address=trim($_POST['address']??'');
        $contact=trim($_POST['contact']??''); $uname=trim($_POST['reg_username']??'');
        $pwd=trim($_POST['reg_password']??''); $cpwd=trim($_POST['confirm_password']??'');
        if (!$fname)   $errors['full_name']='Full name is required.';
        if (!$address) $errors['address']='Address is required.';
        if (!$contact) $errors['contact']='Contact number is required.';
        elseif (!validate_contact($contact)) $errors['contact']='Enter a valid PH number (09XXXXXXXXX).';
        if (!$uname)   $errors['reg_username']='Username is required.';
        if (!$pwd)     $errors['reg_password']='Password is required.';
        elseif ($e=validate_password($pwd)) $errors['reg_password']=$e;
        if ($pwd&&$pwd!==$cpwd) $errors['confirm_password']='Passwords do not match.';
        if (!$errors && db_fetch_one('SELECT id FROM resident_accounts WHERE username=?',[$uname]))
            $errors['reg_username']='Username already taken.';
        if (!$errors) {
            $rid='RES-'.strtoupper(substr(uniqid(),-6));
            db_execute('INSERT INTO residents (resident_id,name,address,contact) VALUES (?,?,?,?)',[$rid,$fname,$address,$contact]);
            db_execute('INSERT INTO resident_accounts (username,password,full_name,address,contact,resident_id) VALUES (?,?,?,?,?,?)',[$uname,hash_password($pwd),$fname,$address,$contact,$rid]);
            flash('success',"Account created! Resident ID: <strong>{$rid}</strong>. You may now sign in.");
            header('Location: /BarangayProject/index.php?tab=login'); exit;
        }
        $error='Please fix the errors below.';
        $tab='register';
    }
}
$flash=get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Resident Portal — Barangay San Rafael</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    :root{--navy:#0B1F4B;--navy-mid:#152D6E;--navy-light:#1E3A8A;--gold:#D4A017;--gold-b:#F0B429;--border:#E2E8F0;--muted:#64748B;--text:#1E293B;}
    *{box-sizing:border-box;margin:0;padding:0;}
    body{font-family:'DM Sans',sans-serif;min-height:100vh;display:flex;background:#EEF2F9;-webkit-font-smoothing:antialiased;overflow:hidden;}
    h1,h2,h3,h4,h5,h6{font-family:'Sora',sans-serif;}

    /* LEFT HERO */
    .auth-left{flex:1;background:linear-gradient(155deg,#061535 0%,#0B1F4B 45%,#152D6E 100%);display:flex;flex-direction:column;align-items:center;justify-content:center;padding:40px 36px;position:relative;overflow:hidden;}
    .auth-left::before{content:'';position:absolute;top:-20%;right:-15%;width:400px;height:400px;background:radial-gradient(circle,rgba(212,160,23,.16) 0%,transparent 65%);pointer-events:none;}
    .auth-left-content{position:relative;z-index:1;text-align:center;max-width:340px;}
    .auth-logo{width:80px;height:80px;border-radius:50%;border:2px solid var(--gold);object-fit:cover;box-shadow:0 0 28px rgba(212,160,23,.35);margin-bottom:16px;}
    .auth-left h1{color:#fff;font-size:1.35rem;font-weight:800;margin-bottom:6px;}
    .auth-tag{color:var(--gold-b);font-size:.76rem;font-weight:600;margin-bottom:12px;letter-spacing:.04em;}
    .auth-desc{color:rgba(255,255,255,.45);font-size:.82rem;line-height:1.6;margin-bottom:22px;}
    .feat{display:flex;align-items:flex-start;gap:10px;margin-bottom:10px;text-align:left;}
    .feat-icon{width:30px;height:30px;border-radius:7px;background:rgba(212,160,23,.12);border:1px solid rgba(212,160,23,.2);display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0;}
    .feat h6{color:#fff;font-size:.76rem;font-weight:600;margin:0 0 1px;}
    .feat p{color:rgba(255,255,255,.38);font-size:.68rem;line-height:1.3;margin:0;}

    /* RIGHT FORM — fixed full height, no scroll */
    .auth-right{width:460px;flex-shrink:0;background:#fff;display:flex;flex-direction:column;height:100vh;overflow:hidden;}
    .auth-right-inner{flex:1;display:flex;flex-direction:column;justify-content:center;padding:28px 36px;overflow:hidden;}

    /* TAB SWITCHER */
    .auth-tabs{display:flex;gap:3px;background:#F1F5F9;border-radius:9px;padding:3px;margin-bottom:16px;flex-shrink:0;}
    .auth-tab-btn{flex:1;border:none;background:transparent;border-radius:7px;padding:8px 10px;font-family:'Sora',sans-serif;font-size:.78rem;font-weight:600;color:var(--muted);cursor:pointer;transition:all .18s;}
    .auth-tab-btn.active{background:#fff;color:var(--navy);box-shadow:0 1px 4px rgba(0,0,0,.1);}

    /* FIELDS — compact */
    .field-group{margin-bottom:10px;}
    .field-label{display:block;font-size:.68rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;}
    .field-input{width:100%;border:1.5px solid var(--border);border-radius:8px;padding:8px 12px;font-family:'DM Sans',sans-serif;font-size:.875rem;color:var(--text);outline:none;transition:border-color .15s,box-shadow .15s;}
    .field-input:focus{border-color:var(--navy-light);box-shadow:0 0 0 3px rgba(30,58,138,.1);}
    .field-input.is-invalid{border-color:#dc3545;}
    .field-error{font-size:.68rem;color:#dc3545;margin-top:2px;display:block;}
    .field-wrap{position:relative;}.field-wrap .field-input{padding-right:38px;}
    .pw-toggle{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--muted);font-size:.9rem;padding:1px;}
    .pw-toggle:hover{color:var(--navy);}

    /* SUBMIT */
    .btn-submit{width:100%;padding:10px;border:none;border-radius:8px;background:var(--navy);color:#fff;font-family:'Sora',sans-serif;font-size:.88rem;font-weight:700;cursor:pointer;transition:all .18s;margin-top:6px;display:flex;align-items:center;justify-content:center;gap:7px;}
    .btn-submit:hover{background:var(--navy-mid);transform:translateY(-1px);box-shadow:0 3px 12px rgba(11,31,75,.2);}

    /* ALERTS */
    .auth-alert{border-radius:8px;padding:9px 13px;font-size:.8rem;display:flex;align-items:flex-start;gap:8px;margin-bottom:12px;flex-shrink:0;}
    .auth-alert.error{background:#FEF2F2;color:#991B1B;border:1px solid #FECACA;}
    .auth-alert.success{background:#F0FDF4;color:#166534;border:1px solid #BBF7D0;}

    /* PANES */
    .auth-pane{display:none;flex:1;flex-direction:column;overflow:hidden;}
    .auth-pane.active{display:flex;}

    /* FORM HEADING */
    .form-head{margin-bottom:14px;flex-shrink:0;}
    .form-head h2{font-size:1.3rem;font-weight:800;color:var(--navy);margin:0 0 2px;}
    .form-head p{color:var(--muted);font-size:.78rem;margin:0;}

    /* FOOTER NOTE */
    .auth-note{text-align:center;padding-top:10px;margin-top:8px;border-top:1px solid var(--border);font-size:.73rem;color:var(--muted);flex-shrink:0;}
    .auth-note a{color:var(--navy);font-weight:600;text-decoration:none;}

    /* FORGOT PASSWORD LINK */
    .forgot-link{text-align:right;margin-top:-4px;margin-bottom:8px;}
    .forgot-link a{font-size:.72rem;color:var(--muted);text-decoration:none;}
    .forgot-link a:hover{color:var(--navy);}

    @keyframes fadeUp{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
    .auth-right{animation:fadeUp .35s ease;}

    @media(max-width:860px){
      body{flex-direction:column;overflow:auto;}
      .auth-left{min-height:auto;padding:28px 24px 20px;flex:none;}
      .auth-left .feat{display:none;}
      .auth-left-content{max-width:100%;}
      .auth-right{width:100%;height:auto;flex:none;}
      .auth-right-inner{padding:24px 22px 32px;}
      .auth-pane{display:none!important;}.auth-pane.active{display:flex!important;}
      body{overflow:auto;}
    }
  </style>
</head>
<body>

<!-- LEFT HERO -->
<div class="auth-left">
  <div class="auth-left-content">
    <img src="/BarangayProject/Logo.jpg" alt="Logo" class="auth-logo">
    <h1>Barangay San Rafael</h1>
    <div class="auth-tag">City of San Pablo, Laguna &nbsp;·&nbsp; Resident Portal</div>
    <p class="auth-desc">Access barangay services online. Submit requests, file complaints, and track your documents — anytime, anywhere.</p>
    <div class="feat"><div class="feat-icon">📄</div><div><h6>Document Requests</h6><p>Request clearances, certificates, and permits</p></div></div>
    <div class="feat"><div class="feat-icon">📣</div><div><h6>File Complaints</h6><p>Report community concerns and track resolution</p></div></div>
    <div class="feat"><div class="feat-icon">🔍</div><div><h6>Real-time Tracking</h6><p>Monitor all your submissions in one place</p></div></div>
  </div>
</div>

<!-- RIGHT FORM -->
<div class="auth-right">
  <div class="auth-right-inner">

    <div class="form-head">
      <h2 id="formH"><?= $tab==='register'?'Create Account':'Welcome back' ?></h2>
      <p id="formS"><?= $tab==='register'?'Register as a Barangay San Rafael resident':'Sign in to your resident account' ?></p>
    </div>

    <div class="auth-tabs">
      <button class="auth-tab-btn <?= $tab!=='register'?'active':'' ?>" onclick="sw('login',this)"><i class="bi bi-box-arrow-in-right me-1"></i>Sign In</button>
      <button class="auth-tab-btn <?= $tab==='register'?'active':'' ?>" onclick="sw('register',this)"><i class="bi bi-person-plus me-1"></i>Register</button>
    </div>

    <?php if($flash): ?>
    <div class="auth-alert <?= $flash['type']==='success'?'success':'error' ?>">
      <i class="bi bi-<?= $flash['type']==='success'?'check-circle':'exclamation-circle' ?>"></i>
      <span><?= $flash['msg'] ?></span>
    </div>
    <?php endif; ?>
    <?php if($error): ?>
    <div class="auth-alert error"><i class="bi bi-exclamation-circle"></i><span><?= e($error) ?></span></div>
    <?php endif; ?>

    <!-- LOGIN PANE -->
    <div class="auth-pane <?= $tab!=='register'?'active':'' ?>" id="pane-login">
      <form method="post" novalidate style="display:flex;flex-direction:column;flex:1;">
        <?= csrf_field() ?><input type="hidden" name="tab" value="login">
        <div class="field-group">
          <label class="field-label">Username</label>
          <input type="text" name="username" class="field-input <?= isset($errors['username'])?'is-invalid':'' ?>" placeholder="Enter your username" value="<?= e($old['username']??'') ?>" autocomplete="username" required>
          <?php if(isset($errors['username'])): ?><span class="field-error"><?= e($errors['username']) ?></span><?php endif; ?>
        </div>
        <div class="field-group">
          <label class="field-label">Password</label>
          <div class="field-wrap">
            <input type="password" name="password" id="loginPw" class="field-input" placeholder="Enter your password" autocomplete="current-password" required>
            <button type="button" class="pw-toggle" onclick="tp('loginPw',this)"><i class="bi bi-eye"></i></button>
          </div>
        </div>
        <div class="forgot-link"><a href="/BarangayProject/resident/forgot_password.php">Forgot password?</a></div>
        <button type="submit" class="btn-submit"><i class="bi bi-box-arrow-in-right"></i>Sign In</button>
      </form>
      <div class="auth-note">Don't have an account? <a href="#" onclick="sw('register',null)">Register here</a></div>
    </div>

    <!-- REGISTER PANE — compact 2-column layout, no scroll -->
    <div class="auth-pane <?= $tab==='register'?'active':'' ?>" id="pane-register">
      <form method="post" novalidate style="display:flex;flex-direction:column;flex:1;">
        <?= csrf_field() ?><input type="hidden" name="tab" value="register">
        <!-- Row 1: Full Name -->
        <div class="field-group">
          <label class="field-label">Full Name <span style="color:#dc3545;">*</span></label>
          <input type="text" name="full_name" class="field-input <?= isset($errors['full_name'])?'is-invalid':'' ?>" placeholder="Juan Dela Cruz" value="<?= e($old['full_name']??'') ?>" required>
          <?php if(isset($errors['full_name'])): ?><span class="field-error"><?= e($errors['full_name']) ?></span><?php endif; ?>
        </div>
        <!-- Row 2: Address -->
        <div class="field-group">
          <label class="field-label">Address <span style="color:#dc3545;">*</span></label>
          <input type="text" name="address" class="field-input <?= isset($errors['address'])?'is-invalid':'' ?>" placeholder="Purok / Street, Barangay" value="<?= e($old['address']??'') ?>" required>
          <?php if(isset($errors['address'])): ?><span class="field-error"><?= e($errors['address']) ?></span><?php endif; ?>
        </div>
        <!-- Row 3: Contact + Username side by side -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
          <div class="field-group">
            <label class="field-label">Contact <span style="color:#dc3545;">*</span></label>
            <input type="text" name="contact" class="field-input <?= isset($errors['contact'])?'is-invalid':'' ?>" placeholder="09XXXXXXXXX" value="<?= e($old['contact']??'') ?>" required>
            <?php if(isset($errors['contact'])): ?><span class="field-error"><?= e($errors['contact']) ?></span><?php endif; ?>
          </div>
          <div class="field-group">
            <label class="field-label">Username <span style="color:#dc3545;">*</span></label>
            <input type="text" name="reg_username" class="field-input <?= isset($errors['reg_username'])?'is-invalid':'' ?>" placeholder="username" value="<?= e($old['reg_username']??'') ?>" required>
            <?php if(isset($errors['reg_username'])): ?><span class="field-error"><?= e($errors['reg_username']) ?></span><?php endif; ?>
          </div>
        </div>
        <!-- Row 4: Password + Confirm side by side -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
          <div class="field-group">
            <label class="field-label">Password <span style="color:#dc3545;">*</span></label>
            <div class="field-wrap">
              <input type="password" name="reg_password" id="regPw" class="field-input <?= isset($errors['reg_password'])?'is-invalid':'' ?>" placeholder="Min. 6 chars" required>
              <button type="button" class="pw-toggle" onclick="tp('regPw',this)"><i class="bi bi-eye"></i></button>
            </div>
            <?php if(isset($errors['reg_password'])): ?><span class="field-error"><?= e($errors['reg_password']) ?></span><?php endif; ?>
          </div>
          <div class="field-group">
            <label class="field-label">Confirm <span style="color:#dc3545;">*</span></label>
            <div class="field-wrap">
              <input type="password" name="confirm_password" id="regPw2" class="field-input <?= isset($errors['confirm_password'])?'is-invalid':'' ?>" placeholder="Repeat" required>
              <button type="button" class="pw-toggle" onclick="tp('regPw2',this)"><i class="bi bi-eye"></i></button>
            </div>
            <?php if(isset($errors['confirm_password'])): ?><span class="field-error"><?= e($errors['confirm_password']) ?></span><?php endif; ?>
          </div>
        </div>
        <button type="submit" class="btn-submit"><i class="bi bi-person-check"></i>Create My Account</button>
      </form>
      <div class="auth-note">Already have an account? <a href="#" onclick="sw('login',null)">Sign in here</a></div>
    </div>

    <p style="text-align:center;color:#CBD5E1;font-size:.65rem;margin-top:10px;">
      Barangay San Rafael &copy; <?= date('Y') ?> &nbsp;·&nbsp; City of San Pablo, Laguna
      &nbsp;·&nbsp;<a href="/BarangayProject/admin/login.php" style="color:#CBD5E1;text-decoration:none;" title="Admin">&#9679;</a>
    </p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function sw(tab,btn){
  document.querySelectorAll('.auth-pane').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('.auth-tab-btn').forEach(b=>b.classList.remove('active'));
  document.getElementById('pane-'+tab).classList.add('active');
  if(btn) btn.classList.add('active');
  else document.querySelectorAll('.auth-tab-btn')[tab==='login'?0:1].classList.add('active');
  document.getElementById('formH').textContent=tab==='register'?'Create Account':'Welcome back';
  document.getElementById('formS').textContent=tab==='register'?'Register as a Barangay San Rafael resident':'Sign in to your resident account';
}
function tp(id,btn){
  const i=document.getElementById(id);
  i.type=i.type==='password'?'text':'password';
  btn.innerHTML=i.type==='text'?'<i class="bi bi-eye-slash"></i>':'<i class="bi bi-eye"></i>';
}
</script>
</body></html>
