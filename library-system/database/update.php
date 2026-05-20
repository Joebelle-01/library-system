<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';

echo "<pre>";
echo "Starting Database Updates...\n";

try {
    // Check if password column exists in students
    $stmt = $pdo->query("SHOW COLUMNS FROM students LIKE 'password'");
    $columnExists = $stmt->fetch();

    if (!$columnExists) {
        $pdo->exec("ALTER TABLE students ADD COLUMN password VARCHAR(255) NULL AFTER email");
        echo "[SUCCESS] Added 'password' column to 'students' table.\n";
        
        // Update default passwords to 'student123'
        $defaultPasswordHash = password_hash('student123', PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE students SET password = ? WHERE password IS NULL")->execute([$defaultPasswordHash]);
        echo "[SUCCESS] Seeded existing students with default password ('student123').\n";
    } else {
        echo "[INFO] 'password' column already exists in 'students' table.\n";
    }

    // Check if book_reservations table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'book_reservations'");
    $tableExists = $stmt->fetch();

    if (!$tableExists) {
        $pdo->exec("
            CREATE TABLE book_reservations (
              id INT AUTO_INCREMENT PRIMARY KEY,
              student_id INT NOT NULL,
              book_id INT NOT NULL,
              reservation_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              status ENUM('pending','approved','cancelled','expired') NOT NULL DEFAULT 'pending',
              CONSTRAINT fk_res_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
              CONSTRAINT fk_res_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
            ) ENGINE=InnoDB;
        ");
        echo "[SUCCESS] Created 'book_reservations' table.\n";
    } else {
        echo "[INFO] 'book_reservations' table already exists.\n";
    }

    echo "\nDatabase migration completed successfully!\n";
} catch (Throwable $e) {
    echo "[ERROR] Database update failed: " . $e->getMessage() . "\n";
}
echo "</pre>";
