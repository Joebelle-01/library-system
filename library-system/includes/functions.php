<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . APP_URL . $path);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function consume_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Invalid CSRF token.');
    }
}

function require_login(): void
{
    if (empty($_SESSION['user'])) {
        redirect('/index.php');
    }
}

function require_role(array $roles): void
{
    require_login();
    if (!in_array($_SESSION['user']['role'], $roles, true)) {
        http_response_code(403);
        exit('Access denied.');
    }
}

function current_user(): array
{
    return $_SESSION['user'] ?? [];
}

function money(float|string|null $amount): string
{
    return '₱' . number_format((float) $amount, 2);
}

function query_value(PDO $pdo, string $sql, array $params = []): mixed
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

function update_overdue_records(PDO $pdo): void
{
    $pdo->exec("UPDATE borrow_records SET status = 'overdue' WHERE return_date IS NULL AND due_date < CURDATE()");
}

function fine_for(DateTimeInterface $dueDate, ?DateTimeInterface $returnDate = null, float $rate = DAILY_FINE_RATE): array
{
    $end = $returnDate ?: new DateTimeImmutable('today');
    $days = max(0, (int) $dueDate->diff($end)->format('%r%a'));
    return ['days' => $days, 'amount' => $days * $rate];
}

function require_student_login(): void
{
    if (empty($_SESSION['student'])) {
        header('Location: ' . APP_URL . '/index.php');
        exit;
    }
}

function current_student(): array
{
    return $_SESSION['student'] ?? [];
}


