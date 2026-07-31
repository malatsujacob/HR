<?php
// module12_disciplinary/reports.php - Disciplinary & Grievance Analytics & Export
require_once '../config/db.php';

// Fetch summary metrics
$totalCases = $pdo->query("SELECT COUNT(*) FROM disciplinary_cases")->fetchColumn();
$openCases = $pdo->query("SELECT COUNT(*) FROM disciplinary_cases WHERE status NOT IN ('Resolved/Actioned', 'Closed')")->fetchColumn();
$resolvedCases = $pdo->query("SELECT COUNT(*) FROM disciplinary_cases WHERE status IN ('Resolved/Actioned', 'Closed')")->fetchColumn();
$totalFines = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM employee_deductions WHERE source = 'Disciplinary'")->fetchColumn();

// Fetch breakdown by type
$typeQuery = $pdo->query("SELECT case_type, COUNT(*) as count FROM disciplinary_cases GROUP BY case_type");
$typeStats = $typeQuery->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent deductions linked to discipline
$deductionQuery = $pdo->query("SELECT ed.*, dc.case_title FROM employee_deductions ed JOIN disciplinary_cases dc ON ed.case_id = dc.case_id WHERE ed.source = 'Disciplinary' ORDER BY ed.created_at DESC LIMIT 10");
$deductions = $deductionQuery->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disciplinary Reports | Chap Chap Africa HRMS</title>
    <style>
        :root {
            --bg-light: #f0f9ff;
            --surface-white: #ffffff;
            --border-color: #bae6fd;
            --text-primary: #0f172a;
            --text-secondary: #334155;
            --accent-skyblue: #0284c7;
            --accent-orange: #f97316;
            --accent-red: #dc2626;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: var(--bg-light);
            color: var(--text-primary);
            margin: 0;
            padding: 20px;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 12px;
            margin-bottom: 20px;
            background-color: var(--bg-light);
        }

        .header-container h1 {
            margin: 0;
            font-size: 22px;
            background-color: var(--bg-light);
            color: var(--text-primary);
            letter-spacing: 0.5px;
        }

        .header-container h1 span.skyblue {
            color: var(--accent-skyblue);
        }

        .header-container h1 span.hrms-brand {
            color: var(--accent-red);
            font-weight: 800;
            background-color: #fee2e2;
            padding: 2px 6px;
            border-radius: 4px;
            border: 1px solid #fecaca;
            margin-left: 6px;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .kpi-card {
            background-color: var(--surface-white);
            border: 1px solid var(--border-color);
            padding: 15px;
            border-radius: 6px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
        }

        .kpi-card h3 {
            margin: 0 0 5px 0;
            font-size: 12px;
            color: var(--text-secondary);
            font-weight: normal;
        }

        .kpi-card .value {
            font-size: 20px;
            font-weight: bold;
            color: var(--accent-skyblue);
        }

        .card {
            background-color: var(--surface-white);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
        }

        .card h2 {
            font-size: 16px;
            margin-top: 0;
            border-left: 3px solid var(--accent-skyblue);
            padding-left: 8px;
            color: var(--text-primary);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            margin-top: 10px;
        }

        th, td {
            padding: 9px 12px;
            border-bottom: 1px solid var(--border-color);
            font-size: 13px;
        }

        th {
            background-color: #e0f2fe;
            color: var(--text-primary);
        }

        td {
            color: var(--text-secondary);
        }

        .back-link {
            display: inline-block;
            margin-bottom: 15px;
            font-size: 13px;
            color: var(--accent-skyblue);
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .export-btn {
            background-color: var(--accent-orange);
            color: #ffffff;
            border: none;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
            float: right;
        }

        .export-btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
<?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>

    <div class="header-container" style="margin-left: 280px;">
        <h1><span class="skyblue">CHAP CHAP AFRICA</span> | <span class="hrms-brand">HRMS</span></h1>
        <div>
            <span style="font-size: 12px; color: var(--text-secondary);">Module 12: Reports & Analytics</span>
        </div>
    </div>

    <a href="index.php" class="back-link">&larr; Back to Dashboard Overview</a>

    <!-- Metric Summaries -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <h3>Total Cases Logged</h3>
            <div class="value"><?php echo $totalCases; ?></div>
        </div>
        <div class="kpi-card">
            <h3>Open / Active Cases</h3>
            <div class="value"><?php echo $openCases; ?></div>
        </div>
        <div class="kpi-card">
            <h3>Resolved Cases</h3>
            <div class="value"><?php echo $resolvedCases; ?></div>
        </div>
        <div class="kpi-card">
            <h3>Total Fines Issued (UGX)</h3>
            <div class="value"><?php echo number_format($totalFines, 2); ?></div>
        </div>
    </div>

    <!-- Case Type Breakdown Table -->
    <div class="card">
        <h2>Breakdown by Violation Type</h2>
        <table>
            <thead>
                <tr>
                    <th>Violation Category</th>
                    <th>Number of Cases</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($typeStats)): ?>
                    <tr><td colspan="2" style="text-align: center;">No data available.</td></tr>
                <?php else: ?>
                    <?php foreach ($typeStats as $stat): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($stat['case_type']); ?></strong></td>
                            <td><?php echo htmlspecialchars($stat['count']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Payroll Deductions Handshake Table -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <h2 style="margin: 0; border: none; padding: 0;">Payroll Integration: Disciplinary Fines</h2>
            <button class="export-btn" onclick="window.print()">Print / Export Report</button>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Deduction ID</th>
                    <th>Employee ID</th>
                    <th>Case Title</th>
                    <th>Amount (UGX)</th>
                    <th>Status</th>
                    <th>Period</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($deductions)): ?>
                    <tr><td colspan="6" style="text-align: center;">No financial deductions linked.</td></tr>
                <?php else: ?>
                    <?php foreach ($deductions as $ded): ?>
                        <tr>
                            <td>DEDUCT-<?php echo str_pad($ded['deduction_id'], 3, '0', STR_PAD_LEFT); ?></td>
                            <td><?php echo htmlspecialchars($ded['employee_id']); ?></td>
                            <td><?php echo htmlspecialchars($ded['case_title']); ?></td>
                            <td><strong><?php echo number_format($ded['amount'], 2); ?></strong></td>
                            <td><span style="color: var(--accent-skyblue); font-weight: bold;"><?php echo htmlspecialchars($ded['status']); ?></span></td>
                            <td><?php echo htmlspecialchars($ded['deduction_period']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>