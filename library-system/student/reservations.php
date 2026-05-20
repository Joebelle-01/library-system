<?php
declare(strict_types=1);
$pageTitle = 'My Book Reservations';
$active = 'reservations';
require_once __DIR__ . '/includes/student_header.php';

$studentId = current_student()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $resId = (int) ($_POST['reservation_id'] ?? 0);

    if ($resId <= 0) {
        flash('error', 'Invalid reservation selection.');
        redirect('/student/reservations.php');
    }

    try {
        // Confirm reservation belongs to this student and is pending
        $res = query_value($pdo, 'SELECT COUNT(*) FROM book_reservations WHERE id = ? AND student_id = ? AND status = "pending"', [$resId, $studentId]);
        if (!$res) {
            throw new RuntimeException('Reservation record not found or no longer cancelable.');
        }

        $stmt = $pdo->prepare('UPDATE book_reservations SET status = "cancelled" WHERE id = ?');
        $stmt->execute([$resId]);

        flash('success', 'Reservation request successfully cancelled.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('/student/reservations.php');
}

$reservationsList = $pdo->prepare("
    SELECT r.*, b.title AS book_title, b.isbn, b.author
    FROM book_reservations r
    INNER JOIN books b ON b.id = r.book_id
    WHERE r.student_id = ?
    ORDER BY r.reservation_date DESC
");
$reservationsList->execute([$studentId]);
$rows = $reservationsList->fetchAll();
?>

<div class="panel">
  <div class="panel-header"><h2>My Book Reservations Tracker</h2></div>
  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th>Book Title</th><th>ISBN</th><th>Author</th><th>Reservation Date</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="6" class="text-center text-secondary py-4">You have not made any book reservations yet.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td><strong><?= e($row['book_title']) ?></strong></td>
            <td><?= e($row['isbn']) ?></td>
            <td><?= e($row['author']) ?></td>
            <td><?= e($row['reservation_date']) ?></td>
            <td>
              <span class="badge text-bg-<?= $row['status'] === 'approved' ? 'success' : ($row['status'] === 'cancelled' ? 'secondary' : 'warning') ?>"><?= e($row['status']) ?></span>
            </td>
            <td class="text-end">
              <?php if ($row['status'] === 'pending'): ?>
                <form method="post" class="d-inline">
                  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                  <input type="hidden" name="reservation_id" value="<?= (int) $row['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle me-1"></i>Cancel Request</button>
                </form>
              <?php else: ?>
                <span class="text-secondary">-</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
