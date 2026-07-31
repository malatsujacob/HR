<?php
// index.php - Master Executive Control Center
require_once 'config/db.php';

// Aggregate global metrics safely
try {
    $totalEmployees = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
} catch (Exception $e) {
    $totalEmployees = 0;
}

try {
    $activeExits = $pdo->query("SELECT COUNT(*) FROM exit_requests WHERE status != 'Finalized / Exited'")->fetchColumn();
} catch (Exception $e) {
    $activeExits = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Sections - HRMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .metric-card {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 6px;
            padding: 15px;
            box-shadow: 0 1px 3px rgba(37, 99, 235, 0.1);
        }
        .metric-card h3 {
            margin: 0 0 8px 0;
            font-size: 11px;
            color: #2563eb;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .metric-card .value {
            font-size: 20px;
            font-weight: 900;
            color: #1e293b;
        }
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
        .module-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 10px;
        }
        .module-btn {
            background-color: #ffffff;
            border: 1px solid #93c5fd;
            padding: 10px 14px;
            text-align: left;
            border-radius: 4px;
            text-decoration: none;
            color: #2563eb;
            font-weight: 900;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
            transition: background 0.2s;
        }
        .module-btn:hover {
            background-color: #dbeafe;
        }
    </style>
</head>
<body>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>

    <div class="container">
        <header>
            <h1><span class="brand-title">CHAP CHAP</span> <span class="brand-title" style="color: #3b82f6;">AFRICA</span> - Executive Control Center</h1>
            <div style="font-size: 11px; font-weight: 900; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Central Navigation</div>
        </header>

        <div class="dashboard-grid">
            <div class="metric-card">
                <h3>Total Employees Registered</h3>
                <div class="value"><?php echo $totalEmployees; ?></div>
            </div>
            <div class="metric-card">
                <h3>Active Offboarding Files</h3>
                <div class="value" style="color: #ea580c;"><?php echo $activeExits; ?></div>
            </div>
            <div class="metric-card">
                <h3>System Status</h3>
                <div class="value" style="color: #16a34a; font-size: 16px; padding-top: 4px;">Operational</div>
            </div>
        </div>

        <div class="card">
            <h2>All Sections (System Flow Order)</h2>
            <div class="module-list">
                <a href="module2_recruitment/index.php" class="module-btn">1. Recruitment Management</a>
                <a href="module4_contracts/index.php" class="module-btn">2. Contracts Management</a>
                <a href="module3_onboarding/index.php" class="module-btn">3. Onboarding Management</a>
                <a href="module5_attendance/index.php" class="module-btn">4. Attendance & Shifts</a>
                <a href="module_1_employees/index.php" class="module-btn">5. Employee Directory & Records</a>
                <a href="module6_leave/index.php" class="module-btn">6. Leave Management</a>
                <a href="module7_payroll/index.php" class="module-btn">7. Payroll (Ugandan Shilling Disbursement)</a>
                <a href="module8_performance/index.php" class="module-btn">8. Performance Reviews</a>
                <a href="module9_training/index.php" class="module-btn">9. Training & Skill Development</a>
                <a href="module10_ess/index.php" class="module-btn">10. Employee Self-Service (ESS)</a>
                <a href="module11_analytics/index.php" class="module-btn">11. HR Analytics & Reporting</a>
                <a href="module12_disciplinary/index.php" class="module-btn">12. Disciplinary Actions</a>
                <a href="module13_offboarding/index.php" class="module-btn">13. Offboarding & Exit Management</a>
            </div>
        </div>
    </div>
    <script src="assets/js/dashboard-nav.js"></script>
</body>
</html>