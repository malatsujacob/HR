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
    if (isset($_POST['clock_action'])) {
        $action = $_POST['clock_action'];
        $gps = $_POST['gps_location'] ?? '0.0000, 0.0000 (Kampala, UG)';
        $today = date('Y-m-d');
        
        if ($action === 'in') {
            $stmt = $pdo->prepare("INSERT INTO employee_attendance (employee_id, attendance_date, clock_in_time, status, gps_location) VALUES (?, ?, NOW(), 'Present', ?)");
            $stmt->execute([$employee_id, $today, $gps]);
            $msg = "Clocked in successfully with GPS verification.";
        } elseif ($action === 'out') {
            $stmt = $pdo->prepare("UPDATE employee_attendance SET clock_out_time = NOW() WHERE employee_id = ? AND attendance_date = ? AND clock_out_time IS NULL");
            $stmt->execute([$employee_id, $today]);
            $msg = "Clocked out successfully.";
        }
    } elseif (isset($_POST['submit_correction'])) {
        $stmt = $pdo->prepare("INSERT INTO attendance_correction_requests (employee_id, missed_date, reason) VALUES (?, ?, ?)");
        $stmt->execute([$employee_id, $_POST['missed_date'], $_POST['reason']]);
        $msg = "Attendance correction ticket submitted to HR/Manager.";
    }
}

// Fetch attendance history for monthly view
$att_stmt = $pdo->prepare("SELECT * FROM employee_attendance WHERE employee_id = ? ORDER BY attendance_date DESC LIMIT 30");
$att_stmt->execute([$employee_id]);
$attendance_logs = $att_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch correction requests
$corr_stmt = $pdo->prepare("SELECT * FROM attendance_correction_requests WHERE employee_id = ? ORDER BY created_at DESC");
$corr_stmt->execute([$employee_id]);
$corrections = $corr_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance & Clock-In - ESS HRMS</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #ffffff; color: #000000; margin: 0; padding: 0; }
        .container { margin-left: 260px !important; max-width: calc(100% - 280px) !important; padding: 25px; box-sizing: border-box; background: #ffffff; min-height: 100vh; }
        header { border-bottom: 2px solid #b3d1ff; padding-bottom: 15px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 30px; background: #ffffff; border: 1px solid #b3d1ff; border-radius: 4px; overflow: hidden; }
        th, td { padding: 12px 14px; text-align: left; border-bottom: 1px solid #b3d1ff; font-size: 13px; vertical-align: middle; }
        th { background: linear-gradient(180deg, #e6f2ff 0%, #cce0ff 100%); color: #0f172a; font-weight: bold; }
        tr:hover { background-color: #f8fafc; }

        .btn-primary { background: linear-gradient(135deg, #3399ff 0%, #0066cc 100%); color: #ffffff; padding: 8px 14px; border-radius: 4px; font-size: 13px; border: none; cursor: pointer; font-weight: bold; }
        .btn-orange { background: linear-gradient(135deg, #ff9933 0%, #ff6600 100%); color: #ffffff; padding: 8px 14px; border-radius: 4px; font-size: 13px; border: none; cursor: pointer; font-weight: bold; }
        .form-section { background: #f8fafc; padding: 20px; border-radius: 6px; border: 1px solid #b3d1ff; margin-bottom: 30px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-size: 11px; font-weight: bold; margin-bottom: 4px; color: #334155; text-transform: uppercase; }
        input, select { padding: 7px; font-size: 12px; border: 1px solid #cbd5e1; border-radius: 4px; width: 100%; box-sizing: border-box; background: #ffffff; }
        
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
            <h1 style="margin: 0; font-size: 22px; color: #0f172a;">Attendance & Clock-In</h1>
            <small style="color: #64748b;">GPS Clocking & Correction Tickets (Module 5 Integration)</small>
        </div>
        <div>
            <a href="dashboard.php" class="nav-btn">Back to Dashboard</a>
        </div>
    </header>

    <?php if (!empty($msg)): ?>
        <div style="background: #ecfdf5; color: #047857; padding: 10px 14px; border-radius: 4px; margin-bottom: 20px; font-size: 13px; border: 1px solid #a7f3d0; font-weight: bold;"><?php echo $msg; ?></div>
    <?php endif; ?>

    <!-- 1. CLOCK IN / OUT ACTION BOX -->
    <div class="form-section">
        <div class="section-title" style="margin-top: 0; border-left-color: #0066cc;">Daily Time Tracking & GPS Verification</div>
        <form method="POST" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <input type="hidden" name="gps_location" value="0.3476° N, 32.5825° E (Kampala Central, UG)">
            <button type="submit" name="clock_action" value="in" class="btn-primary">Clock In Now (GPS Active)</button>
            <button type="submit" name="clock_action" value="out" class="btn-orange">Clock Out</button>
        </form>
    </div>

    <!-- 2. MONTHLY ATTENDANCE HISTORY VIEW -->
    <div class="section-title">Monthly Attendance Calendar View</div>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Clock In Time</th>
                <th>Clock Out Time</th>
                <th>Status</th>
                <th>GPS Location Verified</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($attendance_logs) > 0): ?>
                <?php foreach ($attendance_logs as $al): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($al['attendance_date']); ?></strong></td>
                        <td><?php echo htmlspecialchars($al['clock_in_time'] ?? 'Not recorded'); ?></td>
                        <td><?php echo htmlspecialchars($al['clock_out_time'] ?? 'Still Active'); ?></td>
                        <td><span style="color: #047857; font-weight: bold;"><?php echo htmlspecialchars($al['status']); ?></span></td>
                        <td><small><?php echo htmlspecialchars($al['gps_location']); ?></small></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: #64748b; padding: 20px;">No attendance logs found for this period.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- 3. CORRECTION REQUEST TICKET FORM -->
    <div class="form-section">
        <div class="section-title" style="margin-top: 0; border-left-color: #ff6600;">Request Attendance Correction Ticket</div>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Missed Date</label>
                    <input type="date" name="missed_date" required>
                </div>
                <div class="form-group">
                    <label>Reason for Correction</label>
                    <input type="text" name="reason" placeholder="e.g. Forgot to clock in at 9 AM" required>
                </div>
            </div>
            <button type="submit" name="submit_correction" class="btn-orange" style="margin-top: 12px;">Submit Correction Ticket</button>
        </form>
    </div>

    <!-- Correction Requests History -->
    <div class="section-title">My Attendance Correction Requests</div>
    <table>
        <thead>
            <tr>
                <th>Request ID</th>
                <th>Missed Date</th>
                <th>Reason</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($corrections) > 0): ?>
                <?php foreach ($corrections as $corr): ?>
                    <tr>
                        <td>#<?php echo $corr['correction_id']; ?></td>
                        <td><?php echo htmlspecialchars($corr['missed_date']); ?></td>
                        <td><?php echo htmlspecialchars($corr['reason']); ?></td>
                        <td><strong><?php echo htmlspecialchars($corr['request_status']); ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" style="text-align: center; color: #64748b; padding: 20px;">No correction requests found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>