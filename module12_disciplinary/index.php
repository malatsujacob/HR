<?php
// module12_disciplinary/index.php - Disciplinary & Grievance Main Dashboard
require_once '../config/db.php';

// Fetch summary metrics
$totalCases = $pdo->query("SELECT COUNT(*) FROM disciplinary_cases")->fetchColumn();
$openCases = $pdo->query("SELECT COUNT(*) FROM disciplinary_cases WHERE status NOT IN ('Resolved/Actioned', 'Closed')")->fetchColumn();
$pendingDefenses = $pdo->query("SELECT COUNT(*) FROM disciplinary_cases WHERE status = 'Awaiting Decision'")->fetchColumn();
$totalFines = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM employee_deductions WHERE source = 'Disciplinary'")->fetchColumn();

// Fetch all cases for the data table
$casesQuery = $pdo->query("SELECT * FROM disciplinary_cases ORDER BY created_at DESC");
$cases = $casesQuery->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disciplinary & Grievance - HRMS</title>
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
        .nav-links { 
            display: flex; 
            gap: 10px; 
        }
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
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .kpi-card {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 6px;
            padding: 15px;
            box-shadow: 0 1px 3px rgba(37, 99, 235, 0.1);
        }
        .kpi-card h3 {
            margin: 0 0 8px 0;
            font-size: 11px;
            color: #2563eb;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .kpi-card .value {
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
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 12px; vertical-align: top; }
        th { background-color: #eff6ff; color: #1e293b; text-transform: uppercase; font-size: 11px; font-weight: 900; letter-spacing: 0.5px; border: 1px solid #bfdbfe; }
        .action-links a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 900;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-right: 10px;
        }
        .action-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>
    
    <div class="container">
        <header>
            <h1><span class="brand-title">CHAP CHAP</span> <span class="brand-title" style="color: #3b82f6;">AFRICA</span> - Disciplinary & Grievance</h1>
            <div class="nav-links">
                <a href="create.php" class="btn-primary">+ Report Incident</a>
                <a href="reports.php" class="btn-primary" style="background-color: #3b82f6;">Analytics & Reports</a>
            </div>
        </header>

        <!-- Metric Summaries -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <h3>Total Cases Logged</h3>
                <div class="value"><?php echo $totalCases; ?></div>
            </div>
            <div class="kpi-card">
                <h3>Active / Open Cases</h3>
                <div class="value"><?php echo $openCases; ?></div>
            </div>
            <div class="kpi-card">
                <h3>Awaiting Defense</h3>
                <div class="value"><?php echo $pendingDefenses; ?></div>
            </div>
            <div class="kpi-card">
                <h3>Disciplinary Fines (UGX)</h3>
                <div class="value" style="font-size: 18px;"><?php echo number_format($totalFines, 2); ?></div>
            </div>
        </div>

        <!-- Cases Data Table -->
        <div class="card">
            <h2>Disciplinary Cases & Grievance Tickets</h2>
            <table>
                <thead>
                    <tr>
                        <th>Case ID</th>
                        <th>Title</th>
                        <th>Violation Type</th>
                        <th>Accused ID</th>
                        <th>Status</th>
                        <th>Date Logged</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cases)): ?>
                        <tr><td colspan="7" style="text-align: center; color: #64748b;">No disciplinary cases recorded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($cases as $c): ?>
                            <tr>
                                <td><strong>CASE-<?php echo str_pad($c['case_id'], 3, '0', STR_PAD_LEFT); ?></strong></td>
                                <td><strong><?php echo htmlspecialchars($c['case_title']); ?></strong></td>
                                <td><?php echo htmlspecialchars($c['case_type']); ?></td>
                                <td>#<?php echo htmlspecialchars($c['accused_employee_id']); ?></td>
                                <td><span style="color: #2563eb; font-weight: 900;"><?php echo htmlspecialchars($c['status']); ?></span></td>
                                <td><?php echo htmlspecialchars($c['created_at']); ?></td>
                                <td class="action-links">
                                    <a href="view.php?id=<?php echo $c['case_id']; ?>">Review</a>
                                    <a href="defense.php?id=<?php echo $c['case_id']; ?>">Defense</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>