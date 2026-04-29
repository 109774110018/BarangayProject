<?php
require_once __DIR__ . '/includes/config.php';

// Redirect if already logged in
if (is_admin())    { header('Location: /BarangayProject/admin/dashboard.php');  exit; }
if (is_resident()) { header('Location: /BarangayProject/resident/home.php');    exit; }

$error = '';
$tab   = $_GET['tab'] ?? 'admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tab  = $_POST['tab']      ?? 'admin';
    $user = trim($_POST['username'] ?? '');
    $pass = trim($_POST['password'] ?? '');

    if ($tab === 'admin') {
        $admin = db_fetch_one(
            'SELECT * FROM admins WHERE username = ? AND password = ?',
            [$user, $pass]
        );
        if ($admin) {
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_name'] = $admin['full_name'];
            header('Location: /BarangayProject/admin/dashboard.php');
            exit;
        }
        $error = 'Invalid username or password.';

    } elseif ($tab === 'resident') {
        $acc = db_fetch_one(
            'SELECT * FROM resident_accounts WHERE username = ? AND password = ?',
            [$user, $pass]
        );
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
            db_execute(
                'INSERT INTO residents (resident_id, name, address, contact) VALUES (?,?,?,?)',
                [$rid, $fname, $address, $contact]
            );
            db_execute(
                'INSERT INTO resident_accounts (username, password, full_name, address, contact, resident_id)
                 VALUES (?,?,?,?,?,?)',
                [$uname, $pwd, $fname, $address, $contact, $rid]
            );
            flash('success', "Account created! Your Resident ID: <strong>{$rid}</strong>. You can now log in.");
            header('Location: /BarangayProject/index.php?tab=resident');
            exit;
        }
        $tab = 'register';
    }
}

$flash = get_flash();
$page_title = 'Login';
include __DIR__ . '/includes/header.php';
?>

