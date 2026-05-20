<?php
$pageTitle = 'Fine Management';
$active = 'fines';
require_once __DIR__ . '/../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'mark_paid') {
        $pdo->prepare('UPDATE fines SET status="paid", paid_at=NOW() WHERE id=?')->execute([(int) $_POST['id']]);
        flash('success', 'Fine marked as paid.');
    } elseif ($action === 'waive') {
        require_role(['admin']);
        $pdo->prepare('UPDATE fines SET status="waived" WHERE id=?')->execute([(int) $_POST['id']]);
        flash('success', 'Fine waived.');
    }
    redirect('/admin/fines.php');
}

$fines = $pdo->query("
    SELECT f.*, CONCAT(s.first_name, ' ', s.last_name) AS student_name, s.student_no, b.title
    FROM fines f
    INNER JOIN students s ON s.id = f.student_id
    INNER JOIN borrow_records br ON br.id = f.borrow_record_id
    INNER JOIN books b ON b.id = br.book_id
    ORDER BY f.created_at DESC
")->fetchAll();
?>
<div class="alert alert-info border-0">Daily fine rate setting: <strong><?= money(DAILY_FINE_RATE) ?></strong> per overdue day. Change it in <code>config/app.php</code>.</div>
<div class="panel">
  <div class="table-responsive">
    <table class="table datatable align-middle">
      <thead><tr><th>Student</th><th>Book</th><th>Days</th><th>Rate</th><th>Amount</th><th>Status</th><th>Paid At</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($fines as $fine): ?>
        <tr>
          <td><?= e($fine['student_name']) ?><br><small class="text-secondary"><?= e($fine['student_no']) ?></small></td>
          <td><?= e($fine['title']) ?></td>
          <td><?= (int) $fine['days_overdue'] ?></td>
          <td><?= money($fine['daily_rate']) ?></td>
          <td><?= money($fine['amount']) ?></td>
          <td><span class="badge text-bg-<?= $fine['status'] === 'paid' ? 'success' : ($fine['status'] === 'waived' ? 'secondary' : 'warning') ?>"><?= e($fine['status']) ?></span></td>
          <td><?= e($fine['paid_at']) ?></td>
          <td class="text-end">
            <?php if ($fine['status'] === 'unpaid'): ?>
              <form method="post" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="mark_paid">
                <input type="hidden" name="id" value="<?= (int) $fine['id'] ?>">
                <button class="btn btn-sm btn-success">Paid</button>
              </form>
              <?php if (current_user()['role'] === 'admin'): ?>
              <form method="post" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="waive">
                <input type="hidden" name="id" value="<?= (int) $fine['id'] ?>">
                <button class="btn btn-sm btn-outline-secondary">Waive</button>
              </form>
              <?php endif; ?>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

