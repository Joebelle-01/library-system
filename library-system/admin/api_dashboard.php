<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();
header('Content-Type: application/json');

$trend = $pdo->query("
    SELECT DATE_FORMAT(borrow_date, '%Y-%m') AS label, COUNT(*) AS total
    FROM borrow_records
    GROUP BY DATE_FORMAT(borrow_date, '%Y-%m')
    ORDER BY label
    LIMIT 12
")->fetchAll();

$topBooks = $pdo->query('SELECT title AS label, borrow_count AS total FROM vw_most_borrowed_books ORDER BY borrow_count DESC LIMIT 5')->fetchAll();

echo json_encode(['trend' => $trend, 'topBooks' => $topBooks]);

