<?php
// module13_offboarding/clearance_view.php - Clearance Checklist, Progress, and Final Settlement
require_once '../config/db.php';

$exit_id = intval($_GET['id'] ?? 0);
$message = "";
$error = "";

if ($exit_id <= 0) {
    die("Invalid Exit ID specified.");
}

// Handle Department Clearance Ticking or Final Settlement Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_type = $_POST['action_type'] ?? '';

    if ($action_type === 'clear_department') {
        $checklist_id = intval($_POST['checklist_id'] ?? 0);
        $cleared_by = trim($_POST['cleared_by'] ?? 'Department Head');
        $notes = trim($_POST['notes'] ?? '');

        try {
            $stmt = $pdo->prepare("UPDATE clearance_checklist SET is_cleared = TRUE, cleared_by = ?, cleared_at = CURRENT_TIMESTAMP, notes = ? WHERE id = ?");
            $stmt->execute([$cleared_by, $notes, $checklist_id]);
            $message = "Department clearance item updated successfully!";
        } catch (Exception $e) {
            $error = "Error updating clearance: " . $e->getMessage();
        }
    } elseif ($action_type === 'calculate_settlement') {
        // Check if all departments are cleared first
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM clearance_checklist WHERE exit_request_id = ? AND is_cleared = FALSE");
        $checkStmt->execute([$exit_id]);
        $pendingCount = $checkStmt->fetchColumn();

        if ($pendingCount > 0) {
            $error = "Clearance lock active: All departments must clear the employee before calculation.";
        } else {
            $days_worked = floatval($_POST['days_worked_final_month'] ?? 20);
            $daily_rate = floatval($_POST['daily_rate'] ?? 50000);
            $severance = floatval($_POST['severance_amount'] ?? 0);
            $loan_recovery = floatval($_POST['loan_recovery'] ?? 0);
            $asset_recovery = floatval($_POST['asset_recovery_costs'] ?? 0);
            $manual_adjustments = floatval($_POST['manual_adjustments'] ?? 0);

            $total_payable = ($days_worked * $daily_rate) + $severance - $loan_recovery - $asset_recovery + $manual_adjustments;

            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("INSERT INTO final_settlements (exit_request_id, days_worked_final_month, daily_rate, severance_amount, loan_recovery, asset_recovery_costs, manual_adjustments, total_payable, payroll_push_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
                $stmt->execute([$exit_id, $days_worked, $daily_rate, $severance, $loan_recovery, $asset_recovery, $manual_adjustments, $total_payable]);

                $upd = $pdo->prepare("UPDATE exit_requests SET status = 'Ready for Settlement' WHERE id = ?");
                $upd->execute([$exit_id]);

                $pdo->commit();
                $message = "Final settlement calculated successfully!";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Error calculating settlement: " . $e->getMessage();
            }
        }
    } elseif ($action_type === 'finalize_exit') {
        $employee_id = intval($_POST['employee_id'] ?? 0);
        $do_not_rehire = isset($_POST['do_not_rehire']) ? 1 : 0;

        try {
            $pdo->beginTransaction();

            // 1. Move employee record to offboarded_employees (transactional)
            $pdo->exec("CREATE TABLE IF NOT EXISTS offboarded_employees (
                offboard_id SERIAL PRIMARY KEY,
                original_employee_id INTEGER,
                first_name VARCHAR(50),
                last_name VARCHAR(50),
                personal_email VARCHAR(100),
                work_email VARCHAR(100),
                phone_number VARCHAR(50),
                department VARCHAR(50),
                job_title VARCHAR(100),
                hire_date DATE,
                employment_type VARCHAR(50),
                status VARCHAR(50),
                profile_picture VARCHAR(255),
                document_path VARCHAR(255),
                bank_name VARCHAR(100),
                account_number VARCHAR(100),
                created_at TIMESTAMP,
                updated_at TIMESTAMP,
                exit_reason TEXT,
                last_working_day DATE,
                exit_interview_reason VARCHAR(255),
                exit_interview_text TEXT,
                do_not_rehire BOOLEAN DEFAULT FALSE,
                offboarded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");

            $empStmt = $pdo->prepare("SELECT * FROM employees WHERE employee_id = ?");
            $empStmt->execute([$employee_id]);
            $employee = $empStmt->fetch(PDO::FETCH_ASSOC);

            if ($employee) {
                $ins = $pdo->prepare("INSERT INTO offboarded_employees (
                    original_employee_id, first_name, last_name, personal_email, work_email, phone_number,
                    department, job_title, hire_date, employment_type, status, profile_picture, document_path,
                    bank_name, account_number, created_at, updated_at, exit_reason, last_working_day, exit_interview_reason,
                    exit_interview_text, do_not_rehire
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                $ins->execute([
                    $employee['employee_id'],
                    $employee['first_name'] ?? null,
                    $employee['last_name'] ?? null,
                    $employee['personal_email'] ?? null,
                    $employee['work_email'] ?? null,
                    $employee['phone_number'] ?? null,
                    $employee['department'] ?? null,
                    $employee['job_title'] ?? null,
                    $employee['hire_date'] ?? null,
                    $employee['employment_type'] ?? null,
                    'Exited',
                    $employee['profile_picture'] ?? null,
                    $employee['document_path'] ?? null,
                    $employee['bank_name'] ?? null,
                    $employee['account_number'] ?? null,
                    $employee['created_at'] ?? null,
                    $employee['updated_at'] ?? null,
                    $exitRecord['exit_reason'] ?? null,
                    $exitRecord['last_working_day'] ?? null,
                    $exitRecord['exit_interview_reason'] ?? null,
                    $exitRecord['exit_interview_text'] ?? null,
                    $do_not_rehire
                ]);

                // Mark original employee as Exited (preserve for archival)
                $updEmp = $pdo->prepare("UPDATE employees SET status = 'Exited' WHERE employee_id = ?");
                $updEmp->execute([$employee_id]);

                // Create offboard audit if missing and insert snapshot
                $pdo->exec("CREATE TABLE IF NOT EXISTS offboard_audit (
                    audit_id SERIAL PRIMARY KEY,
                    offboard_id INTEGER,
                    original_employee_id INTEGER,
                    snapshot JSONB,
                    action VARCHAR(50),
                    actor VARCHAR(100),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");

                $snapshot = json_encode($employee);
                $auditIns = $pdo->prepare("INSERT INTO offboard_audit (offboard_id, original_employee_id, snapshot, action) VALUES (?, ?, ?, 'offboarded')");
                $auditIns->execute([$ins->rowCount() ? $pdo->lastInsertId('offboarded_employees_offboard_id_seq') : null, $employee['employee_id'] ?? null, $snapshot]);
            }

            // 2. Update Exit Request Status & Blacklist Flag
            $exitStmt = $pdo->prepare("UPDATE exit_requests SET status = 'Finalized / Exited', do_not_rehire_flag = ? WHERE id = ?");
            $exitStmt->execute([$do_not_rehire, $exit_id]);

            // 3. Push to Payroll Adjustment status
            $payStmt = $pdo->prepare("UPDATE final_settlements SET payroll_push_status = 'Pushed to Payroll' WHERE exit_request_id = ?");
            $payStmt->execute([$exit_id]);

            $pdo->commit();
            $message = "Exit finalized! Status hook flipped to Exited, pushed to payroll, and access locked.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error finalizing exit: " . $e->getMessage();
        }
    }
}

// Fetch Exit Details
$stmt = $pdo->prepare("SELECT * FROM exit_requests WHERE id = ?");
$stmt->execute([$exit_id]);
$exitRecord = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exitRecord) {
    die("Exit record not found.");
}

// Fetch Clearance Checklist Items
$checkQuery = $pdo->prepare("SELECT * FROM clearance_checklist WHERE exit_request_id = ?");
$checkQuery->execute([$exit_id]);
$checklists = $checkQuery->fetchAll(PDO::FETCH_ASSOC);

// Calculate Progress Percentage
$totalItems = count($checklists);
$clearedItems = 0;
foreach ($checklists as $ch) {
    if ($ch['is_cleared']) $clearedItems++;
}
$progressPercent = $totalItems > 0 ? round(($clearedItems / $totalItems) * 100) : 0;

// Fetch Existing Settlement Record if any
$setQuery = $pdo->prepare("SELECT * FROM final_settlements WHERE exit_request_id = ?");
$setQuery->execute([$exit_id]);
$settlement = $setQuery->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clearance File #<?php echo $exit_id; ?> | Chap Chap Africa HRMS</title>
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
            --accent-green: #16a34a;
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

        .progress-bar-container {
            background-color: #e2e8f0;
            border-radius: 4px;
            height: 20px;
            width: 100%;
            margin-bottom: 10px;
            overflow: hidden;
        }

        .progress-bar {
            background-color: var(--accent-green);
            height: 100%;
            text-align: center;
            color: white;
            font-size: 11px;
            font-weight: bold;
            line-height: 20px;
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

        .form-group {
            margin-bottom: 12px;
        }

        label {
            display: block;
            font-size: 12px;
            font-weight: bold;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        input[type="text"], input[type="number"] {
            width: 100%;
            padding: 7px;
            font-size: 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            box-sizing: border-box;
        }

        .submit-btn {
            background-color: var(--accent-orange);
            color: #ffffff;
            border: none;
            padding: 9px 16px;
            font-size: 13px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
        }

        .submit-btn:hover {
            opacity: 0.9;
        }

        .submit-btn:disabled {
            background-color: #cbd5e1;
            cursor: not-allowed;
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
                <span style="font-size: 12px; font-weight: bold; color: var(--text-secondary);">Module 13: Clearance File #<?php echo $exit_id; ?></span>
            </div>
        </div>

        <a href="index.php" class="back-link">&larr; Back to Exit Dashboard</a>

    <?php if (!empty($message)): ?>
        <div class="alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Clearance Progress Bar Card -->
    <div class="card">
        <h2>Clearance Progress Overview</h2>
        <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 5px;">
            Current Status: <strong style="color: var(--accent-orange);"><?php echo htmlspecialchars($exitRecord['status']); ?></strong>
        </p>
        <div class="progress-bar-container">
            <div class="progress-bar" style="width: <?php echo $progressPercent; ?>%;">
                <?php echo $progressPercent; ?>% Complete
            </div>
        </div>
        <p style="font-size: 12px; color: var(--text-secondary);">
            <?php echo $clearedItems; ?> of <?php echo $totalItems; ?> departments have marked their clearance items as completed.
        </p>
    </div>

    <!-- Department Checklist Table -->
    <div class="card">
        <h2>Department Clearance Checklist</h2>
        <table>
            <thead>
                <tr>
                    <th>Department</th>
                    <th>Item / Requirement Description</th>
                    <th>Cleared Status</th>
                    <th>Cleared By / Notes</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($checklists as $chk): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($chk['department_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($chk['item_description']); ?></td>
                        <td>
                            <?php if ($chk['is_cleared']): ?>
                                <span style="color: var(--accent-green); font-weight: bold;">&#10003; Cleared</span>
                            <?php else: ?>
                                <span style="color: var(--accent-red); font-weight: bold;">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($chk['cleared_by'] ?? '-'); ?> 
                            <?php echo !empty($chk['notes']) ? '('.htmlspecialchars($chk['notes']).')' : ''; ?>
                        </td>
                        <td>
                            <?php if (!$chk['is_cleared']): ?>
                                <form method="POST" style="display: flex; gap: 5px; align-items: center;">
                                    <input type="hidden" name="action_type" value="clear_department">
                                    <input type="hidden" name="checklist_id" value="<?php echo $chk['id']; ?>">
                                    <input type="text" name="cleared_by" placeholder="Your Name" required style="width: 90px; padding: 4px;">
                                    <button type="submit" style="background-color: var(--accent-skyblue); color: white; border: none; padding: 4px 8px; border-radius: 3px; cursor: pointer; font-size: 11px;">Tick Clear</button>
                                </form>
                            <?php else: ?>
                                <span style="font-size: 11px; color: var(--text-secondary);">Completed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Final Settlement & Status Hook Panel -->
    <div class="card">
        <h2>Final Settlement Calculation &amp; Status Hook</h2>
        
        <?php if ($progressPercent < 100): ?>
            <p style="font-size: 13px; color: var(--accent-red); font-weight: bold;">
                &commat; Clearance Lock Active: Final Settlement calculations and exit finalization are locked until department clearance reaches 100%.
            </p>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="action_type" value="calculate_settlement">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
                <div class="form-group">
                    <label for="days_worked_final_month">Days Worked in Final Month</label>
                    <input type="number" step="0.5" id="days_worked_final_month" name="days_worked_final_month" value="20" required>
                </div>
                <div class="form-group">
                    <label for="daily_rate">Daily Rate (UGX)</label>
                    <input type="number" step="0.01" id="daily_rate" name="daily_rate" value="50000" required>
                </div>
                <div class="form-group">
                    <label for="severance_amount">Severance / Gratuity (UGX)</label>
                    <input type="number" step="0.01" id="severance_amount" name="severance_amount" value="0">
                </div>
                <div class="form-group">
                    <label for="loan_recovery">Pending Loan Recovery (UGX)</label>
                    <input type="number" step="0.01" id="loan_recovery" name="loan_recovery" value="0">
                </div>
                <div class="form-group">
                    <label for="asset_recovery_costs">Asset Recovery Costs (UGX)</label>
                    <input type="number" step="0.01" id="asset_recovery_costs" name="asset_recovery_costs" value="0">
                </div>
                <div class="form-group">
                    <label for="manual_adjustments">Manual Adjustments (UGX)</label>
                    <input type="number" step="0.01" id="manual_adjustments" name="manual_adjustments" value="0">
                </div>
            </div>

            <button type="submit" class="submit-btn" <?php echo $progressPercent < 100 ? 'disabled' : ''; ?>>Calculate Final Pay</button>
        </form>

        <?php if ($settlement): ?>
            <div style="margin-top: 20px; background-color: var(--bg-light); padding: 15px; border-radius: 4px; border: 1px solid var(--border-color);">
                <h3 style="margin-top: 0; font-size: 14px; color: var(--text-primary);">Settlement Summary Result</h3>
                <p style="font-size: 13px; margin: 4px 0;"><strong>Total Payable Amount:</strong> UGX <?php echo number_format($settlement['total_payable'], 2); ?></p>
                <p style="font-size: 13px; margin: 4px 0;"><strong>Payroll Push Status:</strong> <?php echo htmlspecialchars($settlement['payroll_push_status']); ?></p>

                <?php if ($exitRecord['status'] !== 'Finalized / Exited'): ?>
                    <form method="POST" style="margin-top: 15px;">
                        <input type="hidden" name="action_type" value="finalize_exit">
                        <input type="hidden" name="employee_id" value="<?php echo $exitRecord['employee_id']; ?>">
                        
                        <div style="margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" id="do_not_rehire" name="do_not_rehire" value="1" style="width: auto;">
                            <label for="do_not_rehire" style="margin-bottom: 0; font-weight: normal;">Flag as "Do Not Rehire" (Blacklist)</label>
                        </div>

                        <button type="submit" style="background-color: var(--accent-red); color: white; border: none; padding: 10px 18px; font-weight: bold; border-radius: 4px; cursor: pointer;">
                            Complete Exit &amp; Flip Status Hook (Exited)
                        </button>
                    </form>
                <?php else: ?>
                    <p style="color: var(--accent-green); font-weight: bold; margin-top: 10px;">&#10003; Exit finalized and status hook flipped successfully!</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>