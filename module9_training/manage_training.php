<?php
// module9_training/manage_training.php
require_once '../config/db.php';
require_once 'training_model.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$success_msg = '';
$error_msg = '';

// Ensure default HR password table exists
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS hr_settings (
            id INT PRIMARY KEY,
            hr_password VARCHAR(255) NOT NULL
        )
    ");
    $pdo->exec("
        INSERT INTO hr_settings (id, hr_password)
        SELECT 1, '1234'
        WHERE NOT EXISTS (SELECT 1 FROM hr_settings WHERE id = 1)
    ");
} catch (Exception $e) {}

// Handle HR Login for Training Module
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hr_login'])) {
    $entered_pass = trim($_POST['hr_password_input'] ?? '');
    $stmt = $pdo->query("SELECT hr_password FROM hr_settings WHERE id = 1");
    $row_settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row_settings && $entered_pass === $row_settings['hr_password']) {
        $_SESSION['training_hr_logged_in'] = true;
        $_SESSION['show_training_login_form'] = false;
        $success_msg = "Authenticated successfully!";
    } else {
        $_SESSION['show_training_login_form'] = true;
        $error_msg = "Incorrect password.";
    }
}

// Handle HR Logout for Training Module
if (isset($_GET['logout_hr'])) {
    unset($_SESSION['training_hr_logged_in']);
    unset($_SESSION['show_training_login_form']);
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

// Toggle Inline Login Prompt
if (isset($_GET['toggle_login_form'])) {
    $_SESSION['show_training_login_form'] = !($_SESSION['show_training_login_form'] ?? false);
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

$is_hr_logged = $_SESSION['training_hr_logged_in'] ?? false;
$show_login_form = $_SESSION['show_training_login_form'] ?? false;

$trainModel = new TrainingModel($pdo);
$msg = '';
$employeeOptions = $pdo->query("SELECT employee_id, first_name, last_name FROM employees ORDER BY first_name, last_name")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_hr_logged) {
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
        .box-container { 
            background: #eff6ff; 
            border: 1px solid #bfdbfe; 
            border-radius: 6px; 
            padding: 16px; 
            margin-top: 15px;
            max-width: 400px;
            box-sizing: border-box;
        }
        .btn-purple { 
            background: #7c3aed; 
            color: white; 
            padding: 6px 12px; 
            border-radius: 4px; 
            font-size: 11px; 
            font-weight: 900; 
            border: none; 
            cursor: pointer; 
            text-transform: uppercase;
        }
        .btn-red { 
            background: #dc2626; 
            color: white; 
            padding: 5px 10px; 
            border-radius: 4px; 
            font-size: 10px; 
            font-weight: 900; 
            text-decoration: none; 
            border: none; 
            cursor: pointer; 
            text-transform: uppercase;
        }
        .btn-link-action { 
            background: none; 
            border: none; 
            padding: 0; 
            font-size: 11px; 
            font-weight: 900; 
            color: #1e293b; 
            cursor: pointer; 
            text-align: left; 
            width: 100%; 
            display: block; 
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .btn-link-action:hover { color: #2563eb; }
        .login-input { 
            font-size: 11px; 
            padding: 7px 10px; 
            width: 200px; 
            box-sizing: border-box; 
            border: 1px solid #bfdbfe; 
            border-radius: 4px; 
            background: #fff; 
            color: #1e293b; 
        }
        .alert-error { 
            background: #fee2e2; 
            color: #991b1b; 
            padding: 8px; 
            border-radius: 4px; 
            margin-bottom: 12px; 
            font-size: 11px; 
            font-weight: 900; 
            text-transform: uppercase; 
            border: 1px solid #fecaca;
        }
        .alert-success { 
            background: #dcfce7; 
            color: #166534; 
            padding: 8px; 
            border-radius: 4px; 
            margin-bottom: 12px; 
            font-size: 11px; 
            font-weight: 900; 
            text-transform: uppercase; 
            border: 1px solid #bbf7d0; 
        }
    </style>
</head>
<body>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>

<div class="container">
    <header>
        <h1><span class="brand-title">CHAP CHAP</span> <span class="brand-title" style="color: #3b82f6;">AFRICA</span> - HR Training Management</h1>
        <div>
            <?php if ($is_hr_logged): ?>
                <a href="?logout_hr=1" class="btn-red">Logout Module</a>
            <?php endif; ?>
        </div>
    </header>

    <?php if (!empty($success_msg)): ?>
        <div class="alert-success" style="max-width: 400px;"><?php echo $success_msg; ?></div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div class="alert-error" style="max-width: 400px;"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <?php if ($msg): ?>
        <div class="notice"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <?php if (!$is_hr_logged): ?>
        <div class="box-container">
            <?php if (!$show_login_form): ?>
                <form method="GET" style="margin: 0;">
                    <input type="hidden" name="toggle_login_form" value="1">
                    <button type="submit" class="btn-link-action">
                        🔒 Training Management
                    </button>
                </form>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <form method="GET" style="margin: 0;">
                        <input type="hidden" name="toggle_login_form" value="1">
                        <button type="submit" class="btn-link-action">
                            🔒 Training Management
                        </button>
                    </form>
                    
                    <form method="POST" style="display: flex; gap: 8px; align-items: center; margin: 0; padding-top: 5px; border-top: 1px solid #bfdbfe;">
                        <input type="password" name="hr_password_input" class="login-input" placeholder="Enter password" required autofocus>
                        <button type="submit" name="hr_login" class="btn-purple">Login</button>
                        <a href="?toggle_login_form=1" style="font-size: 11px; color: #64748b; text-decoration: none; padding: 6px 10px; font-weight: 900; text-transform: uppercase;">Cancel</a>
                    </form>
                </div>
            <?php endif; ?>
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