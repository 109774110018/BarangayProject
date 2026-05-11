<?php
require_once __DIR__.'/../includes/config.php';
start_resident_session();
if (is_resident()) { header('Location: /BarangayProject/resident/home.php'); exit; }

$step   = $_GET['step'] ?? '1';
$errors = [];
$success = '';

// Step 1: Enter username + contact to verify identity
// Step 2: Set new password
// Step 3: Done

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $step = $_POST['step'] ?? '1';

    if ($step === '1') {
        $uname   = trim($_POST['username'] ?? '');
        $contact = trim($_POST['contact']  ?? '');
        if (!$uname)   $errors['username'] = 'Username is required.';
        if (!$contact) $errors['contact']  = 'Contact number is required.';
        if (!$errors) {
            $acc = db_fetch_one('SELECT * FROM resident_accounts WHERE username=? AND contact=?', [$uname, $contact]);
            if ($acc) {
                $_SESSION['reset_account_id'] = $acc['id'];
                $_SESSION['reset_username']   = $acc['username'];
                header('Location: /BarangayProject/resident/forgot_password.php?step=2');
                exit;
            }
            $errors['general'] = 'No account found matching that username and contact number.';
        }
    }

    if ($step === '2') {
        if (empty($_SESSION['reset_account_id'])) {
            header('Location: /BarangayProject/resident/forgot_password.php?step=1'); exit;
        }
        $new  = trim($_POST['new_password']     ?? '');
        $conf = trim($_POST['confirm_password'] ?? '');
        if (!$new)  $errors['new_password']      = 'New password is required.';
        elseif ($e = validate_password($new)) $errors['new_password'] = $e;
        if ($new && $new !== $conf) $errors['confirm_password'] = 'Passwords do not match.';
        if (!$errors) {
            db_execute('UPDATE resident_accounts SET password=? WHERE id=?',
                       [hash_password($new), $_SESSION['reset_account_id']]);
            $uname = $_SESSION['reset_username'];
            unset($_SESSION['reset_account_id'], $_SESSION['reset_username']);
            flash('success', "Password reset successfully! You may now <strong>sign in</strong>.");
            header('Location: /BarangayProject/index.php?tab=login'); exit;
        }
        $step = '2';
    }
}

