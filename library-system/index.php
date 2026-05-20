<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (!empty($_SESSION['user'])) {
    redirect('/admin/dashboard.php');
}
if (!empty($_SESSION['student'])) {
    redirect('/student/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    // 1. Attempt Staff/Admin Login first
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND status = "active" LIMIT 1');
    $stmt->execute([$login]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
        flash('success', 'Staff login successful.');
        redirect('/admin/dashboard.php');
    }

    // 2. Attempt Student Login if staff login fails
    $stmt = $pdo->prepare('SELECT * FROM students WHERE (student_no = ? OR email = ?) AND status = "active" LIMIT 1');
    $stmt->execute([$login, $login]);
    $student = $stmt->fetch();

    if ($student && $student['password'] && password_verify($password, $student['password'])) {
        $_SESSION['student'] = [
            'id' => (int) $student['id'],
            'student_no' => $student['student_no'],
            'name' => $student['first_name'] . ' ' . $student['last_name'],
            'email' => $student['email'],
            'course' => $student['course'],
            'year_level' => $student['year_level'],
        ];
        flash('success', 'Student login successful.');
        redirect('/student/dashboard.php');
    }

    flash('error', 'Invalid credentials or password.');
}
$flash = consume_flash();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= APP_NAME ?> - Unified Portal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="login-page">
  <main class="login-card shadow-lg">
    <div class="text-center mb-4">
      <div class="login-icon"><i class="bi bi-shield-lock"></i></div>
      <h1 class="h3 mt-3 mb-1">Unified Library Portal</h1>
      <p class="text-secondary mb-0">Sign in with your Student ID, Staff Email, or Admin credentials.</p>
    </div>
    
    <?php if ($flash): ?>
      <div class="alert alert-danger"><?= e($flash['message']) ?></div>
    <?php endif; ?>
    
    <form method="post" class="needs-validation" novalidate>
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <div class="mb-3">
        <label class="form-label">Student ID, Email, or Username</label>
        <input name="login" class="form-control form-control-lg" placeholder="e.g. STU-2026-001 or admin@library.test" required autofocus>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control form-control-lg" placeholder="Enter your password" required>
      </div>
      <button class="btn btn-primary btn-lg w-100 mt-2" type="submit">Sign In</button>
    </form>
    
    <div class="text-center mt-4">
      <span class="text-secondary">Are you a new student?</span> 
      <a href="<?= APP_URL ?>/register.php" class="text-primary fw-bold ms-1">Register Account</a>
    </div>

    <div class="mt-4 pt-3 border-top">
      <p class="small text-secondary mb-1 fw-bold">Demo Accounts for Testing:</p>
      <div class="accordion accordion-flush" id="demoAccountsAccordion">
        <div class="accordion-item bg-transparent">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed py-2 px-0 bg-transparent text-secondary small" type="button" data-bs-toggle="collapse" data-bs-target="#demoStudent" aria-expanded="false">
              <i class="bi bi-mortarboard me-2"></i>Student Credentials
            </button>
          </h2>
          <div id="demoStudent" class="accordion-collapse collapse" data-bs-parent="#demoAccountsAccordion">
            <div class="accordion-body px-0 py-2 small text-secondary">
              <strong>ID:</strong> <code>STU-2026-001</code><br>
              <strong>Password:</strong> <code>student123</code>
            </div>
          </div>
        </div>
        <div class="accordion-item bg-transparent">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed py-2 px-0 bg-transparent text-secondary small" type="button" data-bs-toggle="collapse" data-bs-target="#demoStaff" aria-expanded="false">
              <i class="bi bi-people me-2"></i>Staff / Admin Credentials
            </button>
          </h2>
          <div id="demoStaff" class="accordion-collapse collapse" data-bs-parent="#demoAccountsAccordion">
            <div class="accordion-body px-0 py-2 small text-secondary">
              <strong>Admin:</strong> <code>admin@library.test</code> / <code>admin123</code><br>
              <strong>Librarian:</strong> <code>librarian@library.test</code> / <code>librarian123</code>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

