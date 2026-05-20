<?php
declare(strict_types=1);
$pageTitle = 'My Profile Settings';
$active = 'profile';
require_once __DIR__ . '/includes/student_header.php';

$studentId = current_student()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '') ?: null;
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';

    try {
        // Validate email duplicate if provided
        if ($email) {
            $duplicate = query_value($pdo, 'SELECT COUNT(*) FROM students WHERE email = ? AND id <> ?', [$email, $studentId]);
            if ($duplicate) {
                throw new RuntimeException('Email address is already in use by another student.');
            }
        }

        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE students SET email = ?, phone = ?, address = ?, password = ? WHERE id = ?');
            $stmt->execute([$email, $phone, $address, $hashed, $studentId]);
        } else {
            $stmt = $pdo->prepare('UPDATE students SET email = ?, phone = ?, address = ? WHERE id = ?');
            $stmt->execute([$email, $phone, $address, $studentId]);
        }

        // Update active session values
        $_SESSION['student']['email'] = $email;

        flash('success', 'Profile settings updated successfully.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('/student/profile.php');
}

// Fetch current details
$stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
$stmt->execute([$studentId]);
$profile = $stmt->fetch();
?>

<div class="row g-4">
  <div class="col-lg-5">
    <div class="panel">
      <div class="panel-header"><h2>Student Details</h2></div>
      <div class="text-center py-4 mb-3">
        <div class="login-icon d-inline-grid mb-3" style="width: 72px; height: 72px; font-size: 2rem;"><i class="bi bi-person-circle"></i></div>
        <h3 class="h5 mb-1"><?= e($profile['first_name'] . ' ' . $profile['last_name']) ?></h3>
        <p class="text-secondary mb-0"><?= e($profile['student_no']) ?></p>
        <span class="badge text-bg-primary mt-2"><?= e($profile['course']) ?></span>
      </div>
      <div class="list-group list-group-flush border-top">
        <div class="list-group-item d-flex justify-content-between align-items-center py-3">
          <span class="text-secondary">Year Level</span>
          <strong><?= e($profile['year_level']) ?></strong>
        </div>
        <div class="list-group-item d-flex justify-content-between align-items-center py-3">
          <span class="text-secondary">Account Status</span>
          <span class="badge text-bg-success"><?= e($profile['status']) ?></span>
        </div>
        <div class="list-group-item d-flex justify-content-between align-items-center py-3">
          <span class="text-secondary">Profile Registered</span>
          <small class="text-secondary"><?= date('F j, Y', strtotime($profile['created_at'])) ?></small>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="panel">
      <div class="panel-header"><h2>Update Profile & Security</h2></div>
      <form method="post" class="needs-validation" novalidate>
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" value="<?= e($profile['email']) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Phone Number</label>
            <input name="phone" class="form-control" value="<?= e($profile['phone']) ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Home Address</label>
            <input name="address" class="form-control" value="<?= e($profile['address']) ?>">
          </div>
          <div class="col-12 border-top pt-3 mt-4">
            <h3 class="h6 mb-2">Change Password</h3>
            <p class="text-secondary small">Leave this field blank if you do not wish to change your current password.</p>
            <input type="password" name="password" placeholder="Enter new password" class="form-control">
          </div>
        </div>
        <button class="btn btn-primary mt-4"><i class="bi bi-check2-circle me-1"></i>Save Changes</button>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
