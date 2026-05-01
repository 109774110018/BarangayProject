<?php
require_once __DIR__ . '/../includes/config.php';
start_admin_session();

if (is_admin()) { header('Location: /BarangayProject/admin/dashboard.php'); exit; }


$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = trim($_POST['password'] ?? '');
    $admin = db_fetch_one('SELECT * FROM admins WHERE username = ? AND password = ?', [$user, $pass]);
    if ($admin) {
        $_SESSION['admin_id']   = $admin['id'];
        $_SESSION['admin_name'] = $admin['full_name'];
        header('Location: /BarangayProject/admin/dashboard.php');
        exit;
    }
    $error = 'Invalid credentials.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Login — Brgy. San Rafael</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    :root { --navy:#0B1F4B; --navy-mid:#152D6E; --gold:#D4A017; --gold-bright:#F0B429; --muted:#64748B; --border:#E2E8F0; }
    * { box-sizing:border-box; margin:0; padding:0; }
    body {
      font-family: 'DM Sans', sans-serif;
      min-height: 100vh;
      background: linear-gradient(135deg, #050E24 0%, #0B1F4B 50%, #0D1F45 100%);
      display: flex; align-items: center; justify-content: center;
      padding: 20px;
    }
    h1,h2,h3,h4,h5,h6 { font-family:'Sora',sans-serif; }

    .admin-card {
      background: rgba(255,255,255,.04);
      border: 1px solid rgba(255,255,255,.1);
      backdrop-filter: blur(12px);
      border-radius: 20px;
      padding: 48px 44px;
      width: 100%; max-width: 420px;
      text-align: center;
      box-shadow: 0 30px 80px rgba(0,0,0,.5);
      animation: fadeUp .4s ease;
    }
    @keyframes fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }

    .admin-logo {
      width: 80px; height: 80px; border-radius: 50%;
      border: 2px solid var(--gold); object-fit: cover;
      box-shadow: 0 0 30px rgba(212,160,23,.35);
      margin-bottom: 6px;
    }
    .admin-badge {
      display: inline-flex; align-items: center; gap: 6px;
      background: rgba(212,160,23,.12); border: 1px solid rgba(212,160,23,.25);
      color: var(--gold-bright); font-size: .7rem; font-weight: 700;
      letter-spacing: .08em; text-transform: uppercase;
      padding: 5px 14px; border-radius: 20px; margin: 14px 0 8px;
    }
    .admin-badge i { font-size: .8rem; }
    h2 { color: #fff; font-size: 1.35rem; font-weight: 800; margin-bottom: 4px; }
    .sub { color: rgba(255,255,255,.4); font-size: .82rem; margin-bottom: 32px; }

    .error-box {
      background: rgba(185,28,28,.15); border: 1px solid rgba(185,28,28,.3);
      border-radius: 8px; padding: 11px 15px;
      color: #FCA5A5; font-size: .84rem;
      display: flex; align-items: center; gap: 9px;
      margin-bottom: 22px; text-align: left;
    }

    .field-group { margin-bottom: 16px; text-align: left; }
    .field-label { display: block; font-size: .72rem; font-weight: 700; color: rgba(255,255,255,.4); text-transform: uppercase; letter-spacing: .07em; margin-bottom: 7px; }
    .field-input {
      width: 100%; background: rgba(255,255,255,.06); border: 1.5px solid rgba(255,255,255,.1);
      border-radius: 9px; padding: 12px 15px;
      font-family: 'DM Sans', sans-serif; font-size: .9rem; color: #fff;
      outline: none; transition: border-color .15s, box-shadow .15s;
    }
    .field-input::placeholder { color: rgba(255,255,255,.25); }
    .field-input:focus { border-color: rgba(212,160,23,.5); box-shadow: 0 0 0 3px rgba(212,160,23,.1); }
    .field-wrap { position: relative; }
    .field-wrap .field-input { padding-right: 44px; }
    .pw-toggle { position: absolute; right: 13px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: rgba(255,255,255,.35); font-size: 1rem; padding: 2px; }
    .pw-toggle:hover { color: rgba(255,255,255,.7); }

    .btn-admin {
      width: 100%; padding: 13px; border: none; border-radius: 9px; margin-top: 6px;
      background: linear-gradient(135deg, var(--gold), #C8890A);
      color: var(--navy); font-family: 'Sora', sans-serif; font-size: .93rem; font-weight: 700;
      cursor: pointer; transition: all .18s; letter-spacing: .01em;
      display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-admin:hover { opacity: .9; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(212,160,23,.3); }

    .back-link { margin-top: 28px; padding-top: 22px; border-top: 1px solid rgba(255,255,255,.07); }
    .back-link a { color: rgba(255,255,255,.35); font-size: .78rem; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: color .15s; }
    .back-link a:hover { color: rgba(255,255,255,.65); }

    .hint { font-size: .72rem; color: rgba(255,255,255,.2); margin-top: 18px; }
  </style>
</head>
<body>
<div class="admin-card">
  <img src="/BarangayProject/Logo.jpg" alt="Logo" class="admin-logo">
  <div class="admin-badge"><i class="bi bi-shield-lock-fill"></i> Admin Access</div>
  <h2>Administrator</h2>
  <p class="sub">Barangay San Rafael Management System</p>

  <?php if ($error): ?>
  <div class="error-box"><i class="bi bi-exclamation-circle-fill"></i> <?= e($error) ?></div>
  <?php endif; ?>

  <form method="post">
    <div class="field-group">
      <label class="field-label">Admin Username</label>
      <input type="text" name="username" class="field-input" placeholder="Enter username" required autocomplete="username" value="<?= e($_POST['username'] ?? '') ?>">
    </div>
    <div class="field-group">
      <label class="field-label">Password</label>
      <div class="field-wrap">
        <input type="password" name="password" id="adminPw" class="field-input" placeholder="Enter password" required autocomplete="current-password">
        <button type="button" class="pw-toggle" onclick="togglePw('adminPw',this)"><i class="bi bi-eye"></i></button>
      </div>
    </div>
    <button type="submit" class="btn-admin">
      <i class="bi bi-shield-check"></i> Access Dashboard
    </button>
  </form>

  <p class="hint">Default: admin1 / admin123 &nbsp;·&nbsp; admin2 / admin456</p>

  <div class="back-link">
    <a href="/BarangayProject/index.php"><i class="bi bi-arrow-left"></i> Back to Resident Portal</a>
  </div>
</div>
<script>
function togglePw(id, btn) {
  const inp = document.getElementById(id);
  inp.type = inp.type === 'password' ? 'text' : 'password';
  btn.innerHTML = inp.type === 'text' ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
}
</script>
</body>
</html>
