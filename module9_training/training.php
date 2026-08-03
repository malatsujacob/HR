<?php
// module9_training/training.php
require_once '../config/db.php';
require_once 'training_model.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$employeeId = $_SESSION['employee_id'] ?? null;

$trainModel = new TrainingModel($pdo);
$catalog = $trainModel->getCatalog();
$enrollments = $employeeId ? $trainModel->getEnrollmentsByEmployee($employeeId) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Schedule - HRMS</title>
    <style>
        body { background: #ffffff; color: #1e293b; margin: 0; font-family: Arial, sans-serif; }
        .container { margin-left: 260px; max-width: calc(100% - 260px); padding: 20px; box-sizing: border-box; background: #ffffff; min-height: 100vh; }
        header { border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        header h1 { font-size: 18px; font-weight: 900; margin: 0; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px; }
        .brand-title { color: #2563eb; font-weight: 900; }
        .card { background: #eff6ff; padding: 16px; border-radius: 6px; border: 1px solid #bfdbfe; margin-bottom: 20px; }
        .card h2 { font-size: 13px; margin-top: 0; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 900; border-left: 3px solid #2563eb; padding-left: 8px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; background: #ffffff; border: 1px solid #bfdbfe; border-radius: 4px; overflow: hidden; }
        th, td { padding: 8px 10px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 11px; }
        th { background: #eff6ff; color: #1e293b; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px; }
        tr:hover { background-color: #f8fafc; }
    </style>
</head>
<body>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>

<div class="container">
    <header>
        <h1><span class="brand-title">CHAP CHAP</span> <span class="brand-title" style="color: #3b82f6;">AFRICA</span> - Training Schedule</h1>
    </header>

    <div class="card">
        <h2>Assigned Training Sessions</h2>
        <?php if ($employeeId): ?>
            <table>
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Venue</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Status</th>
                        <th>Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($enrollments) > 0): ?>
                        <?php foreach ($enrollments as $enrollment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($enrollment['course_name']); ?></td>
                                <td><?php echo htmlspecialchars($enrollment['venue_location']); ?></td>
                                <td><?php echo htmlspecialchars($enrollment['start_time']); ?></td>
                                <td><?php echo htmlspecialchars($enrollment['end_time']); ?></td>
                                <td><strong><?php echo htmlspecialchars($enrollment['completion_status']); ?></strong></td>
                                <td><?php echo htmlspecialchars($enrollment['score_result'] ?? 'Pending'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #64748b; font-weight: 900; text-transform: uppercase;">No assigned training found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="color: #64748b; font-size: 11px; font-weight: 900; text-transform: uppercase;">No employee session detected. Please log in through the system first.</div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Training Catalog</h2>
        <table>
            <thead>
                <tr>
                    <th>Course</th>
                    <th>Category</th>
                    <th>Venue</th>
                    <th>Provider</th>
                    <th>Schedule</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($catalog) > 0): ?>
                    <?php foreach ($catalog as $course): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($course['category']); ?></td>
                            <td><?php echo htmlspecialchars($course['venue_location']); ?></td>
                            <td><?php echo htmlspecialchars($course['trainer_provider']); ?></td>
                            <td><?php echo htmlspecialchars($course['start_time']) . ' - ' . htmlspecialchars($course['end_time']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #64748b; font-weight: 900; text-transform: uppercase;">No training catalog available.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>