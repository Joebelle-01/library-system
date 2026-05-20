<?php
$pageTitle = 'Student Management';
$active = 'students';
require_once __DIR__ . '/../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $data = [
        trim($_POST['student_no'] ?? ''),
        trim($_POST['first_name'] ?? ''),
        trim($_POST['last_name'] ?? ''),
        trim($_POST['course'] ?? ''),
        $_POST['year_level'] ?? '1st Year',
        trim($_POST['email'] ?? '') ?: null,
        trim($_POST['phone'] ?? ''),
        trim($_POST['address'] ?? ''),
        $_POST['status'] ?? 'active',
    ];
    if ($action === 'save') {
        if (!empty($_POST['id'])) {
            $pdo->prepare('UPDATE students SET student_no=?, first_name=?, last_name=?, course=?, year_level=?, email=?, phone=?, address=?, status=? WHERE id=?')->execute([...$data, (int) $_POST['id']]);
            flash('success', 'Student updated.');
        } else {
            $pdo->prepare('INSERT INTO students (student_no, first_name, last_name, course, year_level, email, phone, address, status) VALUES (?,?,?,?,?,?,?,?,?)')->execute($data);
            flash('success', 'Student added.');
        }
    } elseif ($action === 'delete') {
        require_role(['admin']);
        $pdo->prepare('DELETE FROM students WHERE id=?')->execute([(int) $_POST['id']]);
        flash('success', 'Student deleted.');
    }
    redirect('/admin/students.php');
}

$students = $pdo->query('SELECT s.*, COUNT(br.id) AS borrow_count FROM students s LEFT JOIN borrow_records br ON br.student_id=s.id GROUP BY s.id ORDER BY s.created_at DESC')->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <p class="text-secondary mb-0">Maintain student profiles and borrowing eligibility.</p>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#studentModal"><i class="bi bi-plus-lg me-1"></i>Add Student</button>
</div>
<div class="panel">
  <div class="table-responsive">
    <table class="table datatable align-middle">
      <thead><tr><th>Student</th><th>Course</th><th>Year</th><th>Contact</th><th>Borrows</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($students as $student): ?>
        <tr>
          <td><?= e($student['first_name'] . ' ' . $student['last_name']) ?><br><small class="text-secondary"><?= e($student['student_no']) ?></small></td>
          <td><?= e($student['course']) ?></td>
          <td><?= e($student['year_level']) ?></td>
          <td><?= e($student['email']) ?><br><small class="text-secondary"><?= e($student['phone']) ?></small></td>
          <td><a href="<?= APP_URL ?>/reports/index.php?student_id=<?= (int) $student['id'] ?>"><?= (int) $student['borrow_count'] ?></a></td>
          <td><span class="badge text-bg-<?= $student['status'] === 'active' ? 'success' : 'secondary' ?>"><?= e($student['status']) ?></span></td>
          <td class="text-end">
            <button class="btn btn-sm btn-outline-primary edit-student" data-student='<?= e(json_encode($student)) ?>'><i class="bi bi-pencil"></i></button>
            <?php if (current_user()['role'] === 'admin'): ?>
            <form method="post" class="d-inline delete-form">
              <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int) $student['id'] ?>">
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

<div class="modal fade" id="studentModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="post" class="modal-content needs-validation" novalidate>
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" id="student_id">
      <div class="modal-header"><h5 class="modal-title">Student Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body row g-3">
        <div class="col-md-4"><label class="form-label">Student ID</label><input name="student_no" id="student_student_no" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">First Name</label><input name="first_name" id="student_first_name" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">Last Name</label><input name="last_name" id="student_last_name" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Course</label><input name="course" id="student_course" class="form-control" required></div>
        <div class="col-md-3"><label class="form-label">Year Level</label><select name="year_level" id="student_year_level" class="form-select"><option>1st Year</option><option>2nd Year</option><option>3rd Year</option><option>4th Year</option><option>Graduate</option></select></div>
        <div class="col-md-3"><label class="form-label">Status</label><select name="status" id="student_status" class="form-select"><option>active</option><option>inactive</option></select></div>
        <div class="col-md-6"><label class="form-label">Email</label><input name="email" id="student_email" type="email" class="form-control"></div>
        <div class="col-md-6"><label class="form-label">Phone</label><input name="phone" id="student_phone" class="form-control"></div>
        <div class="col-12"><label class="form-label">Address</label><input name="address" id="student_address" class="form-control"></div>
      </div>
      <div class="modal-footer"><button class="btn btn-primary">Save Student</button></div>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

