<?php
require_once '../config/db.php';

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_attendance'])) {
    $employee_id = $_POST['employee_id'];
    $check_in = !empty($_POST['check_in']) ? $_POST['check_in'] : null;
    $check_out = !empty($_POST['check_out']) ? $_POST['check_out'] : null;
    $method = $_POST['clock_in_method'];
    $shift_type = $_POST['shift_type'] ?? 'Morning (9 AM - 5 PM)';
    $overtime_multiplier = floatval($_POST['overtime_multiplier'] ?? 1.5);
    $manual_reason = trim($_POST['manual_adjustment_reason'] ?? '');
    $attendance_date = $_POST['attendance_date'] ?? date('Y-m-d');

    $shift_start_time = '09:00:00';
    $shift_end_time = '17:00:00';
    $is_flexible = false;

    if ($shift_type === 'Night (10 PM - 6 AM)') {
        $shift_start_time = '22:00:00';
        $shift_end_time = '06:00:00';
    } elseif ($shift_type === 'Flexible') {
        $is_flexible = true;
    }

    $total_hours = 0;
    $overtime_hours = 0;
    $lateness_minutes = 0;
    $early_departure_minutes = 0;
    $status = 'Present';

    if ($check_in && $check_out) {
        $in_time = new DateTime($check_in);
        $out_time = new DateTime($check_out);
        
        if ($shift_type === 'Night (10 PM - 6 AM)' && $out_time < $in_time) {
            $out_time->modify('+1 day');
        }

        $interval = $in_time->diff($out_time);
        $total_hours = ($interval->h + ($interval->days * 24)) + ($interval->i / 60);

        if (!$is_flexible) {
            $shift_start = new DateTime($attendance_date . ' ' . $shift_start_time);
            if ($in_time > $shift_start) {
                $late_interval = $shift_start->diff($in_time);
                $lateness_minutes = ($late_interval->h * 60) + $late_interval->i;
            }

            $shift_end_dt = new DateTime($attendance_date . ' ' . $shift_end_time);
            if ($shift_type === 'Night (10 PM - 6 AM)') {
                $shift_end_dt->modify('+1 day');
            }
            if ($out_time < $shift_end_dt) {
                $early_interval = $out_time->diff($shift_end_dt);
                $early_departure_minutes = ($early_interval->h * 60) + $early_interval->i;
            }
        }

        if ($total_hours > 8) {
            $overtime_hours = $total_hours - 8;
        }
    } elseif (!$check_in) {
        $status = 'Absent';
    }

    $leave_check = $pdo->prepare("SELECT leave_type FROM employee_leaves WHERE employee_id = ? AND ? BETWEEN start_date AND end_date AND status = 'Approved'");
    $leave_check->execute([$employee_id, $attendance_date]);
    $leave_type = $leave_check->fetchColumn();
    if ($leave_type) {
        $status = 'Excused (Leave: ' . $leave_type . ')';
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO employee_attendance (employee_id, attendance_date, check_in_time, check_out_time, total_hours_worked, overtime_hours, lateness_minutes, early_departure_minutes, status, clock_in_method, shift_type, manual_adjustment_reason, overtime_multiplier) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON CONFLICT (employee_id, attendance_date) 
            DO UPDATE SET check_in_time = EXCLUDED.check_in_time, check_out_time = EXCLUDED.check_out_time, total_hours_worked = EXCLUDED.total_hours_worked, overtime_hours = EXCLUDED.overtime_hours, lateness_minutes = EXCLUDED.lateness_minutes, early_departure_minutes = EXCLUDED.early_departure_minutes, status = EXCLUDED.status, clock_in_method = EXCLUDED.clock_in_method, shift_type = EXCLUDED.shift_type, manual_adjustment_reason = EXCLUDED.manual_adjustment_reason, overtime_multiplier = EXCLUDED.overtime_multiplier
        ");
        $stmt->execute([$employee_id, $attendance_date, $check_in, $check_out, $total_hours, $overtime_hours, $lateness_minutes, $early_departure_minutes, $status, $method, $shift_type, $manual_reason, $overtime_multiplier]);

        $pay_queue = $pdo->prepare("
            INSERT INTO payroll_attendance_queue (employee_id, attendance_date, total_hours_worked, overtime_hours, lateness_minutes, overtime_multiplier) 
            VALUES (?, ?, ?, ?, ?, ?)
            ON CONFLICT (employee_id, attendance_date) 
            DO UPDATE SET total_hours_worked = EXCLUDED.total_hours_worked, overtime_hours = EXCLUDED.overtime_hours, lateness_minutes = EXCLUDED.lateness_minutes, overtime_multiplier = EXCLUDED.overtime_multiplier
        ");
        $pay_queue->execute([$employee_id, $attendance_date, $total_hours, $overtime_hours, $lateness_minutes, $overtime_multiplier]);

        $success_msg = "Attendance logged successfully with shift and early departure checks applied.";
    } catch (PDOException $e) {
        $error_msg = "Database Error: " . $e->getMessage();
    }
}

