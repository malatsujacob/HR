<?php
// module9_training/process_training.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userRole = strtolower(trim($_SESSION['user_role'] ?? ''));
$isHRAdmin = in_array($userRole, ['admin', 'hr'], true);

if (!$isHRAdmin) {
    header('Location: training.php');
    exit;
}

header('Location: manage_training.php');
exit;