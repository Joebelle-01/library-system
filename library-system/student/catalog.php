<?php
declare(strict_types=1);
$pageTitle = 'Library Book Catalog';
$active = 'catalog';
require_once __DIR__ . '/includes/student_header.php';

$studentId = current_student()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $bookId = (int) ($_POST['book_id'] ?? 0);

    if ($bookId <= 0) {
        flash('error', 'Invalid book selection.');
        redirect('/student/catalog.php');
    }

    try {
        // Check if student already has a pending reservation for this book
        $alreadyReserved = query_value($pdo, 'SELECT COUNT(*) FROM book_reservations WHERE student_id = ? AND book_id = ? AND status = "pending"', [$studentId, $bookId]);
        if ($alreadyReserved) {
            throw new RuntimeException('You already have a pending reservation request for this book.');
        }

        // Check if student currently has this book checked out
        $currentlyBorrowed = query_value($pdo, 'SELECT COUNT(*) FROM borrow_records WHERE student_id = ? AND book_id = ? AND return_date IS NULL', [$studentId, $bookId]);
        if ($currentlyBorrowed) {
            throw new RuntimeException('You currently have an active checkout of this book.');
        }

        // Check book availability count
        $availableCopies = (int) query_value($pdo, 'SELECT available_copies FROM books WHERE id = ? AND status <> "archived"', [$bookId]);
        if ($availableCopies <= 0) {
            throw new RuntimeException('This book is currently out of stock and cannot be reserved.');
        }

        // Insert reservation
        $stmt = $pdo->prepare('INSERT INTO book_reservations (student_id, book_id, status) VALUES (?, ?, "pending")');
        $stmt->execute([$studentId, $bookId]);

        flash('success', 'Book reservation requested successfully! Please wait for a librarian to approve and issue it.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('/student/catalog.php');
}

// Fetch all categories
$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();

// Fetch catalog list
$books = $pdo->query('
    SELECT b.*, c.name AS category 
    FROM books b 
    LEFT JOIN categories c ON c.id = b.category_id 
    WHERE b.status <> "archived" 
    ORDER BY b.title ASC
')->fetchAll();

// Get active pending reservations for this student to customize button states
$pendingReservations = $pdo->prepare('SELECT book_id FROM book_reservations WHERE student_id = ? AND status = "pending"');
$pendingReservations->execute([$studentId]);
$pendingIds = $pendingReservations->fetchAll(PDO::FETCH_COLUMN);

// Get currently borrowed books by this student to customize button states
$borrowedBooks = $pdo->prepare('SELECT book_id FROM borrow_records WHERE student_id = ? AND return_date IS NULL');
$borrowedBooks->execute([$studentId]);
$borrowedIds = $borrowedBooks->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="alert alert-secondary border-0">Browse our collection of reference materials, guides, and novels. You can reserve any in-stock book to hold a copy, then pick it up at the library counter.</div>

<div class="panel">
  <div class="table-responsive">
    <table class="table datatable align-middle">
      <thead><tr><th>Title & Author</th><th>ISBN</th><th>Category</th><th>Location</th><th>Available Copies</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach ($books as $book): ?>
        <tr>
          <td>
            <strong><?= e($book['title']) ?></strong><br>
            <small class="text-secondary">by <?= e($book['author']) ?> (<?= e($book['publisher'] ?: 'Unknown Publisher') ?> <?= $book['published_year'] ? ', ' . $book['published_year'] : '' ?>)</small>
          </td>
          <td><?= e($book['isbn']) ?></td>
          <td><?= e($book['category'] ?? 'Uncategorized') ?></td>
          <td><?= e($book['shelf_location'] ?: 'Not Specified') ?></td>
          <td>
            <?php if ((int) $book['available_copies'] <= 0): ?>
              <span class="badge text-bg-danger">Out of Stock</span>
            <?php else: ?>
              <span class="badge text-bg-success"><?= (int) $book['available_copies'] ?> / <?= (int) $book['quantity'] ?> Available</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if (in_array((int) $book['id'], $pendingIds, true)): ?>
              <button class="btn btn-sm btn-outline-warning" disabled><i class="bi bi-clock-history me-1"></i>Pending Approval</button>
            <?php elseif (in_array((int) $book['id'], $borrowedIds, true)): ?>
              <button class="btn btn-sm btn-outline-primary" disabled><i class="bi bi-journal-check me-1"></i>Active Borrow</button>
            <?php elseif ((int) $book['available_copies'] <= 0): ?>
              <button class="btn btn-sm btn-outline-secondary" disabled>Unavailable</button>
            <?php else: ?>
              <form method="post" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="book_id" value="<?= (int) $book['id'] ?>">
                <button class="btn btn-sm btn-primary"><i class="bi bi-bookmark-plus me-1"></i>Reserve Book</button>
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
