<?php
require_once '../config/db.php';
require_once 'ess_model.php';

$essModel = new ESSModel($pdo);
session_start();

// Fetch metrics for analytics
$total_logins_stmt = $pdo->query("SELECT COUNT(*) as cnt FROM ess_login_logs");
$total_logins = $total_logins_stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0;

$top_actions_stmt = $pdo->query("SELECT action_name, COUNT(*) as action_count FROM ess_action_logs GROUP BY action_name ORDER BY action_count DESC");
$top_actions = $top_actions_stmt->fetchAll(PDO::FETCH_ASSOC);

$dept_adoption_stmt = $pdo->query("
    SELECT department, 
           COUNT(DISTINCT e.employee_id) as total_dept_emps,
           SUM(CASE WHEN l.employee_id IS NOT NULL THEN 1 ELSE 0 END) as active_logged_emps
    FROM employees e 
    LEFT JOIN (SELECT DISTINCT employee_id FROM ess_login_logs) l ON e.employee_id = l.employee_id
    GROUP BY department
");
$dept_adoptions = $dept_adoption_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Admin Analytics - ESS HRMS</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #ffffff; color: #000000; margin: 0; padding: 0; }
        .container { margin-left: 260px !important; max-width: calc(100% - 280px) !important; padding: 25px; box-sizing: border-box; background: #ffffff; min-height: 100vh; }
        header { border-bottom: 2px solid #b3d1ff; padding-bottom: 15px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 30px; background: #ffffff; border: 1px solid #b3d1ff; border-radius: 4px; overflow: hidden; }
        th, td { padding: 12px 14px; text-align: left; border-bottom: 1px solid #b3d1ff; font-size: 13px; vertical-align: middle; }
        th { background: linear-gradient(180deg, #e6f2ff 0%, #cce0ff 100%); color: #0f172a; font-weight: bold; }
        tr:hover { background-color: #f8fafc; }

        .metric-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .metric-card { background: #f8fafc; border: 1px solid #b3d1ff; border-radius: 6px; padding: 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .metric-title { font-size: 11px; font-weight: bold; text-transform: uppercase; color: #64748b; margin-bottom: 8px; }
        .metric-value { font-size: 20px; font-weight: bold; color: #0066cc; }

        .section-title { font-size: 15px; margin-top: 25px; margin-bottom: 12px; color: #0066cc; font-weight: bold; border-left: 4px solid #ff6600; padding-left: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .nav-btn { background: #e6f2ff; color: #0066cc; padding: 8px 14px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: bold; border: 1px solid #b3d1ff; }
        .nav-btn:hover { background: #0066cc; color: #ffffff; }
    </style>
</head>
<body>

<?php 
include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php');
?>

<div class="container">
    <header>
        <div>
            <h1 style="margin: 0; font-size: 22px; color: #0f172a;">HR Admin Portal Adoption Analytics</h1>
            <small style="color: #64748b;">Engagement Tracking, Action Metrics & Department Heatmaps</small>
        </div>
        <div>
            <a href="dashboard.php" class="nav-btn">Back to Dashboard</a>
        </div>
    </header>

    <!-- Top Metrics Overview -->
    <div class="metric-grid">
        <div class="metric-card">
            <div class="metric-title">Total ESS Portal Logins</div>
            <div class="metric-value"><?php echo number_format($total_logins); ?></div>
        </div>
        <div class="metric-card">
            <div class="metric-title">Active Actions Recorded</div>
            <div class="metric-value" style="color: #047857;">
                <?php 
                $sum_actions = array_sum(array_column($top_actions, 'action_count'));
                echo number_format($sum_actions); 
                ?>
            </div>
        </div>
    </div>

    <!-- 1. TOP ESS ACTIONS TAKEN (VERTICAL BAR METRIC) -->
    <div class="section-title">Top ESS Actions Taken</div>
    <table>
        <thead>
            <tr>
                <th>Action Type</th>
                <th>Count of Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($top_actions) > 0): ?>
                <?php foreach ($top_actions as $ta): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($ta['action_name']); ?></strong></td>
                        <td><span style="color: #0066cc; font-weight: bold;"><?php echo number_format($ta['action_count']); ?></span></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="2" style="text-align: center; color: #64748b; padding: 20px;">No action logs recorded yet.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- 2. ADOPTION BY DEPARTMENT (HEATMAP TABLE) -->
    <div class="section-title">Self-Service Adoption by Department (Heatmap)</div>
    <table>
        <thead>
            <tr>
                <th>Department</th>
                <th>Total Employees</th>
                <th>Employees Logged In</th>
                <th>Adoption Rate (%)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($dept_adoptions) > 0): ?>
                <?php foreach ($dept_adoptions as $da): ?>
                    <?php 
                    $total = $da['total_dept_emps'] > 0 ? $da['total_dept_emps'] : 1;
                    $rate = round(($da['active_logged_emps'] / $total) * 100, 1);
                    $bg_color = $rate < 30 ? '#fef2f2' : ($rate < 70 ? '#fef3c7' : '#ecfdf5');
                    ?>
                    <tr style="background-color: <?php echo $bg_color; ?>;">
                        <td><strong><?php echo htmlspecialchars($da['department'] ?? 'General'); ?></strong></td>
                        <td><?php echo $da['total_dept_emps']; ?></td>
                        <td><?php echo $da['active_logged_emps']; ?></td>
                        <td><strong><?php echo $rate; ?>%</strong></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" style="text-align: center; color: #64748b; padding: 20px;">No departmental records available.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>