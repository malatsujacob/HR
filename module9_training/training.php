<?php
// module9_training/training.php
require_once '../config/db.php';
require_once 'training_model.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$trainModel = new TrainingModel($pdo);

$user_role = $_SESSION['user_role'] ?? 'employee';
$logged_in_employee_id = $_SESSION['employee_id'] ?? 0;

$catalog = $trainModel->getCatalog();

// If admin visits training.php, they can view all, otherwise employee sees only their assigned sessions
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
    <title>Training & Development - HRMS</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #0f172a; color: #f8fafc; margin: 0; padding: 0; }
        .container { margin-left: 260px !important; max-width: calc(100% - 280px) !important; padding: 25px; box-sizing: border-box; background: #0f172a; min-height: 100vh; }
        header { border-bottom: 2px solid #334155; padding-bottom: 15px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        
        .brand-title { font-size: 22px; font-weight: bold; margin: 0; color: #f8fafc; }
        .brand-blue { color: #38bdf8; }
        .brand-red { color: #f43f5e; }

        .btn-manage { background: linear-gradient(135deg, #38bdf8 0%, #0284c7 100%); color: #ffffff; padding: 8px 14px; border-radius: 4px; font-size: 13px; text-decoration: none; font-weight: bold; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 30px; background: #1e293b; border: 1px solid #334155; border-radius: 4px; overflow: hidden; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #334155; font-size: 13px; vertical-align: middle; color: #f8fafc; }
        th { background: #0f172a; color: #38bdf8; font-weight: bold; border-bottom: 2px solid #334155; }
        tr:hover { background-color: #334155; }

        .section-title { font-size: 15px; margin-top: 10px; margin-bottom: 12px; color: #38bdf8; font-weight: bold; border-left: 4px solid #f97316; padding-left: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
    </style>
</head>
<body>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>

<div class="container">
    <header>
        <div>
            <h1 class="brand-title">
                <span class="brand-blue">CHAP CHAP</span> <span class="brand-red">AFRICA</span> - Training & Development
            </h1>
            <small style="color: #94a3b8;">
                <?php echo ($user_role === 'admin') ? 'Master Overview of Training Programs & Compliance' : 'My Assigned Training Sessions & Compliance Status'; ?>
            </small>
        </div>
        <?php if ($user_role === 'admin'): ?>
            <div>
                <a href="manage_training.php" class="btn-manage">⚙️ HR Management Suite</a>
            </div>
        <?php endif; ?>
    </header>

    <!-- My Assignments / Enrollments Matrix -->
    <div class="section-title">My Assigned Training & Compliance Record</div>
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
                        <td><span style="color: #38bdf8; font-weight: bold;"><?php echo htmlspecialchars($enr['completion_status']); ?></span></td>
                        <td><strong><?php echo htmlspecialchars($enr['score_result'] ?? 'Pending'); ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?php echo ($user_role === 'admin') ? '7' : '6'; ?>" style="text-align: center; color: #94a3b8; padding: 20px;">No training assignments found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Available Training Catalogs Set by HR -->
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
                    <td colspan="6" style="text-align: center; color: #94a3b8; padding: 20px;">No training programs available at the moment.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</div>

</body>
</html>