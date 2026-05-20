<?php
declare(strict_types=1);
$pageTitle = 'Category Management';
$active = 'categories';
require_once __DIR__ . '/../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = !empty($_POST['id']) ? (int) $_POST['id'] : null;
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (!$name) {
            flash('error', 'Category name is required.');
            redirect('/admin/categories.php');
        }

        try {
            if ($id) {
                $stmt = $pdo->prepare('UPDATE categories SET name = ?, description = ? WHERE id = ?');
                $stmt->execute([$name, $description, $id]);
                flash('success', 'Category updated.');
            } else {
                // Check if category name exists
                $exists = query_value($pdo, 'SELECT COUNT(*) FROM categories WHERE name = ?', [$name]);
                if ($exists) {
                    flash('error', 'Category name already exists.');
                    redirect('/admin/categories.php');
                }
                $stmt = $pdo->prepare('INSERT INTO categories (name, description) VALUES (?, ?)');
                $stmt->execute([$name, $description]);
                flash('success', 'Category added.');
            }
        } catch (Throwable $e) {
            flash('error', 'Error: ' . $e->getMessage());
        }
    } elseif ($action === 'delete') {
        require_role(['admin']);
        $id = (int) ($_POST['id'] ?? 0);
        try {
            $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
            $stmt->execute([$id]);
            flash('success', 'Category deleted.');
        } catch (Throwable $e) {
            flash('error', 'Cannot delete category: it may be linked to active books.');
        }
    }
    redirect('/admin/categories.php');
}

$categoriesList = $pdo->query('SELECT c.*, COUNT(b.id) AS book_count FROM categories c LEFT JOIN books b ON b.category_id = c.id GROUP BY c.id ORDER BY c.name ASC')->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <p class="text-secondary mb-0">Organize books by creating and updating library genres or categories.</p>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal"><i class="bi bi-plus-lg me-1"></i>Add Category</button>
</div>

<div class="panel">
  <div class="table-responsive">
    <table class="table datatable align-middle">
      <thead><tr><th>Name</th><th>Description</th><th>Associated Books</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($categoriesList as $cat): ?>
        <tr>
          <td><strong><?= e($cat['name']) ?></strong></td>
          <td><?= e($cat['description'] ?: 'No description provided.') ?></td>
          <td><span class="badge text-bg-primary"><?= (int) $cat['book_count'] ?> books</span></td>
          <td class="text-end">
            <button class="btn btn-sm btn-outline-primary edit-category" data-category='<?= e(json_encode($cat)) ?>'><i class="bi bi-pencil"></i></button>
            <?php if (current_user()['role'] === 'admin'): ?>
              <form method="post" class="d-inline delete-form">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $cat['id'] ?>">
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

<div class="modal fade" id="categoryModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content needs-validation" novalidate>
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" id="category_id">
      <div class="modal-header"><h5 class="modal-title">Category Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body row g-3">
        <div class="col-12"><label class="form-label">Category Name</label><input name="name" id="category_name" class="form-control" required></div>
        <div class="col-12"><label class="form-label">Description</label><textarea name="description" id="category_description" class="form-control" rows="3"></textarea></div>
      </div>
      <div class="modal-footer"><button class="btn btn-primary">Save Category</button></div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.edit-category').forEach((button) => {
    button.addEventListener('click', () => {
      const cat = JSON.parse(button.dataset.category);
      document.getElementById('category_id').value = cat.id;
      document.getElementById('category_name').value = cat.name;
      document.getElementById('category_description').value = cat.description ?? '';
      bootstrap.Modal.getOrCreateInstance(document.getElementById('categoryModal')).show();
    });
  });

  const categoryModal = document.getElementById('categoryModal');
  if (categoryModal) {
    categoryModal.addEventListener('hidden.bs.modal', () => {
      const form = categoryModal.querySelector('form');
      if (form) {
        form.reset();
        form.classList.remove('was-validated');
      }
      document.getElementById('category_id').value = '';
    });
  }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
