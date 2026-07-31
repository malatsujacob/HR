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
$leave_balances = $essModel->getLeaveBalances($_SESSION['ess_emp_id']);

// Fetch active announcements
$announcements_stmt = $pdo->query("SELECT * FROM system_announcements ORDER BY created_at DESC LIMIT 3");
$announcements = $announcements_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch pending leave count
$pending_leaves_count = count(array_filter($essModel->getLeaveRequests($_SESSION['ess_emp_id']), function($l) {
    return $l['request_status'] === 'Pending';
}));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ESS Dashboard - HRMS</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #ffffff; color: #000000; margin: 0; padding: 0; }
        .container { margin-left: 260px !important; max-width: calc(100% - 280px) !important; padding: 25px; box-sizing: border-box; background: #ffffff; min-height: 100vh; }
        header { border-bottom: 2px solid #b3d1ff; padding-bottom: 15px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        
        .widget-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .widget-card { background: #f8fafc; border: 1px solid #b3d1ff; border-radius: 6px; padding: 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .widget-title { font-size: 11px; font-weight: bold; text-transform: uppercase; color: #64748b; margin-bottom: 8px; }
        .widget-value { font-size: 20px; font-weight: bold; color: #0066cc; }

        .announcement-box { background: #fef3c7; border: 1px solid #f59e0b; padding: 14px; border-radius: 6px; margin-bottom: 25px; color: #92400e; font-size: 13px; }
        .nav-links { display: flex; gap: 10px; margin-bottom: 25px; flex-wrap: wrap; }
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
            <h1 style="margin: 0; font-size: 22px; color: #0f172a;">Welcome back, <?php echo htmlspecialchars($current_employee['first_name'] ?? 'Worker'); ?>!</h1>
            <small style="color: #64748b;">Employee Self-Service Central Dashboard</small>
        </div>
        <div>
            <a href="ess.php" class="nav-btn" style="background: #b91c1c; color: #ffffff; border: none;">Back to Profile Portal</a>
        </div>
    </header>

    <!-- Quick Navigation Modules -->
    <div class="nav-links">
        <a href="payslips.php" class="nav-btn">Payslips & Finance</a>
        <a href="attendance.php" class="nav-btn">Attendance & Clock-In</a>
        <a href="performance.php" class="nav-btn">Performance Appraisals</a>
        <a href="training_onboarding.php" class="nav-btn">Training & Onboarding</a>
    </div>

    <!-- Announcements -->
    <?php if (count($announcements) > 0): ?>
        <div class="announcement-box">
            <strong>📢 Recent Announcement:</strong>
            <?php foreach ($announcements as $ann): ?>
                <div style="margin-top: 4px;"><?php echo htmlspecialchars($ann['title']); ?>: <?php echo htmlspecialchars($ann['message']); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Summary Widgets -->
    <div class="widget-grid">
        <div class="widget-card">
            <div class="widget-title">Remaining Annual Leave</div>
            <div class="widget-value">
                <?php 
                $annual_bal = array_filter($leave_balances, function($l) { return $l['leave_type'] === 'Annual'; });
                $bal = reset($annual_bal);
                echo $bal ? htmlspecialchars($bal['remaining'] . ' Days') : '0 Days';
                ?>
            </div>
        </div>
        <div class="widget-card">
            <div class="widget-title">Clock-In Status Today</div>
            <div class="widget-value" style="color: #047857;">Checked In</div>
        </div>
        <div class="widget-card">
            <div class="widget-title">Pending Leave Requests</div>
            <div class="widget-value" style="color: #ff6600;"><?php echo $pending_leaves_count; ?> Active</div>
        </div>
        <div class="widget-card">
            <div class="widget-title">Upcoming Training</div>
            <div class="widget-value" style="color: #0f172a;">0 Scheduled</div>
        </div>
    </div>
</div>

</body>
</html>