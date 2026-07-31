<?php
// module12_disciplinary/view.php - Case Details, Timeline, and Disciplinary Action Execution
require_once '../config/db.php';

$case_id = intval($_GET['id'] ?? 0);
$message = "";
$error = "";

if ($case_id <= 0) {
    die("Invalid Case ID specified.");
}

// Handle Form Submission for Disciplinary Actions & Penalties
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_taken = trim($_POST['disciplinary_action'] ?? '');
    $new_status = trim($_POST['status'] ?? 'Resolved/Actioned');
    $penalty_amount = floatval($_POST['penalty_amount'] ?? 0);
    $penalty_reason = trim($_POST['penalty_reason'] ?? '');
    $deduction_period = trim($_POST['deduction_period'] ?? 'Next Payroll');
    $actor_id = intval($_POST['actor_id'] ?? 1); // Default HR Admin ID

    try {
        $pdo->beginTransaction();

        // 1. Update the disciplinary case record
        $updateStmt = $pdo->prepare("UPDATE disciplinary_cases SET status = ?, disciplinary_action = ?, updated_at = CURRENT_TIMESTAMP WHERE case_id = ?");
        $updateStmt->execute([$new_status, $action_taken, $case_id]);

        // 2. Log action in timeline audit trail
        $logStmt = $pdo->prepare("INSERT INTO case_timeline_logs (case_id, actor_user_id, action_performed, notes) VALUES (?, ?, ?, ?)");
        $logStmt->execute([$case_id, $actor_id, "Action Applied: $action_taken", "Status updated to: $new_status"]);

        // 3. SPECIAL INTEGRATION: If Financial Penalty is selected, create pending deduction for Payroll (Module 7)
        if ($action_taken === 'Financial Penalty' && $penalty_amount > 0) {
            $empStmt = $pdo->prepare("SELECT accused_employee_id FROM disciplinary_cases WHERE case_id = ?");
            $empStmt->execute([$case_id]);
            $accusedId = $empStmt->fetchColumn();

            if ($accusedId) {
                $deductStmt = $pdo->prepare("INSERT INTO employee_deductions (employee_id, case_id, amount, reason, source, status, deduction_period) VALUES (?, ?, ?, ?, 'Disciplinary', 'Pending', ?)");
                $deductStmt->execute([$accusedId, $case_id, $penalty_amount, $penalty_reason ?: "Disciplinary Fine for Case #$case_id", $deduction_period]);
            }
        }

        // 4. SPECIAL INTEGRATION: If Termination is selected, trigger Module 11/12 Exit Workflow marker
        if ($action_taken === 'Termination') {
            $empStmt = $pdo->prepare("SELECT accused_employee_id FROM disciplinary_cases WHERE case_id = ?");
            $empStmt->execute([$case_id]);
            $accusedId = $empStmt->fetchColumn();

            if ($accusedId) {
                $termLog = $pdo->prepare("INSERT INTO case_timeline_logs (case_id, actor_user_id, action_performed, notes) VALUES (?, ?, 'Termination Triggered', ?)");
                $termLog->execute([$case_id, $actor_id, "Automatic trigger sent to Offboarding & Clearance workflow for Employee ID: $accusedId"]);
            }
        }

        $pdo->commit();
        $message = "Disciplinary action successfully processed and recorded!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error processing action: " . $e->getMessage();
    }
}

// Fetch Case Details
$stmt = $pdo->prepare("SELECT * FROM disciplinary_cases WHERE case_id = ?");
$stmt->execute([$case_id]);
$case = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$case) {
    die("Case not found.");
}

