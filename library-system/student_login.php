<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (!empty($_SESSION['student'])) {
    header('Location: ' . APP_URL . '/student/dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    // Search by student number OR email
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
        header('Location: ' . APP_URL . '/student/dashboard.php');
        exit;
    }

    flash('error', 'Invalid student number/email or password.');
}
$flash = consume_flash();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= APP_NAME ?> - Student Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="login-page">
  <main class="login-card shadow-lg">
    <div class="text-center mb-4">
      <div class="login-icon"><i class="bi bi-mortarboard"></i></div>
      <h1 class="h3 mt-3 mb-1">Student Portal</h1>
      <p class="text-secondary mb-0">Sign in to search the library, track borrows, and manage reservations.</p>
    </div>
    
    <?php if ($flash): ?>
      <div class="alert alert-danger"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <form method="post" class="needs-validation" novalidate>
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <div class="mb-3">
        <label class="form-label">Student ID or Email</label>
        <input name="login" class="form-control form-control-lg" value="STU-2026-001" placeholder="e.g. STU-2026-001" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control form-control-lg" value="student123" placeholder="Enter password" required>
      </div>
      <button class="btn btn-primary btn-lg w-100 mt-2" type="submit">Sign In</button>
    </form>
    
    <div class="text-center mt-4">
      <span class="text-secondary">New here?</span> <a href="<?= APP_URL ?>/register.php" class="text-primary font-weight-bold">Register Account</a>
    </div>
    <div class="text-center mt-3">
      <a href="<?= APP_URL ?>/index.php" class="text-secondary small"><i class="bi bi-arrow-left me-1"></i>Go to Staff Login</a>
    </div>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
