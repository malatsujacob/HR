<?php
// module6_leave/index.php - Leave central module entry
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Management | Chap Chap Africa HRMS</title>
    <!-- Link to the shared global stylesheet -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background-color: #f8fafc; color: #0f172a; margin: 0; font-family: Arial, sans-serif; }
        .container { margin-left: 260px; max-width: calc(100% - 280px); padding: 25px; box-sizing: border-box; background: #f8fafc; min-height: 100vh; }
        header { border-bottom: 2px solid #cbd5e1; padding-bottom: 15px; margin-bottom: 25px; }
        header h1 { font-size: 22px; font-weight: 900; margin: 0; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; }
        .links-list { display: flex; flex-direction: row; gap: 15px; }
        .link-card { background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); width: fit-content; }
        .link-card a { display: block; color: #0284c7; text-decoration: none; font-weight: 900; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
        .link-card a:hover { text-decoration: underline; color: #0369a1; }
    </style>
</head>
<body>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>
    <div class="container">
        <header>
            <h1>Leave Management</h1>
        </header>
        <div class="links-list">
            <div class="link-card">
                <a href="dashboard.php">Leave Dashboard</a>
            </div>
            <div class="link-card">
                <a href="holidays.php">Holidays</a>
            </div>
            <div class="link-card">
                <a href="leave.php">Leave Requests</a>
            </div>
        </div>
    </div>
</body>
</html>