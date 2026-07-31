<?php
require_once '../config/db.php';

try {
    $passed = $pdo->query("SELECT COUNT(*) FROM employee_contracts WHERE probation_status = 'Passed'")->fetchColumn();
    $extended = $pdo->query("SELECT COUNT(*) FROM employee_contracts WHERE probation_status = 'Extended'")->fetchColumn();
    $failed = $pdo->query("SELECT COUNT(*) FROM employee_contracts WHERE probation_status = 'Failed'")->fetchColumn();
    $pending = $pdo->query("SELECT COUNT(*) FROM employee_contracts WHERE probation_status = 'Pending Review'")->fetchColumn();

    $monthly_expiring = $pdo->query("
        SELECT TO_CHAR(contract_end_date, 'Month') as expiry_month, 
               EXTRACT(MONTH FROM contract_end_date) as month_num,
               COUNT(*) as total 
        FROM employee_contracts 
        WHERE contract_end_date >= DATE_TRUNC('quarter', CURRENT_DATE) 
          AND contract_end_date < (DATE_TRUNC('quarter', CURRENT_DATE) + INTERVAL '3 month')
        GROUP BY expiry_month, month_num
        ORDER BY month_num ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $dept_stats = $pdo->query("
        SELECT e.department, 
               SUM(CASE WHEN c.probation_status = 'Passed' THEN 1 ELSE 0 END) as passed_count,
               COUNT(c.contract_id) as total_count
        FROM employee_contracts c
        JOIN employees e ON c.employee_id = e.employee_id
        GROUP BY e.department
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $passed = 0; $extended = 0; $failed = 0; $pending = 0; $monthly_expiring = []; $dept_stats = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contracts & Probation Dashboard - HRMS</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #ffffff; color: #000000; margin: 0; padding: 0; }
        .container { margin-left: 260px !important; max-width: calc(100% - 280px) !important; padding: 20px; box-sizing: border-box; background: #ffffff; min-height: 100vh; }
        header { border-bottom: 2px solid #b3d1ff; padding-bottom: 15px; margin-bottom: 20px; }
        .card-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .card { background: #f0f7ff; padding: 20px; border-radius: 6px; border: 1px solid #b3d1ff; text-align: center; }
        .card h3 { margin: 0 0 10px 0; font-size: 14px; color: #000000; }
        .card p { margin: 0; font-size: 24px; font-weight: bold; color: #3399ff; }
        .chart-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .chart-box { background: #f0f7ff; padding: 20px; border-radius: 6px; border: 1px solid #b3d1ff; margin-bottom: 20px; }
    </style>
</head>
<body>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>

<div class="container">
    <header>
        <h1 style="margin: 0; color: #000000; font-size: 22px;">Contracts & Probation Analytics Dashboard</h1>
    </header>

    <div class="card-grid">
        <div class="card">
            <h3>Pending Probation Reviews</h3>
            <p style="color: #cc6600;"><?php echo $pending; ?></p>
        </div>
        <div class="card">
            <h3>Passed Probation</h3>
            <p style="color: #0066cc;"><?php echo $passed; ?></p>
        </div>
    </div>

    <div class="chart-grid">
        <div class="chart-box">
            <h3 style="margin-top: 0; color: #000000; font-size: 15px;">Probation Status Summary</h3>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li style="padding: 8px 0; border-bottom: 1px solid #b3d1ff; font-size: 14px;">Passed: <span style="float: right; font-weight: bold; color: #0066cc;"><?php echo $passed; ?></span></li>
                <li style="padding: 8px 0; border-bottom: 1px solid #b3d1ff; font-size: 14px;">Extended: <span style="float: right; font-weight: bold; color: #cc6600;"><?php echo $extended; ?></span></li>
                <li style="padding: 8px 0; border-bottom: 1px solid #b3d1ff; font-size: 14px;">Failed: <span style="float: right; font-weight: bold; color: #cc0000;"><?php echo $failed; ?></span></li>
                <li style="padding: 8px 0; font-size: 14px;">Pending Review: <span style="float: right; font-weight: bold; color: #555555;"><?php echo $pending; ?></span></li>
            </ul>
        </div>

        <div class="chart-box">
            <h3 style="margin-top: 0; color: #000000; font-size: 15px;">Contracts Expiring This Quarter (By Month)</h3>
            <?php if (count($monthly_expiring) > 0): ?>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <?php foreach ($monthly_expiring as $m): ?>
                        <li style="padding: 8px 0; border-bottom: 1px solid #b3d1ff; font-size: 14px;">
                            <strong><?php echo trim($m['expiry_month']); ?>:</strong> 
                            <span style="float: right; font-weight: bold; color: #3399ff;"><?php echo $m['total']; ?> Contracts</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p style="color: #555; font-size: 13px; margin: 0;">No contracts expiring this quarter.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="chart-box">
        <h3 style="margin-top: 0; color: #000000; font-size: 15px;">Probation Success Rate by Department</h3>
        <?php if (count($dept_stats) > 0): ?>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <?php foreach ($dept_stats as $stat): 
                    $rate = $stat['total_count'] > 0 ? round(($stat['passed_count'] / $stat['total_count']) * 100, 1) : 0;
                ?>
                    <li style="padding: 8px 0; border-bottom: 1px solid #b3d1ff; font-size: 14px;">
                        <strong><?php echo htmlspecialchars($stat['department']); ?>:</strong> 
                        <span style="float: right; font-weight: bold; color: #3399ff;"><?php echo $rate; ?>% Passed</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p style="color: #555; font-size: 13px; margin: 0;">No department data available yet.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>