// Guard step 2 — must have session token
if ($step === '2' && empty($_SESSION['reset_account_id'])) {
    header('Location: /BarangayProject/resident/forgot_password.php?step=1'); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Forgot Password — Barangay San Rafael</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    :root{--navy:#0B1F4B;--navy-mid:#152D6E;--gold:#D4A017;--gold-b:#F0B429;--border:#E2E8F0;--muted:#64748B;--text:#1E293B;}
    *{box-sizing:border-box;margin:0;padding:0;}
    body{font-family:'DM Sans',sans-serif;min-height:100vh;background:linear-gradient(155deg,#061535 0%,#0B1F4B 45%,#152D6E 100%);display:flex;align-items:center;justify-content:center;padding:20px;}
    h1,h2,h3,h4,h5,h6{font-family:'Sora',sans-serif;}
    .card{background:#fff;border-radius:18px;padding:40px 36px;width:100%;max-width:420px;box-shadow:0 24px 60px rgba(0,0,0,.35);animation:fadeUp .35s ease;}
    @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
    .logo{width:60px;height:60px;border-radius:50%;border:2px solid var(--gold);object-fit:cover;box-shadow:0 0 20px rgba(212,160,23,.3);margin:0 auto 12px;display:block;}
    h2{font-size:1.25rem;font-weight:800;color:var(--navy);text-align:center;margin-bottom:4px;}
    .sub{color:var(--muted);font-size:.82rem;text-align:center;margin-bottom:24px;line-height:1.5;}
    /* Steps indicator */
    .steps{display:flex;align-items:center;justify-content:center;gap:0;margin-bottom:24px;}
    .step-dot{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.78rem;font-weight:700;font-family:'Sora',sans-serif;transition:all .2s;}
    .step-dot.done{background:var(--navy);color:#fff;}
    .step-dot.active{background:var(--gold);color:var(--navy);box-shadow:0 0 0 3px rgba(212,160,23,.25);}
    .step-dot.pending{background:#E2E8F0;color:#94A3B8;}
    .step-line{flex:1;height:2px;background:#E2E8F0;max-width:48px;}
    .step-line.done{background:var(--navy);}
    /* Fields */
    .f-group{margin-bottom:14px;}
    .f-label{display:block;font-size:.72rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px;}
    .f-input{width:100%;border:1.5px solid var(--border);border-radius:9px;padding:10px 14px;font-family:'DM Sans',sans-serif;font-size:.9rem;color:var(--text);outline:none;transition:border-color .15s,box-shadow .15s;}
    .f-input:focus{border-color:#1E3A8A;box-shadow:0 0 0 3px rgba(30,58,138,.1);}
    .f-input.is-invalid{border-color:#dc3545;}
    .f-error{font-size:.72rem;color:#dc3545;margin-top:3px;display:block;}
    .f-wrap{position:relative;}.f-wrap .f-input{padding-right:42px;}
    .pw-toggle{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--muted);font-size:.95rem;}
    .pw-toggle:hover{color:var(--navy);}
    /* Buttons */
    .btn-main{width:100%;padding:11px;border:none;border-radius:9px;background:var(--navy);color:#fff;font-family:'Sora',sans-serif;font-size:.9rem;font-weight:700;cursor:pointer;transition:all .18s;margin-top:4px;}
    .btn-main:hover{background:var(--navy-mid);transform:translateY(-1px);}
    .err-box{background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:10px 14px;color:#991B1B;font-size:.82rem;display:flex;align-items:center;gap:8px;margin-bottom:16px;}
    .back-link{text-align:center;margin-top:18px;padding-top:16px;border-top:1px solid var(--border);}
    .back-link a{color:var(--navy);font-size:.78rem;font-weight:600;text-decoration:none;}
    .back-link a:hover{text-decoration:underline;}
    .info-box{background:#EFF6FF;border:1px solid #BFDBFE;border-radius:8px;padding:10px 14px;color:#1E40AF;font-size:.8rem;margin-bottom:16px;display:flex;align-items:flex-start;gap:8px;line-height:1.5;}
  </style>
</head>
<body>
<div class="card">
  <img src="/BarangayProject/Logo.jpg" alt="Logo" class="logo">
  <h2>Reset Password</h2>
  <p class="sub">Barangay San Rafael Resident Portal</p>

  <!-- Step Indicator -->
  <div class="steps">
    <div class="step-dot <?= $step>='1'?($step==='1'?'active':'done'):'pending' ?>">
      <?= $step>'1'?'<i class="bi bi-check-lg"></i>':'1' ?>
    </div>
    <div class="step-line <?= $step>'1'?'done':'' ?>"></div>
    <div class="step-dot <?= $step==='2'?'active':($step>'2'?'done':'pending') ?>">
      <?= $step>'2'?'<i class="bi bi-check-lg"></i>':'2' ?>
    </div>
  </div>

  <?php if (isset($errors['general'])): ?>
  <div class="err-box"><i class="bi bi-exclamation-circle-fill"></i><?= e($errors['general']) ?></div>
  <?php endif; ?>

  <?php if ($step === '1'): ?>
  <!-- STEP 1: Verify Identity -->
  <div class="info-box"><i class="bi bi-info-circle-fill mt-1" style="flex-shrink:0;"></i>Enter your <strong>username</strong> and <strong>registered contact number</strong> to verify your identity.</div>
  <form method="post" novalidate><?= csrf_field() ?>
    <input type="hidden" name="step" value="1">
    <div class="f-group">
      <label class="f-label">Username</label>
      <input type="text" name="username" class="f-input <?= isset($errors['username'])?'is-invalid':'' ?>" placeholder="Your username" value="<?= e($_POST['username']??'') ?>" required>
      <?php if(isset($errors['username'])): ?><span class="f-error"><?= e($errors['username']) ?></span><?php endif; ?>
    </div>
    <div class="f-group">
      <label class="f-label">Registered Contact No.</label>
      <input type="text" name="contact" class="f-input <?= isset($errors['contact'])?'is-invalid':'' ?>" placeholder="09XXXXXXXXX" value="<?= e($_POST['contact']??'') ?>" required>
      <?php if(isset($errors['contact'])): ?><span class="f-error"><?= e($errors['contact']) ?></span><?php endif; ?>
    </div>
    <button type="submit" class="btn-main"><i class="bi bi-shield-check me-1"></i>Verify Identity</button>
  </form>

  <?php elseif ($step === '2'): ?>
  <!-- STEP 2: New Password -->
  <div class="info-box"><i class="bi bi-check-circle-fill mt-1" style="flex-shrink:0;color:#15803D;"></i>Identity verified for <strong><?= e($_SESSION['reset_username']) ?></strong>. Set your new password below.</div>
  <form method="post" novalidate><?= csrf_field() ?>
    <input type="hidden" name="step" value="2">
    <div class="f-group">
      <label class="f-label">New Password</label>
      <div class="f-wrap">
        <input type="password" name="new_password" id="newPw" class="f-input <?= isset($errors['new_password'])?'is-invalid':'' ?>" placeholder="Min. 6 characters" required>
        <button type="button" class="pw-toggle" onclick="tp('newPw',this)"><i class="bi bi-eye"></i></button>
      </div>
      <?php if(isset($errors['new_password'])): ?><span class="f-error"><?= e($errors['new_password']) ?></span><?php endif; ?>
    </div>
    <div class="f-group">
      <label class="f-label">Confirm New Password</label>
      <div class="f-wrap">
        <input type="password" name="confirm_password" id="confPw" class="f-input <?= isset($errors['confirm_password'])?'is-invalid':'' ?>" placeholder="Repeat new password" required>
        <button type="button" class="pw-toggle" onclick="tp('confPw',this)"><i class="bi bi-eye"></i></button>
      </div>
      <?php if(isset($errors['confirm_password'])): ?><span class="f-error"><?= e($errors['confirm_password']) ?></span><?php endif; ?>
    </div>
    <button type="submit" class="btn-main"><i class="bi bi-key me-1"></i>Reset Password</button>
  </form>
  <?php endif; ?>

  <div class="back-link">
    <a href="/BarangayProject/index.php"><i class="bi bi-arrow-left me-1"></i>Back to Sign In</a>
  </div>
</div>
<script>function tp(id,btn){const i=document.getElementById(id);i.type=i.type==='password'?'text':'password';btn.innerHTML=i.type==='text'?'<i class="bi bi-eye-slash"></i>':'<i class="bi bi-eye"></i>';}</script>
</body></html>
