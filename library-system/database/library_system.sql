CREATE DATABASE IF NOT EXISTS library_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE library_system;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS book_reservations;
DROP TABLE IF EXISTS fines;
DROP TABLE IF EXISTS borrow_records;
DROP TABLE IF EXISTS books;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','librarian') NOT NULL DEFAULT 'librarian',
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  description TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_no VARCHAR(40) NOT NULL UNIQUE,
  first_name VARCHAR(80) NOT NULL,
  last_name VARCHAR(80) NOT NULL,
  course VARCHAR(120) NOT NULL,
  year_level ENUM('1st Year','2nd Year','3rd Year','4th Year','Graduate') NOT NULL,
  email VARCHAR(160) NULL UNIQUE,
  password VARCHAR(255) NULL,
  phone VARCHAR(40) NULL,
  address VARCHAR(255) NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE books (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NULL,
  title VARCHAR(180) NOT NULL,
  author VARCHAR(160) NOT NULL,
  isbn VARCHAR(40) NOT NULL UNIQUE,
  publisher VARCHAR(160) NULL,
  published_year YEAR NULL,
  quantity INT NOT NULL DEFAULT 1,
  available_copies INT NOT NULL DEFAULT 1,
  shelf_location VARCHAR(80) NULL,
  status ENUM('available','limited','unavailable','archived') NOT NULL DEFAULT 'available',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_books_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  CONSTRAINT chk_book_quantity CHECK (quantity >= 0),
  CONSTRAINT chk_available_copies CHECK (available_copies >= 0 AND available_copies <= quantity)
) ENGINE=InnoDB;

CREATE TABLE borrow_records (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  book_id INT NOT NULL,
  borrowed_by INT NULL,
  returned_by INT NULL,
  borrow_date DATE NOT NULL,
  due_date DATE NOT NULL,
  return_date DATE NULL,
  status ENUM('borrowed','returned','overdue') NOT NULL DEFAULT 'borrowed',
  remarks VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_borrow_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT fk_borrow_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
  CONSTRAINT fk_borrow_user FOREIGN KEY (borrowed_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_return_user FOREIGN KEY (returned_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE fines (
  id INT AUTO_INCREMENT PRIMARY KEY,
  borrow_record_id INT NOT NULL UNIQUE,
  student_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  days_overdue INT NOT NULL DEFAULT 0,
  daily_rate DECIMAL(10,2) NOT NULL DEFAULT 10.00,
  status ENUM('unpaid','paid','waived') NOT NULL DEFAULT 'unpaid',
  paid_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_fine_borrow FOREIGN KEY (borrow_record_id) REFERENCES borrow_records(id) ON DELETE CASCADE,
  CONSTRAINT fk_fine_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT chk_fine_amount CHECK (amount >= 0)
) ENGINE=InnoDB;

CREATE TABLE book_reservations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  book_id INT NOT NULL,
  reservation_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status ENUM('pending','approved','cancelled','expired') NOT NULL DEFAULT 'pending',
  CONSTRAINT fk_res_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT fk_res_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO users (name, email, password, role) VALUES
('System Administrator', 'admin@library.test', '$2y$10$2ikaI0Q.MpTPISOcorIkheYXfpn.uQxBfPqyXL48PW52G22LfFdh2', 'admin'),
('Main Librarian', 'librarian@library.test', '$2y$10$avNIUS7Iti0anNAp1RO9eeu3OumLwBigDQ0EUB3zZmY4MljRlGL7a', 'librarian');

INSERT INTO categories (name, description) VALUES
('Computer Science', 'Programming, databases, systems, and AI'),
('Business', 'Management, accounting, and entrepreneurship'),
('Education', 'Teaching, curriculum, and learning references'),
('Literature', 'Novels, poetry, and literary criticism'),
('Science', 'Biology, physics, chemistry, and general science');

INSERT INTO students (student_no, first_name, last_name, course, year_level, email, password, phone, address) VALUES
('STU-2026-001', 'Mika', 'Santos', 'BS Information Technology', '3rd Year', 'mika.santos@example.com', '$2y$10$h9W8N3V7m/g435S9WJz0fO2n9t5vVfGv2t3h8XpMh2YvWf1j1v4J2', '09170000001', 'Manila'),
('STU-2026-002', 'Jonas', 'Reyes', 'BS Computer Science', '2nd Year', 'jonas.reyes@example.com', '$2y$10$h9W8N3V7m/g435S9WJz0fO2n9t5vVfGv2t3h8XpMh2YvWf1j1v4J2', '09170000002', 'Quezon City'),
('STU-2026-003', 'Elaine', 'Cruz', 'BS Education', '4th Year', 'elaine.cruz@example.com', '$2y$10$h9W8N3V7m/g435S9WJz0fO2n9t5vVfGv2t3h8XpMh2YvWf1j1v4J2', '09170000003', 'Pasig'),
('STU-2026-004', 'Carlo', 'Lim', 'BS Business Administration', '1st Year', 'carlo.lim@example.com', '$2y$10$h9W8N3V7m/g435S9WJz0fO2n9t5vVfGv2t3h8XpMh2YvWf1j1v4J2', '09170000004', 'Makati');

INSERT INTO books (category_id, title, author, isbn, publisher, published_year, quantity, available_copies, shelf_location, status) VALUES
(1, 'Database System Concepts', 'Abraham Silberschatz', '9780073523323', 'McGraw-Hill', 2019, 4, 3, 'CS-A1', 'available'),
(1, 'Clean Code', 'Robert C. Martin', '9780132350884', 'Prentice Hall', 2008, 3, 2, 'CS-A2', 'available'),
(2, 'Principles of Management', 'Harold Koontz', '9781259005129', 'McGraw-Hill', 2020, 2, 2, 'BUS-B1', 'available'),
(3, 'The Art of Teaching', 'Jay Parini', '9780199899487', 'Oxford', 2016, 2, 1, 'EDU-C1', 'available'),
(4, 'Noli Me Tangere', 'Jose Rizal', '9789712735630', 'Anvil', 2014, 5, 5, 'LIT-D1', 'available'),
(5, 'A Brief History of Time', 'Stephen Hawking', '9780553380163', 'Bantam', 1998, 2, 1, 'SCI-E1', 'limited');

INSERT INTO borrow_records (student_id, book_id, borrowed_by, returned_by, borrow_date, due_date, return_date, status, remarks) VALUES
(1, 1, 1, NULL, DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 5 DAY), NULL, 'borrowed', 'Course reference'),
(2, 2, 2, NULL, DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_SUB(CURDATE(), INTERVAL 3 DAY), NULL, 'overdue', 'Programming assignment'),
(3, 4, 2, 1, DATE_SUB(CURDATE(), INTERVAL 12 DAY), DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'returned', 'Returned with good condition'),
(4, 6, 1, NULL, DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 6 DAY), NULL, 'borrowed', 'Science report');

INSERT INTO fines (borrow_record_id, student_id, amount, days_overdue, daily_rate, status) VALUES
(2, 2, 30.00, 3, 10.00, 'unpaid'),
(3, 3, 40.00, 4, 10.00, 'paid');

