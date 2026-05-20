# Advanced Library Management System

A full-stack **PHP + MySQL** library management system with a dual-portal architecture — a powerful **Admin/Staff Panel** and a self-service **Student Portal**.

---

## 🗂️ Project Structure

```
library-system/
├── admin/                   # Admin & Librarian pages
│   ├── dashboard.php        # Overview stats + charts
│   ├── books.php            # Book CRUD
│   ├── categories.php       # Book category CRUD
│   ├── students.php         # Student record management
│   ├── borrow.php           # Borrow history log
│   ├── returns.php          # Process book returns + fine calculation
│   ├── reservations_admin.php  # Approve/reject student reservations
│   ├── fines.php            # Fine tracking and payment
│   ├── users.php            # Staff account management (admin only)
│   └── api_dashboard.php    # JSON endpoint for dashboard charts
├── student/                 # Student Portal
│   ├── dashboard.php        # Student home: active borrows + return alerts
│   ├── catalog.php          # Browse & reserve books
│   ├── reservations.php     # Track reservation status
│   ├── history.php          # Full borrowing history with filters
│   ├── receipt.php          # Printable return receipt
│   ├── profile.php          # Update profile & password
│   └── includes/            # Student-specific header & sidebar
├── includes/                # Shared layout components
│   ├── header.php
│   ├── footer.php
│   ├── sidebar.php
│   └── functions.php        # Core helpers, auth, CSRF, fine calculator
├── config/
│   ├── app.php              # App constants (APP_URL, DAILY_FINE_RATE)
│   └── database.php         # PDO connection
├── assets/
│   ├── css/style.css        # Custom design system
│   └── js/app.js            # DataTables, SweetAlert2, Chart.js wiring
├── database/
│   ├── library_system.sql   # Full schema (tables + seed data)
│   └── full_setup.sql       # Master setup script
├── sql/
│   ├── advanced_views.sql   # MySQL views (vw_borrow_details, etc.)
│   └── sample_data.sql      # Optional demo book data
├── reports/
│   └── index.php            # Printable borrow/return/fine reports
├── index.php                # Unified login (Admin + Student auto-detect)
├── register.php             # Student self-registration
├── logout.php               # Session destroy & redirect
└── student_login.php        # Legacy redirect → index.php
```

---

## 🚀 Quick Setup

### 1. Requirements
- XAMPP (PHP 8.1+, MySQL 8.0+)
- Web browser

### 2. Install
```bash
# Place the project folder inside htdocs
# Then import the database:
mysql -u root -p < database/full_setup.sql
```

### 3. Configure
Edit `config/app.php` if your server path differs:
```php
define('APP_URL', '/library-system');  // adjust to match your server path
define('DAILY_FINE_RATE', 10.00);      // fine per overdue day (₱)
```

### 4. Open in Browser
```
http://localhost/library-system/
```

---

## 👤 Default Login Credentials

| Role | Username / Student No. | Password |
|------|------------------------|----------|
| Admin | admin@library.edu | admin123 |
| Librarian | librarian@library.edu | lib123 |
| Student | 2024301001 | student123 |

---

## ✨ Features

### Admin / Staff Panel
- 📊 Dashboard with live stats (books, students, overdue, fines) and borrowing trend charts
- 📚 Book & Category CRUD with availability tracking
- 🎓 Student record management with borrow count
- ✅ Reservation approval — converts to borrow record in a single transaction
- 🔄 Return processing with automatic fine calculation
- 💰 Fine tracking (paid / unpaid / waived)
- 🖨️ Printable reports (by student, date, status)
- 👥 Staff account management (Admin only)

### Student Portal
- 🔐 Self-registration & secure login (bcrypt passwords)
- 🏠 Personal dashboard with active checkouts, countdown to due dates, and return confirmation banners
- 🕐 Color-coded due date badges (Green / Amber / Red)
- 📖 Searchable book catalog with live reservation
- 📋 Complete borrow history with On Time / Late Returns / Fined filters
- 🧾 Printable return receipts per transaction
- ⚙️ Profile & password settings

---

## 🛡️ Security
- Bcrypt password hashing (`PASSWORD_DEFAULT`)
- CSRF token on all POST forms
- Session isolation: `$_SESSION['user']` (staff) vs `$_SESSION['student']`
- PDO prepared statements throughout — no raw SQL interpolation
- `SELECT ... FOR UPDATE` with `START TRANSACTION` / `COMMIT` / `ROLLBACK` for race-condition-safe borrow and return operations

---

## 🗄️ Key Database Objects

| Object | Type | Purpose |
|--------|------|---------|
| `vw_borrow_details` | VIEW | Joins borrow records with student, book, category, and fine data |
| `vw_most_borrowed_books` | VIEW | RANK() window function for top-borrowed books chart |
| `vw_overdue_students` | VIEW | Overdue records with days and estimated fine |
| `vw_student_borrow_stats` | VIEW | Per-student borrow summary with subquery aggregation |
