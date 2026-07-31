<?php
require_once '../config/db.php';

try {
    $dept_utilization = $pdo->query("
        SELECT e.department, 
               ROUND(AVG(CASE WHEN lr.status = 'Approved' THEN 5 ELSE 0 END), 2) as avg_used
        FROM employees e
        LEFT JOIN employee_leaves lr ON e.employee_id = lr.employee_id
        GROUP BY e.department
    ")->fetchAll(PDO::FETCH_ASSOC);

    $leave_types_count = $pdo->query("
        SELECT leave_type, COUNT(*) as req_count
        FROM employee_leaves
        GROUP BY leave_type
    ")->fetchAll(PDO::FETCH_ASSOC);

    $pending_approvals = $pdo->query("
        SELECT e.department as manager_name, COUNT(*) as pending_count
        FROM employee_leaves lr
        JOIN employees e ON lr.employee_id = e.employee_id
        WHERE lr.status = 'Pending'
        GROUP BY e.department
    ")->fetchAll(PDO::FETCH_ASSOC);

    $sick_trend = $pdo->query("
        SELECT TO_CHAR(start_date, 'Mon') as month_name, COUNT(*) as sick_days
        FROM employee_leaves
        WHERE leave_type = 'Sick Leave' AND start_date >= (CURRENT_DATE - INTERVAL '1 year')
        GROUP BY TO_CHAR(start_date, 'Mon'), DATE_TRUNC('month', start_date)
        ORDER BY DATE_TRUNC('month', start_date)
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $dept_utilization = [];
    $leave_types_count = [];
    $pending_approvals = [];
    $sick_trend = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Analytics Dashboard - HRMS</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: Arial, sans-serif; background-color: #ffffff; color: #000000; margin: 0; padding: 0; }
        .container { margin-left: 260px !important; max-width: calc(100% - 280px) !important; padding: 20px; box-sizing: border-box; background: #ffffff; min-height: 100vh; }
        header { border-bottom: 2px solid #b3d1ff; padding-bottom: 15px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .btn-primary { background-color: #3399ff; color: #ffffff; padding: 8px 14px; border-radius: 4px; font-size: 13px; border: none; cursor: pointer; font-weight: bold; text-decoration: none; }
        .btn-primary:hover { background-color: #0066cc; }
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .chart-card { background: #f0f7ff; border: 1px solid #b3d1ff; border-radius: 6px; padding: 15px; }
        .chart-card h4 { margin-top: 0; font-size: 14px; color: #000; border-bottom: 1px solid #b3d1ff; padding-bottom: 8px; }
    </style>
</head>
<body>

<?php 
include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php');
?>

<div class="container">
    <header>
        <h1 style="margin: 0; color: #000000; font-size: 22px;">Leave Analytics Dashboard</h1>
        <a href="leave.php" class="btn-primary">Back to Leave Management</a>
    </header>

    <div class="dashboard-grid">
        <div class="chart-card">
            <h4>Leave Balance Utilization by Department (%)</h4>
            <canvas id="utilizationChart" height="180"></canvas>
        </div>
        <div class="chart-card">
            <h4>Leave Requests by Type (Number of Requests)</h4>
            <canvas id="typeChart" height="180"></canvas>
        </div>
        <div class="chart-card">
            <h4>Pending Leave Approvals by Manager</h4>
            <canvas id="pendingChart" height="180"></canvas>
        </div>
        <div class="chart-card">
            <h4>Sick Leave Trend (Total Days Taken)</h4>
            <canvas id="sickTrendChart" height="180"></canvas>
        </div>
    </div>
</div>

<script>
    const utilData = <?php echo json_encode($dept_utilization); ?>;
    new Chart(document.getElementById('utilizationChart'), {
        type: 'bar',
        data: {
            labels: utilData.map(d => d.department || 'Unassigned'),
            datasets: [{
                label: 'Average Days Used',
                data: utilData.map(d => d.avg_used || 0),
                backgroundColor: '#3399ff'
            }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });

    const typeData = <?php echo json_encode($leave_types_count); ?>;
    new Chart(document.getElementById('typeChart'), {
        type: 'bar',
        data: {
            labels: typeData.map(d => d.leave_type),
            datasets: [{
                label: 'Number of Requests',
                data: typeData.map(d => d.req_count || 0),
                backgroundColor: '#33cc99'
            }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });

    const pendingData = <?php echo json_encode($pending_approvals); ?>;
    new Chart(document.getElementById('pendingChart'), {
        type: 'bar',
        data: {
            labels: pendingData.map(d => d.manager_name || 'General'),
            datasets: [{
                label: 'Pending Requests',
                data: pendingData.map(d => d.pending_count || 0),
                backgroundColor: '#ff9999'
            }]
        },
        options: { indexAxis: 'y', responsive: true, scales: { x: { beginAtZero: true } } }
    });

    const sickData = <?php echo json_encode($sick_trend); ?>;
    new Chart(document.getElementById('sickTrendChart'), {
        type: 'line',
        data: {
            labels: sickData.map(d => d.month_name),
            datasets: [{
                label: 'Sick Days Taken',
                data: sickData.map(d => d.sick_days || 0),
                borderColor: '#0066cc',
                backgroundColor: 'rgba(51, 153, 255, 0.1)',
                fill: true,
                tension: 0.1
            }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });
</script>

</body>
</html>