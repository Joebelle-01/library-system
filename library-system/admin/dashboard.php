<?php
$pageTitle = 'Dashboard';
$active = 'dashboard';
require_once __DIR__ . '/../includes/header.php';

$stats = [
    'books' => query_value($pdo, 'SELECT COUNT(*) FROM books'),
    'students' => query_value($pdo, 'SELECT COUNT(*) FROM students'),
    'borrowed' => query_value($pdo, "SELECT COUNT(*) FROM borrow_records WHERE status IN ('borrowed','overdue')"),
    'returned' => query_value($pdo, "SELECT COUNT(*) FROM borrow_records WHERE status = 'returned'"),
    'overdue' => query_value($pdo, 'SELECT COUNT(*) FROM borrow_records WHERE return_date IS NULL AND due_date < CURDATE()'),
    'fines' => query_value($pdo, "SELECT COALESCE(SUM(amount),0) FROM fines WHERE status = 'paid'"),
];
$recent = $pdo->query('SELECT * FROM vw_borrow_details ORDER BY id DESC LIMIT 8')->fetchAll();
?>
<div class="row g-3 mb-4">
  <?php
  $cards = [
      ['Total Books', $stats['books'], 'bi-journal-bookmark', 'primary'],
      ['Total Students', $stats['students'], 'bi-mortarboard', 'success'],
      ['Borrowed Books', $stats['borrowed'], 'bi-box-arrow-up-right', 'warning'],
      ['Returned Books', $stats['returned'], 'bi-box-arrow-in-down-left', 'info'],
      ['Overdue Books', $stats['overdue'], 'bi-exclamation-triangle', 'danger'],
      ['Fines Collected', money($stats['fines']), 'bi-cash-coin', 'dark'],
  ];
  foreach ($cards as $card): ?>
    <div class="col-12 col-sm-6 col-xl-4">
      <div class="metric-card">
        <div>
          <p><?= e($card[0]) ?></p>
          <h2><?= e((string) $card[1]) ?></h2>
        </div>
        <span class="metric-icon text-bg-<?= e($card[3]) ?>"><i class="bi <?= e($card[2]) ?>"></i></span>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-4 mb-4">
  <div class="col-lg-7">
    <div class="panel">
      <div class="panel-header">
        <h2>Borrowing Trend</h2>
      </div>
      <canvas id="borrowTrendChart" height="120"></canvas>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="panel">
      <div class="panel-header">
        <h2>Top Books</h2>
      </div>
      <canvas id="topBooksChart" height="170"></canvas>
    </div>
  </div>
</div>

<div class="panel">
  <div class="panel-header">
    <h2>Recent Transactions</h2>
  </div>
  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th>Student</th><th>Book</th><th>Borrowed</th><th>Due</th><th>Status</th><th>Fine</th></tr></thead>
      <tbody>
      <?php foreach ($recent as $row): ?>
        <tr>
          <td><?= e($row['student_name']) ?><br><small class="text-secondary"><?= e($row['student_no']) ?></small></td>
          <td><?= e($row['book_title']) ?></td>
          <td><?= e($row['borrow_date']) ?></td>
          <td><?= e($row['due_date']) ?></td>
          <td><span class="badge text-bg-<?= $row['status'] === 'overdue' ? 'danger' : ($row['status'] === 'returned' ? 'success' : 'primary') ?>"><?= e($row['status']) ?></span></td>
          <td><?= money($row['fine_amount']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

