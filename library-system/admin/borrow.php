<?php
$pageTitle = 'Borrowing Module';
$active = 'borrow';
require_once __DIR__ . '/../includes/header.php';

$history = $pdo->query('SELECT * FROM vw_borrow_details ORDER BY id DESC LIMIT 30')->fetchAll();
?>
<div class="row g-4">
  <div class="col-12">
    <div class="panel">
      <div class="panel-header"><h2>Borrow History</h2></div>
      <div class="table-responsive">
        <table class="table datatable align-middle">
          <thead><tr><th>Student</th><th>Book</th><th>Borrowed</th><th>Due</th><th>Status</th></tr></thead>
          <tbody>
          <?php foreach ($history as $row): ?>
            <tr>
              <td><?= e($row['student_name']) ?></td>
              <td><?= e($row['book_title']) ?></td>
              <td><?= e($row['borrow_date']) ?></td>
              <td><?= e($row['due_date']) ?></td>
              <td><span class="badge text-bg-<?= $row['status'] === 'overdue' ? 'danger' : ($row['status'] === 'returned' ? 'success' : 'primary') ?>"><?= e($row['status']) ?></span></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

