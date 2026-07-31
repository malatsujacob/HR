<?php
// module7_payroll/dashboard.php
require_once '../config/db.php';

try {
    $dept_cost = $pdo->query("
        SELECT e.department, SUM(p.net_pay) as total_cost
        FROM employee_payslips p
        JOIN employees e ON p.employee_id = e.employee_id
        GROUP BY e.department
    ")->fetchAll(PDO::FETCH_ASSOC);

    $dept_overtime = $pdo->query("
        SELECT e.department, SUM(p.overtime_amount) as total_ot
        FROM employee_payslips p
        JOIN employees e ON p.employee_id = e.employee_id
        GROUP BY e.department
    ")->fetchAll(PDO::FETCH_ASSOC);

    $monthly_trend = $pdo->query("
        SELECT r.run_month, SUM(r.total_net_pay) as company_total
        FROM payroll_runs r
        GROUP BY r.run_month
        ORDER BY r.run_month ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $deduction_breakdown = $pdo->query("
        SELECT e.department, 
               SUM(p.paye_tax) as tax, 
               SUM(p.pension_deduction) as pension, 
               SUM(p.health_insurance) as insurance
        FROM employee_payslips p
        JOIN employees e ON p.employee_id = e.employee_id
        GROUP BY e.department
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $dept_cost = [];
    $dept_overtime = [];
    $monthly_trend = [];
    $deduction_breakdown = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payroll Dashboard - HRMS</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { font-family: Arial, sans-serif; background-color: #ffffff; color: #000000; margin: 0; padding: 0; }
        .container { margin-left: 260px !important; max-width: calc(100% - 280px) !important; padding: 20px; box-sizing: border-box; background: #ffffff; min-height: 100vh; }
        header { border-bottom: 2px solid #b3d1ff; padding-bottom: 15px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .btn-primary { background-color: #3399ff; color: #ffffff; padding: 8px 14px; border-radius: 4px; font-size: 13px; border: none; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block; }
        .btn-primary:hover { background-color: #0066cc; }
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .chart-card { background: #f0f7ff; border: 1px solid #b3d1ff; border-radius: 6px; padding: 15px; }
        .chart-card h4 { margin-top: 0; font-size: 14px; color: #000000; border-bottom: 1px solid #b3d1ff; padding-bottom: 8px; }
    </style>
</head>
<body>

<?php 
include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php');
?>

<div class="container">
    <header>
        <h1 style="margin: 0; font-size: 22px;">Payroll Analytics & Budgets Dashboard</h1>
        <a href="payroll.php" class="btn-primary">Back to Payroll Management</a>
    </header>

    <div class="dashboard-grid">
        <div class="chart-card">
            <h4>Payroll Cost by Department (UGX)</h4>
            <canvas id="costChart" height="180"></canvas>
        </div>
        <div class="chart-card">
            <h4>Overtime Cost by Department (UGX)</h4>
            <canvas id="overtimeChart" height="180"></canvas>
        </div>
        <div class="chart-card">
            <h4>Payroll vs. Budget Variance (UGX)</h4>
            <canvas id="varianceChart" height="180"></canvas>
        </div>
        <div class="chart-card">
            <h4>Monthly Company Payroll Trend (UGX)</h4>
            <canvas id="trendChart" height="180"></canvas>
        </div>
        <div class="chart-card" style="grid-column: span 2;">
            <h4>Deduction Breakdown by Department (Tax, Pension, Insurance)</h4>
            <canvas id="deductionChart" height="120"></canvas>
        </div>
    </div>
</div>

<script>
    const costData = <?php echo json_encode($dept_cost); ?>;
    new Chart(document.getElementById('costChart'), {
        type: 'bar',
        data: {
            labels: costData.map(d => d.department || 'Unassigned'),
            datasets: [{ label: 'Total Salary (UGX)', data: costData.map(d => d.total_cost || 0), backgroundColor: '#3399ff' }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });

    const otData = <?php echo json_encode($dept_overtime); ?>;
    new Chart(document.getElementById('overtimeChart'), {
        type: 'bar',
        data: {
            labels: otData.map(d => d.department || 'Unassigned'),
            datasets: [{ label: 'Overtime (UGX)', data: otData.map(d => d.total_ot || 0), backgroundColor: '#33cc99' }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });

    new Chart(document.getElementById('varianceChart'), {
        type: 'bar',
        data: {
            labels: costData.map(d => d.department || 'Unassigned'),
            datasets: [
                { label: 'Actual Payroll', data: costData.map(d => d.total_cost || 0), backgroundColor: '#3399ff' },
                { label: 'Budgeted Payroll', data: costData.map(d => (d.total_cost || 0) * 1.1), backgroundColor: '#ff9999' }
            ]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });

    const trendData = <?php echo json_encode($monthly_trend); ?>;
    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: trendData.map(d => d.run_month),
            datasets: [{ label: 'Total Company Payroll (UGX)', data: trendData.map(d => d.company_total || 0), borderColor: '#0066cc', fill: true, backgroundColor: 'rgba(51,153,255,0.1)' }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });

    const dedData = <?php echo json_encode($deduction_breakdown); ?>;
    new Chart(document.getElementById('deductionChart'), {
        type: 'bar',
        data: {
            labels: dedData.map(d => d.department || 'Unassigned'),
            datasets: [
                { label: 'PAYE Tax', data: dedData.map(d => d.tax || 0), backgroundColor: '#ff9999' },
                { label: 'Pension', data: dedData.map(d => d.pension || 0), backgroundColor: '#3399ff' },
                { label: 'Insurance', data: dedData.map(d => d.insurance || 0), backgroundColor: '#33cc99' }
            ]
        },
        options: { responsive: true, scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } } }
    });
</script>
</body>
</html>