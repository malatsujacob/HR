<?php
// module4_contracts/index.php - Contracts central module entry
session_start();

// Ensure session is active
if (!isset($_SESSION['employee_id'])) {
    header('Location: ../login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contracts Management - HRMS</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f9ff;
            margin: 0;
            padding: 0;
        }
        .container {
            margin: 20px auto 40px auto;
            margin-left: 280px;
            max-width: calc(100% - 320px);
            padding: 24px;
            box-sizing: border-box;
            background: #ffffff;
            min-height: calc(100vh - 60px);
            border-radius: 10px;
            border: 1px solid #bae6fd;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.05);
        }
        header {
            border-bottom: 2px solid #e0f2fe;
            padding-bottom: 12px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .page-title {
            margin: 0;
            color: #0369a1;
            font-size: 18px;
            font-weight: 800;
            text-transform: uppercase;
            text-align: center;
            width: 100%;
        }
        .section-title {
            text-align: center;
            font-size: 14px;
            font-weight: 800;
            color: #0369a1;
            margin-top: 5px;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .links-grid {
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-width: 450px;
            margin: 0 auto;
        }
        .link-card {
            background: #ffffff;
            border: 1px solid #bae6fd;
            border-radius: 6px;
            padding: 10px 14px;
            box-shadow: 0 2px 6px rgba(2, 132, 199, 0.03);
        }
        .link-card a {
            display: inline-block;
            color: #0284c7;
            text-decoration: none;
            font-weight: bold;
            font-size: 12px;
        }
        .link-card a:hover {
            color: #0369a1;
            text-decoration: underline;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 15px;
            color: #64748b;
            text-decoration: none;
            font-size: 11px;
            font-weight: bold;
        }
        .back-link:hover { color: #0284c7; }
    </style>
</head>
<body>
    <?php 
    // Safely load sidebar without failing hard or looping
    $sidebar_file = $_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php';
    if (file_exists($sidebar_file)) {
        include($sidebar_file);
    }
    ?>
    
    <div class="container">
        <a href="../index.php" class="back-link">&larr; Back to Control Center</a>
        <header>
            <h1 class="page-title">Contracts Management</h1>
        </header>

        <h2 class="section-title">Navigation Hub</h2>
        
        <div class="links-grid">
            <div class="link-card">
                <a href="dashboard.php">Contracts Dashboard</a>
            </div>
            <div class="link-card">
                <a href="contracts.php">Contracts</a>
            </div>
        </div>
    </div>
</body>
</html>