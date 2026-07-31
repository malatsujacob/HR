<?php
require_once '../config/db.php';
require_once 'attendance_model.php';

$model = new AttendanceModel($pdo);

// Fetch Analytics Data
try {
    $absenteeism_dept = $pdo->query("
        SELECT e.department, 
               ROUND(100.0 * SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) / NULLIF(COUNT(a.attendance_id), 0), 2) as absent_rate
        FROM employees e
        LEFT JOIN employee_attendance a ON e.employee_id = a.employee_id
        GROUP BY e.department
    ")->fetchAll(PDO::FETCH_ASSOC);

    $late_dept = $pdo->query("
        SELECT e.department, COUNT(a.attendance_id) as late_count
        FROM employee_attendance a
        JOIN employees e ON a.employee_id = e.employee_id
        WHERE a.lateness_minutes > 0
        GROUP BY e.department
    ")->fetchAll(PDO::FETCH_ASSOC);

    $monthly_trend = $pdo->query("
        SELECT TO_CHAR(attendance_date, 'Mon') as month_name, SUM(total_hours_worked) as total_hours
        FROM employee_attendance
        WHERE attendance_date >= (CURRENT_DATE - INTERVAL '1 year')
        GROUP BY TO_CHAR(attendance_date, 'Mon'), DATE_TRUNC('month', attendance_date)
        ORDER BY DATE_TRUNC('month', attendance_date)
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $absenteeism_dept = [];
    $late_dept = [];
    $monthly_trend = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Analytics Dashboard - HRMS</title>
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

<?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>

<div class="container">
    <header>
        <h1 style="margin: 0; color: #000000; font-size: 22px;">Attendance Analytics Dashboard</h1>
        <a href="attendance.php" class="btn-primary">Back to Attendance Log</a>
    </header>

    <div class="dashboard-grid">
        <div class="chart-card">
            <h4>Absenteeism Rate by Department (%)</h4>
            <canvas id="absenteeismChart" height="180"></canvas>
        </div>
        <div class="chart-card">
            <h4>Late Arrivals by Department (Count)</h4>
            <canvas id="lateChart" height="180"></canvas>
        </div>
        <div class="chart-card">
            <h4>Monthly Attendance Trend (Total Hours Worked)</h4>
            <canvas id="trendChart" height="180"></canvas>
        </div>
    </div>
</div>

<script>
    const absentData = <?php echo json_encode($absenteeism_dept); ?>;
    new Chart(document.getElementById('absenteeismChart'), {
        type: 'bar',
        data: {
            labels: absentData.map(d => d.department || 'Unassigned'),
            datasets: [{
                label: 'Absenteeism Rate (%)',
                data: absentData.map(d => d.absent_rate || 0),
                backgroundColor: '#3399ff'
            }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });

    const lateData = <?php echo json_encode($late_dept); ?>;
    new Chart(document.getElementById('lateChart'), {
        type: 'bar',
        data: {
            labels: lateData.map(d => d.department || 'Unassigned'),
            datasets: [{
                label: 'Late Count',
                data: lateData.map(d => d.late_count || 0),
                backgroundColor: '#ff9999'
            }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });

    const trendData = <?php echo json_encode($monthly_trend); ?>;
    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: trendData.map(d => d.month_name),
            datasets: [{
                label: 'Total Hours Worked',
                data: trendData.map(d => d.total_hours || 0),
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