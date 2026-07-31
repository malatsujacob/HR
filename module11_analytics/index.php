<?php
// module11_analytics/index.php - Visual HR Analytics & Reports Dashboard
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Analytics & Reports - HRMS</title>
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
        .btn-primary { 
            background-color: #2563eb; 
            color: #ffffff; 
            padding: 8px 14px; 
            border-radius: 4px; 
            font-size: 11px; 
            border: none; 
            cursor: pointer; 
            font-weight: 900; 
            text-decoration: none; 
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .btn-primary:hover { background-color: #1d4ed8; }
        .analytics-grid {
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
        .link-card p {
            display: none;
        }
        .link-card:hover { 
            background: #dbeafe; 
        }
    </style>
</head>
<body>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>

    <div class="container">
        <header>
            <h1><span class="brand-title">CHAP CHAP</span> <span class="brand-title" style="color: #3b82f6;">AFRICA</span> - Analytics</h1>
        </header>

        <div class="analytics-grid">
            <div class="metric-card">
                <h3>Active Headcount</h3>
                <div class="value" id="active-headcount">0</div>
            </div>
            <div class="metric-card">
                <h3>Turnover Rate</h3>
                <div class="value" id="turnover-rate">0%</div>
            </div>
            <div class="metric-card">
                <h3>Payroll Cost (UGX)</h3>
                <div class="value" id="payroll-cost" style="font-size: 18px;">UGX 0</div>
            </div>
        </div>

        <div class="card" style="max-width: 400px;">
            <div class="links-grid">
                <div class="link-card">
                    <a href="api/kpi.php">KPI API</a>
                </div>
                <div class="link-card">
                    <a href="api/export.php">Export Data</a>
                </div>
                <div class="link-card">
                    <a href="api/department-metrics.php">Department Metrics</a>
                </div>
                <div class="link-card">
                    <a href="api/drilldown.php">Drilldown Reports</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        fetch('api/kpi.php')
            .then(response => response.json())
            .then(result => {
                if(result.status === 'success' && result.data) {
                    document.getElementById('active-headcount').innerText = result.data.active_headcount || 0;
                    document.getElementById('turnover-rate').innerText = (result.data.monthly_turnover_rate || 0) + '%';
                    document.getElementById('payroll-cost').innerText = 'UGX ' + Number(result.data.monthly_payroll_cost || 0).toLocaleString();
                }
            })
            .catch(error => {
                console.error('Error fetching KPI data:', error);
            });
    </script>
</body>
</html>