<?php
require_once '../config/db.php';

// Determine the employee identifier from either URL or POST
$employee_id = $_GET['delete_id'] ?? $_POST['delete_id'] ?? $_GET['id'] ?? $_POST['id'] ?? null;

if ($employee_id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM employees WHERE employee_id = :id");
        $stmt->execute(['id' => $employee_id]);

        header("Location: list.php");
        exit();
    } catch (PDOException $e) {
        die("Error deleting record: " . $e->getMessage());
    }
}

header("Location: list.php");
exit();
?>