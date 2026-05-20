<?php
$studentNav = [
    ['dashboard', 'My Dashboard', '/student/dashboard.php', 'bi-speedometer2'],
    ['catalog', 'Book Catalog', '/student/catalog.php', 'bi-journal-bookmark'],
    ['reservations', 'My Reservations', '/student/reservations.php', 'bi-calendar-check'],
    ['history', 'Borrow History', '/student/history.php', 'bi-clock-history'],
    ['profile', 'My Profile', '/student/profile.php', 'bi-person-gear'],
];
?>
<aside class="sidebar d-none d-lg-flex">
  <a class="brand" href="<?= APP_URL ?>/student/dashboard.php">
    <span class="brand-mark"><i class="bi bi-mortarboard"></i></span>
    <span>Student Portal</span>
  </a>
  <nav class="nav flex-column gap-1">
    <?php foreach ($studentNav as $item): ?>
      <a class="nav-link <?= $active === $item[0] ? 'active' : '' ?>" href="<?= APP_URL . $item[2] ?>">
        <i class="bi <?= $item[3] ?>"></i><span><?= e($item[1]) ?></span>
      </a>
    <?php endforeach; ?>
    <hr style="border-top: 1px solid rgba(255,255,255,0.1); margin: 20px 0;">
    <a class="nav-link" href="<?= APP_URL ?>/logout.php" style="color: #f87171;">
      <i class="bi bi-box-arrow-left"></i><span>Logout</span>
    </a>
  </nav>
</aside>

<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileSidebar">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title">Student Portal</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body">
    <nav class="nav flex-column gap-1">
      <?php foreach ($studentNav as $item): ?>
        <a class="nav-link <?= $active === $item[0] ? 'active' : '' ?>" href="<?= APP_URL . $item[2] ?>">
          <i class="bi <?= $item[3] ?> me-2"></i><?= e($item[1]) ?>
        </a>
      <?php endforeach; ?>
      <hr>
      <a class="nav-link" href="<?= APP_URL ?>/logout.php" style="color: #ef4444;">
        <i class="bi bi-box-arrow-left me-2"></i>Logout
      </a>
    </nav>
  </div>
</div>
