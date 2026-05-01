<?php
require_once __DIR__ . '/../includes/config.php';
start_resident_session();
require_resident();
$acc = current_resident();

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action'] ?? '';
    $name     = trim($_POST['full_name'] ?? '');
    $address  = trim($_POST['address']   ?? '');
    $contact  = trim($_POST['contact']   ?? '');

    if ($action === 'update_info') {
        if (!$name || !$address || !$contact) {
            $error = 'All fields are required.';
        } else {
            db_execute(
                'UPDATE resident_accounts SET full_name=?, address=?, contact=? WHERE id=?',
                [$name, $address, $contact, $acc['id']]
            );
            // Sync residents table
            db_execute(
                'UPDATE residents SET name=?, address=?, contact=? WHERE resident_id=?',
                [$name, $address, $contact, $acc['resident_id']]
            );
            $_SESSION['resident_name'] = $name;
            flash('success', 'Profile updated successfully!');
            header('Location: /BarangayProject/resident/profile.php');
            exit;
        }
    }

    if ($action === 'change_password') {
        $cur_pw  = $_POST['current_password'] ?? '';
        $new_pw  = $_POST['new_password']     ?? '';
        $conf_pw = $_POST['confirm_password'] ?? '';

        if (!$cur_pw || !$new_pw || !$conf_pw) {
            $error = 'All password fields are required.';
        } elseif ($acc['password'] !== $cur_pw) {
            $error = 'Current password is incorrect.';
        } elseif ($new_pw !== $conf_pw) {
            $error = 'New passwords do not match.';
        } elseif (strlen($new_pw) < 4) {
            $error = 'New password must be at least 4 characters.';
        } else {
            db_execute('UPDATE resident_accounts SET password=? WHERE id=?',
                       [$new_pw, $acc['id']]);
            flash('success', 'Password changed successfully!');
            header('Location: /BarangayProject/resident/profile.php');
            exit;
        }
    }

    // Re-fetch updated account
    $acc = current_resident();
}

$flash = get_flash();
$initials = implode('', array_map(fn($w) => strtoupper($w[0]),
    array_slice(explode(' ', $acc['full_name']), 0, 2)));

$page_title = 'My Profile';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/resident_sidebar.php';
?>

<div class="main-content">
  <div class="topbar">
    <div>
      <h5><i class="bi bi-person-circle me-2"></i>My Profile</h5>
      <small>Manage your account information</small>
    </div>
  </div>
  <div class="p-4">

    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible">
      <?= $flash['msg'] ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-danger">
      <i class="bi bi-exclamation-circle me-1"></i><?= e($error) ?>
    </div>
    <?php endif; ?>

    <div class="row g-4">

      <!-- Avatar Card -->
      <div class="col-lg-4">
        <div class="card text-center p-4">
          <div class="rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center fw-bold text-white"
               style="width:90px;height:90px;background:#1B5E20;font-size:2rem;">
            <?= e($initials) ?>
          </div>
          <h5 class="fw-bold mb-1"><?= e($acc['full_name']) ?></h5>
          <p class="text-muted mb-1"><code>@<?= e($acc['username']) ?></code></p>
          <div class="my-2">
            <span class="record-id-chip"><?= e($acc['resident_id'] ?? 'N/A') ?></span>
          </div>
          <hr>
          <div class="text-start small text-muted">
            <div class="mb-2">
              <i class="bi bi-geo-alt me-2"></i><?= e($acc['address']) ?>
            </div>
            <div class="mb-2">
              <i class="bi bi-telephone me-2"></i><?= e($acc['contact']) ?>
            </div>
            <div>
              <i class="bi bi-calendar me-2"></i>Joined <?= substr($acc['created_at'], 0, 10) ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Edit Forms -->
      <div class="col-lg-8">

        <!-- Edit Profile Info -->
        <div class="card mb-4">
          <div class="card-header">
            <i class="bi bi-pencil me-2"></i>Edit Profile Information
          </div>
          <div class="card-body p-4">
            <form method="post">
              <input type="hidden" name="action" value="update_info">
              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label">Full Name</label>
                  <input type="text" name="full_name" class="form-control"
                         value="<?= e($acc['full_name']) ?>" required>
                </div>
                <div class="col-12">
                  <label class="form-label">Address</label>
                  <input type="text" name="address" class="form-control"
                         value="<?= e($acc['address']) ?>" required>
                </div>
                <div class="col-12">
                  <label class="form-label">Contact No.</label>
                  <input type="text" name="contact" class="form-control"
                         value="<?= e($acc['contact']) ?>" required>
                </div>
                <div class="col-12">
                  <label class="form-label">Username</label>
                  <input type="text" class="form-control"
                         value="<?= e($acc['username']) ?>" disabled>
                  <small class="text-muted">Username cannot be changed.</small>
                </div>
              </div>
              <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                  <i class="bi bi-save me-1"></i> Save Changes
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Change Password -->
        <div class="card">
          <div class="card-header">
            <i class="bi bi-shield-lock me-2"></i>Change Password
          </div>
          <div class="card-body p-4">
            <form method="post">
              <input type="hidden" name="action" value="change_password">
              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label">Current Password</label>
                  <div class="input-group">
                    <input type="password" name="current_password" id="curPw"
                           class="form-control" required>
                    <button type="button" class="btn btn-outline-secondary"
                            onclick="togglePw('curPw', this)">
                      <i class="bi bi-eye"></i>
                    </button>
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">New Password</label>
                  <div class="input-group">
                    <input type="password" name="new_password" id="newPw"
                           class="form-control" minlength="4" required>
                    <button type="button" class="btn btn-outline-secondary"
                            onclick="togglePw('newPw', this)">
                      <i class="bi bi-eye"></i>
                    </button>
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Confirm New Password</label>
                  <div class="input-group">
                    <input type="password" name="confirm_password" id="confPw"
                           class="form-control" required>
                    <button type="button" class="btn btn-outline-secondary"
                            onclick="togglePw('confPw', this)">
                      <i class="bi bi-eye"></i>
                    </button>
                  </div>
                </div>
              </div>
              <div class="mt-3">
                <button type="submit" class="btn btn-warning">
                  <i class="bi bi-key me-1"></i> Change Password
                </button>
              </div>
            </form>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
function togglePw(id, btn) {
  const inp = document.getElementById(id);
  const hidden = inp.type === 'password';
  inp.type = hidden ? 'text' : 'password';
  btn.innerHTML = hidden ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
