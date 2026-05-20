<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_student_login();
update_overdue_records($pdo);
$pageTitle = $pageTitle ?? 'Student Dashboard';
$active = $active ?? '';
$flash = consume_flash();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
  <script>window.APP_URL = "<?= APP_URL ?>";</script>
</head>
<body>
<div class="app-shell">
  <?php include __DIR__ . '/student_sidebar.php'; ?>
  <main class="main-content">
    <nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
      <div class="container-fluid">
        <button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
          <i class="bi bi-list"></i>
        </button>
        <div>
          <h1 class="h4 mb-0"><?= e($pageTitle) ?></h1>
          <small class="text-secondary">Welcome, <?= e(current_student()['name'] ?? 'Student') ?> (<?= e(current_student()['student_no'] ?? '') ?>)</small>
        </div>
        <div class="dropdown">
          <button class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle me-1"></i>Student
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="<?= APP_URL ?>/student/profile.php">My Profile</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?= APP_URL ?>/logout.php">Logout</a></li>
          </ul>
        </div>
      </div>
    </nav>
    <div class="content-wrap">
<?php if ($flash): ?>
      <div data-flash-type="<?= e($flash['type']) ?>" data-flash-message="<?= e($flash['message']) ?>"></div>
<?php endif; ?>
