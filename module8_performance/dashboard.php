<?php
require_once '../config/db.php';
require_once 'performance_model.php';

$perfModel = new PerformanceModel($pdo);

// Fetch data for charts
$rating_dist = $pdo->query("
    SELECT manager_rating, COUNT(*) as count 
    FROM employee_appraisals 
    GROUP BY manager_rating 
    ORDER BY manager_rating ASC
")->fetchAll(PDO::FETCH_ASSOC);

$dept_ratings = $pdo->query("
    SELECT e.department, AVG(a.manager_rating) as avg_score 
    FROM employee_appraisals a
    JOIN employees e ON a.employee_id = e.employee_id
    GROUP BY e.department
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Analytics Dashboard - HRMS</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #ffffff; color: #000000; margin: 0; padding: 0; }
        .container { margin-left: 260px !important; max-width: calc(100% - 280px) !important; padding: 20px; box-sizing: border-box; background: #ffffff; min-height: 100vh; }
        header { border-bottom: 2px solid #b3d1ff; padding-bottom: 15px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .btn-primary { background-color: #3399ff; color: #ffffff; padding: 8px 14px; border-radius: 4px; font-size: 13px; border: none; cursor: pointer; font-weight: bold; text-decoration: none; }
        .grid-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
        .card { background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 20px; }
        .card h3 { margin-top: 0; font-size: 15px; color: #1e293b; border-bottom: 1px solid #cbd5e1; padding-bottom: 8px; }
    </style>
</head>
<body>

<?php 
include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php');
?>

<div class="container">
    <header>
        <div>
            <h1 style="margin: 0; font-size: 22px;">Performance Analytics Dashboard</h1>
            <small style="color: #64748b;">Workforce Rating Distribution & Department Metrics</small>
        </div>
        <a href="performance.php" class="btn-primary">Back to Appraisals</a>
    </header>

    <div class="grid-cards">
        <!-- Chart 1: Rating Distribution -->
        <div class="card">
            <h3>Performance Rating Distribution</h3>
            <p style="font-size: 12px; color: #64748b;">Shows workforce performance levels.</p>
            <ul>
                <?php foreach ($rating_dist as $r): ?>
                    <li>Rating <?php echo $r['manager_rating']; ?>/5: <strong><?php echo $r['count']; ?> Employees</strong></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Chart 2: Average Rating by Department -->
        <div class="card">
            <h3>Average Rating by Department</h3>
            <p style="font-size: 12px; color: #64748b;">Flags underperforming departments.</p>
            <ul>
                <?php foreach ($dept_ratings as $d): ?>
                    <li><?php echo htmlspecialchars($d['department']); ?>: <strong><?php echo number_format($d['avg_score'], 2); ?> / 5</strong></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

</body>
</html>