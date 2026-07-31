<?php
// module8_performance/performance.php - Performance management main page
require_once '../config/db.php';
require_once 'performance_model.php';

$perfModel = new PerformanceModel($pdo);
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_goal'])) {
        $perfModel->createGoal($_POST);
        $msg = "Goal successfully established.";
    } elseif (isset($_POST['submit_appraisal'])) {
        $perfModel->submitAppraisal($_POST);
        $msg = "Appraisal recorded successfully. Ratings of 1 or 2 automatically triggered a PIP and Training recommendation.";
    } elseif (isset($_POST['submit_360'])) {
        $perfModel->submit360Feedback($_POST);
        $msg = "360-Degree feedback submitted successfully.";
    }
}

$appraisals = $perfModel->getAllAppraisals();
$pips = $perfModel->getActivePIPs();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Management - HRMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { 
            background-color: #ffffff; 
            color: #1e293b; 
            margin: 0; 
            font-family: Arial, sans-serif; 
        }
        .container { 
            margin-left: 260px; 
            max-width: calc(100% - 260px); 
            padding: 20px; 
            box-sizing: border-box; 
            background: #ffffff; 
            min-height: 100vh; 
        }
        header { 
            border-bottom: 2px solid #e2e8f0; 
            padding-bottom: 12px; 
            margin-bottom: 20px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        header h1 { 
            font-size: 18px; 
            font-weight: 900; 
            margin: 0; 
            color: #1e293b; 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
        }
        .brand-title { color: #2563eb; font-weight: 900; }
        .brand-title span { color: #3b82f6; }
        .btn-primary { 
            background-color: #2563eb; 
            color: #ffffff; 
            padding: 8px 14px; 
            border-radius: 4px; 
            font-size: 11px; 
            border: none; 
            cursor: pointer; 
            font-weight: 900; 
            text-decoration: none; 
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .btn-primary:hover { background-color: #1d4ed8; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 30px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 12px; vertical-align: top; }
        th { background-color: #eff6ff; color: #1e293b; text-transform: uppercase; font-size: 11px; font-weight: 900; letter-spacing: 0.5px; border: 1px solid #bfdbfe; }
        .form-card { background: #eff6ff; padding: 20px; border-radius: 6px; border: 1px solid #bfdbfe; margin-bottom: 25px; box-shadow: 0 1px 3px rgba(37, 99, 235, 0.1); }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-size: 11px; font-weight: 900; margin-bottom: 5px; color: #2563eb; text-transform: uppercase; letter-spacing: 0.5px; }
        input, select, textarea { padding: 8px; font-size: 12px; border: 1px solid #93c5fd; border-radius: 4px; width: 100%; box-sizing: border-box; background: #ffffff; color: #1e293b; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #2563eb; }
        h3 { font-size: 14px; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 900; }
    </style>
</head>
<body>

<?php 
include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php');
?>

<div class="container">
    <header>
        <h1><span class="brand-title">CHAP CHAP</span> <span class="brand-title" style="color: #3b82f6;">AFRICA</span> - Performance</h1>
        <a href="dashboard.php" class="btn-primary">Analytics</a>
    </header>

    <?php if (!empty($msg)): ?>
        <div style="background: #eff6ff; color: #2563eb; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 12px; border: 1px solid #bfdbfe; font-weight: bold;"><?php echo $msg; ?></div>
    <?php endif; ?>

    <h3>Completed Appraisals</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Emp ID</th>
                <th>Name</th>
                <th>Department</th>
                <th>Cycle</th>
                <th>Self</th>
                <th>Manager</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($appraisals) > 0): ?>
                <?php foreach ($appraisals as $app): ?>
                    <tr>
                        <td><strong>#<?php echo $app['appraisal_id']; ?></strong></td>
                        <td>#<?php echo $app['employee_id']; ?></td>
                        <td><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($app['department']); ?></td>
                        <td><?php echo htmlspecialchars($app['review_cycle']); ?></td>
                        <td><?php echo $app['self_rating']; ?> / 5</td>
                        <td><strong><?php echo $app['manager_rating']; ?> / 5</strong></td>
                        <td>
                            <span style="color: <?php echo $app['status'] == 'PIP Assigned' ? '#dc2626' : '#2563eb'; ?>; font-weight: bold;">
                                <?php echo htmlspecialchars($app['status']); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align: center; color: #64748b;">No records found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="form-card">
        <h3 style="margin-top: 0;">Goal Setting</h3>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Employee ID</label>
                    <input type="number" name="employee_id" placeholder="ID..." required>
                </div>
                <div class="form-group">
                    <label>Goal Title</label>
                    <input type="text" name="goal_title" placeholder="Title..." required>
                </div>
                <div class="form-group">
                    <label>Review Cycle</label>
                    <select name="cycle" required>
                        <option value="Quarterly">Quarterly</option>
                        <option value="Half-Yearly">Half-Yearly</option>
                        <option value="Annual">Annual</option>
                    </select>
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label>Description</label>
                    <textarea name="description" rows="2" placeholder="Objectives..."></textarea>
                </div>
            </div>
            <button type="submit" name="save_goal" class="btn-primary" style="margin-top: 15px;">Save Goal</button>
        </form>
    </div>

    <div class="form-card">
        <h3 style="margin-top: 0;">Conduct Appraisal</h3>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Employee ID</label>
                    <input type="number" name="employee_id" placeholder="ID..." required>
                </div>
                <div class="form-group">
                    <label>Review Cycle</label>
                    <select name="review_cycle" required>
                        <option value="Q1">Q1</option>
                        <option value="Q2">Q2</option>
                        <option value="Q3">Q3</option>
                        <option value="Q4">Q4</option>
                        <option value="Annual">Annual</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Self Rating (1-5)</label>
                    <select name="self_rating" required>
                        <option value="5">5 - Outstanding</option>
                        <option value="4">4 - Exceeds</option>
                        <option value="3" selected>3 - Meets</option>
                        <option value="2">2 - Below</option>
                        <option value="1">1 - Needs Improvement</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Manager Rating (1-5)</label>
                    <select name="manager_rating" required>
                        <option value="5">5 - Outstanding</option>
                        <option value="4">4 - Exceeds</option>
                        <option value="3" selected>3 - Meets</option>
                        <option value="2">2 - Below (Triggers PIP)</option>
                        <option value="1">1 - Needs Improvement (Triggers PIP)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Achievements</label>
                    <textarea name="self_summary" rows="2" placeholder="Summary..."></textarea>
                </div>
                <div class="form-group">
                    <label>Improvement Suggestions</label>
                    <textarea name="improvement_suggestions" rows="2" placeholder="Suggestions..."></textarea>
                </div>
            </div>
            <button type="submit" name="submit_appraisal" class="btn-primary" style="margin-top: 15px;">Submit Appraisal</button>
        </form>
    </div>

    <div class="form-card">
        <h3 style="margin-top: 0;">360-Degree Feedback</h3>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Target Employee ID</label>
                    <input type="number" name="employee_id" placeholder="ID..." required>
                </div>
                <div class="form-group">
                    <label>Reviewer Role</label>
                    <select name="reviewer_role" required>
                        <option value="Peer">Peer</option>
                        <option value="Subordinate">Subordinate</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Rating (1-5)</label>
                    <select name="rating" required>
                        <option value="5">5 - Outstanding</option>
                        <option value="4">4 - Exceeds</option>
                        <option value="3" selected>3 - Meets</option>
                        <option value="2">2 - Below</option>
                        <option value="1">1 - Needs Improvement</option>
                    </select>
                </div>
                <div class="form-group" style="display: flex; flex-direction: row; align-items: center; gap: 8px; margin-top: 20px;">
                    <input type="checkbox" name="is_anonymous" value="1" checked style="width: auto;">
                    <label style="margin: 0; color: #1e293b;">Anonymous</label>
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label>Feedback Comments</label>
                    <textarea name="feedback_comments" rows="2" placeholder="Comments..." required></textarea>
                </div>
            </div>
            <button type="submit" name="submit_360" class="btn-primary" style="margin-top: 15px;">Submit Feedback</button>
        </form>
    </div>

    <h3>Active PIPs</h3>
    <table>
        <thead>
            <tr>
                <th>PIP ID</th>
                <th>Emp ID</th>
                <th>Name</th>
                <th>Department</th>
                <th>Action Items / Reason</th>
                <th>Deadline</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($pips) > 0): ?>
                <?php foreach ($pips as $pip): ?>
                    <tr>
                        <td><strong>#<?php echo $pip['pip_id']; ?></strong></td>
                        <td>#<?php echo $pip['employee_id']; ?></td>
                        <td><?php echo htmlspecialchars($pip['first_name'] . ' ' . $pip['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($pip['department']); ?></td>
                        <td><?php echo htmlspecialchars($pip['action_items']); ?></td>
                        <td><?php echo htmlspecialchars($pip['deadline_date']); ?></td>
                        <td><strong style="color: #dc2626;"><?php echo htmlspecialchars($pip['status']); ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: #64748b;">No active PIPs found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>