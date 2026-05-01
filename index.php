<?php
require_once __DIR__ . '/includes/config.php';
start_resident_session();


if (is_resident()) { header('Location: /BarangayProject/resident/home.php');    exit; }

$error = '';
$tab   = $_GET['tab'] ?? 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tab  = $_POST['tab'] ?? 'login';
    $user = trim($_POST['username'] ?? '');
    $pass = trim($_POST['password'] ?? '');

    if ($tab === 'login') {
        $acc = db_fetch_one('SELECT * FROM resident_accounts WHERE username = ? AND password = ?', [$user, $pass]);
        if ($acc) {
            $_SESSION['resident_account_id'] = $acc['id'];
            $_SESSION['resident_name']       = $acc['full_name'];
            header('Location: /BarangayProject/resident/home.php');
            exit;
        }
        $error = 'Invalid username or password.';

    } elseif ($tab === 'register') {
        $fname   = trim($_POST['full_name'] ?? '');
        $address = trim($_POST['address']   ?? '');
        $contact = trim($_POST['contact']   ?? '');
        $uname   = trim($_POST['reg_username'] ?? '');
        $pwd     = trim($_POST['reg_password'] ?? '');
        $cpwd    = trim($_POST['confirm_password'] ?? '');

        if (!$fname || !$address || !$contact || !$uname || !$pwd) {
            $error = 'All fields are required.';
        } elseif ($pwd !== $cpwd) {
            $error = 'Passwords do not match.';
        } elseif (strlen($pwd) < 4) {
            $error = 'Password must be at least 4 characters.';
        } elseif (db_fetch_one('SELECT id FROM resident_accounts WHERE username = ?', [$uname])) {
            $error = 'Username already taken.';
        } else {
            $rid = 'RES-' . strtoupper(substr(uniqid(), -6));
            db_execute('INSERT INTO residents (resident_id, name, address, contact) VALUES (?,?,?,?)', [$rid, $fname, $address, $contact]);
            db_execute('INSERT INTO resident_accounts (username, password, full_name, address, contact, resident_id) VALUES (?,?,?,?,?,?)', [$uname, $pwd, $fname, $address, $contact, $rid]);
            flash('success', "Account created! Your Resident ID: <strong>{$rid}</strong>. You may now log in.");
            header('Location: /BarangayProject/index.php?tab=login');
            exit;
        }
        $tab = 'register';
    }
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Resident Portal — Brgy. San Rafael</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --navy: #0B1F4B; --navy-mid: #152D6E; --navy-light: #1E3A8A;
      --gold: #D4A017; --gold-bright: #F0B429; --gold-pale: #FEF9EC;
      --bg: #EEF2F9; --surface: #fff; --border: #E2E8F0;
      --text: #1E293B; --muted: #64748B;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'DM Sans', sans-serif;
      min-height: 100vh; display: flex;
      background: #EEF2F9;
      -webkit-font-smoothing: antialiased;
    }
    h1,h2,h3,h4,h5,h6,strong { font-family: 'Sora', sans-serif; }

    /* LEFT PANEL */
    .auth-left {
      flex: 1; min-height: 100vh;
      background: linear-gradient(155deg, #061535 0%, #0B1F4B 40%, #152D6E 80%, #0D2558 100%);
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      padding: 60px 48px; position: relative; overflow: hidden;
    }
    .auth-left::before {
      content: '';
      position: absolute; top: -30%; right: -20%;
      width: 500px; height: 500px;
      background: radial-gradient(circle, rgba(212,160,23,.18) 0%, transparent 65%);
      pointer-events: none;
    }
    .auth-left::after {
      content: '';
      position: absolute; bottom: -20%; left: -15%;
      width: 400px; height: 400px;
      background: radial-gradient(circle, rgba(30,58,138,.5) 0%, transparent 65%);
      pointer-events: none;
    }
    .auth-left-content { position: relative; z-index: 1; text-align: center; max-width: 380px; }
    .auth-logo {
      width: 100px; height: 100px; border-radius: 50%;
      border: 3px solid var(--gold);
      object-fit: cover;
      box-shadow: 0 0 40px rgba(212,160,23,.4);
      margin-bottom: 28px;
    }
    .auth-left h1 { color: #fff; font-size: 1.6rem; font-weight: 800; line-height: 1.2; margin-bottom: 10px; }
    .auth-left .auth-tagline { color: var(--gold-bright); font-size: .85rem; font-weight: 500; margin-bottom: 32px; letter-spacing: .02em; }
    .auth-left p.desc { color: rgba(255,255,255,.55); font-size: .88rem; line-height: 1.65; }
    .feature-list { margin-top: 36px; display: flex; flex-direction: column; gap: 14px; text-align: left; }
    .feature-item { display: flex; align-items: flex-start; gap: 13px; }
    .feature-icon { width: 36px; height: 36px; border-radius: 9px; background: rgba(212,160,23,.15); border: 1px solid rgba(212,160,23,.2); display: flex; align-items: center; justify-content: center; color: var(--gold-bright); font-size: 1rem; flex-shrink: 0; margin-top: 1px; }
    .feature-item h6 { color: #fff; font-size: .83rem; font-weight: 600; margin-bottom: 1px; }
    .feature-item p { color: rgba(255,255,255,.45); font-size: .77rem; line-height: 1.4; }

    /* RIGHT PANEL */
    .auth-right {
      width: 480px; flex-shrink: 0;
      background: var(--surface);
      display: flex; flex-direction: column;
      justify-content: center; padding: 48px 44px;
      min-height: 100vh;
    }
    .auth-right .form-header { margin-bottom: 32px; }
    .auth-right .form-header h2 { font-size: 1.55rem; font-weight: 800; color: var(--navy); }
    .auth-right .form-header p { color: var(--muted); font-size: .875rem; margin-top: 5px; }

    /* TAB SWITCHER */
    .auth-tabs { display: flex; gap: 4px; background: #F1F5F9; border-radius: 10px; padding: 4px; margin-bottom: 28px; }
    .auth-tab-btn {
      flex: 1; border: none; background: transparent; border-radius: 7px;
      padding: 9px 12px; font-family: 'Sora', sans-serif; font-size: .83rem;
      font-weight: 600; color: var(--muted); cursor: pointer; transition: all .18s;
    }
    .auth-tab-btn.active { background: var(--surface); color: var(--navy); box-shadow: 0 1px 4px rgba(0,0,0,.1); }

    /* FORM FIELDS */
    .field-group { margin-bottom: 18px; }
    .field-label { display: block; font-size: .75rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; margin-bottom: 7px; }
    .field-input {
      width: 100%; border: 1.5px solid var(--border); border-radius: 9px;
      padding: 11px 14px; font-family: 'DM Sans', sans-serif; font-size: .9rem;
      color: var(--text); background: #fff; transition: border-color .15s, box-shadow .15s;
      outline: none;
    }
    .field-input:focus { border-color: var(--navy-light); box-shadow: 0 0 0 3px rgba(30,58,138,.1); }
    .field-wrap { position: relative; }
    .field-wrap .field-input { padding-right: 44px; }
    .field-wrap .pw-toggle { position: absolute; right: 13px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--muted); font-size: 1rem; padding: 2px; }
    .field-wrap .pw-toggle:hover { color: var(--navy); }

    /* SUBMIT BUTTON */
    .btn-submit {
      width: 100%; padding: 13px; border: none; border-radius: 9px;
      background: var(--navy); color: #fff;
      font-family: 'Sora', sans-serif; font-size: .93rem; font-weight: 700;
      cursor: pointer; transition: all .18s; letter-spacing: .01em;
      display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-submit:hover { background: var(--navy-mid); transform: translateY(-1px); box-shadow: 0 4px 16px rgba(11,31,75,.22); }
    .btn-submit:active { transform: none; }

    /* ALERTS */
    .auth-alert {
      border-radius: 9px; padding: 12px 16px; font-size: .86rem;
      display: flex; align-items: flex-start; gap: 10px; margin-bottom: 20px;
    }
    .auth-alert.error { background: #FEF2F2; color: #991B1B; border: 1px solid #FECACA; }
    .auth-alert.success { background: #F0FDF4; color: #166534; border: 1px solid #BBF7D0; }
    .auth-alert i { margin-top: 1px; flex-shrink: 0; }

    /* DIVIDER */
    .or-divider { display: flex; align-items: center; gap: 12px; margin: 22px 0; }
    .or-divider::before, .or-divider::after { content: ''; flex: 1; height: 1px; background: var(--border); }
    .or-divider span { color: var(--muted); font-size: .78rem; white-space: nowrap; }

    /* FOOTER NOTE */
    .auth-footer-note { margin-top: 28px; padding-top: 20px; border-top: 1px solid var(--border); text-align: center; }
    .auth-footer-note p { font-size: .77rem; color: var(--muted); }
    .auth-footer-note a { color: var(--navy); font-weight: 600; text-decoration: none; }
    .auth-footer-note a:hover { text-decoration: underline; }

    /* ANIMATIONS */
    @keyframes fadeUp { from { opacity:0; transform:translateY(14px); } to { opacity:1; transform:translateY(0); } }
    .auth-right { animation: fadeUp .4s ease; }
    .auth-left-content { animation: fadeUp .5s ease .1s both; }

    /* PANE TRANSITIONS */
    .auth-pane { display: none; }
    .auth-pane.active { display: block; animation: fadeUp .25s ease; }

    @media (max-width: 900px) {
      body { flex-direction: column; }
      .auth-left { min-height: auto; padding: 40px 30px 30px; }
      .auth-left .feature-list { display: none; }
      .auth-right { width: 100%; min-height: auto; padding: 36px 28px 48px; }
    }
  </style>
</head>
<body>

<!-- LEFT HERO PANEL -->
<div class="auth-left">
  <div class="auth-left-content">
    <img src="/BarangayProject/Logo.jpg" alt="Brgy. San Rafael Logo" class="auth-logo">
    <h1>Barangay San Rafael</h1>
    <div class="auth-tagline">City of San Pablo, Laguna • Resident Portal</div>
    <p class="desc">Access barangay services online. Submit requests, file complaints, and track your documents — anytime, anywhere.</p>
    <div class="feature-list">
      <div class="feature-item">
        <div class="feature-icon"><i class="bi bi-file-earmark-check"></i></div>
        <div>
          <h6>Document Requests</h6>
          <p>Request barangay clearances, certificates, and permits online</p>
        </div>
      </div>
      <div class="feature-item">
        <div class="feature-icon"><i class="bi bi-megaphone"></i></div>
        <div>
          <h6>File Complaints</h6>
          <p>Report community concerns and track their resolution status</p>
        </div>
      </div>
      <div class="feature-item">
        <div class="feature-icon"><i class="bi bi-search"></i></div>
        <div>
          <h6>Real-time Tracking</h6>
          <p>Monitor the progress of all your submissions in one place</p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- RIGHT FORM PANEL -->
<div class="auth-right">

  <?php if ($flash): ?>
  <div class="auth-alert <?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
    <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
    <span><?= $flash['msg'] ?></span>
  </div>
  <?php endif; ?>

  <!-- Tab state: login or register -->
  <div class="form-header">
    <h2 id="formHeading"><?= $tab === 'register' ? 'Create Account' : 'Welcome back' ?></h2>
    <p id="formSubhead"><?= $tab === 'register' ? 'Register as a Barangay San Rafael resident' : 'Sign in to your resident account' ?></p>
  </div>

  <div class="auth-tabs">
    <button class="auth-tab-btn <?= $tab !== 'register' ? 'active' : '' ?>" onclick="switchTab('login')">
      <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
    </button>
    <button class="auth-tab-btn <?= $tab === 'register' ? 'active' : '' ?>" onclick="switchTab('register')">
      <i class="bi bi-person-plus me-1"></i> Register
    </button>
  </div>

  <?php if ($error): ?>
  <div class="auth-alert error">
    <i class="bi bi-exclamation-circle"></i>
    <span><?= e($error) ?></span>
  </div>
  <?php endif; ?>

  <!-- LOGIN PANE -->
  <div class="auth-pane <?= $tab !== 'register' ? 'active' : '' ?>" id="pane-login">
    <form method="post">
      <input type="hidden" name="tab" value="login">
      <div class="field-group">
        <label class="field-label">Username</label>
        <input type="text" name="username" class="field-input" placeholder="Enter your username" required autocomplete="username">
      </div>
      <div class="field-group">
        <label class="field-label">Password</label>
        <div class="field-wrap">
          <input type="password" name="password" id="loginPw" class="field-input" placeholder="Enter your password" required autocomplete="current-password">
          <button type="button" class="pw-toggle" onclick="togglePw('loginPw', this)"><i class="bi bi-eye"></i></button>
        </div>
      </div>
      <button type="submit" class="btn-submit mt-2">
        <i class="bi bi-box-arrow-in-right"></i> Sign In
      </button>
    </form>
    <div class="auth-footer-note">
      <p>Don't have an account? <a href="#" onclick="switchTab('register')">Register here</a></p>
    </div>
  </div>

  <!-- REGISTER PANE -->
  <div class="auth-pane <?= $tab === 'register' ? 'active' : '' ?>" id="pane-register">
    <form method="post">
      <input type="hidden" name="tab" value="register">
      <div class="row g-3">
        <div class="col-12">
          <div class="field-group">
            <label class="field-label">Full Name</label>
            <input type="text" name="full_name" class="field-input" placeholder="Juan Dela Cruz" required value="<?= e($_POST['full_name'] ?? '') ?>">
          </div>
        </div>
        <div class="col-12">
          <div class="field-group">
            <label class="field-label">Address</label>
            <input type="text" name="address" class="field-input" placeholder="Purok / Street, Barangay" required value="<?= e($_POST['address'] ?? '') ?>">
          </div>
        </div>
        <div class="col-12">
          <div class="field-group">
            <label class="field-label">Contact No.</label>
            <input type="text" name="contact" class="field-input" placeholder="09XXXXXXXXX" required value="<?= e($_POST['contact'] ?? '') ?>">
          </div>
        </div>
        <div class="col-12">
          <div class="field-group">
            <label class="field-label">Username</label>
            <input type="text" name="reg_username" class="field-input" placeholder="Choose a username" required value="<?= e($_POST['reg_username'] ?? '') ?>">
          </div>
        </div>
        <div class="col-md-6">
          <div class="field-group">
            <label class="field-label">Password</label>
            <div class="field-wrap">
              <input type="password" name="reg_password" id="regPw" class="field-input" placeholder="Min. 4 characters" required>
              <button type="button" class="pw-toggle" onclick="togglePw('regPw', this)"><i class="bi bi-eye"></i></button>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="field-group">
            <label class="field-label">Confirm Password</label>
            <div class="field-wrap">
              <input type="password" name="confirm_password" id="regPw2" class="field-input" placeholder="Repeat password" required>
              <button type="button" class="pw-toggle" onclick="togglePw('regPw2', this)"><i class="bi bi-eye"></i></button>
            </div>
          </div>
        </div>
        <div class="col-12">
          <button type="submit" class="btn-submit">
            <i class="bi bi-person-check"></i> Create My Account
          </button>
        </div>
      </div>
    </form>
    <div class="auth-footer-note">
      <p>Already have an account? <a href="#" onclick="switchTab('login')">Sign in here</a></p>
    </div>
  </div>

  <p style="text-align:center; color:#CBD5E1; font-size:.72rem; margin-top:24px;">
    Barangay San Rafael &copy; <?= date('Y') ?> &nbsp;·&nbsp; City of San Pablo, Laguna
    &nbsp;·&nbsp; <a href="/BarangayProject/admin/login.php" style="color:#CBD5E1; text-decoration:none;" title="Admin Access">&#9679;</a>
  </p>

</div>

<script>
function switchTab(tab) {
  document.querySelectorAll('.auth-pane').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.auth-tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('pane-' + tab).classList.add('active');
  event.currentTarget && event.currentTarget.classList.add('active');
  // update tab buttons properly
  const btns = document.querySelectorAll('.auth-tab-btn');
  btns[0].classList.toggle('active', tab === 'login');
  btns[1].classList.toggle('active', tab === 'register');
  document.getElementById('formHeading').textContent = tab === 'register' ? 'Create Account' : 'Welcome back';
  document.getElementById('formSubhead').textContent = tab === 'register' ? 'Register as a Barangay San Rafael resident' : 'Sign in to your resident account';
}
function togglePw(id, btn) {
  const inp = document.getElementById(id);
  const hidden = inp.type === 'password';
  inp.type = hidden ? 'text' : 'password';
  btn.innerHTML = hidden ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
}
</script>
</body>
</html>
