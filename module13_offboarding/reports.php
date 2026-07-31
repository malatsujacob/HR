<?php
// module13_offboarding/reports.php - Offboarding Analytics & Reports
require_once '../config/db.php';

// Fetch summary metrics
$totalExits = $pdo->query("SELECT COUNT(*) FROM exit_requests")->fetchColumn();
$finalizedExits = $pdo->query("SELECT COUNT(*) FROM exit_requests WHERE status = 'Finalized / Exited'")->fetchColumn();
$blacklistedCount = $pdo->query("SELECT COUNT(*) FROM exit_requests WHERE do_not_rehire_flag = TRUE")->fetchColumn();

// Fetch exit reasons breakdown (Layer 1)
$exitReasons = $pdo->query("SELECT exit_reason, COUNT(*) as count FROM exit_requests GROUP BY exit_reason")->fetchAll(PDO::FETCH_ASSOC);

// Fetch exit interview feedback (Layer 2)
$feedbackRecords = $pdo->query("SELECT id, employee_id, exit_interview_reason, exit_interview_text, recommend_toggle FROM exit_requests WHERE exit_interview_text IS NOT NULL AND exit_interview_text != '' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics & Reports | Chap Chap Africa HRMS</title>
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
            color: var(--text-primary);
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
            font-size: 22px;
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

        .print-btn {
            background-color: var(--accent-orange);
            color: #ffffff;
            border: none;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
        }

        .print-btn:hover {
            opacity: 0.9;
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
        .content {
            margin-left: 220px;
            padding-right: 20px;
        }
    </style>
</head>
<body>
<?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>

    <div class="content">
        <div class="header-container">
            <h1><span class="skyblue">CHAP CHAP AFRICA</span> | <span class="hrms-brand">HRMS</span></h1>
            <div>
                <button class="print-btn" onclick="window.print()">Print Analytics Report</button>
            </div>
        </div>

        <a href="index.php" class="back-link">&larr; Back to Exit Dashboard</a>

    <!-- KPI Summary Grid -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <h3>Total Exits Recorded</h3>
            <div class="value"><?php echo $totalExits; ?></div>
        </div>
        <div class="kpi-card">
            <h3>Finalized &amp; Archived</h3>
            <div class="value"><?php echo $finalizedExits; ?></div>
        </div>
        <div class="kpi-card">
            <h3>Blacklisted (Do Not Rehire)</h3>
            <div class="value" style="color: var(--accent-red);"><?php echo $blacklistedCount; ?></div>
        </div>
    </div>

    <!-- Exit Reasons Summary (Layer 1) -->
    <div class="card">
        <h2>Exit Reasons Summary (Analytics Breakdown)</h2>
        <table>
            <thead>
                <tr>
                    <th>Exit Reason Category</th>
                    <th>Total Count</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($exitReasons)): ?>
                    <tr><td colspan="2" style="text-align: center;">No exit reasons recorded yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($exitReasons as $er): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($er['exit_reason']); ?></strong></td>
                            <td><?php echo htmlspecialchars($er['count']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Exit Interview Qualitative Feedback (Layer 2) -->
    <div class="card">
        <h2>Exit Interview Qualitative Feedback &amp; Insights</h2>
        <table>
            <thead>
                <tr>
                    <th>Exit ID</th>
                    <th>Employee ID</th>
                    <th>Primary Reason</th>
                    <th>Open Feedback Notes</th>
                    <th>Would Recommend</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($feedbackRecords)): ?>
                    <tr><td colspan="5" style="text-align: center;">No qualitative feedback records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($feedbackRecords as $fb): ?>
                        <tr>
                            <td>EXIT-<?php echo str_pad($fb['id'], 3, '0', STR_PAD_LEFT); ?></td>
                            <td><strong><?php echo htmlspecialchars($fb['employee_id']); ?></strong></td>
                            <td><?php echo htmlspecialchars($fb['exit_interview_reason'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($fb['exit_interview_text']); ?></td>
                            <td><?php echo $fb['recommend_toggle'] ? 'Yes' : 'No'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>