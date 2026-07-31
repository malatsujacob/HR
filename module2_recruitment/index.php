<?php
// module2_recruitment/index.php - Recruitment Hub
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recruitment Management | Chap Chap Africa HRMS</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f9ff;
            margin: 0;
            padding: 0;
        }

        .main-content {
            margin-left: 240px;
            padding: 30px;
            box-sizing: border-box;
            min-height: 100vh;
        }

        h1 {
            font-size: 22px;
            color: #0f172a;
            margin-bottom: 5px;
        }

        p.subtitle {
            font-size: 13px;
            color: #334155;
            margin-bottom: 25px;
        }

        /* Horizontal row arrangement */
        .cards-container {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        /* Minimized horizontal row button styling */
        .module-card {
            background-color: #ffffff;
            border: 1px solid #bae6fd;
            border-radius: 4px;
            padding: 8px 12px;
            text-decoration: none;
            display: flex;
            align-items: center;
            max-width: 240px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
            transition: background 0.2s, border-color 0.2s;
        }

        .module-card:hover {
            background-color: #e0f2fe;
            border-color: #0284c7;
        }

        .module-card h3 {
            margin: 0;
            font-size: 12px;
            color: #0284c7;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>

    <div class="main-content">
        <h1>Recruitment Management</h1>
        <p class="subtitle">Manage the recruitment pipeline.</p>

        <div class="cards-container">
            <a href="index.php" class="module-card">
                <h3>Dashboard</h3>
            </a>
            <a href="requisitions.php" class="module-card">
                <h3>Requisitions</h3>
            </a>
            <a href="candidates.php" class="module-card">
                <h3>Candidates</h3>
            </a>
            <a href="interviews.php" class="module-card">
                <h3>Interviews</h3>
            </a>
            <a href="offers.php" class="module-card">
                <h3>Offers</h3>
            </a>
            <a href="emails.php" class="module-card">
                <h3>Emails</h3>
            </a>
        </div>
    </div>
</body>
</html>