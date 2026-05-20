# Advanced Library Management System

A complete XAMPP-ready Library Management System built with PHP, MySQL, HTML, CSS, JavaScript, Bootstrap 5, PDO, DataTables, Chart.js, and SweetAlert2.

## Features

- Admin and librarian authentication with password hashing and sessions
- Role-based access for destructive actions
- Responsive Bootstrap 5 admin dashboard with sidebar navigation
- Book inventory CRUD with categories, ISBN, quantity, available copies, status, search, and filters
- Student records CRUD with course, year level, contact details, and borrowing history
- Borrowing module with `START TRANSACTION`, `SELECT ... FOR UPDATE`, `COMMIT`, and `ROLLBACK`
- Return module with stock restoration and automatic overdue fine computation
- Fine management with paid and waived status
- Printable reports for borrowed, returned, overdue, student history, and fines
- Advanced SQL examples using joins, subqueries, views, and window functions
- Star-schema warehouse: `dim_student`, `dim_book`, `dim_date`, `fact_borrowing`
- ETL process to extract operational data, transform analytics fields, and load warehouse tables
- Analytics dashboard for most borrowed books, trends, monthly borrows, fines, and active students

## Requirements

- XAMPP with Apache, PHP, and MySQL/MariaDB
- Browser with internet access for CDN assets, or download Bootstrap/DataTables/Chart.js/SweetAlert2 locally and update the include paths

## Setup

1. Copy the `library-system` folder into `C:\xampp\htdocs\library-system`.
2. Start Apache and MySQL in the XAMPP Control Panel.
3. Open phpMyAdmin at `http://localhost/phpmyadmin`.
4. Import these files in order:
   - `database/library_system.sql`
   - `warehouse/warehouse_schema.sql`
   - `sql/advanced_views.sql`
5. Visit `http://localhost/library-system`.

## Demo Accounts

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@library.test` | `admin123` |
| Librarian | `librarian@library.test` | `librarian123` |

## Project Structure

```text
library-system/
├── config/
├── database/
├── warehouse/
├── etl/
├── admin/
├── assets/
│   ├── css/
│   ├── js/
│   ├── images/
│   └── bootstrap/
├── includes/
├── reports/
├── sql/
└── index.php
```

## Database Concepts Demonstrated

- Referential integrity through foreign keys, constraints, `NOT NULL`, `UNIQUE`, and cascading behavior
- Transaction management in `admin/borrow.php` and `admin/returns.php`
- Concurrency control with `SELECT ... FOR UPDATE`
- SQL views in `sql/advanced_views.sql`
- Window functions through `vw_most_borrowed_books`
- Subqueries through `vw_student_borrow_stats` and report examples
- Star-schema data warehouse and ETL processing

## Notes

- The default database connection uses MySQL user `root` with an empty password in `config/database.php`, matching a typical XAMPP setup.
- Daily fine rate is configured in `config/app.php` as `DAILY_FINE_RATE`.
- Run the ETL page after adding or returning books to refresh warehouse analytics.

