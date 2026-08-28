<?php
// module13_offboarding/initiate.php - Initiate Exit & Clearance (Manual Entry)
require_once '../config/db.php';

$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = trim($_POST['employee_id'] ?? '');
    $last_working_day = trim($_POST['last_working_day'] ?? '');
    $exit_reason = trim($_POST['exit_reason'] ?? '');
    $exit_interview_reason = trim($_POST['exit_interview_reason'] ?? '');
    $exit_interview_text = trim($_POST['exit_interview_text'] ?? '');
    $recommend_toggle = isset($_POST['recommend_toggle']) ? 1 : 0;

    if (empty($employee_id) || empty($last_working_day) || empty($exit_reason)) {
        $error = "Please fill in all mandatory fields.";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Insert Exit Request (Layer 1 Reason captured)
            $stmt = $pdo->prepare("INSERT INTO exit_requests (employee_id, last_working_day, exit_reason, exit_interview_reason, exit_interview_text, recommend_toggle, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending Clearance') RETURNING id");
            $stmt->execute([$employee_id, $last_working_day, $exit_reason, $exit_interview_reason, $exit_interview_text, $recommend_toggle]);
            $exit_id = $stmt->fetchColumn();

            // 2. Generate Department Clearance Checklist Items
            $departments = [
                ['IT', 'Return company laptop, access card, and revoke email access'],
                ['Finance', 'Clear outstanding loans, salary advances, and travel imprest'],
                ['HR', 'Complete exit interview and sign NDA/handover notes'],
                ['Facilities', 'Return office keys, parking sticker, and desk space clearance']
            ];

            foreach ($departments as $dept) {
                $chkStmt = $pdo->prepare("INSERT INTO clearance_checklist (exit_request_id, department_name, item_description, is_cleared) VALUES (?, ?, ?, FALSE)");
                $chkStmt->execute([$exit_id, $dept[0], $dept[1]]);
            }

            // 3. Update Module 1 Status Hook marker to Pending Clearance
            $hookStmt = $pdo->prepare("UPDATE employees SET status = 'Exiting / Pending Clearance' WHERE employee_id = ?");
            $hookStmt->execute([intval($employee_id)]);

            $pdo->commit();
            $message = "Exit request successfully initiated and department clearance checklists generated.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error initiating exit: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initiate Exit | Chap Chap Africa HRMS</title>
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

        .form-container {
            background-color: var(--surface-white);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 20px;
            max-width: 650px;
            margin: 0 auto;
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
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

        input[type="text"], input[type="date"], textarea {
            width: 100%;
            padding: 8px;
            font-size: 13px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            box-sizing: border-box;
            outline: none;
        }

        input:focus, textarea:focus {
            border-color: var(--accent-skyblue);
        }

        textarea {
            resize: vertical;
            height: 100px;
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
                <span style="font-size: 13px; font-weight: bold; color: var(--text-secondary);">Module 13: Initiate Exit</span>
            </div>
        </div>

        <div class="form-container">
        <a href="index.php" class="back-link">&larr; Back to Exit Dashboard</a>
        <h2 style="font-size: 16px; margin-top: 0; color: var(--text-primary); border-left: 3px solid var(--accent-skyblue); padding-left: 8px;">Initiate Employee Exit & Clearance</h2>

        <?php if (!empty($message)): ?>
            <div class="alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="employee_id">Employee Name or ID *</label>
                <input type="text" id="employee_id" name="employee_id" placeholder="Enter employee name or ID code" required>
            </div>

            <div class="form-group">
                <label for="last_working_day">Last Working Day *</label>
                <input type="date" id="last_working_day" name="last_working_day" required>
            </div>

            <div class="form-group">
                <label for="exit_reason">Exit Reason Category (Layer 1) *</label>
                <input type="text" id="exit_reason" name="exit_reason" placeholder="e.g., Resignation (Voluntary), Retirement, Termination" required>
            </div>

            <div class="form-group">
                <label for="exit_interview_reason">Exit Interview Primary Reason (Layer 2)</label>
                <input type="text" id="exit_interview_reason" name="exit_interview_reason" placeholder="e.g., Better Pay, Career Growth, Relocation">
            </div>

            <div class="form-group">
                <label for="exit_interview_text">Open Feedback & Qualitative Notes</label>
                <textarea id="exit_interview_text" name="exit_interview_text" placeholder="Enter detailed interview feedback..."></textarea>
            </div>

            <div class="form-group" style="display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" id="recommend_toggle" name="recommend_toggle" value="1" checked style="width: auto;">
                <label for="recommend_toggle" style="margin-bottom: 0; font-weight: normal;">Recommend company to others (Yes)</label>
            </div>

            <button type="submit" class="submit-btn">Initiate Exit & Generate Checklists</button>
        </form>
    </div>

</body>
</html>