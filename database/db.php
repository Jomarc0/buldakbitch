<?php
declare(strict_types=1);

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'Buldaks');
define('DB_USER', 'root');
define('DB_PASS', '');

$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo "Database connection failed.";
    exit;
}
?>
