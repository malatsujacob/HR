<?php
// module9_training/manage_training.php
require_once '../config/db.php';
require_once 'training_model.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$trainModel = new TrainingModel($pdo);
$msg = '';

$user_role = $_SESSION['user_role'] ?? 'employee';
$logged_in_employee_id = $_SESSION['employee_id'] ?? 0;

// Handle form submissions only if user is admin
if ($user_role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_course'])) {
        $trainModel->addCourse($_POST);
        $msg = "Training session successfully designed and published.";
    } elseif (isset($_POST['enroll_employee'])) {
        $trainModel->enrollEmployee($_POST);
        $msg = "Trainee successfully enrolled into the session.";
    } elseif (isset($_POST['update_progress'])) {
        $trainModel->updateProgress($_POST);
        $msg = "Trainee evaluation updated successfully.";
    }
}

$catalog = $trainModel->getCatalog();

if ($user_role === 'admin') {
    $enrollments = $trainModel->getEnrollments();
} else {
    $enrollments = $trainModel->getEnrollmentsByEmployee($logged_in_employee_id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Management Suite - hrms</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f8fafc; color: #0f172a; margin: 0; padding: 0; }
        .container { margin-left: 260px !important; max-width: calc(100% - 280px) !important; padding: 25px; box-sizing: border-box; background: #f8fafc; min-height: 100vh; }
        header { border-bottom: 2px solid #cbd5e1; padding-bottom: 15px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        
        .brand-title { font-size: 22px; font-weight: bold; margin: 0; color: #0f172a; }
        .brand-blue { color: #0284c7; }
        .brand-red { color: #e11d48; }

        .btn-primary { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #ffffff; padding: 8px 14px; border-radius: 4px; font-size: 13px; border: none; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block; }
        .btn-orange { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #ffffff; padding: 8px 14px; border-radius: 4px; font-size: 13px; border: none; cursor: pointer; font-weight: bold; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 30px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 4px; overflow: hidden; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #cbd5e1; font-size: 13px; vertical-align: middle; color: #0f172a; }
        th { background: #f1f5f9; color: #0369a1; font-weight: bold; border-bottom: 2px solid #cbd5e1; }
        tr:hover { background-color: #f8fafc; }

        .form-section { background: #ffffff; padding: 20px; border-radius: 6px; border: 1px solid #cbd5e1; margin-top: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .form-card { background: #f8fafc; padding: 18px; border-radius: 6px; border: 1px solid #cbd5e1; margin-bottom: 20px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-size: 11px; font-weight: bold; margin-bottom: 4px; color: #475569; text-transform: uppercase; }
        input, select, textarea { padding: 7px; font-size: 12px; border: 1px solid #94a3b8; border-radius: 4px; width: 100%; box-sizing: border-box; background: #ffffff; color: #0f172a; }
        input:focus, select:focus, textarea:focus { border-color: #0284c7; outline: none; }
        
        .section-title { font-size: 15px; margin-top: 10px; margin-bottom: 12px; color: #0369a1; font-weight: bold; border-left: 4px solid #0284c7; padding-left: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
    </style>
</head>
<body>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>

<div class="container">
    <header>
        <div>
            <h1 class="brand-title">
                <span class="brand-blue">CHAP CHAP</span> <span class="brand-red">AFRICA</span> - Training Module
            </h1>
            <small style="color: #64748b;"><?php echo ($user_role === 'admin') ? 'Administrator Control Center' : 'Employee Training View'; ?></small>
        </div>
    </header>

    <?php if (!empty($msg)): ?>
        <div style="background: #f0fdf4; color: #166534; padding: 10px 14px; border-radius: 4px; margin-bottom: 20px; font-size: 13px; border: 1px solid #bbf7d0; font-weight: bold;"><?php echo $msg; ?></div>
    <?php endif; ?>

    <!-- ONLY ADMINS CAN SEE AND PROGRAM TRAINING SETTINGS -->
    <?php if ($user_role === 'admin'): ?>
    <div class="form-section" style="margin-top: 0;">
        <div class="section-title" style="border-left-color: #0284c7; margin-bottom: 20px;">HR Management Control Center</div>

        <!-- 1. Design & Schedule Training Session -->
        <div class="form-card">
            <h4 style="margin-top: 0; font-size: 13px; color: #0f172a; margin-bottom: 10px; text-transform: uppercase;">1. Design & Publish Training Session</h4>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Course Name</label>
                        <input type="text" name="course_name" placeholder="Workplace Safety & Compliance" required>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <input type="text" name="category" placeholder="Health & Safety" required>
                    </div>
                    <div class="form-group">
                        <label>Venue / Link</label>
                        <input type="text" name="venue_location" placeholder="Main Boardroom / Zoom Link" required>
                    </div>
                    <div class="form-group">
                        <label>Provider / Trainer</label>
                        <input type="text" name="trainer_provider" placeholder="Internal HR / Safety Corp" required>
                    </div>
                    <div class="form-group">
                        <label>Start Time</label>
                        <input type="datetime-local" name="start_time" required>
                    </div>
                    <div class="form-group">
                        <label>End Time</label>
                        <input type="datetime-local" name="end_time" required>
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label>Session Description</label>
                        <textarea name="description" rows="2" placeholder="Brief outline of the training session..."></textarea>
                    </div>
                </div>
                <button type="submit" name="add_course" class="btn-orange" style="margin-top: 12px;">Publish Training Session</button>
            </form>
        </div>

        <!-- 2. Manual Trainee Assignment -->
        <div class="form-card">
            <h4 style="margin-top: 0; font-size: 13px; color: #0f172a; margin-bottom: 10px; text-transform: uppercase;">2. Enroll Trainee (Manual Entry)</h4>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Training ID</label>
                        <input type="number" name="training_id" placeholder="Course ID" required>
                    </div>
                    <div class="form-group">
                        <label>Employee ID</label>
                        <input type="number" name="employee_id" placeholder="Employee ID" required>
                    </div>
                </div>
                <button type="submit" name="enroll_employee" class="btn-primary" style="margin-top: 12px;">Enroll Trainee</button>
            </form>
        </div>

        <!-- 3. Record Evaluation / Progress -->
        <div class="form-card" style="margin-bottom: 0;">
            <h4 style="margin-top: 0; font-size: 13px; color: #0f172a; margin-bottom: 10px; text-transform: uppercase;">3. Record Trainee Evaluation & Score</h4>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Enrollment ID</label>
                        <input type="number" name="enrollment_id" placeholder="Enrollment ID" required>
                    </div>
                    <div class="form-group">
                        <label>Completion Status</label>
                        <input type="text" name="completion_status" placeholder="Completed" required>
                    </div>
                    <div class="form-group">
                        <label>Score Result</label>
                        <input type="text" name="score_result" placeholder="95% or Pass" required>
                    </div>
                </div>
                <button type="submit" name="update_progress" class="btn-orange" style="margin-top: 12px;">Save Evaluation</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- VIEWABLE BY BOTH EMPLOYEES AND ADMINS (EMPLOYEES ONLY SEE THEIR OWN ASSIGNMENTS) -->
    <div class="section-title" style="margin-top: 30px;">Assigned Training & Compliance Record</div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Course / Session</th>
                <th>Venue / Link</th>
                <?php if ($user_role === 'admin'): ?><th>Employee ID</th><?php endif; ?>
                <th>Schedule Window</th>
                <th>Status</th>
                <th>Score</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($enrollments) > 0): ?>
                <?php foreach ($enrollments as $enr): ?>
                    <tr>
                        <td><strong>#<?php echo $enr['enrollment_id']; ?></strong></td>
                        <td><?php echo htmlspecialchars($enr['course_name']); ?></td>
                        <td><?php echo htmlspecialchars($enr['venue_location']); ?></td>
                        <?php if ($user_role === 'admin'): ?><td>#<?php echo $enr['employee_id']; ?></td><?php endif; ?>
                        <td><small><?php echo htmlspecialchars($enr['start_time']); ?></small></td>
                        <td><span style="color: #0284c7; font-weight: bold;"><?php echo htmlspecialchars($enr['completion_status']); ?></span></td>
                        <td><strong><?php echo htmlspecialchars($enr['score_result'] ?? 'Pending'); ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?php echo ($user_role === 'admin') ? '7' : '6'; ?>" style="text-align: center; color: #64748b; padding: 20px;">No training assignments found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- PUBLISHED CATALOG FOR EMPLOYEES TO VIEW -->
    <div class="section-title" style="margin-top: 30px;">Published Training Catalogs</div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Course Name</th>
                <th>Category</th>
                <th>Venue / Link</th>
                <th>Provider</th>
                <th>Schedule Window</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($catalog) > 0): ?>
                <?php foreach ($catalog as $cat): ?>
                    <tr>
                        <td><strong>#<?php echo $cat['training_id']; ?></strong></td>
                        <td><?php echo htmlspecialchars($cat['course_name']); ?></td>
                        <td><?php echo htmlspecialchars($cat['category']); ?></td>
                        <td><?php echo htmlspecialchars($cat['venue_location']); ?></td>
                        <td><?php echo htmlspecialchars($cat['trainer_provider']); ?></td>
                        <td><small><?php echo htmlspecialchars($cat['start_time']); ?> to <?php echo htmlspecialchars($cat['end_time']); ?></small></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: #64748b; padding: 20px;">No training programs available at the moment.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</div>

</body>
</html>