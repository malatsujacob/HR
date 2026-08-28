<?php
// index.php - Master Executive Control Center
session_start();
require_once 'config/db.php';

// 1. Kick out anyone who is not logged in
if (!isset($_SESSION['employee_id'])) {
    header('Location: login.php');
    exit;
}

// 2. Flexible role normalization and authorization check
$user_role = trim($_SESSION['role'] ?? '');
$allowed_roles = ['HR', 'Assistant HR', 'CEO', 'MD', 'hr', 'assistant hr', 'Human Resources'];

if (!in_array($user_role, $allowed_roles, true)) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Unauthorized - Executive Control Center</title>
        <style>
            body { background: #0f172a; color: #fff; font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
            .error-box { background: #ffffff; color: #1e293b; padding: 30px; border-radius: 10px; max-width: 400px; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.3); }
            h2 { color: #dc2626; margin-top: 0; font-size: 16px; text-transform: uppercase; }
            p { font-size: 12px; line-height: 1.5; color: #475569; }
            .btn { background: #2563eb; color: white; padding: 8px 16px; border-radius: 5px; text-decoration: none; font-weight: bold; font-size: 11px; display: inline-block; margin-top: 15px; }
        </style>
    </head>
    <body>
        <div class="error-box">
            <h2>Access Restricted</h2>
            <p>Your current account role (<strong><?php echo htmlspecialchars($user_role ?: 'None Assigned'); ?></strong>) does not have administrative access permissions.</p>
            <a href="logout.php" class="btn">Sign Out</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Aggregate global metrics safely
try {
    $totalEmployees = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
} catch (Exception $e) { $totalEmployees = 0; }

try {
    $activeExits = $pdo->query("SELECT COUNT(*) FROM exit_requests WHERE status != 'Finalized / Exited'")->fetchColumn();
} catch (Exception $e) { $activeExits = 0; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive Control Center - HRMS</title>
    <style>
        body { 
            background-color: #f0f9ff; 
            color: #1e293b; 
            margin: 0; 
            font-family: Arial, sans-serif; 
        }

        /* Main container layout accommodating the sidebar on the left */
        .container { 
            margin: 20px auto 40px auto;
            margin-left: 280px; /* Leaves room for your sidebar */
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
        header h1 { 
            font-size: 16px; 
            font-weight: 800; 
            margin: 0; 
            color: #0369a1; 
            text-transform: uppercase; 
        }
        .brand-title { color: #0284c7; font-weight: 900; }
        
        .dashboard-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 12px; 
            margin-bottom: 20px; 
        }
        .metric-card { 
            background-color: #f8fafc; 
            border: 1px solid #bae6fd; 
            border-radius: 6px; 
            padding: 14px; 
        }
        .metric-card h3 { 
            margin: 0 0 6px 0; 
            font-size: 10px; 
            color: #0369a1; 
            font-weight: 800; 
            text-transform: uppercase; 
        }
        .metric-card .value { 
            font-size: 20px; 
            font-weight: 800; 
            color: #0284c7; 
        }

        .card { 
            background: #ffffff; 
            padding: 18px; 
            border-radius: 6px; 
            border: 1px solid #bae6fd; 
            margin-bottom: 20px; 
        }
        .card h2 { 
            font-size: 13px; 
            margin: 0 0 12px 0; 
            color: #0369a1; 
            text-transform: uppercase; 
            font-weight: 800; 
            border-left: 3px solid #0284c7; 
            padding-left: 8px; 
        }

        .module-list { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); 
            gap: 10px; 
        }
        .module-btn {
            background-color: #f0f9ff;
            border: 1px solid #bae6fd;
            padding: 10px 14px;
            text-align: left;
            border-radius: 6px;
            text-decoration: none;
            color: #0369a1;
            font-weight: bold;
            font-size: 12px;
            display: block;
            transition: background 0.2s, border-color 0.2s;
        }
        .module-btn:hover { 
            background-color: #e0f2fe; 
            border-color: #7dd3fc; 
        }
        .module-sublist { 
            margin-top: 6px; 
            display: flex; 
            flex-direction: column; 
            gap: 6px; 
            grid-column: 1 / -1;
            padding-left: 15px;
            border-left: 2px solid #e2e8f0;
        }
        .module-btn.sub { 
            background-color: #ffffff; 
            border-color: #e2e8f0; 
            color: #475569; 
            font-size: 11px; 
        }
    </style>
</head>
<body>
    <?php 
    // Safely include your global sidebar navigation
    $sidebar_file = $_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php';
    if (file_exists($sidebar_file)) {
        include($sidebar_file);
    }
    ?>

    <div class="container">
        <header>
            <div>
                <h1><span class="brand-title">CHAP CHAP</span> <span style="color: #0369a1;">AFRICA</span></h1>
                <div style="font-size: 10px; font-weight: bold; color: #64748b; text-transform: uppercase; margin-top: 2px;">Executive Control Center</div>
            </div>
            <div>
                <span style="font-size: 11px; color: #334155; font-weight: bold; margin-right: 12px;">Welcome, <?php echo htmlspecialchars($_SESSION['first_name'] ?? 'Admin'); ?></span>
                <a href="logout.php" style="font-size: 11px; font-weight: bold; background: #fee2e2; color: #991b1b; padding: 6px 12px; border-radius: 4px; text-decoration: none; border: 1px solid #f87171;">Sign Out</a>
            </div>
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
                <div class="value" style="color: #16a34a; font-size: 18px;">Operational</div>
            </div>
        </div>

        <div class="card">
            <h2>System Navigation Flow</h2>
            <div class="module-list">
                <a href="module2_recruitment/index.php" class="module-btn">1. Recruitment Management</a>
                <a href="module4_contracts/index.php" class="module-btn">2. Contracts Management</a>
                <a href="module3_onboarding/index.php" class="module-btn">3. Onboarding Management</a>
                <a href="module5_attendance/index.php" class="module-btn">4. Attendance & Shifts</a>
                <a href="module_1_employees/index.php" class="module-btn">5. Employee Directory & Records</a>
                <a href="module6_leave/index.php" class="module-btn">6. Leave Management</a>
                <a href="module7_payroll/index.php" class="module-btn">7. Payroll (UGX Disbursement)</a>
                <a href="module8_performance/index.php" class="module-btn">8. Performance Reviews</a>
                
                <div style="grid-column: 1 / -1; background: #f8fafc; padding: 10px; border-radius: 6px; border: 1px solid #bae6fd;">
                    <a href="module9_training/index.php" class="module-btn" style="background: #ffffff; margin-bottom: 6px;">9. Training Management</a>
                    <div class="module-sublist">
                        <a href="module9_training/training.php" class="module-btn sub">9.1 Training Schedule</a>
                        <a href="module9_training/manage_training.php" class="module-btn sub">9.2 Training Setup</a>
                    </div>
                </div>

                <a href="module10_ess/index.php" class="module-btn">10. Employee Self-Service (ESS)</a>
                <a href="module11_analytics/index.php" class="module-btn">11. HR Analytics & Reporting</a>
                <a href="module12_disciplinary/index.php" class="module-btn">12. Disciplinary Actions</a>
                <a href="module13_offboarding/index.php" class="module-btn">13. Offboarding & Management</a>
            </div>
        </div>
    </div>
</body>
</html>