<?php
// module12_disciplinary/defense.php - Employee Self-Service Defense & Response Portal
require_once '../config/db.php';

$case_id = intval($_GET['id'] ?? 0);
$message = "";
$error = "";

if ($case_id <= 0) {
    die("Invalid Case ID specified.");
}

// Handle Defense Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_statement = trim($_POST['employee_statement'] ?? '');
    $employee_id = intval($_POST['employee_id'] ?? 0);

    if (empty($employee_statement) || $employee_id <= 0) {
        $error = "Please provide your Employee ID and your written defense statement.";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Update status to Awaiting Decision or update confidential notes / defense log
            $updateStmt = $pdo->prepare("UPDATE disciplinary_cases SET status = 'Awaiting Decision', updated_at = CURRENT_TIMESTAMP WHERE case_id = ?");
            $updateStmt->execute([$case_id]);

            // 2. Log defense submission in audit timeline
            $logStmt = $pdo->prepare("INSERT INTO case_timeline_logs (case_id, actor_user_id, action_performed, notes) VALUES (?, ?, 'Defense Submitted', ?)");
            $logStmt->execute([$case_id, $employee_id, "Employee submitted written statement: " . substr($employee_statement, 0, 80) . "..."]);

            // 3. Optional: Insert into grievance_tickets or update confidential notes if needed
            $noteStmt = $pdo->prepare("UPDATE grievance_tickets SET confidential_notes = CONCAT(COALESCE(confidential_notes, ''), '\n[Defense Statement]: ', ?) WHERE case_id = ?");
            // If grievance table doesn't have a row yet, we can insert or catch gracefully
            try {
                $noteStmt->execute([$employee_statement, $case_id]);
            } catch (Exception $ex) {
                // Ignore if not a grievance ticket, timeline log is sufficient
            }

            $pdo->commit();
            $message = "Your defense statement has been successfully submitted to HR.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error submitting defense: " . $e->getMessage();
        }
    }
}

// Fetch Case Summary (Masking reporter if anonymous)
$stmt = $pdo->prepare("SELECT * FROM disciplinary_cases WHERE case_id = ?");
$stmt->execute([$case_id]);
$case = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$case) {
    die("Case not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Defense Portal #<?php echo $case_id; ?> | Chap Chap Africa HRMS</title>
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

        input[type="number"], textarea {
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
            height: 140px;
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

        .case-summary-box {
            background-color: var(--bg-light);
            border: 1px solid var(--border-color);
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .case-summary-box p {
            margin: 4px 0;
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
    </style>
</head>
<body>
<?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>

    <div class="header-container" style="margin-left: 280px;">
        <h1><span class="skyblue">CHAP CHAP AFRICA</span> | <span class="hrms-brand">HRMS</span></h1>
        <div>
            <span style="font-size: 12px; color: var(--text-secondary);">Module 12: Employee Defense Portal</span>
        </div>
    </div>

    <div class="form-container">
        <a href="index.php" class="back-link">&larr; Back to Dashboard Overview</a>
        <h2 style="font-size: 16px; margin-top: 0; color: var(--text-primary); border-left: 3px solid var(--accent-skyblue); padding-left: 8px;">Submit Defense Statement</h2>

        <div class="case-summary-box">
            <strong style="color: var(--text-primary);">Allegation Summary:</strong>
            <p><strong>Title:</strong> <?php echo htmlspecialchars($case['case_title']); ?></p>
            <p><strong>Violation Type:</strong> <?php echo htmlspecialchars($case['case_type']); ?></p>
            <p><strong>Incident Date:</strong> <?php echo htmlspecialchars($case['incident_date']); ?> at <?php echo htmlspecialchars($case['location']); ?></p>
            <p><strong>Details:</strong> <?php echo nl2br(htmlspecialchars($case['detailed_description'])); ?></p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="employee_id">Your Employee ID *</label>
                <input type="number" id="employee_id" name="employee_id" placeholder="Confirm your ID (e.g., <?php echo $case['accused_employee_id']; ?>)" required>
            </div>

            <div class="form-group">
                <label for="employee_statement">Your Written Defense / Explanation *</label>
                <textarea id="employee_statement" name="employee_statement" placeholder="Provide your account, counter-evidence, or mitigating factors..." required></textarea>
            </div>

            <button type="submit" class="submit-btn">Submit Defense to HR Panel</button>
        </form>
    </div>

</body>
</html>