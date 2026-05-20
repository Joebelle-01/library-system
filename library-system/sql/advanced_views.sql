USE library_system;

CREATE OR REPLACE VIEW vw_borrow_details AS
SELECT
  br.id,
  br.borrow_date,
  br.due_date,
  br.return_date,
  br.status,
  CONCAT(s.first_name, ' ', s.last_name) AS student_name,
  s.student_no,
  s.course,
  b.title AS book_title,
  b.author,
  b.isbn,
  c.name AS category,
  COALESCE(f.amount, 0) AS fine_amount,
  COALESCE(f.status, 'none') AS fine_status
FROM borrow_records br
INNER JOIN students s ON s.id = br.student_id
INNER JOIN books b ON b.id = br.book_id
LEFT JOIN categories c ON c.id = b.category_id
LEFT JOIN fines f ON f.borrow_record_id = br.id;

CREATE OR REPLACE VIEW vw_most_borrowed_books AS
SELECT
  b.id,
  b.title,
  b.author,
  COUNT(br.id) AS borrow_count,
  RANK() OVER (ORDER BY COUNT(br.id) DESC) AS borrow_rank
FROM books b
LEFT JOIN borrow_records br ON br.book_id = b.id
GROUP BY b.id, b.title, b.author;

CREATE OR REPLACE VIEW vw_overdue_students AS
SELECT
  br.id AS borrow_record_id,
  s.student_no,
  CONCAT(s.first_name, ' ', s.last_name) AS student_name,
  b.title,
  br.due_date,
  DATEDIFF(CURDATE(), br.due_date) AS days_overdue,
  (DATEDIFF(CURDATE(), br.due_date) * 10.00) AS estimated_fine
FROM borrow_records br
INNER JOIN students s ON s.id = br.student_id
INNER JOIN books b ON b.id = br.book_id
WHERE br.return_date IS NULL AND br.due_date < CURDATE();

CREATE OR REPLACE VIEW vw_student_borrow_stats AS
SELECT
  s.id,
  s.student_no,
  CONCAT(s.first_name, ' ', s.last_name) AS student_name,
  COUNT(br.id) AS total_borrows,
  SUM(CASE WHEN br.status = 'returned' THEN 1 ELSE 0 END) AS returned_count,
  SUM(CASE WHEN br.return_date IS NULL AND br.due_date < CURDATE() THEN 1 ELSE 0 END) AS overdue_count,
  (SELECT COALESCE(SUM(f.amount), 0) FROM fines f WHERE f.student_id = s.id AND f.status = 'paid') AS total_paid_fines
FROM students s
LEFT JOIN borrow_records br ON br.student_id = s.id
GROUP BY s.id, s.student_no, s.first_name, s.last_name;

