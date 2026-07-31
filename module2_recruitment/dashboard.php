<?php
require_once '../config/db.php';

// Fetch metrics data from database
try {
    $stmt = $pdo->query("SELECT department, COUNT(*) as count FROM job_requisitions WHERE status = 'Approved' GROUP BY department");
    $open_reqs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT source, COUNT(*) as count FROM candidates GROUP BY source");
    $apps_by_source = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT source, COUNT(*) as count FROM candidates WHERE pipeline_stage = 'Hired' GROUP BY source");
    $hires_by_source = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT urgency, COUNT(*) as count FROM job_requisitions GROUP BY urgency");
    $reqs_by_priority = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT pipeline_stage, COUNT(*) as count FROM candidates GROUP BY pipeline_stage");
    $funnel_stages = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    $error = "Error fetching analytics data: " . $e->getMessage();
}

$funnel_order = ['New Application', 'Screened', 'Shortlisted', 'Interviewed', 'Offered', 'Hired'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recruitment Analytics Dashboard - HRMS</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #eef2f7;
            margin: 0;
            padding: 0;
        }
        .container {
            margin: 20px 20px 40px 280px;
            max-width: calc(100% - 320px);
            padding: 28px;
            box-sizing: border-box;
            background: #ffffff;
            min-height: calc(100vh - 60px);
            border-radius: 18px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
        }
        header {
            border-bottom: 2px solid #eaeaea;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .grid-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        .card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #e5e5e5;
        }
        .card h3 {
            margin-top: 0;
            font-size: 16px;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 8px;
        }
    </style>
</head>
<body>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>

<div class="container">
    <header>
        <h1 style="margin: 0; color: #111;">Recruitment Analytics Dashboard</h1>
    </header>

    <?php if (isset($error)): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px;"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="grid-container">
        <div class="card">
            <h3>Open Requisitions by Department</h3>
            <canvas id="openReqsChart"></canvas>
        </div>
        <div class="card">
            <h3>Applications by Source</h3>
            <canvas id="appsSourceChart"></canvas>
        </div>
        <div class="card">
            <h3>Hires by Source (Quality Check)</h3>
            <canvas id="hiresSourceChart"></canvas>
        </div>
        <div class="card">
            <h3>Requisitions by Priority</h3>
            <canvas id="reqPriorityChart"></canvas>
        </div>
    </div>

    <div class="card" style="margin-top: 20px;">
        <h3>Recruitment Pipeline Funnel Drop-off</h3>
        <canvas id="funnelChart" style="max-height: 250px;"></canvas>
    </div>
</div>

<script>
    const openReqsData = <?php echo json_encode($open_reqs); ?>;
    new Chart(document.getElementById('openReqsChart'), {
        type: 'bar',
        data: {
            labels: openReqsData.map(d => d.department),
            datasets: [{ label: 'Unfilled Jobs', data: openReqsData.map(d => d.count), backgroundColor: '#0056b3' }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });

    const appsSourceData = <?php echo json_encode($apps_by_source); ?>;
    new Chart(document.getElementById('appsSourceChart'), {
        type: 'bar',
        data: {
            labels: appsSourceData.map(d => d.source),
            datasets: [{ label: 'Volume of Applications', data: appsSourceData.map(d => d.count), backgroundColor: '#17a2b8' }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });

    const hiresSourceData = <?php echo json_encode($hires_by_source); ?>;
    new Chart(document.getElementById('hiresSourceChart'), {
        type: 'bar',
        data: {
            labels: hiresSourceData.map(d => d.source),
            datasets: [{ label: 'Successful Hires', data: hiresSourceData.map(d => d.count), backgroundColor: '#28a745' }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });

    const reqPriorityData = <?php echo json_encode($reqs_by_priority); ?>;
    new Chart(document.getElementById('reqPriorityChart'), {
        type: 'bar',
        data: {
            labels: reqPriorityData.map(d => d.urgency),
            datasets: [{ label: 'Count', data: reqPriorityData.map(d => d.count), backgroundColor: '#ff7b00' }]
        },
        options: { responsive: true, indexAxis: 'y', scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });

    const funnelStagesMap = <?php echo json_encode($funnel_stages); ?>;
    const funnelLabels = ['New Application', 'Screened', 'Shortlisted', 'Interviewed', 'Offered', 'Hired'];
    const funnelCounts = funnelLabels.map(stage => funnelStagesMap[stage] || 0);

    new Chart(document.getElementById('funnelChart'), {
        type: 'bar',
        data: {
            labels: funnelLabels,
            datasets: [{ label: 'Candidates per Stage', data: funnelCounts, backgroundColor: ['#0b0f19', '#0056b3', '#17a2b8', '#ff7b00', '#fd7e14', '#28a745'] }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });
</script>

</body>
</html>