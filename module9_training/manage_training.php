<?php
// module9_training/manage_training.php
require_once '../config/db.php';
require_once 'training_model.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userRole = strtolower(trim($_SESSION['user_role'] ?? ''));
$allowedRoles = ['admin', 'hr'];
$isHRAdmin = in_array($userRole, $allowedRoles, true);

$accessDenied = false;
if (!$isHRAdmin) {
    $accessDenied = true;
}

$trainModel = new TrainingModel($pdo);
$msg = '';
$employeeOptions = $pdo->query("SELECT employee_id, first_name, last_name FROM employees ORDER BY first_name, last_name")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_course'])) {
        $trainModel->addCourse($_POST);
        $msg = 'Training session successfully designed and published.';
    } elseif (isset($_POST['enroll_employee'])) {
        if (!$trainModel->trainingExists((int)($_POST['training_id'] ?? 0))) {
            $msg = 'Invalid training session selected. Please choose an existing training session.';
        } else {
            $trainModel->enrollEmployee($_POST);
            $msg = 'Trainee successfully enrolled into the session.';
        }
    } elseif (isset($_POST['update_progress'])) {
        $trainModel->updateProgress($_POST);
        $msg = 'Training progress record saved successfully.';
    }
}

$catalog = $trainModel->getCatalog();
$enrollments = $trainModel->getEnrollments();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Training Management - HRMS</title>
    <style>
        body { background: #ffffff; color: #1e293b; margin: 0; font-family: Arial, sans-serif; }
        .container { margin-left: 260px; max-width: calc(100% - 260px); padding: 20px; box-sizing: border-box; background: #ffffff; min-height: 100vh; }
        header { border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        header h1 { font-size: 18px; font-weight: 900; margin: 0; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px; }
        .brand-title { color: #2563eb; font-weight: 900; }
        .card { background: #eff6ff; padding: 16px; border-radius: 6px; border: 1px solid #bfdbfe; margin-bottom: 20px; }
        .card h2 { font-size: 13px; margin-top: 0; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 900; border-left: 3px solid #2563eb; padding-left: 8px; margin-bottom: 12px; }
        .form-row { display: grid; gap: 12px; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: 12px; }
        label { display: block; font-size: 11px; font-weight: 900; margin-bottom: 4px; color: #1e293b; text-transform: uppercase; }
        input, textarea, select { width: 100%; padding: 8px 10px; border: 1px solid #bfdbfe; border-radius: 4px; background: #ffffff; font-size: 11px; box-sizing: border-box; color: #1e293b; }
        textarea { resize: vertical; }
        .btn { display: inline-block; background: #2563eb; color: #ffffff; padding: 8px 14px; border-radius: 4px; text-decoration: none; font-weight: 900; font-size: 11px; text-transform: uppercase; border: none; cursor: pointer; }
        .btn:hover { background: #1d4ed8; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; background: #ffffff; border: 1px solid #bfdbfe; border-radius: 4px; overflow: hidden; }
        th, td { padding: 8px 10px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 11px; }
        th { background: #eff6ff; color: #1e293b; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px; }
        tr:hover { background-color: #f8fafc; }
        .notice { background: #dbeafe; border: 1px solid #bfdbfe; padding: 10px 14px; border-radius: 4px; color: #1e40af; margin-bottom: 15px; font-size: 11px; font-weight: 900; text-transform: uppercase; }
    </style>
</head>
<body>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>

<div class="container">
    <header>
        <h1><span class="brand-title">CHAP CHAP</span> <span class="brand-title" style="color: #3b82f6;">AFRICA</span> - HR Training Management</h1>
    </header>

    <?php if ($msg): ?>
        <div class="notice"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <?php if (!$isHRAdmin): ?>
        <div class="card">
            <h2>HR Access Required</h2>
            <p style="font-size: 11px; color: #64748b; font-weight: 900; text-transform: uppercase; margin-bottom: 12px;">You are currently not logged in as HR or Admin. To use the training setup tools, please log in as HR.</p>
            <p><a href="hr_login.php" class="btn">Login as HR</a></p>
            <p style="margin-top: 12px; font-size: 11px; color: #64748b; font-weight: 900; text-transform: uppercase;">If you only need to view scheduled trainings, use the Training Schedule page.</p>
        </div>
    <?php else: ?>
        <div class="card">
            <h2>Create Training Session</h2>
            <form method="POST">
                <div class="form-row">
                    <div>
                        <label>Course Name</label>
                        <input type="text" name="course_name" required>
                    </div>
                    <div>
                        <label>Category</label>
                        <input type="text" name="category" required>
                    </div>
                </div>
                <div class="form-row">
                    <div>
                        <label>Venue / Link</label>
                        <input type="text" name="venue_location" required>
                    </div>
                    <div>
                        <label>Trainer / Provider</label>
                        <input type="text" name="trainer_provider" required>
                    </div>
                </div>
                <div class="form-row">
                    <div>
                        <label>Start Time</label>
                        <input type="datetime-local" name="start_time" required>
                    </div>
                    <div>
                        <label>End Time</label>
                        <input type="datetime-local" name="end_time" required>
                    </div>
                </div>
                <div class="form-row">
                    <div style="grid-column: span 2;">
                        <label>Description</label>
                        <textarea name="description" rows="2"></textarea>
                    </div>
                </div>
                <div class="form-row">
                    <div>
                        <label>Department (optional)</label>
                        <input type="text" name="department">
                    </div>
                    <div>
                        <label>Score Tracking</label>
                        <select name="score_tracking">
                            <option value="0">Disabled</option>
                            <option value="1">Enabled</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="add_course" class="btn">Publish Session</button>
            </form>
        </div>

        <div class="card">
            <h2>Enroll Employee</h2>
            <form method="POST">
                <div class="form-row">
                    <div>
                        <label>Select Training Session</label>
                        <select name="training_id" required>
                            <option value="">Choose a training</option>
                            <?php foreach ($catalog as $course): ?>
                                <option value="<?php echo htmlspecialchars($course['training_id']); ?>"><?php echo htmlspecialchars($course['course_name'] . ' - ' . $course['category']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Employee Name</label>
                        <select name="employee_id[]" multiple size="4" required>
                            <?php foreach ($employeeOptions as $employee): ?>
                                <option value="<?php echo htmlspecialchars($employee['employee_id']); ?>"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color:#64748b; font-size: 10px; font-weight: 900; text-transform: uppercase; display: block; margin-top: 4px;">Hold Ctrl (Cmd on Mac) to select multiple employees.</small>
                    </div>
                </div>
                <div class="form-row">
                    <div>
                        <label>Nomination Type</label>
                        <select name="nomination_type">
                            <option value="Manual">Manual</option>
                            <option value="Department">Department</option>
                            <option value="Specific">Specific</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="enroll_employee" class="btn">Enroll</button>
            </form>
        </div>

        <div class="card">
            <h2>Update Training Progress</h2>
            <form method="POST">
                <div class="form-row">
                    <div>
                        <label>Enrollment ID</label>
                        <input type="number" name="enrollment_id" required>
                    </div>
                    <div>
                        <label>Completion Status</label>
                        <input type="text" name="completion_status" required>
                    </div>
                    <div>
                        <label>Score Result</label>
                        <input type="text" name="score_result" required>
                    </div>
                </div>
                <button type="submit" name="update_progress" class="btn">Save Progress</button>
            </form>
        </div>

        <div class="card">
            <h2>Current Enrollments</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Course</th>
                        <th>Employee</th>
                        <th>Status</th>
                        <th>Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($enrollments) > 0): ?>
                        <?php foreach ($enrollments as $enrollment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($enrollment['enrollment_id']); ?></td>
                                <td><?php echo htmlspecialchars($enrollment['course_name']); ?></td>
                                <td><?php echo htmlspecialchars($enrollment['first_name'] . ' ' . $enrollment['last_name']); ?></td>
                                <td><strong><?php echo htmlspecialchars($enrollment['completion_status']); ?></strong></td>
                                <td><?php echo htmlspecialchars($enrollment['score_result'] ?? 'Pending'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center; color:#64748b; font-weight: 900; text-transform: uppercase;">No training enrollments found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

</body>
</html>