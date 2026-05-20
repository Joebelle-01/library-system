<?php
$pageTitle = 'Borrowing Module';
$active = 'borrow';
require_once __DIR__ . '/../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $studentId = (int) ($_POST['student_id'] ?? 0);
    $bookId = (int) ($_POST['book_id'] ?? 0);
    $borrowDate = $_POST['borrow_date'] ?: date('Y-m-d');
    $dueDate = $_POST['due_date'] ?: date('Y-m-d', strtotime('+7 days'));
    $remarks = trim($_POST['remarks'] ?? '');

    try {
        $pdo->exec('START TRANSACTION');

        // Concurrency control: lock the selected book row until commit/rollback.
        $bookStmt = $pdo->prepare('SELECT id, available_copies FROM books WHERE id = ? FOR UPDATE');
        $bookStmt->execute([$bookId]);
        $book = $bookStmt->fetch();

        if (!$book || (int) $book['available_copies'] <= 0) {
            throw new RuntimeException('Selected book is unavailable.');
        }

        $studentExists = query_value($pdo, 'SELECT COUNT(*) FROM students WHERE id = ? AND status = "active"', [$studentId]);
        if (!$studentExists) {
            throw new RuntimeException('Select an active student.');
        }

        $borrowStmt = $pdo->prepare('INSERT INTO borrow_records (student_id, book_id, borrowed_by, borrow_date, due_date, status, remarks) VALUES (?,?,?,?,?,"borrowed",?)');
        $borrowStmt->execute([$studentId, $bookId, current_user()['id'], $borrowDate, $dueDate, $remarks]);

        $updateStmt = $pdo->prepare("UPDATE books SET available_copies = available_copies - 1, status = CASE WHEN available_copies - 1 = 0 THEN 'unavailable' WHEN available_copies - 1 <= 2 THEN 'limited' ELSE 'available' END WHERE id = ?");
        $updateStmt->execute([$bookId]);

        $pdo->exec('COMMIT');
        flash('success', 'Book borrowed successfully.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->exec('ROLLBACK');
        }
        flash('error', $e->getMessage());
    }
    redirect('/admin/borrow.php');
}

$students = $pdo->query("SELECT id, student_no, CONCAT(first_name, ' ', last_name) AS name FROM students WHERE status='active' ORDER BY last_name")->fetchAll();
$books = $pdo->query("SELECT id, title, isbn, available_copies FROM books WHERE status <> 'archived' ORDER BY title")->fetchAll();
$history = $pdo->query('SELECT * FROM vw_borrow_details ORDER BY id DESC LIMIT 30')->fetchAll();
?>
<div class="row g-4">
  <div class="col-lg-4">
    <div class="panel">
      <div class="panel-header"><h2>Borrow Book</h2></div>
      <form method="post" class="needs-validation" novalidate>
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="mb-3">
          <label class="form-label">Student</label>
          <select name="student_id" class="form-select" required>
            <option value="">Select student</option>
            <?php foreach ($students as $student): ?><option value="<?= (int) $student['id'] ?>"><?= e($student['student_no'] . ' - ' . $student['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Book</label>
          <select name="book_id" id="borrowBookSelect" class="form-select" required>
            <option value="">Select book</option>
            <?php foreach ($books as $book): ?><option value="<?= (int) $book['id'] ?>"><?= e($book['title'] . ' (' . $book['available_copies'] . ' available)') ?></option><?php endforeach; ?>
          </select>
          <div class="form-text" id="availabilityText">Choose a book to check availability.</div>
        </div>
        <div class="row g-2">
          <div class="col-md-6 mb-3"><label class="form-label">Borrow Date</label><input type="date" name="borrow_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
          <div class="col-md-6 mb-3"><label class="form-label">Due Date</label><input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" required></div>
        </div>
        <div class="mb-3"><label class="form-label">Remarks</label><textarea name="remarks" class="form-control" rows="3"></textarea></div>
        <button class="btn btn-primary w-100">Borrow Book</button>
      </form>
    </div>
  </div>
  <div class="col-lg-8">
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

