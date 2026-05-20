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
    $studentNo = trim($_POST['student_no'] ?? '');
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $yearLevel = $_POST['year_level'] ?? '1st Year';
    $email = trim($_POST['email'] ?? '') ?: null;
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (!$studentNo || !$firstName || !$lastName || !$course || !$password) {
        flash('error', 'All required fields must be filled.');
    } else {
        try {
            // Check for duplicate student number
            $noExists = query_value($pdo, 'SELECT COUNT(*) FROM students WHERE student_no = ?', [$studentNo]);
            if ($noExists) {
                throw new RuntimeException('Student ID number is already registered.');
            }

            // Check for duplicate email
            if ($email) {
                $emailExists = query_value($pdo, 'SELECT COUNT(*) FROM students WHERE email = ?', [$email]);
                if ($emailExists) {
                    throw new RuntimeException('Email address is already registered.');
                }
            }

            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO students (student_no, first_name, last_name, course, year_level, email, password, phone, address, status) VALUES (?,?,?,?,?,?,?,?,?,"active")');
            $stmt->execute([$studentNo, $firstName, $lastName, $course, $yearLevel, $email, $hashed, $phone, $address]);

            flash('success', 'Registration successful! You can now log in.');
            header('Location: ' . APP_URL . '/student_login.php');
            exit;
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
    }
}
$flash = consume_flash();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= APP_NAME ?> - Student Registration</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
  <style>
    .register-card {
      width: min(100%, 680px);
      background: rgba(255, 255, 255, 0.96);
      backdrop-filter: blur(16px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      padding: 40px;
      border-radius: var(--radius-lg);
      box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    }
  </style>
</head>
<body class="login-page">
  <main class="register-card shadow-lg">
    <div class="text-center mb-4">
      <div class="login-icon"><i class="bi bi-mortarboard"></i></div>
      <h1 class="h3 mt-3 mb-1">Student Registration</h1>
      <p class="text-secondary mb-0">Create your student account to search the library catalog and reserve books.</p>
    </div>
    
    <?php if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : 'success' ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <form method="post" class="needs-validation" novalidate>
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Student ID / Number <span class="text-danger">*</span></label>
          <input name="student_no" placeholder="e.g. STU-2026-0005" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Password <span class="text-danger">*</span></label>
          <input type="password" name="password" placeholder="Min 6 characters" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">First Name <span class="text-danger">*</span></label>
          <input name="first_name" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Last Name <span class="text-danger">*</span></label>
          <input name="last_name" class="form-control" required>
        </div>
        <div class="col-md-8">
          <label class="form-label">Course / Program <span class="text-danger">*</span></label>
          <input name="course" placeholder="e.g. BS Computer Science" class="form-control" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Year Level <span class="text-danger">*</span></label>
          <select name="year_level" class="form-select">
            <option>1st Year</option>
            <option>2nd Year</option>
            <option>3rd Year</option>
            <option>4th Year</option>
            <option>Graduate</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Email Address</label>
          <input type="email" name="email" placeholder="e.g. email@example.com" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label">Phone Number</label>
          <input name="phone" placeholder="e.g. 09170000000" class="form-control">
        </div>
        <div class="col-12">
          <label class="form-label">Home Address</label>
          <input name="address" placeholder="Current address" class="form-control">
        </div>
      </div>
      <button class="btn btn-primary btn-lg w-100 mt-4" type="submit">Sign Up</button>
    </form>
    
    <div class="text-center mt-4">
      <span class="text-secondary">Already have a student account?</span> <a href="<?= APP_URL ?>/student_login.php" class="text-primary font-weight-bold">Login Here</a>
    </div>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
