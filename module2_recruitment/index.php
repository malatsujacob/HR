<?php
//HR/ module2_recruitment/index.php - Recruitment Hub
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recruitment Management | Chap Chap Africa HRMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/module2_recruitment.css">
    <style>
        /* Strict Uniform & Compact Grid Layout */
        .links-list {
            display: grid;
            grid-template-columns: repeat(3, 1fr); /* Exactly 3 uniform columns across */
            gap: 12px;
            margin-top: 20px;
            max-width: 900px;
        }

        .link-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 0 15px;
            text-align: center;
            text-decoration: none;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
            transition: all 0.2s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 55px; /* Compact and strictly fixed height for all boxes */
            width: 100%;  /* Fills the exact grid cell width uniformly */
            box-sizing: border-box;
        }

        .link-card:hover {
            transform: translateY(-1px);
            border-color: #7c3aed;
            box-shadow: 0 3px 5px -1px rgba(124, 58, 237, 0.1);
        }

        .link-card h3 {
            margin: 0;
            font-size: 14px;
            color: #1e293b;
            font-weight: 600;
            white-space: nowrap; /* Prevents awkward text wrapping */
        }
    </style>
</head>
<body>
    <?php include(__DIR__ . '/../includes/sidebar.php'); ?>

    <div class="m2-container">
        <header class="m2-header">
            <h1 class="m2-page-title">Recruitment Management</h1>
        </header>

        <div class="links-list">
            <a href="index.php" class="link-card">
                <h3>Dashboard</h3>
            </a>
            <a href="requisitions.php" class="link-card">
                <h3>Requisitions</h3>
            </a>
            <a href="candidates.php" class="link-card">
                <h3>Candidates</h3>
            </a>
            <a href="interviews.php" class="link-card">
                <h3>Interviews</h3>
            </a>
            <a href="offers.php" class="link-card">
                <h3>Offers</h3>
            </a>
            <a href="emails.php" class="link-card">
                <h3>Emails</h3>
            </a>
        </div>
    </div>
</body>
</html>