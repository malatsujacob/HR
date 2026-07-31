<?php
require_once '../config/db.php';
require_once 'ess_model.php';

$essModel = new ESSModel($pdo);
session_start();

if (!isset($_SESSION['ess_emp_id'])) {
    header("Location: ess.php");
    exit;
}

$current_employee = $essModel->getEmployeeProfile($_SESSION['ess_emp_id']);
$employee_id = $_SESSION['ess_emp_id'];
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['enroll_course_id'])) {
        $stmt = $pdo->prepare("INSERT INTO employee_training_enrollments (employee_id, course_id, status) VALUES (?, ?, 'Enrolled')");
        $stmt->execute([$employee_id, $_POST['enroll_course_id']]);
        $msg = "Successfully enrolled in course. Approval request sent if budget attached.";
    } elseif (isset($_POST['complete_onboarding_task'])) {
        $stmt = $pdo->prepare("UPDATE onboarding_checklists SET status = 'Uploaded' WHERE task_id = ? AND employee_id = ?");
        $stmt->execute([$_POST['task_id'], $employee_id]);
        $msg = "Onboarding task updated successfully.";
    }
}

// Fetch available training courses
$courses = $pdo->query("SELECT * FROM training_courses ORDER BY course_title ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch my enrolled courses
$my_trainings_stmt = $pdo->prepare("
    SELECT et.*, tc.course_title, tc.category 
    FROM employee_training_enrollments et 
    JOIN training_courses tc ON et.course_id = tc.course_id 
    WHERE et.employee_id = ?
");
$my_trainings_stmt->execute([$employee_id]);
$my_trainings = $my_trainings_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch onboarding tasks
$onboarding_stmt = $pdo->prepare("SELECT * FROM onboarding_checklists WHERE employee_id = ?");
$onboarding_stmt->execute([$employee_id]);
$onboarding_tasks = $onboarding_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training & Onboarding - ESS HRMS</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #ffffff; color: #000000; margin: 0; padding: 0; }
        .container { margin-left: 260px !important; max-width: calc(100% - 280px) !important; padding: 25px; box-sizing: border-box; background: #ffffff; min-height: 100vh; }
        header { border-bottom: 2px solid #b3d1ff; padding-bottom: 15px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 30px; background: #ffffff; border: 1px solid #b3d1ff; border-radius: 4px; overflow: hidden; }
        th, td { padding: 12px 14px; text-align: left; border-bottom: 1px solid #b3d1ff; font-size: 13px; vertical-align: middle; }
        th { background: linear-gradient(180deg, #e6f2ff 0%, #cce0ff 100%); color: #0f172a; font-weight: bold; }
        tr:hover { background-color: #f8fafc; }

        .btn-primary { background: linear-gradient(135deg, #3399ff 0%, #0066cc 100%); color: #ffffff; padding: 6px 12px; border-radius: 4px; font-size: 12px; border: none; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block; }
        .btn-orange { background: linear-gradient(135deg, #ff9933 0%, #ff6600 100%); color: #ffffff; padding: 6px 12px; border-radius: 4px; font-size: 12px; border: none; cursor: pointer; font-weight: bold; }
        
        .section-title { font-size: 15px; margin-top: 25px; margin-bottom: 12px; color: #0066cc; font-weight: bold; border-left: 4px solid #ff6600; padding-left: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .nav-btn { background: #e6f2ff; color: #0066cc; padding: 8px 14px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: bold; border: 1px solid #b3d1ff; }
        .nav-btn:hover { background: #0066cc; color: #ffffff; }
    </style>
</head>
<body>

<?php 
include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php');
?>

<div class="container">
    <header>
        <div>
            <h1 style="margin: 0; font-size: 22px; color: #0f172a;">Training & Onboarding Portal</h1>
            <small style="color: #64748b;">Course Catalog, Learning & New Hire Checklists (Modules 3 & 9)</small>
        </div>
        <div>
            <a href="dashboard.php" class="nav-btn">Back to Dashboard</a>
        </div>
    </header>

    <?php if (!empty($msg)): ?>
        <div style="background: #ecfdf5; color: #047857; padding: 10px 14px; border-radius: 4px; margin-bottom: 20px; font-size: 13px; border: 1px solid #a7f3d0; font-weight: bold;"><?php echo $msg; ?></div>
    <?php endif; ?>

    <!-- 1. NEW HIRE ONBOARDING TASKS (MODULE 3) -->
    <div class="section-title" style="margin-top: 0;">New Hire Onboarding Checklist & Document Uploads</div>
    <table>
        <thead>
            <tr>
                <th>Task Description</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($onboarding_tasks) > 0): ?>
                <?php foreach ($onboarding_tasks as $ot): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($ot['task_name']); ?></strong></td>
                        <td><span style="color: #b91c1c; font-weight: bold;"><?php echo htmlspecialchars($ot['status']); ?></span></td>
                        <td>
                            <?php if ($ot['status'] === 'Pending'): ?>
                                <form method="POST">
                                    <input type="hidden" name="task_id" value="<?php echo $ot['task_id']; ?>">
                                    <button type="submit" name="complete_onboarding_task" class="btn-orange">Upload Document / E-Sign</button>
                                </form>
                            <?php else: ?>
                                <span style="color: #047857; font-weight: bold;">Completed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3" style="text-align: center; color: #64748b; padding: 20px;">No pending onboarding tasks for your account.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- 2. TRAINING CATALOG (MODULE 9) -->
    <div class="section-title">Available Training Course Catalog</div>
    <table>
        <thead>
            <tr>
                <th>Course Title</th>
                <th>Category</th>
                <th>Description</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($courses) > 0): ?>
                <?php foreach ($courses as $c): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($c['course_title']); ?></strong></td>
                        <td><?php echo htmlspecialchars($c['category']); ?></td>
                        <td><?php echo htmlspecialchars($c['description']); ?></td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="enroll_course_id" value="<?php echo $c['course_id']; ?>">
                                <button type="submit" class="btn-primary">Enroll Now</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" style="text-align: center; color: #64748b; padding: 20px;">No training courses available in catalog.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- 3. MY LEARNING & CERTIFICATES -->
    <div class="section-title">My Learning History & Certificates</div>
    <table>
        <thead>
            <tr>
                <th>Course Title</th>
                <th>Category</th>
                <th>Status</th>
                <th>Certificate</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($my_trainings) > 0): ?>
                <?php foreach ($my_trainings as $mt): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($mt['course_title']); ?></strong></td>
                        <td><?php echo htmlspecialchars($mt['category']); ?></td>
                        <td><strong><?php echo htmlspecialchars($mt['status']); ?></strong></td>
                        <td>
                            <?php if (!empty($mt['certificate_path'])): ?>
                                <a href="<?php echo htmlspecialchars($mt['certificate_path']); ?>" class="btn-primary" target="_blank">Download Certificate</a>
                            <?php else: ?>
                                <span style="color: #64748b;">Not issued yet</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" style="text-align: center; color: #64748b; padding: 20px;">You are not enrolled in any courses.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>