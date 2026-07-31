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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_appraisal'])) {
    $stmt = $pdo->prepare("INSERT INTO self_appraisals (employee_id, review_period, rating, achievements, status) VALUES (?, ?, ?, ?, 'Submitted')");
    $stmt->execute([
        $employee_id,
        $_POST['review_period'],
        $_POST['rating'],
        $_POST['achievements']
    ]);
    $msg = "Self-appraisal submitted successfully to HR and your manager.";
}

// Fetch active OKRs/KPIs
$okr_stmt = $pdo->prepare("SELECT * FROM employee_okrs WHERE employee_id = ? ORDER BY target_date ASC");
$okr_stmt->execute([$employee_id]);
$okrs = $okr_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch historical appraisals
$app_stmt = $pdo->prepare("SELECT * FROM self_appraisals WHERE employee_id = ? ORDER BY created_at DESC");
$app_stmt->execute([$employee_id]);
$appraisals = $app_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Appraisals - ESS HRMS</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #ffffff; color: #000000; margin: 0; padding: 0; }
        .container { margin-left: 260px !important; max-width: calc(100% - 280px) !important; padding: 25px; box-sizing: border-box; background: #ffffff; min-height: 100vh; }
        header { border-bottom: 2px solid #b3d1ff; padding-bottom: 15px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 30px; background: #ffffff; border: 1px solid #b3d1ff; border-radius: 4px; overflow: hidden; }
        th, td { padding: 12px 14px; text-align: left; border-bottom: 1px solid #b3d1ff; font-size: 13px; vertical-align: middle; }
        th { background: linear-gradient(180deg, #e6f2ff 0%, #cce0ff 100%); color: #0f172a; font-weight: bold; }
        tr:hover { background-color: #f8fafc; }

        .btn-orange { background: linear-gradient(135deg, #ff9933 0%, #ff6600 100%); color: #ffffff; padding: 8px 14px; border-radius: 4px; font-size: 13px; border: none; cursor: pointer; font-weight: bold; }
        .form-section { background: #f8fafc; padding: 20px; border-radius: 6px; border: 1px solid #b3d1ff; margin-bottom: 30px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-size: 11px; font-weight: bold; margin-bottom: 4px; color: #334155; text-transform: uppercase; }
        input, select, textarea { padding: 7px; font-size: 12px; border: 1px solid #cbd5e1; border-radius: 4px; width: 100%; box-sizing: border-box; background: #ffffff; }
        
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
            <h1 style="margin: 0; font-size: 22px; color: #0f172a;">Performance Management</h1>
            <small style="color: #64748b;">OKRs, Self-Appraisals & Feedback (Module 8 Integration)</small>
        </div>
        <div>
            <a href="dashboard.php" class="nav-btn">Back to Dashboard</a>
        </div>
    </header>

    <?php if (!empty($msg)): ?>
        <div style="background: #ecfdf5; color: #047857; padding: 10px 14px; border-radius: 4px; margin-bottom: 20px; font-size: 13px; border: 1px solid #a7f3d0; font-weight: bold;"><?php echo $msg; ?></div>
    <?php endif; ?>

    <!-- 1. ACTIVE OKRS / KPIS VIEW -->
    <div class="section-title" style="margin-top: 0;">Active Goals (OKRs / KPIs Set by Manager)</div>
    <table>
        <thead>
            <tr>
                <th>Objective Title</th>
                <th>Key Results</th>
                <th>Target Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($okrs) > 0): ?>
                <?php foreach ($okrs as $okr): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($okr['objective_title']); ?></strong></td>
                        <td><?php echo htmlspecialchars($okr['key_results']); ?></td>
                        <td><?php echo htmlspecialchars($okr['target_date']); ?></td>
                        <td><span style="color: #047857; font-weight: bold;"><?php echo htmlspecialchars($okr['status']); ?></span></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" style="text-align: center; color: #64748b; padding: 20px;">No active OKRs assigned currently.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- 2. SUBMIT SELF-APPRAISAL FORM -->
    <div class="form-section">
        <div class="section-title" style="margin-top: 0; border-left-color: #ff6600;">Submit Self-Appraisal Evaluation</div>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Review Period</label>
                    <input type="text" name="review_period" placeholder="e.g. H1 2026" required>
                </div>
                <div class="form-group">
                    <label>Self Rating (1 to 5)</label>
                    <select name="rating" required>
                        <option value="5">5 - Exceptional</option>
                        <option value="4">4 - Exceeds Expectations</option>
                        <option value="3" selected>3 - Meets Expectations</option>
                        <option value="2">2 - Needs Improvement</option>
                        <option value="1">1 - Unsatisfactory</option>
                    </select>
                </div>
            </div>
            <div class="form-group" style="margin-top: 12px;">
                <label>Key Achievements & Remarks</label>
                <textarea name="achievements" rows="3" placeholder="Summarize your key performance outputs and milestones..." required></textarea>
            </div>
            <button type="submit" name="submit_appraisal" class="btn-orange" style="margin-top: 12px;">Submit Self-Appraisal</button>
        </form>
    </div>

    <!-- 3. PAST REVIEWS & HISTORICAL RATINGS -->
    <div class="section-title">Past Appraisals & Manager Feedback History</div>
    <table>
        <thead>
            <tr>
                <th>Review Period</th>
                <th>Self Rating</th>
                <th>Achievements</th>
                <th>Manager Feedback</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($appraisals) > 0): ?>
                <?php foreach ($appraisals as $app): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($app['review_period']); ?></strong></td>
                        <td><span style="color: #0066cc; font-weight: bold;"><?php echo htmlspecialchars($app['rating']); ?> / 5</span></td>
                        <td><?php echo htmlspecialchars($app['achievements']); ?></td>
                        <td><?php echo htmlspecialchars($app['manager_feedback'] ?? 'Pending manager review'); ?></td>
                        <td><strong><?php echo htmlspecialchars($app['status']); ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: #64748b; padding: 20px;">No historical appraisal records found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>