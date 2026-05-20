<?php
$pageTitle = 'ETL Process';
$active = 'etl';
require_once __DIR__ . '/../includes/header.php';

$log = [];

function date_key_for(string $date): int
{
    return (int) date('Ymd', strtotime($date));
}

function load_date_dim(PDO $pdo, ?string $date, array &$log): ?int
{
    if (!$date) {
        return null;
    }
    $ts = strtotime($date);
    $key = (int) date('Ymd', $ts);
    $stmt = $pdo->prepare('INSERT INTO dim_date (date_key, full_date, day_num, month_num, month_name, quarter_num, year_num) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE full_date=VALUES(full_date)');
    $stmt->execute([$key, date('Y-m-d', $ts), (int) date('j', $ts), (int) date('n', $ts), date('F', $ts), (int) ceil((int) date('n', $ts) / 3), (int) date('Y', $ts)]);
    $log[] = "Loaded date dimension {$date}.";
    return $key;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $pdo->exec('START TRANSACTION');

        $pdo->exec("
            INSERT INTO dim_student (student_id, student_no, student_name, course, year_level)
            SELECT id, student_no, CONCAT(first_name, ' ', last_name), course, year_level FROM students
            ON DUPLICATE KEY UPDATE student_no=VALUES(student_no), student_name=VALUES(student_name), course=VALUES(course), year_level=VALUES(year_level)
        ");
        $log[] = 'Extracted and loaded student dimensions.';

        $pdo->exec("
            INSERT INTO dim_book (book_id, title, author, isbn, category)
            SELECT b.id, b.title, b.author, b.isbn, c.name
            FROM books b LEFT JOIN categories c ON c.id = b.category_id
            ON DUPLICATE KEY UPDATE title=VALUES(title), author=VALUES(author), isbn=VALUES(isbn), category=VALUES(category)
        ");
        $log[] = 'Extracted and loaded book dimensions.';

        $records = $pdo->query("
            SELECT br.*, COALESCE(f.amount, 0) AS fine_amount, COALESCE(f.days_overdue, 0) AS fine_days
            FROM borrow_records br
            LEFT JOIN fines f ON f.borrow_record_id = br.id
        ")->fetchAll();

        $factStmt = $pdo->prepare("
            INSERT INTO fact_borrowing (student_key, book_key, borrow_date_key, due_date_key, return_date_key, borrow_count, fine_amount, borrowing_duration, days_overdue, source_borrow_record_id)
            VALUES (?,?,?,?,?,1,?,?,?,?)
            ON DUPLICATE KEY UPDATE fine_amount=VALUES(fine_amount), borrowing_duration=VALUES(borrowing_duration), days_overdue=VALUES(days_overdue), return_date_key=VALUES(return_date_key), loaded_at=CURRENT_TIMESTAMP
        ");

        foreach ($records as $record) {
            $borrowKey = load_date_dim($pdo, $record['borrow_date'], $log);
            $dueKey = load_date_dim($pdo, $record['due_date'], $log);
            $returnKey = load_date_dim($pdo, $record['return_date'], $log);
            $studentKey = query_value($pdo, 'SELECT student_key FROM dim_student WHERE student_id=?', [(int) $record['student_id']]);
            $bookKey = query_value($pdo, 'SELECT book_key FROM dim_book WHERE book_id=?', [(int) $record['book_id']]);
            $endDate = $record['return_date'] ?: date('Y-m-d');
            $duration = max(0, (int) ((strtotime($endDate) - strtotime($record['borrow_date'])) / 86400));
            $daysOverdue = max((int) $record['fine_days'], max(0, (int) ((strtotime($endDate) - strtotime($record['due_date'])) / 86400)));
            $factStmt->execute([(int) $studentKey, (int) $bookKey, $borrowKey, $dueKey, $returnKey, (float) $record['fine_amount'], $duration, $daysOverdue, (int) $record['id']]);
        }

        $pdo->exec('COMMIT');
        flash('success', 'ETL completed successfully.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->exec('ROLLBACK');
        }
        flash('error', 'ETL failed: ' . $e->getMessage());
    }
    $_SESSION['etl_log'] = $log;
    redirect('/etl/etl_process.php');
}

$lastLog = $_SESSION['etl_log'] ?? [];
unset($_SESSION['etl_log']);
$factCount = query_value($pdo, 'SELECT COUNT(*) FROM fact_borrowing');
?>
<div class="row g-4">
  <div class="col-lg-5">
    <div class="panel">
      <div class="panel-header"><h2>Run ETL</h2></div>
      <p class="text-secondary">Extract operational borrowing data, transform it for analytics, and load the star-schema warehouse tables.</p>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <button class="btn btn-primary btn-lg"><i class="bi bi-database-gear me-1"></i>Run ETL Process</button>
      </form>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="panel">
      <div class="panel-header"><h2>Warehouse Status</h2></div>
      <p class="display-6 mb-1"><?= (int) $factCount ?></p>
      <p class="text-secondary">Rows in <code>fact_borrowing</code></p>
      <?php if ($lastLog): ?>
        <ul class="list-group list-group-flush">
          <?php foreach (array_slice($lastLog, -12) as $entry): ?><li class="list-group-item"><?= e($entry) ?></li><?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
