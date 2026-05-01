<?php
require_once __DIR__ . '/../includes/config.php';
start_admin_session();
require_admin();

// ── POST Actions ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name    = trim($_POST['full_name'] ?? '');
        $address = trim($_POST['address']   ?? '');
        $contact = trim($_POST['contact']   ?? '');
        $uname   = trim($_POST['username']  ?? '');
        $pwd     = trim($_POST['password']  ?? '');

        if (!$name || !$address || !$contact || !$uname || !$pwd) {
            flash('danger', 'All fields are required.');
        } elseif (db_fetch_one('SELECT id FROM resident_accounts WHERE username = ?', [$uname])) {
            flash('danger', 'Username already taken.');
        } else {
            $rid = 'RES-' . strtoupper(substr(uniqid(), -6));
            db_execute('INSERT INTO residents (resident_id, name, address, contact) VALUES (?,?,?,?)',
                       [$rid, $name, $address, $contact]);
            db_execute('INSERT INTO resident_accounts (username, password, full_name, address, contact, resident_id)
                        VALUES (?,?,?,?,?,?)',
                       [$uname, $pwd, $name, $address, $contact, $rid]);
            flash('success', "Account created! Resident ID: <strong>{$rid}</strong>");
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['account_id'] ?? 0);
        $row = db_fetch_one('SELECT resident_id FROM resident_accounts WHERE id = ?', [$id]);
        if ($row) {
            db_execute('DELETE FROM resident_accounts WHERE id = ?', [$id]);
            flash('success', 'Resident account deleted.');
        }
    }

    header('Location: /BarangayProject/admin/residents.php');
    exit;
}

$residents = db_fetch_all('
    SELECT ra.*, r.resident_id as res_id
    FROM resident_accounts ra
    LEFT JOIN residents r ON ra.resident_id = r.resident_id
    ORDER BY ra.created_at DESC
');

$flash = get_flash();
$page_title = 'Residents';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admin_sidebar.php';
?>

<div class="main-content">
  <div class="topbar">
    <div>
      <h5><i class="bi bi-people me-2"></i>Resident Accounts</h5>
      <small>Manage registered resident logins</small>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
      <i class="bi bi-person-plus me-1"></i> Add Resident
    </button>
  </div>

  <div class="p-4">
    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible">
      <?= $flash['msg'] ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-people me-2"></i>Registered Accounts</span>
        <span class="badge bg-secondary"><?= count($residents) ?> total</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>#</th><th>Full Name</th><th>Username</th>
                <th>Address</th><th>Contact</th><th>Resident ID</th>
                <th>Joined</th><th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($residents)): ?>
              <tr><td colspan="8" class="text-center text-muted py-5">
                <i class="bi bi-people fs-3 d-block mb-2"></i>No residents yet.
              </td></tr>
              <?php else: ?>
              <?php foreach ($residents as $i => $r): ?>
              <tr>
                <td><small class="text-muted"><?= $i + 1 ?></small></td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                         style="width:32px;height:32px;background:#1B5E20;font-size:.75rem;flex-shrink:0;">
                      <?= strtoupper(substr($r['full_name'], 0, 2)) ?>
                    </div>
                    <span class="fw-semibold" style="font-size:.875rem;"><?= e($r['full_name']) ?></span>
                  </div>
                </td>
                <td><code>@<?= e($r['username']) ?></code></td>
                <td><small><?= e($r['address']) ?></small></td>
                <td><small><?= e($r['contact']) ?></small></td>
                <td><span class="record-id-chip"><?= e($r['resident_id'] ?? '—') ?></span></td>
                <td><small class="text-muted"><?= substr($r['created_at'], 0, 10) ?></small></td>
                <td>
                  <form method="post" class="d-inline"
                        onsubmit="return confirm('Delete account for <?= e($r['full_name']) ?>? This cannot be undone.')">
                    <input type="hidden" name="action"     value="delete">
                    <input type="hidden" name="account_id" value="<?= $r['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="background:#1B5E20;color:#fff;">
        <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Add Resident Account</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="post">
        <input type="hidden" name="action" value="add">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Full Name</label>
              <input type="text" name="full_name" class="form-control" placeholder="Juan Dela Cruz" required>
            </div>
            <div class="col-12">
              <label class="form-label">Address</label>
              <input type="text" name="address" class="form-control" placeholder="Purok / Street" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Contact No.</label>
              <input type="text" name="contact" class="form-control" placeholder="09XXXXXXXXX" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Username</label>
              <input type="text" name="username" class="form-control" placeholder="username" required>
            </div>
            <div class="col-12">
              <label class="form-label">Password</label>
              <div class="input-group">
                <input type="password" name="password" id="addPass" class="form-control" required>
                <button type="button" class="btn btn-outline-secondary"
                        onclick="togglePw2('addPass',this)">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg me-1"></i> Create Account
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function togglePw2(id, btn) {
  const inp = document.getElementById(id);
  const isHidden = inp.type === 'password';
  inp.type = isHidden ? 'text' : 'password';
  btn.innerHTML = isHidden ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
