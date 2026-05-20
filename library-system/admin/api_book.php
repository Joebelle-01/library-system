<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();
header('Content-Type: application/json');

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT id, title, available_copies FROM books WHERE id = ?');
$stmt->execute([$id]);
echo json_encode($stmt->fetch() ?: ['available_copies' => 0]);

