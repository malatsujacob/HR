<?php
// module9_training/onboarding_trainings.php
require_once '../config/db.php';
require_once 'training_model.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$trainModel = new TrainingModel($pdo);
$catalog = $trainModel->getCatalog();

// Filter courses intended for onboarding (category contains 'onboard' case-insensitive)
$onboard = array_filter($catalog, function($c) {
    return isset($c['category']) && stripos($c['category'], 'onboard') !== false;
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Onboarding Trainings - HRMS</title>
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
        <h1><span class="brand-title">CHAP CHAP</span> <span class="brand-title" style="color: #3b82f6;">AFRICA</span> - Onboarding Training Programs</h1>
    </header>

    <div class="card">
        <h2>Onboarding Catalog</h2>
        <table>
            <thead>
                <tr>
                    <th>Course</th>
                    <th>Category</th>
                    <th>Schedule</th>
                    <th>Venue</th>
                    <th>Trainer</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($onboard) > 0): ?>
                    <?php foreach ($onboard as $course): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($course['course_name'] ?? 'Untitled'); ?></td>
                            <td><?php echo htmlspecialchars($course['category'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($course['start_time'] ?? 'TBD') . ' - ' . htmlspecialchars($course['end_time'] ?? 'TBD'); ?></td>
                            <td><?php echo htmlspecialchars($course['venue_location'] ?? 'TBD'); ?></td>
                            <td><?php echo htmlspecialchars($course['trainer_provider'] ?? 'TBD'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #64748b; font-weight: 900; text-transform: uppercase;">No onboarding training programs found. Please ask HR to create courses with category 'Onboarding'.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>