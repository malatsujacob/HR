<?php
// module7_payroll/index.php - Payroll central module entry
require_once '../config/db.php';

// Dynamically fetch the latest valid run_id and payslip_id from PostgreSQL
try {
    $latest_run = $pdo->query("SELECT run_id FROM payroll_runs ORDER BY run_id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $run_id = $latest_run['run_id'] ?? 0;

    $latest_slip = $pdo->query("SELECT payslip_id FROM employee_payslips ORDER BY payslip_id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $payslip_id = $latest_slip['payslip_id'] ?? 0;
} catch (Exception $e) {
    $run_id = 0;
    $payslip_id = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll & Disbursement - HRMS</title>
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
            max-width: 400px; 
        }
        .links-grid { 
            display: flex; 
            flex-direction: column; 
            gap: 10px; 
            margin-top: 10px; 
            align-items: flex-start; 
        }
        .link-card { 
            background: #ffffff; 
            border: 1px solid #93c5fd;
            padding: 8px 14px; 
            border-radius: 4px; 
            display: inline-block; 
            width: 100%;
            box-sizing: border-box;
            transition: background 0.2s;
        }
        .link-card a { 
            color: #2563eb; 
            text-decoration: none; 
            font-weight: 900; 
            font-size: 11px; 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
            display: block; 
        }
        .link-card:hover { 
            background: #dbeafe; 
        }
    </style>
</head>
<body>

<?php 
include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php');
?>

<div class="container">
    <header>
        <h1><span class="brand-title">CHAP CHAP</span> <span class="brand-title" style="color: #3b82f6;">AFRICA</span> - Payroll & Disbursement</h1>
    </header>

    <div class="card">
        <div class="links-grid">
            <div class="link-card">
                <a href="dashboard.php">Payroll Dashboard</a>
            </div>
            <div class="link-card">
                <a href="payroll.php">Payroll</a>
            </div>
            <div class="link-card">
                <a href="disbursement_engine.php?run_id=<?php echo $run_id; ?>">Disbursement Engine</a>
            </div>
            <div class="link-card">
                <a href="export_bank.php?run_id=<?php echo $run_id; ?>">Export Bank</a>
            </div>
            <div class="link-card">
                <a href="payslip.php?payslip_id=<?php echo $payslip_id; ?>">Payslips</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>