try {
    $employees = $pdo->query("SELECT employee_id, first_name, last_name, department FROM employees WHERE status != 'Exited' ORDER BY first_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    
    $attendance_logs = $pdo->query("
        SELECT a.*, e.first_name, e.last_name, e.department 
        FROM employee_attendance a 
        JOIN employees e ON a.employee_id = e.employee_id 
        ORDER BY a.attendance_date DESC, a.attendance_id DESC 
        LIMIT 50
    ")->fetchAll(PDO::FETCH_ASSOC);

    $absenteeism_dept = $pdo->query("
        SELECT e.department, 
               ROUND(100.0 * SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) / NULLIF(COUNT(a.attendance_id), 0), 2) as absent_rate
        FROM employees e
        LEFT JOIN employee_attendance a ON e.employee_id = a.employee_id
        GROUP BY e.department
    ")->fetchAll(PDO::FETCH_ASSOC);

    $late_dept = $pdo->query("
        SELECT e.department, COUNT(a.attendance_id) as late_count
        FROM employee_attendance a
        JOIN employees e ON a.employee_id = e.employee_id
        WHERE a.lateness_minutes > 0
        GROUP BY e.department
    ")->fetchAll(PDO::FETCH_ASSOC);

    $monthly_trend = $pdo->query("
        SELECT TO_CHAR(attendance_date, 'Mon') as month_name, SUM(total_hours_worked) as total_hours
        FROM employee_attendance
        WHERE attendance_date >= (CURRENT_DATE - INTERVAL '1 year')
        GROUP BY TO_CHAR(attendance_date, 'Mon'), DATE_TRUNC('month', attendance_date)
        ORDER BY DATE_TRUNC('month', attendance_date)
    ")->fetchAll(PDO::FETCH_ASSOC);

    $daily_avg = $pdo->query("
        SELECT EXTRACT(DAY FROM attendance_date) as day_num, AVG(EXTRACT(HOUR FROM check_in_time) + EXTRACT(MINUTE FROM check_in_time)/60.0) as avg_hour
        FROM employee_attendance
        WHERE check_in_time IS NOT NULL
        GROUP BY EXTRACT(DAY FROM attendance_date)
        ORDER BY day_num
        LIMIT 31
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $employees = [];
    $attendance_logs = [];
    $absenteeism_dept = [];
    $late_dept = [];
    $monthly_trend = [];
    $daily_avg = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance & Time Tracking - HRMS</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: Arial, sans-serif; background-color: #ffffff; color: #000000; margin: 0; padding: 0; }
        .container { margin-left: 260px !important; max-width: calc(100% - 280px) !important; padding: 20px; box-sizing: border-box; background: #ffffff; min-height: 100vh; }
        header { border-bottom: 2px solid #b3d1ff; padding-bottom: 15px; margin-bottom: 20px; }
        .btn-primary { background-color: #3399ff; color: #ffffff; padding: 8px 14px; border-radius: 4px; font-size: 13px; border: none; cursor: pointer; font-weight: bold; }
        .btn-primary:hover { background-color: #0066cc; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 30px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #b3d1ff; font-size: 13px; vertical-align: top; }
        th { background-color: #e6f2ff; color: #000000; }
        tr:hover { background-color: #f0f7ff; }
        .form-card { background: #f0f7ff; padding: 20px; border-radius: 6px; border: 1px solid #b3d1ff; margin-bottom: 25px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #000; }
        select, input[type="text"], input[type="date"], input[type="time"], input[type="number"], textarea { padding: 6px; font-size: 12px; border: 1px solid #3399ff; border-radius: 3px; width: 100%; box-sizing: border-box; }
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .chart-card { background: #f0f7ff; border: 1px solid #b3d1ff; border-radius: 6px; padding: 15px; }
        .chart-card h4 { margin-top: 0; font-size: 14px; color: #000; border-bottom: 1px solid #b3d1ff; padding-bottom: 8px; }
    </style>
</head>
<body>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>

<div class="container">
    <header>
        <h1 style="margin: 0; color: #000000; font-size: 22px;">Attendance & Time Tracking</h1>
    </header>

    <?php if (!empty($success_msg)): ?>
        <div style="background: #e6f2ff; color: #0044cc; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 13px; border: 1px solid #3399ff;"><?php echo $success_msg; ?></div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div style="background: #fff0f0; color: #cc0000; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 13px; border: 1px solid #ff9999;"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <!-- 1. Active Shift Log & Attendance Records Table (Strictly Positioned at the Very Top) -->
    <h3 style="color: #000000; font-size: 16px; margin-top: 0;">Active Shift Log & Attendance Records</h3>
    <table>
        <thead>
            <tr>
                <th>Employee</th>
                <th>Date & Shift</th>
                <th>Check-In / Out</th>
                <th>Hours & Overtime</th>
                <th>Status, Lateness & Early Departure</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($attendance_logs) > 0): ?>
                <?php foreach ($attendance_logs as $row): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></strong><br>
                            <small style="color: #555;"><?php echo htmlspecialchars($row['department']); ?></small>
                        </td>
                        <td>
                            Date: <strong><?php echo htmlspecialchars($row['attendance_date']); ?></strong><br>
                            Shift: <strong style="color: #0066cc;"><?php echo htmlspecialchars($row['shift_type'] ?? 'Morning (9 AM - 5 PM)'); ?></strong><br>
                            Method: <?php echo htmlspecialchars($row['clock_in_method']); ?>
                        </td>
                        <td>
                            In: <strong style="color: #0066cc;"><?php echo htmlspecialchars($row['check_in_time'] ?? 'None'); ?></strong><br>
                            Out: <strong style="color: #0066cc;"><?php echo htmlspecialchars($row['check_out_time'] ?? 'None'); ?></strong>
                        </td>
                        <td>
                            Worked: <strong><?php echo number_format($row['total_hours_worked'], 2); ?> hrs</strong><br>
                            Overtime: <strong style="color: #009900;"><?php echo number_format($row['overtime_hours'], 2); ?> hrs</strong> (<?php echo htmlspecialchars($row['overtime_multiplier']); ?>x)
                        </td>
                        <td>
                            Status: <strong style="color: <?php echo strpos($row['status'], 'Present') !== false ? '#009900' : '#cc0000'; ?>;"><?php echo htmlspecialchars($row['status']); ?></strong><br>
                            Late: <span style="color: #cc0000;"><?php echo htmlspecialchars($row['lateness_minutes'] ?? 0); ?> mins</span> | 
                            Early Out: <span style="color: #cc0000;"><?php echo htmlspecialchars($row['early_departure_minutes'] ?? 0); ?> mins</span><br>
                            <small style="color: #555;">Note: <?php echo htmlspecialchars($row['manual_adjustment_reason'] ?? 'None'); ?></small>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: #555;">No attendance logs found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- 2. Clock-In / Manual Adjustment Form -->
    <div class="form-card">
        <h3 style="margin-top: 0; font-size: 16px; color: #000;">Clock-In & Manual Time Adjustment Form</h3>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Select Employee</label>
                    <select name="employee_id" required>
                        <option value="">Choose Employee</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?php echo $emp['employee_id']; ?>"><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['department'] . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Attendance Date</label>
                    <input type="date" name="attendance_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label>Shift Timing Definition</label>
                    <select name="shift_type" required>
                        <option value="Morning (9 AM - 5 PM)">Morning (9 AM - 5 PM)</option>
                        <option value="Night (10 PM - 6 AM)">Night (10 PM - 6 AM)</option>
                        <option value="Flexible">Flexible (No Fixed Lateness/Early Out)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Clock-In Method</label>
                    <select name="clock_in_method" required>
                        <option value="Biometric">Biometric Device</option>
                        <option value="Mobile QR">Mobile QR Scan</option>
                        <option value="Web Manual">Web Manual Entry</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Check-In Time</label>
                    <input type="time" name="check_in">
                </div>
                <div class="form-group">
                    <label>Check-Out Time</label>
                    <input type="time" name="check_out">
                </div>
                <div class="form-group">
                    <label>Overtime Rate Multiplier</label>
                    <select name="overtime_multiplier">
                        <option value="1.5">1.5x (Weekday OT)</option>
                        <option value="2.0">2.0x (Holiday/Weekend OT)</option>
                        <option value="1.0">1.0x (Standard)</option>
                    </select>
                </div>
            </div>
            <div class="form-group" style="margin-top: 15px;">
                <label>Manual Adjustment Reason (If supervisor adjusting)</label>
                <textarea name="manual_adjustment_reason" placeholder="State reason for manual correction or missing clock-in..."></textarea>
            </div>
            <button type="submit" name="log_attendance" class="btn-primary" style="margin-top: 15px;">Submit Attendance Log</button>
        </form>
    </div>

    <!-- 3. Attendance Analytics Dashboard Charts -->
    <h3 style="color: #000000; font-size: 16px; margin-top: 30px;">Attendance Analytics Dashboard</h3>
    <div class="dashboard-grid">
        <div class="chart-card">
            <h4>Absenteeism Rate by Department (%)</h4>
            <canvas id="absenteeismChart" height="180"></canvas>
        </div>
        <div class="chart-card">
            <h4>Late Arrivals by Department (Count)</h4>
            <canvas id="lateChart" height="180"></canvas>
        </div>
        <div class="chart-card">
            <h4>Monthly Attendance Trend (Total Hours Worked)</h4>
            <canvas id="trendChart" height="180"></canvas>
        </div>
        <div class="chart-card">
            <h4>Average Daily Attendance (Clock-In Time Heatmap/Avg)</h4>
            <canvas id="dailyAvgChart" height="180"></canvas>
        </div>
    </div>
</div>

<script>
    const absentData = <?php echo json_encode($absenteeism_dept); ?>;
    new Chart(document.getElementById('absenteeismChart'), {
        type: 'bar',
        data: {
            labels: absentData.map(d => d.department || 'Unassigned'),
            datasets: [{
                label: 'Absenteeism Rate (%)',
                data: absentData.map(d => d.absent_rate || 0),
                backgroundColor: '#3399ff'
            }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });

    const lateData = <?php echo json_encode($late_dept); ?>;
    new Chart(document.getElementById('lateChart'), {
        type: 'bar',
        data: {
            labels: lateData.map(d => d.department || 'Unassigned'),
            datasets: [{
                label: 'Late Count',
                data: lateData.map(d => d.late_count || 0),
                backgroundColor: '#ff9999'
            }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });

    const trendData = <?php echo json_encode($monthly_trend); ?>;
    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: trendData.map(d => d.month_name),
            datasets: [{
                label: 'Total Hours Worked',
                data: trendData.map(d => d.total_hours || 0),
                borderColor: '#0066cc',
                backgroundColor: 'rgba(51, 153, 255, 0.1)',
                fill: true,
                tension: 0.1
            }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });

    const dailyData = <?php echo json_encode($daily_avg); ?>;
    new Chart(document.getElementById('dailyAvgChart'), {
        type: 'bar',
        data: {
            labels: dailyData.map(d => 'Day ' + d.day_num),
            datasets: [{
                label: 'Avg Check-In Hour (24h)',
                data: dailyData.map(d => d.avg_hour || 0),
                backgroundColor: '#33cc99'
            }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true, max: 24 } } }
    });
</script>

</body>
</html>