<div class="login-wrapper">
  <div style="width:100%;max-width:500px;padding:16px;">

    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible mb-3">
      <?= $flash['msg'] ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="login-card">
      <div class="login-header">
        <img src="/BarangayProject/Logo.jpg" alt="Barangay San Rafael Logo" class="login-logo">
        <h4><?= APP_NAME ?></h4>
        <small><?= APP_TAGLINE ?></small>
      </div>

      <!-- Tab Nav -->
      <div class="px-3 pt-3">
        <ul class="nav nav-pills nav-fill gap-1" id="loginTab" role="tablist">
          <li class="nav-item">
            <button class="nav-link <?= $tab === 'admin' ? 'active' : '' ?>"
                    id="admin-tab" data-bs-toggle="pill" data-bs-target="#admin-pane"
                    type="button">
              <i class="bi bi-shield-lock me-1"></i> Admin
            </button>
          </li>
          <li class="nav-item">
            <button class="nav-link <?= $tab === 'resident' ? 'active' : '' ?>"
                    id="resident-tab" data-bs-toggle="pill" data-bs-target="#resident-pane"
                    type="button">
              <i class="bi bi-person me-1"></i> Resident Login
            </button>
          </li>
          <li class="nav-item">
            <button class="nav-link <?= $tab === 'register' ? 'active' : '' ?>"
                    id="register-tab" data-bs-toggle="pill" data-bs-target="#register-pane"
                    type="button">
              <i class="bi bi-person-plus me-1"></i> Register
            </button>
          </li>
        </ul>
      </div>

      <div class="login-body">
        <?php if ($error): ?>
        <div class="alert alert-danger py-2 mb-3">
          <i class="bi bi-exclamation-circle me-1"></i> <?= e($error) ?>
        </div>
        <?php endif; ?>

        <div class="tab-content" id="loginTabContent">

          <!-- Admin Login -->
          <div class="tab-pane fade <?= $tab === 'admin' ? 'show active' : '' ?>" id="admin-pane">
            <form method="post">
              <input type="hidden" name="tab" value="admin">
              <div class="mb-3">
                <label class="form-label"><i class="bi bi-person me-1"></i>Username</label>
                <input type="text" name="username" class="form-control"
                       placeholder="Enter admin username" required
                       value="<?= e($_POST['username'] ?? '') ?>">
              </div>
              <div class="mb-3">
                <label class="form-label"><i class="bi bi-lock me-1"></i>Password</label>
                <div class="input-group">
                  <input type="password" name="password" id="adminPass"
                         class="form-control" placeholder="Enter password" required>
                  <button type="button" class="btn btn-outline-secondary"
                          onclick="togglePw('adminPass', this)">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
              </div>
              <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                <i class="bi bi-box-arrow-in-right me-1"></i> Login as Admin
              </button>
              <p class="text-center text-muted mt-3 mb-0" style="font-size:.75rem;">
                Default: admin1 / admin123 &nbsp;·&nbsp; admin2 / admin456
              </p>
            </form>
          </div>

          <!-- Resident Login -->
          <div class="tab-pane fade <?= $tab === 'resident' ? 'show active' : '' ?>" id="resident-pane">
            <form method="post">
              <input type="hidden" name="tab" value="resident">
              <div class="mb-3">
                <label class="form-label"><i class="bi bi-person me-1"></i>Username</label>
                <input type="text" name="username" class="form-control"
                       placeholder="Enter your username" required>
              </div>
              <div class="mb-3">
                <label class="form-label"><i class="bi bi-lock me-1"></i>Password</label>
                <div class="input-group">
                  <input type="password" name="password" id="resPass"
                         class="form-control" placeholder="Enter password" required>
                  <button type="button" class="btn btn-outline-secondary"
                          onclick="togglePw('resPass', this)">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
              </div>
              <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                <i class="bi bi-box-arrow-in-right me-1"></i> Login as Resident
              </button>
              <p class="text-center mt-3 mb-0" style="font-size:.8rem;">
                No account? <a href="#" onclick="document.getElementById('register-tab').click()">Register here</a>
              </p>
            </form>
          </div>

          <!-- Register -->
          <div class="tab-pane fade <?= $tab === 'register' ? 'show active' : '' ?>" id="register-pane">
            <form method="post">
              <input type="hidden" name="tab" value="register">
              <div class="mb-2">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control"
                       placeholder="Juan Dela Cruz" required
                       value="<?= e($_POST['full_name'] ?? '') ?>">
              </div>
              <div class="mb-2">
                <label class="form-label">Address</label>
                <input type="text" name="address" class="form-control"
                       placeholder="Purok / Street / Barangay" required
                       value="<?= e($_POST['address'] ?? '') ?>">
              </div>
              <div class="mb-2">
                <label class="form-label">Contact No.</label>
                <input type="text" name="contact" class="form-control"
                       placeholder="09XXXXXXXXX" required
                       value="<?= e($_POST['contact'] ?? '') ?>">
              </div>
              <div class="mb-2">
                <label class="form-label">Username</label>
                <input type="text" name="reg_username" class="form-control"
                       placeholder="Choose a username" required
                       value="<?= e($_POST['reg_username'] ?? '') ?>">
              </div>
              <div class="mb-2">
                <label class="form-label">Password</label>
                <div class="input-group">
                  <input type="password" name="reg_password" id="regPass"
                         class="form-control" placeholder="At least 4 characters" required>
                  <button type="button" class="btn btn-outline-secondary"
                          onclick="togglePw('regPass', this)">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <div class="input-group">
                  <input type="password" name="confirm_password" id="regPass2"
                         class="form-control" placeholder="Repeat password" required>
                  <button type="button" class="btn btn-outline-secondary"
                          onclick="togglePw('regPass2', this)">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
              </div>
              <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                <i class="bi bi-person-check me-1"></i> Create Account
              </button>
            </form>
          </div>

        </div><!-- /tab-content -->
      </div><!-- /login-body -->
    </div><!-- /login-card -->

    <p class="text-center mt-3 mb-0" style="color:rgba(255,255,255,.5);font-size:.75rem;">
      <?= BARANGAY ?> &copy; <?= date('Y') ?>
    </p>
  </div>
</div>

<script>
function togglePw(id, btn) {
  const inp = document.getElementById(id);
  const isHidden = inp.type === 'password';
  inp.type = isHidden ? 'text' : 'password';
  btn.innerHTML = isHidden ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
