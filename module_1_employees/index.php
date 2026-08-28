<?php
// module_1_employees/index.php - Employee Directory central module entry
session_start();
require_once '../config/db.php';

// 1. Ensure user is logged in at the application level
if (!isset($_SESSION['employee_id']) && !isset($_SESSION['user_id']) && !isset($_SESSION['employees_hr_logged_in'])) {
    header("Location: ../index.php?error=unauthorized");
    exit();
}

$user_role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';

// 2. Strict HR & Assistant HR check + Developer Override shortcut
$is_strict_hr = in_array($user_role, ['HR', 'Assistant HR']);
$is_developer = (isset($_SESSION['is_developer']) && $_SESSION['is_developer'] === true) || ($user_role === 'Developer');

if ($is_strict_hr || $is_developer) {
    // HR and Developers stay in the Employee Directory
    $is_hr_logged = true;
} else {
    // Normal employees are automatically routed to the correct ESS portal file
    header("Location: ../module10_ess/ess.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Directory | Chap Chap Africa HRMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background-color: #f8fafc; color: #0f172a; margin: 0; font-family: Arial, sans-serif; }
        .container { margin-left: 260px; max-width: calc(100% - 280px); padding: 25px; box-sizing: border-box; background: #f8fafc; min-height: 100vh; }
        header { border-bottom: 2px solid #cbd5e1; padding-bottom: 15px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        header h1 { font-size: 22px; font-weight: 900; margin: 0; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; }
        .links-list { display: flex; flex-direction: column; gap: 8px; }
        .link-card { background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); width: fit-content; }
        .link-card a { display: block; color: #0284c7; text-decoration: none; font-weight: 900; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
        .link-card a:hover { text-decoration: underline; color: #0369a1; }
        .role-indicator { font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; }
    </style>
</head>
<body>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>
    <div class="container">
        <header>
            <h1>Employee Directory</h1>
            <div style="display: flex; gap: 15px; align-items: center;">
                <span class="role-indicator">Role: <?php echo htmlspecialchars($user_role ?: ($is_developer ? 'Developer Override' : 'HR')); ?></span>
            </div>
        </header>

        <div class="links-list">
            <div class="link-card">
                <a href="list.php">Employee List</a>
            </div>
            <div class="link-card">
                <a href="add.php">Add New Employee</a>
            </div>
            <div class="link-card">
                <a href="edit.php">Edit Employee</a>
            </div>
            <div class="link-card">
                <a href="view.php">Employee Details</a>
            </div>
            <div class="link-card">
                <a href="import_export.php">Import / Export</a>
            </div>
            <div class="link-card">
                <a href="upload_doc.php">Upload Documents</a>
            </div>
        </div>
    </div>
</body>
</html>