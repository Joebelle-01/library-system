<?php
$nav = [
    ['dashboard', 'Dashboard', '/admin/dashboard.php', 'bi-speedometer2'],
    ['books', 'Books', '/admin/books.php', 'bi-journal-bookmark'],
    ['categories', 'Categories', '/admin/categories.php', 'bi-tags'],
    ['students', 'Students', '/admin/students.php', 'bi-mortarboard'],
    ['borrow', 'Borrow', '/admin/borrow.php', 'bi-box-arrow-up-right'],
    ['returns', 'Returns', '/admin/returns.php', 'bi-box-arrow-in-down-left'],
    ['reservations', 'Reservations', '/admin/reservations_admin.php', 'bi-calendar-check'],
    ['fines', 'Fines', '/admin/fines.php', 'bi-cash-coin'],
    ['reports', 'Reports', '/reports/index.php', 'bi-printer'],
];

if (current_user()['role'] === 'admin') {
    $nav[] = ['users', 'Staff Users', '/admin/users.php', 'bi-people'];
}
?>
<aside class="sidebar d-none d-lg-flex">
  <a class="brand" href="<?= APP_URL ?>/admin/dashboard.php">
    <span class="brand-mark"><i class="bi bi-book"></i></span>
    <span>Library System</span>
  </a>
  <nav class="nav flex-column gap-1">
    <?php foreach ($nav as $item): ?>
      <a class="nav-link <?= $active === $item[0] ? 'active' : '' ?>" href="<?= APP_URL . $item[2] ?>">
        <i class="bi <?= $item[3] ?>"></i><span><?= e($item[1]) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>
</aside>

<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileSidebar">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title">Library System</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body">
    <nav class="nav flex-column gap-1">
      <?php foreach ($nav as $item): ?>
        <a class="nav-link <?= $active === $item[0] ? 'active' : '' ?>" href="<?= APP_URL . $item[2] ?>">
          <i class="bi <?= $item[3] ?> me-2"></i><?= e($item[1]) ?>
        </a>
      <?php endforeach; ?>
    </nav>
  </div>
</div>
