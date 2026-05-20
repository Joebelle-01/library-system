<?php
declare(strict_types=1);
$pageTitle = 'Student Dashboard';
$active = 'dashboard';
require_once __DIR__ . '/includes/student_header.php';

$studentId = current_student()['id'];

// Get statistics
$stats = [
    'borrowed' => query_value($pdo, "SELECT COUNT(*) FROM borrow_records WHERE student_id = ? AND status IN ('borrowed','overdue')", [$studentId]),
    'overdue' => query_value($pdo, "SELECT COUNT(*) FROM borrow_records WHERE student_id = ? AND status = 'overdue'", [$studentId]),
    'fines' => query_value($pdo, "SELECT COALESCE(SUM(amount), 0) FROM fines WHERE student_id = ? AND status = 'unpaid'", [$studentId]),
];

// Active borrows
$activeBorrows = $pdo->prepare("
    SELECT br.*, b.title, b.author, b.isbn,
           COALESCE(f.amount, 0) AS fine_amount, COALESCE(f.status, 'none') AS fine_status
    FROM borrow_records br
    INNER JOIN books b ON b.id = br.book_id
    LEFT JOIN fines f ON f.borrow_record_id = br.id
    WHERE br.student_id = ? AND br.return_date IS NULL
    ORDER BY br.due_date ASC
");
$activeBorrows->execute([$studentId]);
$activeRows = $activeBorrows->fetchAll();

// Past borrow history
$pastBorrows = $pdo->prepare("
    SELECT br.*, b.title, b.author, b.isbn,
           COALESCE(f.amount, 0) AS fine_amount, COALESCE(f.status, 'none') AS fine_status
    FROM borrow_records br
    INNER JOIN books b ON b.id = br.book_id
    LEFT JOIN fines f ON f.borrow_record_id = br.id
    WHERE br.student_id = ? AND br.return_date IS NOT NULL
    ORDER BY br.return_date DESC
    LIMIT 10
");
$pastBorrows->execute([$studentId]);
$pastRows = $pastBorrows->fetchAll();
?>

<div class="row g-3 mb-4">
  <div class="col-12 col-md-4">
    <div class="metric-card">
      <div>
        <p>Books Checked Out</p>
        <h2><?= (int) $stats['borrowed'] ?></h2>
      </div>
      <span class="metric-icon text-bg-primary"><i class="bi bi-book"></i></span>
    </div>
  </div>
  <div class="col-12 col-md-4">
    <div class="metric-card">
      <div>
        <p>Overdue Books</p>
        <h2><?= (int) $stats['overdue'] ?></h2>
      </div>
      <span class="metric-icon text-bg-danger"><i class="bi bi-exclamation-triangle"></i></span>
    </div>
  </div>
  <div class="col-12 col-md-4">
    <div class="metric-card">
      <div>
        <p>Unpaid Fines</p>
        <h2><?= money($stats['fines']) ?></h2>
      </div>
      <span class="metric-icon text-bg-warning"><i class="bi bi-cash-coin"></i></span>
    </div>
  </div>
</div>

<div class="panel mb-4">
  <div class="panel-header">
    <h2>My Currently Checked Out Books</h2>
  </div>
  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th>Book Title</th><th>ISBN</th><th>Borrow Date</th><th>Due Date</th><th>Status</th><th>Accrued Fine</th></tr></thead>
      <tbody>
      <?php if (empty($activeRows)): ?>
        <tr><td colspan="6" class="text-center text-secondary py-4">You do not have any active books checked out.</td></tr>
      <?php else: ?>
        <?php foreach ($activeRows as $row): ?>
          <tr>
            <td><strong><?= e($row['title']) ?></strong><br><small class="text-secondary">by <?= e($row['author']) ?></small></td>
            <td><?= e($row['isbn']) ?></td>
            <td><?= e($row['borrow_date']) ?></td>
            <td><?= e($row['due_date']) ?></td>
            <td>
              <span class="badge text-bg-<?= $row['status'] === 'overdue' ? 'danger' : 'primary' ?>"><?= e($row['status']) ?></span>
            </td>
            <td>
              <?php 
                $due = new DateTime($row['due_date']);
                $today = new DateTime('today');
                $fineDetails = fine_for($due, $today);
                if ($fineDetails['amount'] > 0) {
                    echo '<span class="text-danger fw-bold">' . money($fineDetails['amount']) . ' (' . $fineDetails['days'] . ' days overdue)</span>';
                } else {
                    echo '<span class="text-success">No fines</span>';
                }
              ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="panel">
  <div class="panel-header">
    <h2>Recent Return History</h2>
  </div>
  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th>Book Title</th><th>ISBN</th><th>Borrow Date</th><th>Due Date</th><th>Return Date</th><th>Fine Paid/Waived</th></tr></thead>
      <tbody>
      <?php if (empty($pastRows)): ?>
        <tr><td colspan="6" class="text-center text-secondary py-4">No borrowing history recorded yet.</td></tr>
      <?php else: ?>
        <?php foreach ($pastRows as $row): ?>
          <tr>
            <td><strong><?= e($row['title']) ?></strong><br><small class="text-secondary">by <?= e($row['author']) ?></small></td>
            <td><?= e($row['isbn']) ?></td>
            <td><?= e($row['borrow_date']) ?></td>
            <td><?= e($row['due_date']) ?></td>
            <td><?= e($row['return_date']) ?></td>
            <td>
              <?php if ((float) $row['fine_amount'] > 0): ?>
                <span class="badge text-bg-<?= $row['fine_status'] === 'paid' ? 'success' : ($row['fine_status'] === 'waived' ? 'secondary' : 'warning') ?>">
                  <?= money($row['fine_amount']) ?> - <?= e($row['fine_status']) ?>
                </span>
              <?php else: ?>
                <span class="text-success">None</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
