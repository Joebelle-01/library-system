<?php
$pageTitle = 'Return Module';
$active = 'returns';
require_once __DIR__ . '/../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $borrowId = (int) ($_POST['borrow_record_id'] ?? 0);
    $returnDate = $_POST['return_date'] ?: date('Y-m-d');

    try {
        $pdo->exec('START TRANSACTION');
        $stmt = $pdo->prepare('SELECT * FROM borrow_records WHERE id = ? AND return_date IS NULL FOR UPDATE');
        $stmt->execute([$borrowId]);
        $record = $stmt->fetch();
        if (!$record) {
            throw new RuntimeException('Borrow record is no longer open.');
        }

        $bookStmt = $pdo->prepare('SELECT id, quantity, available_copies FROM books WHERE id = ? FOR UPDATE');
        $bookStmt->execute([(int) $record['book_id']]);
        $book = $bookStmt->fetch();
        if (!$book) {
            throw new RuntimeException('Book record not found.');
        }

        $due = new DateTimeImmutable($record['due_date']);
        $returned = new DateTimeImmutable($returnDate);
        $fine = fine_for($due, $returned);
        $newStatus = $fine['days'] > 0 ? 'overdue' : 'returned';

        $pdo->prepare('UPDATE borrow_records SET return_date=?, returned_by=?, status=? WHERE id=?')->execute([$returnDate, current_user()['id'], 'returned', $borrowId]);
        $pdo->prepare("UPDATE books SET available_copies = LEAST(quantity, available_copies + 1), status = CASE WHEN LEAST(quantity, available_copies + 1) = 0 THEN 'unavailable' WHEN LEAST(quantity, available_copies + 1) <= 2 THEN 'limited' ELSE 'available' END WHERE id=?")->execute([(int) $record['book_id']]);

        if ($fine['amount'] > 0) {
            $fineStmt = $pdo->prepare('INSERT INTO fines (borrow_record_id, student_id, amount, days_overdue, daily_rate, status) VALUES (?,?,?,?,?,"unpaid") ON DUPLICATE KEY UPDATE amount=VALUES(amount), days_overdue=VALUES(days_overdue), status=IF(status="paid", status, "unpaid")');
            $fineStmt->execute([$borrowId, (int) $record['student_id'], $fine['amount'], $fine['days'], DAILY_FINE_RATE]);
        }

        $pdo->exec('COMMIT');
        flash('success', $fine['amount'] > 0 ? 'Book returned with an overdue fine.' : 'Book returned successfully.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->exec('ROLLBACK');
        }
        flash('error', $e->getMessage());
    }
    redirect('/admin/returns.php');
}

$openRecords = $pdo->query("SELECT * FROM vw_borrow_details WHERE status IN ('borrowed','overdue') ORDER BY due_date ASC")->fetchAll();
$returned = $pdo->query("SELECT * FROM vw_borrow_details WHERE status = 'returned' ORDER BY return_date DESC LIMIT 30")->fetchAll();
?>
<div class="row g-4">
  <div class="col-lg-4">
    <div class="panel">
      <div class="panel-header"><h2>Record Return</h2></div>
      <form method="post" class="needs-validation" novalidate>
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="mb-3">
          <label class="form-label">Open Borrow Record</label>
          <select name="borrow_record_id" class="form-select" required>
            <option value="">Select transaction</option>
            <?php foreach ($openRecords as $row): ?>
              <option value="<?= (int) $row['id'] ?>"><?= e($row['student_name'] . ' - ' . $row['book_title'] . ' due ' . $row['due_date']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3"><label class="form-label">Return Date</label><input type="date" name="return_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
        <button class="btn btn-primary w-100">Return Book</button>
      </form>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="panel">
      <div class="panel-header"><h2>Returned Books</h2></div>
      <div class="table-responsive">
        <table class="table datatable align-middle">
          <thead><tr><th>Student</th><th>Book</th><th>Returned</th><th>Fine</th><th>Fine Status</th></tr></thead>
          <tbody>
          <?php foreach ($returned as $row): ?>
            <tr>
              <td><?= e($row['student_name']) ?></td>
              <td><?= e($row['book_title']) ?></td>
              <td><?= e($row['return_date']) ?></td>
              <td><?= money($row['fine_amount']) ?></td>
              <td><?= e($row['fine_status']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

