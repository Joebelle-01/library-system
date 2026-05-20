<?php
$pageTitle = 'Reports';
$active = 'reports';
require_once __DIR__ . '/../includes/header.php';

$type = $_GET['type'] ?? 'borrowed';
$studentId = (int) ($_GET['student_id'] ?? 0);
$where = '1=1';
$params = [];

if ($type === 'returned') {
    $where .= " AND status = 'returned'";
} elseif ($type === 'overdue') {
    $where .= " AND return_date IS NULL AND due_date < CURDATE()";
} elseif ($type === 'fines') {
    $where .= " AND fine_amount > 0";
} else {
    $where .= " AND status IN ('borrowed','overdue')";
}
if ($studentId > 0) {
    $where .= ' AND id IN (SELECT br2.id FROM borrow_records br2 WHERE br2.student_id = ?)';
    $params[] = $studentId;
}

$stmt = $pdo->prepare("SELECT * FROM vw_borrow_details WHERE {$where} ORDER BY borrow_date DESC");
$stmt->execute($params);
$rows = $stmt->fetchAll();
$students = $pdo->query("SELECT id, student_no, CONCAT(first_name, ' ', last_name) AS name FROM students ORDER BY last_name")->fetchAll();
?>
<div class="panel mb-4 no-print">
  <form class="row g-3 align-items-end">
    <div class="col-md-4">
      <label class="form-label">Report Type</label>
      <select name="type" class="form-select">
        <?php foreach (['borrowed' => 'Borrowed Books', 'returned' => 'Returned Books', 'overdue' => 'Overdue Books', 'fines' => 'Fine Collection'] as $key => $label): ?>
          <option value="<?= e($key) ?>" <?= $type === $key ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-5">
      <label class="form-label">Student History</label>
      <select name="student_id" class="form-select">
        <option value="0">All students</option>
        <?php foreach ($students as $student): ?>
          <option value="<?= (int) $student['id'] ?>" <?= $studentId === (int) $student['id'] ? 'selected' : '' ?>><?= e($student['student_no'] . ' - ' . $student['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3 d-flex gap-2">
      <button class="btn btn-primary flex-fill">Generate</button>
      <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i></button>
    </div>
  </form>
</div>

<div class="print-header d-none d-print-block">
  <h1>Advanced Library Management System</h1>
  <p><?= e(ucfirst($type)) ?> report generated <?= date('Y-m-d H:i') ?></p>
</div>

<div class="panel">
  <div class="panel-header">
    <h2><?= e(ucfirst($type)) ?> Report</h2>
  </div>
  <div class="table-responsive">
    <table class="table datatable align-middle">
      <thead><tr><th>Student</th><th>Book</th><th>Borrow Date</th><th>Due Date</th><th>Return Date</th><th>Status</th><th>Fine</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $row): ?>
        <tr>
          <td><?= e($row['student_name']) ?><br><small><?= e($row['student_no']) ?></small></td>
          <td><?= e($row['book_title']) ?><br><small><?= e($row['isbn']) ?></small></td>
          <td><?= e($row['borrow_date']) ?></td>
          <td><?= e($row['due_date']) ?></td>
          <td><?= e($row['return_date']) ?></td>
          <td><?= e($row['status']) ?></td>
          <td><?= money($row['fine_amount']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="panel mt-4 no-print">
  <div class="panel-header"><h2>Advanced SQL Demonstrations</h2></div>
  <div class="row g-3">
    <div class="col-md-6">
      <h3 class="h6">Window Function: Most Borrowed Ranking</h3>
      <pre class="sql-box">SELECT title, borrow_count, RANK() OVER (ORDER BY borrow_count DESC) FROM vw_most_borrowed_books;</pre>
    </div>
    <div class="col-md-6">
      <h3 class="h6">Subquery: Student Fine Totals</h3>
      <pre class="sql-box">SELECT student_name, (SELECT SUM(amount) FROM fines WHERE student_id = s.id) AS fines FROM students s;</pre>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

