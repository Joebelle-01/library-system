USE library_system;

DROP TABLE IF EXISTS fact_borrowing;
DROP TABLE IF EXISTS dim_date;
DROP TABLE IF EXISTS dim_book;
DROP TABLE IF EXISTS dim_student;

CREATE TABLE dim_student (
  student_key INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL UNIQUE,
  student_no VARCHAR(40) NOT NULL,
  student_name VARCHAR(180) NOT NULL,
  course VARCHAR(120) NOT NULL,
  year_level VARCHAR(40) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE dim_book (
  book_key INT AUTO_INCREMENT PRIMARY KEY,
  book_id INT NOT NULL UNIQUE,
  title VARCHAR(180) NOT NULL,
  author VARCHAR(160) NOT NULL,
  isbn VARCHAR(40) NOT NULL,
  category VARCHAR(120) NULL
) ENGINE=InnoDB;

CREATE TABLE dim_date (
  date_key INT PRIMARY KEY,
  full_date DATE NOT NULL UNIQUE,
  day_num INT NOT NULL,
  month_num INT NOT NULL,
  month_name VARCHAR(20) NOT NULL,
  quarter_num INT NOT NULL,
  year_num INT NOT NULL
) ENGINE=InnoDB;

CREATE TABLE fact_borrowing (
  fact_id INT AUTO_INCREMENT PRIMARY KEY,
  student_key INT NOT NULL,
  book_key INT NOT NULL,
  borrow_date_key INT NOT NULL,
  due_date_key INT NOT NULL,
  return_date_key INT NULL,
  borrow_count INT NOT NULL DEFAULT 1,
  fine_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  borrowing_duration INT NOT NULL DEFAULT 0,
  days_overdue INT NOT NULL DEFAULT 0,
  source_borrow_record_id INT NOT NULL UNIQUE,
  loaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_fact_student FOREIGN KEY (student_key) REFERENCES dim_student(student_key),
  CONSTRAINT fk_fact_book FOREIGN KEY (book_key) REFERENCES dim_book(book_key),
  CONSTRAINT fk_fact_borrow_date FOREIGN KEY (borrow_date_key) REFERENCES dim_date(date_key),
  CONSTRAINT fk_fact_due_date FOREIGN KEY (due_date_key) REFERENCES dim_date(date_key),
  CONSTRAINT fk_fact_return_date FOREIGN KEY (return_date_key) REFERENCES dim_date(date_key)
) ENGINE=InnoDB;

