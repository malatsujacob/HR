<?php
// config/db.php - Database Connection Link
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'HR');
define('DB_USER', 'postgres');
define('DB_PASS', 'Jack.02.+ma'); 

define('APP_ROOT', realpath(__DIR__ . '/../'));

try {
    $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>