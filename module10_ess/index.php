<?php
// module10_ess/index.php - ESS central module entry
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Self-Service - HRMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { 
            background-color: #ffffff; 
            color: #1e293b; 
            margin: 0; 
            font-family: Arial, sans-serif; 
        }
        .container { 
            margin-left: 260px; 
            max-width: calc(100% - 260px); 
            padding: 20px; 
            box-sizing: border-box; 
            background: #ffffff; 
            min-height: 100vh; 
        }
        header { 
            border-bottom: 2px solid #e2e8f0; 
            padding-bottom: 12px; 
            margin-bottom: 20px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        header h1 { 
            font-size: 18px; 
            font-weight: 900; 
            margin: 0; 
            color: #1e293b; 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
        }
        .brand-title { color: #2563eb; font-weight: 900; }
        .brand-title span { color: #3b82f6; }
        .card { 
            background: #eff6ff; 
            padding: 20px; 
            border-radius: 6px; 
            border: 1px solid #bfdbfe; 
            box-shadow: 0 1px 3px rgba(37, 99, 235, 0.1); 
            margin-bottom: 20px; 
        }
        .card h2 {
            font-size: 14px;
            margin-top: 0;
            color: #1e293b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 900;
            border-left: 3px solid #2563eb;
            padding-left: 8px;
        }
        .links-grid { 
            display: grid; 
            gap: 10px; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            margin-top: 10px; 
        }
        .link-card { 
            background: #ffffff; 
            border: 1px solid #93c5fd; 
            border-radius: 4px; 
            padding: 12px; 
        }
        .link-card a { 
            display: block; 
            color: #2563eb; 
            text-decoration: none; 
            font-weight: 900; 
            font-size: 11px; 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
        }
        .link-card a:hover { 
            text-decoration: underline; 
        }
    </style>
</head>
<body>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>
    
    <div class="container">
        <header>
            <h1><span class="brand-title">CHAP CHAP</span> <span class="brand-title" style="color: #3b82f6;">AFRICA</span> - Employee Self-Service</h1>
            <div style="font-size: 11px; font-weight: 900; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">ESS Portal</div>
        </header>

        <div class="card">
            <h2>ESS Navigation</h2>
            <div class="links-grid">
                <div class="link-card"><a href="dashboard.php">Dashboard</a></div>
                <div class="link-card"><a href="ess.php">Portal</a></div>
                <div class="link-card"><a href="attendance.php">Attendance</a></div>
                <div class="link-card"><a href="payslips.php">Payslips</a></div>
                <div class="link-card"><a href="performance.php">Performance</a></div>
                <div class="link-card"><a href="training_onboarding.php">Training & Onboarding</a></div>
            </div>
        </div>
    </div>
</body>
</html>