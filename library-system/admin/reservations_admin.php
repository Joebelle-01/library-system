<?php
declare(strict_types=1);
$pageTitle = 'Reservation Requests';
$active = 'reservations';
require_once __DIR__ . '/../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $resId = (int) ($_POST['id'] ?? 0);

    if ($resId <= 0) {
        flash('error', 'Invalid reservation selection.');
        redirect('/admin/reservations_admin.php');
    }

    try {
        if ($action === 'approve') {
            $pdo->exec('START TRANSACTION');

            // Fetch reservation details with lock
            $resStmt = $pdo->prepare('SELECT * FROM book_reservations WHERE id = ? AND status = "pending" FOR UPDATE');
            $resStmt->execute([$resId]);
            $res = $resStmt->fetch();

            if (!$res) {
                throw new RuntimeException('Reservation request is no longer pending.');
            }

            $bookId = (int) $res['book_id'];
            $studentId = (int) $res['student_id'];

            // Concurrency Lock: Check book copies
            $bookStmt = $pdo->prepare('SELECT id, title, available_copies FROM books WHERE id = ? FOR UPDATE');
            $bookStmt->execute([$bookId]);
            $book = $bookStmt->fetch();

            if (!$book || (int) $book['available_copies'] <= 0) {
                throw new RuntimeException('Book is currently unavailable.');
            }

            // Create Borrow record
            $borrowDate = date('Y-m-d');
            $dueDate = date('Y-m-d', strtotime('+7 days'));
            $borrowStmt = $pdo->prepare('INSERT INTO borrow_records (student_id, book_id, borrowed_by, borrow_date, due_date, status, remarks) VALUES (?,?,?,?,?,"borrowed",?)');
            $borrowStmt->execute([$studentId, $bookId, current_user()['id'], $borrowDate, $dueDate, 'Approved from reservation request.']);

            // Update Book availability
            $updateStmt = $pdo->prepare("UPDATE books SET available_copies = available_copies - 1, status = CASE WHEN available_copies - 1 = 0 THEN 'unavailable' WHEN available_copies - 1 <= 2 THEN 'limited' ELSE 'available' END WHERE id = ?");
            $updateStmt->execute([$bookId]);

            // Update Reservation status
            $resUpdate = $pdo->prepare('UPDATE book_reservations SET status = "approved" WHERE id = ?');
            $resUpdate->execute([$resId]);

            $pdo->exec('COMMIT');
            flash('success', 'Reservation approved. Book has been checked out successfully.');
        } elseif ($action === 'cancel') {
            $resUpdate = $pdo->prepare('UPDATE book_reservations SET status = "cancelled" WHERE id = ? AND status = "pending"');
            $resUpdate->execute([$resId]);
            flash('success', 'Reservation request has been cancelled.');
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->exec('ROLLBACK');
        }
        flash('error', $e->getMessage());
    }
    redirect('/admin/reservations_admin.php');
}

$reservations = $pdo->query("
    SELECT r.*, 
           CONCAT(s.first_name, ' ', s.last_name) AS student_name, s.student_no, s.course,
           b.title AS book_title, b.isbn, b.available_copies
    FROM book_reservations r
    INNER JOIN students s ON s.id = r.student_id
    INNER JOIN books b ON b.id = r.book_id
    ORDER BY r.reservation_date DESC
")->fetchAll();
?>

<div class="alert alert-info border-0">Students can reserve books through their portal. Approve a request to instantly issue the book and deduct it from the catalog stock.</div>

<div class="panel">
  <div class="panel-header"><h2>Pending & Past Reservations</h2></div>
  <div class="table-responsive">
    <table class="table datatable align-middle">
      <thead><tr><th>Student</th><th>Book</th><th>Reservation Date</th><th>Status</th><th>Stock Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($reservations as $row): ?>
        <tr>
          <td><?= e($row['student_name']) ?><br><small class="text-secondary"><?= e($row['student_no']) ?> (<?= e($row['course']) ?>)</small></td>
          <td><?= e($row['book_title']) ?><br><small class="text-secondary"><?= e($row['isbn']) ?></small></td>
          <td><?= e($row['reservation_date']) ?></td>
          <td>
            <span class="badge text-bg-<?= $row['status'] === 'approved' ? 'success' : ($row['status'] === 'cancelled' ? 'secondary' : 'warning') ?>"><?= e($row['status']) ?></span>
          </td>
          <td>
            <?php if ($row['status'] === 'pending'): ?>
              <span class="badge text-bg-<?= (int) $row['available_copies'] > 0 ? 'primary' : 'danger' ?>"><?= (int) $row['available_copies'] ?> copies available</span>
            <?php else: ?>
              <span class="text-secondary">-</span>
            <?php endif; ?>
          </td>
          <td class="text-end">
            <?php if ($row['status'] === 'pending'): ?>
              <?php if ((int) $row['available_copies'] > 0): ?>
                <form method="post" class="d-inline">
                  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                  <input type="hidden" name="action" value="approve">
                  <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                  <button class="btn btn-sm btn-success me-1">Approve & Issue</button>
                </form>
              <?php else: ?>
                <button class="btn btn-sm btn-outline-secondary me-1" disabled>Out of Stock</button>
              <?php endif; ?>
              <form method="post" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="cancel">
                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                <button class="btn btn-sm btn-outline-danger">Reject</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
