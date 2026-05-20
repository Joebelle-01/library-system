<?php
declare(strict_types=1);
$pageTitle = 'Borrow History';
$active = 'history';
require_once __DIR__ . '/includes/student_header.php';

$studentId = current_student()['id'];

// Filter support
$filter = $_GET['filter'] ?? 'all';
$allowedFilters = ['all', 'returned', 'overdue', 'fined'];

if (!in_array($filter, $allowedFilters, true)) {
    $filter = 'all';
}

$whereClauses = ['br.student_id = ?', 'br.return_date IS NOT NULL'];
if ($filter === 'overdue') {
    $whereClauses[] = "br.status = 'overdue' OR (br.due_date < br.return_date)";
} elseif ($filter === 'fined') {
    $whereClauses[] = 'f.amount > 0';
} elseif ($filter === 'returned') {
    $whereClauses[] = "br.status = 'returned'";
}

$whereSQL = implode(' AND ', $whereClauses);

$stmt = $pdo->prepare("
    SELECT br.id, br.borrow_date, br.due_date, br.return_date, br.status,
           b.title, b.author, b.isbn,
           COALESCE(f.amount, 0) AS fine_amount,
           COALESCE(f.status, 'none') AS fine_status,
           CONCAT(u.first_name, ' ', u.last_name) AS returned_by_name
    FROM borrow_records br
    INNER JOIN books b ON b.id = br.book_id
    LEFT JOIN fines f ON f.borrow_record_id = br.id
    LEFT JOIN users u ON u.id = br.returned_by
    WHERE {$whereSQL}
    ORDER BY br.return_date DESC
");
$stmt->execute([$studentId]);
$rows = $stmt->fetchAll();

$totalBorrows = (int) query_value($pdo, 'SELECT COUNT(*) FROM borrow_records WHERE student_id = ? AND return_date IS NOT NULL', [$studentId]);
$totalFined   = (int) query_value($pdo, 'SELECT COUNT(*) FROM borrow_records br LEFT JOIN fines f ON f.borrow_record_id = br.id WHERE br.student_id = ? AND br.return_date IS NOT NULL AND f.amount > 0', [$studentId]);
$totalPaid    = (float) query_value($pdo, "SELECT COALESCE(SUM(f.amount),0) FROM fines f WHERE f.student_id = ? AND f.status = 'paid'", [$studentId]);
?>

<div class="row g-3 mb-4">
  <div class="col-12 col-md-4">
    <div class="metric-card">
      <div>
        <p>Total Returns</p>
        <h2><?= $totalBorrows ?></h2>
      </div>
      <span class="metric-icon text-bg-primary"><i class="bi bi-arrow-return-left"></i></span>
    </div>
  </div>
  <div class="col-12 col-md-4">
    <div class="metric-card">
      <div>
        <p>Books with Fine</p>
        <h2><?= $totalFined ?></h2>
      </div>
      <span class="metric-icon text-bg-warning"><i class="bi bi-cash-coin"></i></span>
    </div>
  </div>
  <div class="col-12 col-md-4">
    <div class="metric-card">
      <div>
        <p>Total Fines Paid</p>
        <h2><?= money($totalPaid) ?></h2>
      </div>
      <span class="metric-icon text-bg-success"><i class="bi bi-check-circle"></i></span>
    </div>
  </div>
</div>

<div class="panel">
  <div class="panel-header d-flex flex-wrap align-items-center justify-content-between gap-2">
    <h2 class="mb-0">Complete Borrow History</h2>
    <div class="d-flex gap-2 flex-wrap">
      <?php
        $filters = [
          'all'      => ['label' => 'All', 'icon' => 'bi-list-ul'],
          'returned' => ['label' => 'On Time', 'icon' => 'bi-check-circle'],
          'overdue'  => ['label' => 'Overdue', 'icon' => 'bi-exclamation-circle'],
          'fined'    => ['label' => 'Fined', 'icon' => 'bi-cash-coin'],
        ];
        foreach ($filters as $key => $meta):
          $active_class = $filter === $key ? 'btn-primary' : 'btn-outline-secondary';
      ?>
      <a href="?filter=<?= $key ?>" class="btn btn-sm <?= $active_class ?>">
        <i class="bi <?= $meta['icon'] ?> me-1"></i><?= $meta['label'] ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table datatable align-middle">
      <thead>
        <tr>
          <th>Book Title</th>
          <th>ISBN</th>
          <th>Borrow Date</th>
          <th>Due Date</th>
          <th>Return Date</th>
          <th>Received By</th>
          <th>Fine</th>
          <th>Fine Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="9" class="text-center text-secondary py-5">
          <i class="bi bi-inbox fs-2 d-block mb-2"></i>No records found for this filter.
        </td></tr>
      <?php else: ?>
        <?php foreach ($rows as $row): ?>
          <?php
            $wasLate = strtotime($row['return_date']) > strtotime($row['due_date']);
            $fineAmt  = (float) $row['fine_amount'];
          ?>
          <tr>
            <td>
              <strong><?= e($row['title']) ?></strong><br>
              <small class="text-secondary">by <?= e($row['author']) ?></small>
            </td>
            <td><?= e($row['isbn']) ?></td>
            <td><?= date('M j, Y', strtotime($row['borrow_date'])) ?></td>
            <td><?= date('M j, Y', strtotime($row['due_date'])) ?></td>
            <td>
              <?= date('M j, Y', strtotime($row['return_date'])) ?>
              <?php if ($wasLate): ?>
                <br><small class="text-danger"><i class="bi bi-exclamation-circle me-1"></i>Returned late</small>
              <?php else: ?>
                <br><small class="text-success"><i class="bi bi-check-circle me-1"></i>On time</small>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($row['returned_by_name']): ?>
                <span class="badge text-bg-light text-dark border">
                  <i class="bi bi-person me-1"></i><?= e($row['returned_by_name']) ?>
                </span>
              <?php else: ?>
                <span class="text-secondary">—</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($fineAmt > 0): ?>
                <span class="fw-bold text-danger"><?= money($fineAmt) ?></span>
              <?php else: ?>
                <span class="text-success">None</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($fineAmt > 0): ?>
                <?php
                  $fsBadge = match($row['fine_status']) {
                      'paid'   => 'success',
                      'waived' => 'secondary',
                      default  => 'warning text-dark',
                  };
                ?>
                <span class="badge text-bg-<?= $fsBadge ?>"><?= ucfirst(e($row['fine_status'])) ?></span>
              <?php else: ?>
                <span class="badge text-bg-success">N/A</span>
              <?php endif; ?>
            </td>
            <td>
              <a href="<?= APP_URL ?>/student/receipt.php?id=<?= (int)$row['id'] ?>"
                 class="btn btn-sm btn-outline-primary" target="_blank" title="Print Receipt">
                <i class="bi bi-printer"></i>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
