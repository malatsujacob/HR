<?php
// module12_disciplinary/create.php - Report Incident (Manual Entry)
require_once '../config/db.php';

$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $case_title = trim($_POST['case_title'] ?? '');
    $case_type = trim($_POST['case_type'] ?? '');
    $accused_input = trim($_POST['accused_employee'] ?? '');
    $incident_date = trim($_POST['incident_date'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $detailed_description = trim($_POST['detailed_description'] ?? '');
    $is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;

    if (empty($case_title) || empty($case_type) || empty($accused_input) || empty($incident_date) || empty($detailed_description)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            // Resolve manual text input (Name or ID) to the correct numeric employee_id
            $emp_id = null;
            if (is_numeric($accused_input)) {
                $chk = $pdo->prepare("SELECT employee_id FROM employees WHERE employee_id = ?");
                $chk->execute([$accused_input]);
                $emp_id = $chk->fetchColumn();
            }

            if (!$emp_id) {
                // Try matching by first name, last name, or combined name string
                $name_parts = explode(' ', $accused_input);
                if (count($name_parts) >= 2) {
                    $stmt_emp = $pdo->prepare("SELECT employee_id FROM employees WHERE LOWER(first_name) = LOWER(?) AND LOWER(last_name) = LOWER(?)");
                    $stmt_emp->execute([$name_parts[0], $name_parts[1]]);
                    $emp_id = $stmt_emp->fetchColumn();
                }
                
                if (!$emp_id) {
                    $stmt_emp = $pdo->prepare("SELECT employee_id FROM employees WHERE LOWER(first_name) LIKE LOWER(?) OR LOWER(last_name) LIKE LOWER(?)");
                    $stmt_emp->execute(["%$accused_input%", "%$accused_input%"]);
                    $emp_id = $stmt_emp->fetchColumn();
                }
            }

            if (!$emp_id) {
                throw new Exception("Could not find an active employee matching '" . htmlspecialchars($accused_input) . "'. Please check the name.");
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO disciplinary_cases (case_title, case_type, accused_employee_id, incident_date, location, detailed_description, is_anonymous, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Investigation Ongoing')");
            $stmt->execute([$case_title, $case_type, $emp_id, $incident_date, $location, $detailed_description, $is_anonymous]);
            $case_id = $pdo->lastInsertId();

            $logStmt = $pdo->prepare("INSERT INTO case_timeline_logs (case_id, actor_user_id, action_performed, notes) VALUES (?, 1, 'Incident Logged', ?)");
            $logStmt->execute([$case_id, "Case reported manually for employee ID: " . $emp_id]);

            $pdo->commit();
            $message = "Disciplinary incident successfully reported and logged.";
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Error saving incident: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Incident | Chap Chap Africa HRMS</title>
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
            height: 120px;
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
    </style>
</head>
<body>
<?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>

    <div class="header-container" style="margin-left: 280px;">
        <h1><span class="skyblue">CHAP CHAP AFRICA</span> | <span class="hrms-brand">HRMS</span></h1>
        <div>
            <span style="font-size: 12px; color: var(--text-secondary);">Module 12: Report Incident</span>
        </div>
    </div>

    <div class="form-container">
        <a href="index.php" class="back-link">&larr; Back to Dashboard Overview</a>
        <h2 style="font-size: 16px; margin-top: 0; color: var(--text-primary); border-left: 3px solid var(--accent-skyblue); padding-left: 8px;">Log Disciplinary Incident</h2>

        <?php if (!empty($message)): ?>
            <div class="alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="case_title">Case Title *</label>
                <input type="text" id="case_title" name="case_title" required>
            </div>

            <div class="form-group">
                <label for="case_type">Violation Category *</label>
                <input type="text" id="case_type" name="case_type" placeholder="e.g., Attendance, Insubordination, Conduct" required>
            </div>

            <div class="form-group">
                <label for="accused_employee">Accused Employee Name *</label>
                <input type="text" id="accused_employee" name="accused_employee" placeholder="Enter name manually (e.g. Malatsu Jacob)" required>
            </div>

            <div class="form-group">
                <label for="incident_date">Incident Date *</label>
                <input type="date" id="incident_date" name="incident_date" required>
            </div>

            <div class="form-group">
                <label for="location">Location</label>
                <input type="text" id="location" name="location" placeholder="e.g., Kampala Branch">
            </div>

            <div class="form-group">
                <label for="detailed_description">Detailed Description *</label>
                <textarea id="detailed_description" name="detailed_description" required></textarea>
            </div>

            <div class="form-group" style="display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" id="is_anonymous" name="is_anonymous" value="1" style="width: auto;">
                <label for="is_anonymous" style="margin-bottom: 0; font-weight: normal;">Submit Anonymously (Hide reporter details)</label>
            </div>

            <button type="submit" class="submit-btn">Submit Case</button>
        </form>
    </div>

</body>
</html>