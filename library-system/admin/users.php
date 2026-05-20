<?php
declare(strict_types=1);
$pageTitle = 'Staff Management';
$active = 'users';
require_once __DIR__ . '/../includes/header.php';
require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save') {
        $id = !empty($_POST['id']) ? (int) $_POST['id'] : null;
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'librarian';
        $status = $_POST['status'] ?? 'active';
        $password = $_POST['password'] ?? '';

        if (!$name || !$email) {
            flash('error', 'Name and Email are required.');
            redirect('/admin/users.php');
        }

        try {
            if ($id) {
                // Edit existing user
                if (!empty($password)) {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare('UPDATE users SET name=?, email=?, role=?, status=?, password=? WHERE id=?');
                    $stmt->execute([$name, $email, $role, $status, $hashed, $id]);
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET name=?, email=?, role=?, status=? WHERE id=?');
                    $stmt->execute([$name, $email, $role, $status, $id]);
                }
                flash('success', 'Staff account updated.');
            } else {
                // Add new user
                if (empty($password)) {
                    flash('error', 'Password is required for new accounts.');
                    redirect('/admin/users.php');
                }
                
                // Check if email already exists
                $emailExists = query_value($pdo, 'SELECT COUNT(*) FROM users WHERE email = ?', [$email]);
                if ($emailExists) {
                    flash('error', 'Email is already registered.');
                    redirect('/admin/users.php');
                }

                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, status) VALUES (?,?,?,?,?)');
                $stmt->execute([$name, $email, $hashed, $role, $status]);
                flash('success', 'Staff account registered.');
            }
        } catch (Throwable $e) {
            flash('error', 'Error: ' . $e->getMessage());
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id === (int) current_user()['id']) {
            flash('error', 'You cannot delete your own account.');
        } else {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$id]);
            flash('success', 'Staff account deleted.');
        }
    }
    redirect('/admin/users.php');
}

$usersList = $pdo->query('SELECT * FROM users ORDER BY created_at DESC')->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <p class="text-secondary mb-0">Register and manage administrative and librarian accounts.</p>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal"><i class="bi bi-plus-lg me-1"></i>Add Staff</button>
</div>

<div class="panel">
  <div class="table-responsive">
    <table class="table datatable align-middle">
      <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Registered At</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($usersList as $usr): ?>
        <tr>
          <td>
            <strong><?= e($usr['name']) ?></strong>
            <?php if ($usr['id'] === (int) current_user()['id']): ?>
              <span class="badge text-bg-primary ms-1">You</span>
            <?php endif; ?>
          </td>
          <td><?= e($usr['email']) ?></td>
          <td><span class="badge text-bg-<?= $usr['role'] === 'admin' ? 'dark' : 'info' ?>"><?= e($usr['role']) ?></span></td>
          <td><span class="badge text-bg-<?= $usr['status'] === 'active' ? 'success' : 'secondary' ?>"><?= e($usr['status']) ?></span></td>
          <td><?= e($usr['created_at']) ?></td>
          <td class="text-end">
            <button class="btn btn-sm btn-outline-primary edit-user" data-user='<?= e(json_encode($usr)) ?>'><i class="bi bi-pencil"></i></button>
            <?php if ($usr['id'] !== (int) current_user()['id']): ?>
              <form method="post" class="d-inline delete-form">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $usr['id'] ?>">
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="userModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content needs-validation" novalidate>
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" id="user_id">
      <div class="modal-header"><h5 class="modal-title">Staff Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body row g-3">
        <div class="col-12"><label class="form-label">Full Name</label><input name="name" id="user_name" class="form-control" required></div>
        <div class="col-12"><label class="form-label">Email Address</label><input name="email" id="user_email" type="email" class="form-control" required></div>
        <div class="col-12">
            <label class="form-label">Password <span class="text-secondary" id="passHelp">(Leave empty to keep existing password)</span></label>
            <input name="password" id="user_password" type="password" class="form-control">
        </div>
        <div class="col-md-6"><label class="form-label">Role</label><select name="role" id="user_role" class="form-select"><option value="librarian">Librarian</option><option value="admin">Administrator</option></select></div>
        <div class="col-md-6"><label class="form-label">Status</label><select name="status" id="user_status" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
      </div>
      <div class="modal-footer"><button class="btn btn-primary">Save Account</button></div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.edit-user').forEach((button) => {
    button.addEventListener('click', () => {
      const user = JSON.parse(button.dataset.user);
      document.getElementById('user_id').value = user.id;
      document.getElementById('user_name').value = user.name;
      document.getElementById('user_email').value = user.email;
      document.getElementById('user_role').value = user.role;
      document.getElementById('user_status').value = user.status;
      document.getElementById('user_password').required = false;
      document.getElementById('passHelp').style.display = 'inline';
      bootstrap.Modal.getOrCreateInstance(document.getElementById('userModal')).show();
    });
  });

  const userModal = document.getElementById('userModal');
  if (userModal) {
    userModal.addEventListener('hidden.bs.modal', () => {
      const form = userModal.querySelector('form');
      if (form) {
        form.reset();
        form.classList.remove('was-validated');
      }
      document.getElementById('user_id').value = '';
      document.getElementById('user_password').required = true;
      document.getElementById('passHelp').style.display = 'none';
    });
  }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
