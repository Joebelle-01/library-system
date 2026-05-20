<?php
$pageTitle = 'Book Management';
$active = 'books';
require_once __DIR__ . '/../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $data = [
        trim($_POST['title'] ?? ''),
        trim($_POST['author'] ?? ''),
        trim($_POST['isbn'] ?? ''),
        $_POST['category_id'] !== '' ? (int) $_POST['category_id'] : null,
        trim($_POST['publisher'] ?? ''),
        $_POST['published_year'] ?: null,
        max(0, (int) ($_POST['quantity'] ?? 0)),
        max(0, (int) ($_POST['available_copies'] ?? 0)),
        trim($_POST['shelf_location'] ?? ''),
        $_POST['status'] ?? 'available',
    ];

    if ($action === 'save') {
        if (!empty($_POST['id'])) {
            $stmt = $pdo->prepare('UPDATE books SET title=?, author=?, isbn=?, category_id=?, publisher=?, published_year=?, quantity=?, available_copies=?, shelf_location=?, status=? WHERE id=?');
            $stmt->execute([...$data, (int) $_POST['id']]);
            flash('success', 'Book updated.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO books (title, author, isbn, category_id, publisher, published_year, quantity, available_copies, shelf_location, status) VALUES (?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute($data);
            flash('success', 'Book added.');
        }
    } elseif ($action === 'delete') {
        require_role(['admin']);
        $stmt = $pdo->prepare('DELETE FROM books WHERE id = ?');
        $stmt->execute([(int) $_POST['id']]);
        flash('success', 'Book deleted.');
    }
    redirect('/admin/books.php');
}

$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$books = $pdo->query('SELECT b.*, c.name AS category FROM books b LEFT JOIN categories c ON c.id = b.category_id ORDER BY b.created_at DESC')->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <p class="text-secondary mb-0">Manage inventory, categories, availability, and shelf details.</p>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bookModal"><i class="bi bi-plus-lg me-1"></i>Add Book</button>
</div>
<div class="panel">
  <div class="table-responsive">
    <table class="table datatable align-middle">
      <thead><tr><th>Title</th><th>Author</th><th>ISBN</th><th>Category</th><th>Qty</th><th>Available</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($books as $book): ?>
        <tr>
          <td><?= e($book['title']) ?><br><small class="text-secondary"><?= e($book['shelf_location']) ?></small></td>
          <td><?= e($book['author']) ?></td>
          <td><?= e($book['isbn']) ?></td>
          <td><?= e($book['category'] ?? 'Uncategorized') ?></td>
          <td><?= e((string) $book['quantity']) ?></td>
          <td><?= e((string) $book['available_copies']) ?></td>
          <td><span class="badge text-bg-secondary"><?= e($book['status']) ?></span></td>
          <td class="text-end">
            <button class="btn btn-sm btn-outline-primary edit-book" data-book='<?= e(json_encode($book)) ?>'><i class="bi bi-pencil"></i></button>
            <?php if (current_user()['role'] === 'admin'): ?>
            <form method="post" class="d-inline delete-form">
              <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int) $book['id'] ?>">
              <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="bookModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <form method="post" class="modal-content needs-validation" novalidate>
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" id="book_id">
      <div class="modal-header"><h5 class="modal-title">Book Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body row g-3">
        <div class="col-md-8"><label class="form-label">Title</label><input name="title" id="book_title" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">ISBN</label><input name="isbn" id="book_isbn" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Author</label><input name="author" id="book_author" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Category</label><select name="category_id" id="book_category_id" class="form-select"><option value="">Uncategorized</option><?php foreach ($categories as $cat): ?><option value="<?= (int) $cat['id'] ?>"><?= e($cat['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><label class="form-label">Publisher</label><input name="publisher" id="book_publisher" class="form-control"></div>
        <div class="col-md-2"><label class="form-label">Year</label><input name="published_year" id="book_published_year" type="number" min="1500" max="2100" class="form-control"></div>
        <div class="col-md-2"><label class="form-label">Quantity</label><input name="quantity" id="book_quantity" type="number" min="0" class="form-control" required></div>
        <div class="col-md-2"><label class="form-label">Available</label><input name="available_copies" id="book_available_copies" type="number" min="0" class="form-control" required></div>
        <div class="col-md-2"><label class="form-label">Status</label><select name="status" id="book_status" class="form-select"><option>available</option><option>limited</option><option>unavailable</option><option>archived</option></select></div>
        <div class="col-md-4"><label class="form-label">Shelf</label><input name="shelf_location" id="book_shelf_location" class="form-control"></div>
      </div>
      <div class="modal-footer"><button class="btn btn-primary">Save Book</button></div>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

