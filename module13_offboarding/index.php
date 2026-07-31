<?php
// module13_offboarding/index.php - Offboarding Dashboard
require_once '../config/db.php';

// Fetch KPI metrics
$totalExits = $pdo->query("SELECT COUNT(*) FROM exit_requests")->fetchColumn();
$pendingClearance = $pdo->query("SELECT COUNT(*) FROM exit_requests WHERE status = 'Pending Clearance'")->fetchColumn();
$readySettlement = $pdo->query("SELECT COUNT(*) FROM exit_requests WHERE status = 'Ready for Settlement'")->fetchColumn();
$finalizedExits = $pdo->query("SELECT COUNT(*) FROM exit_requests WHERE status = 'Finalized / Exited'")->fetchColumn();

// Fetch Active Exit Files
$exits = $pdo->query("SELECT * FROM exit_requests ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offboarding & Clearance - HRMS</title>
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
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 12px; vertical-align: top; }
        th { background-color: #eff6ff; color: #1e293b; text-transform: uppercase; font-size: 11px; font-weight: 900; letter-spacing: 0.5px; border: 1px solid #bfdbfe; }
        .view-link {
            color: #2563eb;
            text-decoration: none;
            font-weight: 900;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .view-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>
    
    <div class="container">
        <header>
            <h1><span class="brand-title">CHAP CHAP</span> <span class="brand-title" style="color: #3b82f6;">AFRICA</span> - Offboarding & Clearance</h1>
            <div class="nav-links">
                <a href="reports.php" class="btn-primary" style="background-color: #3b82f6;">Analytics & Reports</a>
                <a href="initiate.php" class="btn-primary">+ Initiate New Exit</a>
            </div>
        </header>

        <!-- KPI Summary Grid -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <h3>Total Exits</h3>
                <div class="value"><?php echo $totalExits; ?></div>
            </div>
            <div class="kpi-card">
                <h3>Pending Clearances</h3>
                <div class="value" style="color: #ea580c;"><?php echo $pendingClearance; ?></div>
            </div>
            <div class="kpi-card">
                <h3>Ready for Settlement</h3>
                <div class="value" style="color: #2563eb;"><?php echo $readySettlement; ?></div>
            </div>
            <div class="kpi-card">
                <h3>Finalized / Exited</h3>
                <div class="value" style="color: #16a34a;"><?php echo $finalizedExits; ?></div>
            </div>
        </div>

        <!-- Active Exit Files Table Container -->
        <div class="card">
            <div class="action-bar">
                <h2>Active Exit Files</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Exit ID</th>
                        <th>Employee ID</th>
                        <th>Exit Reason</th>
                        <th>Last Working Day</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($exits)): ?>
                        <tr><td colspan="6" style="text-align: center; color: #64748b;">No exit files found. Click "+ Initiate New Exit" above to start an offboarding file.</td></tr>
                    <?php else: ?>
                        <?php foreach ($exits as $ex): ?>
                            <tr>
                                <td><strong>EXIT-<?php echo str_pad($ex['id'], 3, '0', STR_PAD_LEFT); ?></strong></td>
                                <td><strong>#<?php echo htmlspecialchars($ex['employee_id']); ?></strong></td>
                                <td><?php echo htmlspecialchars($ex['exit_reason']); ?></td>
                                <td><?php echo htmlspecialchars($ex['last_working_day']); ?></td>
                                <td><span style="color: #2563eb; font-weight: 900;"><?php echo htmlspecialchars($ex['status']); ?></span></td>
                                <td>
                                    <a href="clearance_view.php?id=<?php echo $ex['id']; ?>" class="view-link">View File &rarr;</a>
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