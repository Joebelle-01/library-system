<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_student_login();

$studentId = current_student()['id'];
$borrowId  = (int) ($_GET['id'] ?? 0);

if (!$borrowId) {
    header('Location: ' . APP_URL . '/student/history.php');
    exit;
}

// Fetch record — must belong to this student
$stmt = $pdo->prepare("
    SELECT br.id, br.borrow_date, br.due_date, br.return_date, br.status, br.remarks,
           b.title, b.author, b.isbn,
           COALESCE(f.amount, 0) AS fine_amount,
           COALESCE(f.status, 'none') AS fine_status,
           COALESCE(f.days_overdue, 0) AS days_overdue,
           COALESCE(f.daily_rate, 0) AS daily_rate,
           CONCAT(s.first_name, ' ', s.last_name) AS student_name,
           s.student_no, s.course, s.year_level,
           u.name AS returned_by_name,
           u.role AS returned_by_role
    FROM borrow_records br
    INNER JOIN books b ON b.id = br.book_id
    INNER JOIN students s ON s.id = br.student_id
    LEFT JOIN fines f ON f.borrow_record_id = br.id
    LEFT JOIN users u ON u.id = br.returned_by
    WHERE br.id = ? AND br.student_id = ? AND br.return_date IS NOT NULL
");
$stmt->execute([$borrowId, $studentId]);
$rec = $stmt->fetch();

if (!$rec) {
    header('Location: ' . APP_URL . '/student/history.php');
    exit;
}

$wasLate = strtotime($rec['return_date']) > strtotime($rec['due_date']);
$fineAmt = (float) $rec['fine_amount'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Return Receipt — <?= e($rec['title']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; background: #f0f4f8; color: #1e293b; }
    .receipt-wrap { max-width: 620px; margin: 40px auto; }
    .receipt-card {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 8px 40px rgba(0,0,0,0.10);
      overflow: hidden;
    }
    .receipt-header {
      background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%);
      color: #fff;
      padding: 32px 36px 24px;
    }
    .receipt-header .library-name {
      font-family: 'Outfit', sans-serif;
      font-size: 1.35rem;
      font-weight: 700;
      letter-spacing: -0.01em;
    }
    .receipt-header .receipt-label {
      font-size: 0.78rem;
      font-weight: 600;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      opacity: 0.75;
      margin-top: 4px;
    }
    .receipt-body { padding: 28px 36px; }
    .section-title {
      font-family: 'Outfit', sans-serif;
      font-weight: 600;
      font-size: 0.72rem;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: #64748b;
      margin-bottom: 10px;
      padding-bottom: 6px;
      border-bottom: 1px solid #e2e8f0;
    }
    .info-row { display: flex; justify-content: space-between; align-items: flex-start; padding: 5px 0; font-size: 0.9rem; }
    .info-row .label { color: #64748b; flex: 0 0 45%; }
    .info-row .value { font-weight: 500; text-align: right; flex: 1; }
    .badge-status { padding: 4px 12px; border-radius: 999px; font-size: 0.78rem; font-weight: 600; }
    .fine-box {
      border-radius: 10px;
      padding: 14px 18px;
      margin-top: 16px;
    }
    .fine-box.fine-ok  { background: #f0fdf4; border: 1px solid #bbf7d0; }
    .fine-box.fine-due { background: #fff7ed; border: 1px solid #fed7aa; }
    .fine-box.fine-paid{ background: #f0f9ff; border: 1px solid #bae6fd; }
    .receipt-footer {
      padding: 18px 36px;
      background: #f8fafc;
      border-top: 1px solid #e2e8f0;
      font-size: 0.78rem;
      color: #94a3b8;
    }
    .sig-line { border-top: 1px solid #cbd5e1; width: 160px; margin-top: 36px; padding-top: 6px; font-size: 0.72rem; color: #94a3b8; text-align: center; }
    .print-btn { display: inline-flex; align-items: center; gap: 8px; }
    @media print {
      body { background: #fff !important; }
      .no-print { display: none !important; }
      .receipt-wrap { margin: 0; max-width: 100%; }
      .receipt-card { box-shadow: none; border-radius: 0; }
    }
  </style>
</head>
<body>
<div class="receipt-wrap">

  <!-- Back + Print buttons (hidden in print) -->
  <div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <a href="<?= APP_URL ?>/student/history.php" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i>Back to History
    </a>
    <button onclick="window.print()" class="btn btn-primary btn-sm print-btn">
      <i class="bi bi-printer"></i> Print Receipt
    </button>
  </div>

  <div class="receipt-card">
    <!-- Header -->
    <div class="receipt-header">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="library-name"><i class="bi bi-book-half me-2"></i><?= e(APP_NAME) ?></div>
          <div class="receipt-label">Official Return Receipt</div>
        </div>
        <div class="text-end" style="font-size:0.8rem; opacity:0.8;">
          <div>Receipt #<?= str_pad((string)$rec['id'], 6, '0', STR_PAD_LEFT) ?></div>
          <div>Issued: <?= date('M j, Y') ?></div>
        </div>
      </div>
    </div>

    <div class="receipt-body">

      <!-- Student Info -->
      <div class="section-title">Student Information</div>
      <div class="info-row"><span class="label">Student Name</span><span class="value"><?= e($rec['student_name']) ?></span></div>
      <div class="info-row"><span class="label">Student No.</span><span class="value"><?= e($rec['student_no']) ?></span></div>
      <div class="info-row"><span class="label">Course & Year</span><span class="value"><?= e($rec['course']) ?><?= $rec['year_level'] ? ' — Year ' . e($rec['year_level']) : '' ?></span></div>

      <div class="mt-4"></div>

      <!-- Book Info -->
      <div class="section-title">Book Details</div>
      <div class="info-row"><span class="label">Book Title</span><span class="value fw-semibold"><?= e($rec['title']) ?></span></div>
      <div class="info-row"><span class="label">Author</span><span class="value"><?= e($rec['author']) ?></span></div>
      <div class="info-row"><span class="label">ISBN</span><span class="value"><?= e($rec['isbn']) ?></span></div>

      <div class="mt-4"></div>

      <!-- Transaction Info -->
      <div class="section-title">Transaction Details</div>
      <div class="info-row"><span class="label">Borrow Date</span><span class="value"><?= date('F j, Y', strtotime($rec['borrow_date'])) ?></span></div>
      <div class="info-row"><span class="label">Due Date</span><span class="value"><?= date('F j, Y', strtotime($rec['due_date'])) ?></span></div>
      <div class="info-row">
        <span class="label">Return Date</span>
        <span class="value">
          <?= date('F j, Y', strtotime($rec['return_date'])) ?>
          <?php if ($wasLate): ?>
            <span class="badge-status ms-1" style="background:#fee2e2;color:#b91c1c;">Late</span>
          <?php else: ?>
            <span class="badge-status ms-1" style="background:#dcfce7;color:#15803d;">On Time</span>
          <?php endif; ?>
        </span>
      </div>
      <?php if ($rec['returned_by_name']): ?>
      <div class="info-row">
        <span class="label">Processed By</span>
        <span class="value"><?= e($rec['returned_by_name']) ?> <small class="text-secondary">(<?= ucfirst(e($rec['returned_by_role'] ?? '')) ?>)</small></span>
      </div>
      <?php endif; ?>

      <!-- Fine Box -->
      <?php if ($fineAmt > 0): ?>
        <?php
          $boxClass = match($rec['fine_status']) {
            'paid'  => 'fine-paid',
            default => 'fine-due',
          };
        ?>
        <div class="fine-box <?= $boxClass ?> mt-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-semibold" style="font-size:0.9rem;">Overdue Fine</div>
              <div style="font-size:0.78rem; color:#64748b;"><?= (int)$rec['days_overdue'] ?> day<?= (int)$rec['days_overdue'] !== 1 ? 's' : '' ?> overdue &times; <?= money($rec['daily_rate']) ?>/day</div>
            </div>
            <div>
              <div class="fw-bold fs-5"><?= money($fineAmt) ?></div>
              <div class="text-end" style="font-size:0.75rem;">
                <?php if ($rec['fine_status'] === 'paid'): ?>
                  <span style="color:#0369a1;font-weight:600;">✔ Paid</span>
                <?php elseif ($rec['fine_status'] === 'waived'): ?>
                  <span style="color:#475569;font-weight:600;">Waived</span>
                <?php else: ?>
                  <span style="color:#c2410c;font-weight:600;">Unpaid</span>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php else: ?>
        <div class="fine-box fine-ok mt-3">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-check-circle-fill text-success fs-5"></i>
            <div>
              <div class="fw-semibold" style="font-size:0.9rem; color:#15803d;">No Fine Incurred</div>
              <div style="font-size:0.78rem; color:#64748b;">Book was returned on or before the due date.</div>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Signature Line -->
      <div class="d-flex justify-content-end mt-4">
        <div class="sig-line">
          <?php if ($rec['returned_by_name']): ?>
            <?= e($rec['returned_by_name']) ?>
          <?php else: ?>
            Library Staff
          <?php endif; ?>
          <br>Authorized Signature
        </div>
      </div>

    </div><!-- /receipt-body -->

    <!-- Footer -->
    <div class="receipt-footer d-flex justify-content-between">
      <span><i class="bi bi-shield-check me-1"></i>This is an official library receipt.</span>
      <span>Record ID #<?= str_pad((string)$rec['id'], 6, '0', STR_PAD_LEFT) ?></span>
    </div>
  </div><!-- /receipt-card -->

</div>
<script>
  // Auto-open print dialog when page loads via direct URL with ?print=1
  if (new URLSearchParams(location.search).get('print') === '1') {
    window.addEventListener('load', () => window.print());
  }
</script>
</body>
</html>
