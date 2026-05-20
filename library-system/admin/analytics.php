<?php
$pageTitle = 'Analytics Dashboard';
$active = 'analytics';
require_once __DIR__ . '/../includes/header.php';

$topBooks = $pdo->query('SELECT * FROM vw_most_borrowed_books ORDER BY borrow_rank LIMIT 10')->fetchAll();
$topStudents = $pdo->query('SELECT * FROM vw_student_borrow_stats ORDER BY total_borrows DESC LIMIT 10')->fetchAll();
$monthly = $pdo->query("
    SELECT d.year_num, d.month_num, d.month_name, SUM(f.borrow_count) AS borrows, SUM(f.fine_amount) AS fines
    FROM fact_borrowing f
    INNER JOIN dim_date d ON d.date_key = f.borrow_date_key
    GROUP BY d.year_num, d.month_num, d.month_name
    ORDER BY d.year_num, d.month_num
")->fetchAll();
$warehouseCount = query_value($pdo, 'SELECT COUNT(*) FROM fact_borrowing');
?>
<div class="alert alert-secondary border-0">Warehouse fact rows loaded: <strong><?= (int) $warehouseCount ?></strong>. Run the ETL process when operational data changes.</div>
<div class="row g-4 mb-4">
  <div class="col-lg-6">
    <div class="panel">
      <div class="panel-header"><h2>Monthly Borrow Analytics</h2></div>
      <canvas id="warehouseMonthlyChart" height="150" data-labels='<?= e(json_encode(array_map(fn($r) => $r['month_name'] . ' ' . $r['year_num'], $monthly))) ?>' data-borrows='<?= e(json_encode(array_column($monthly, 'borrows'))) ?>' data-fines='<?= e(json_encode(array_column($monthly, 'fines'))) ?>'></canvas>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="panel">
      <div class="panel-header"><h2>Fine Collection Analytics</h2></div>
      <canvas id="fineChart" height="150"></canvas>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-6">
    <div class="panel">
      <div class="panel-header"><h2>Most Borrowed Books</h2></div>
      <div class="table-responsive">
        <table class="table align-middle">
          <thead><tr><th>Rank</th><th>Title</th><th>Author</th><th>Borrows</th></tr></thead>
          <tbody>
          <?php foreach ($topBooks as $book): ?>
            <tr><td><?= (int) $book['borrow_rank'] ?></td><td><?= e($book['title']) ?></td><td><?= e($book['author']) ?></td><td><?= (int) $book['borrow_count'] ?></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="panel">
      <div class="panel-header"><h2>Top Active Students</h2></div>
      <div class="table-responsive">
        <table class="table align-middle">
          <thead><tr><th>Student</th><th>Borrows</th><th>Returned</th><th>Overdue</th><th>Paid Fines</th></tr></thead>
          <tbody>
          <?php foreach ($topStudents as $student): ?>
            <tr><td><?= e($student['student_name']) ?><br><small class="text-secondary"><?= e($student['student_no']) ?></small></td><td><?= (int) $student['total_borrows'] ?></td><td><?= (int) $student['returned_count'] ?></td><td><?= (int) $student['overdue_count'] ?></td><td><?= money($student['total_paid_fines']) ?></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