// Fetch Timeline Audit Logs
$logQuery = $pdo->prepare("SELECT * FROM case_timeline_logs WHERE case_id = ? ORDER BY logged_at DESC");
$logQuery->execute([$case_id]);
$logs = $logQuery->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case Details #<?php echo $case_id; ?> | Chap Chap Africa HRMS</title>
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
            padding: 0;
        }

        .main-content {
            margin-left: 260px;
            padding: 20px;
            box-sizing: border-box;
            min-height: 100vh;
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

        .details-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        @media (max-width: 900px) {
            .details-grid {
                grid-template-columns: 1fr;
            }
            .main-content {
                margin-left: 0;
            }
        }

        .card {
            background-color: var(--surface-white);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
        }

        .card h2 {
            font-size: 16px;
            margin-top: 0;
            border-left: 3px solid var(--accent-skyblue);
            padding-left: 8px;
            color: var(--text-primary);
        }

        .info-row {
            margin-bottom: 12px;
            font-size: 13px;
        }

        .info-row strong {
            color: var(--text-primary);
            display: inline-block;
            width: 150px;
        }

        .info-row span {
            color: var(--text-secondary);
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: bold;
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        select, input[type="number"], input[type="text"], textarea {
            width: 100%;
            padding: 8px;
            font-size: 13px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            box-sizing: border-box;
            outline: none;
        }

        select:focus, input:focus, textarea:focus {
            border-color: var(--accent-skyblue);
        }

        .submit-btn {
            background-color: var(--accent-orange);
            color: #ffffff;
            border: none;
            padding: 10px 18px;
            font-size: 14px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }

        .submit-btn:hover {
            opacity: 0.9;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            margin-top: 10px;
        }

        th, td {
            padding: 8px 10px;
            border-bottom: 1px solid var(--border-color);
            font-size: 12px;
        }

        th {
            background-color: #e0f2fe;
            color: var(--text-primary);
        }

        td {
            color: var(--text-secondary);
        }

        .alert-success {
            background-color: #dcfce7;
            border: 1px solid #bbf7d0;
            color: #166534;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 13px;
        }

        .alert-error {
            background-color: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 13px;
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
    </style>
    <script>
        function togglePenaltyFields() {
            const actionSelect = document.getElementById('disciplinary_action');
            const penaltyContainer = document.getElementById('penalty_fields');
            if (actionSelect.value === 'Financial Penalty') {
                penaltyContainer.style.display = 'block';
            } else {
                penaltyContainer.style.display = 'none';
            }
        }
    </script>
</head>
<body>
<?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>

<div class="main-content">
    <div class="header-container">
        <h1><span class="skyblue">CHAP CHAP AFRICA</span> | <span class="hrms-brand">HRMS</span></h1>
        <div>
            <span style="font-size: 12px; color: var(--text-secondary);">Module 12: Case Review & Action</span>
        </div>
    </div>

    <a href="index.php" class="back-link">&larr; Back to Dashboard Overview</a>

    <?php if (!empty($message)): ?>
        <div class="alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="details-grid">
        <!-- Left Column: Case Information & Audit Timeline -->
        <div>
            <div class="card">
                <h2>Case Overview: <?php echo htmlspecialchars($case['case_title']); ?></h2>
                
                <div class="info-row"><strong>Case ID:</strong> <span>CASE-<?php echo str_pad($case['case_id'], 3, '0', STR_PAD_LEFT); ?></span></div>
                <div class="info-row"><strong>Violation Type:</strong> <span><?php echo htmlspecialchars($case['case_type']); ?></span></div>
                <div class="info-row"><strong>Accused Employee ID:</strong> <span><?php echo htmlspecialchars($case['accused_employee_id']); ?></span></div>
                <div class="info-row"><strong>Current Status:</strong> <span style="font-weight: bold; color: var(--accent-skyblue);"><?php echo htmlspecialchars($case['status']); ?></span></div>
                <div class="info-row"><strong>Incident Date:</strong> <span><?php echo htmlspecialchars($case['incident_date']); ?></span></div>
                <div class="info-row"><strong>Location:</strong> <span><?php echo htmlspecialchars($case['location']); ?></span></div>
                <div class="info-row"><strong>Anonymous Report:</strong> <span><?php echo $case['is_anonymous'] ? 'Yes (Reporter Hidden)' : 'No'; ?></span></div>
                
                <div style="margin-top: 15px;">
                    <strong style="font-size: 13px; color: var(--text-primary);">Detailed Description:</strong>
                    <p style="background-color: var(--bg-light); padding: 10px; border-radius: 4px; font-size: 13px; color: var(--text-secondary); line-height: 1.4; margin-top: 5px;">
                        <?php echo nl2br(htmlspecialchars($case['detailed_description'])); ?>
                    </p>
                </div>
            </div>

            <!-- Audit Trail Timeline Log -->
            <div class="card">
                <h2>Audit Trail & Timeline Log</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Date / Time</th>
                            <th>Action Performed</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="3" style="text-align: center;">No audit logs recorded yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($log['logged_at']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($log['action_performed']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($log['notes']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right Column: Action Execution & Payroll/Exit Triggers -->
        <div>
            <div class="card">
                <h2>Execute Disciplinary Action</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="disciplinary_action">Select Outcome *</label>
                        <select id="disciplinary_action" name="disciplinary_action" onchange="togglePenaltyFields()" required>
                            <option value="">-- Choose Outcome --</option>
                            <option value="Verbal Warning">Verbal Warning</option>
                            <option value="Written Warning">Written Warning</option>
                            <option value="Final Warning">Final Warning</option>
                            <option value="Suspension">Suspension</option>
                            <option value="Financial Penalty">Financial Penalty (Payroll Deduction)</option>
                            <option value="Termination">Termination (Triggers Offboarding)</option>
                            <option value="No Action / Dismissed">No Action / Dismissed</option>
                        </select>
                    </div>

                    <!-- Dynamic Financial Penalty Fields -->
                    <div id="penalty_fields" style="display: none; background-color: var(--bg-light); padding: 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid var(--border-color);">
                        <div class="form-group">
                            <label for="penalty_amount">Penalty Amount (UGX) *</label>
                            <input type="number" step="0.01" id="penalty_amount" name="penalty_amount" placeholder="e.g., 200000">
                        </div>
                        <div class="form-group">
                            <label for="penalty_reason">Deduction Reason *</label>
                            <input type="text" id="penalty_reason" name="penalty_reason" placeholder="e.g., Damaged company laptop">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label for="deduction_period">Deduction Period *</label>
                            <select id="deduction_period" name="deduction_period">
                                <option value="Next Payroll">Deduct in Next Payroll</option>
                                <option value="Spread over 2 Months">Spread over 2 Months</option>
                                <option value="Spread over 3 Months">Spread over 3 Months</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="status">Update Case Status *</label>
                        <select id="status" name="status" required>
                            <option value="Investigation Ongoing" <?php echo $case['status'] === 'Investigation Ongoing' ? 'selected' : ''; ?>>Investigation Ongoing</option>
                            <option value="Awaiting Decision" <?php echo $case['status'] === 'Awaiting Decision' ? 'selected' : ''; ?>>Awaiting Decision</option>
                            <option value="Resolved/Actioned" <?php echo $case['status'] === 'Resolved/Actioned' ? 'selected' : ''; ?>>Resolved/Actioned</option>
                            <option value="Closed" <?php echo $case['status'] === 'Closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                    </div>

                    <button type="submit" class="submit-btn">Apply Action & Sync Loops</button>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>