<?php
declare(strict_types=1);

ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Manila');

define('APP_NAME', 'Advanced Library Management System');
define('APP_URL', '/library-system');
define('DAILY_FINE_RATE', 10.00);

