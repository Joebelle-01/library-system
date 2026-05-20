<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (!empty($_SESSION['user'])) {
    redirect('/admin/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND status = "active" LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
        flash('success', 'Login successful.');
        redirect('/admin/dashboard.php');
    }

    flash('error', 'Invalid email or password.');
}
$flash = consume_flash();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= APP_NAME ?> Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="login-page">
  <main class="login-card shadow-lg">
    <div class="text-center mb-4">
      <div class="login-icon"><i class="bi bi-book"></i></div>
      <h1 class="h3 mt-3 mb-1">Advanced Library Management System</h1>
      <p class="text-secondary mb-0">Sign in to manage books, borrowers, reports, and analytics.</p>
    </div>
    <?php if ($flash): ?>
      <div class="alert alert-danger"><?= e($flash['message']) ?></div>
    <?php endif; ?>
    <form method="post" class="needs-validation" novalidate>
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control form-control-lg" value="admin@library.test" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control form-control-lg" value="admin123" required>
      </div>
      <button class="btn btn-primary btn-lg w-100" type="submit">Login</button>
    </form>
    <div class="text-center mt-4">
      <a href="<?= APP_URL ?>/student_login.php" class="btn btn-outline-primary w-100"><i class="bi bi-mortarboard me-1"></i>Student Portal Login</a>
    </div>
    <div class="small text-secondary mt-4">
      Admin: admin@library.test / admin123<br>
      Librarian: librarian@library.test / librarian123
    </div>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

