<?php
require_once '../config/db.php';

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_shift'])) {
    $shift_name = trim($_POST['shift_name'] ?? '');
    $start_time = !empty($_POST['start_time']) ? $_POST['start_time'] : null;
    $end_time = !empty($_POST['end_time']) ? $_POST['end_time'] : null;
    $is_flexible = isset($_POST['is_flexible']) ? 1 : 0;

    if (!empty($shift_name)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO shift_timings (shift_name, start_time, end_time, is_flexible) VALUES (?, ?, ?, ?)");
            $stmt->execute([$shift_name, $start_time, $end_time, $is_flexible]);
            $success_msg = "New shift timing created successfully.";
        } catch (PDOException $e) {
            $error_msg = "Database Error: " . $e->getMessage();
        }
    } else {
        $error_msg = "Shift name is required.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_assignment'])) {
    $employee_id = $_POST['employee_id'] ?? null;
    $shift_id = $_POST['shift_id'] ?? null;

    if ($employee_id && $shift_id) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO employee_shifts (employee_id, shift_id, assigned_date) 
                VALUES (?, ?, CURRENT_DATE)
                ON CONFLICT (employee_id) 
                DO UPDATE SET shift_id = EXCLUDED.shift_id, assigned_date = CURRENT_DATE
            ");
            $stmt->execute([$employee_id, $shift_id]);
            $success_msg = "Shift assignment updated successfully.";
        } catch (PDOException $e) {
            $error_msg = "Database Error: " . $e->getMessage();
        }
    } else {
        $error_msg = "Please select both an employee and a shift timing.";
    }
}

try {
    $shifts = $pdo->query("SELECT * FROM shift_timings ORDER BY shift_id DESC")->fetchAll(PDO::FETCH_ASSOC);
    $employees = $pdo->query("SELECT employee_id, first_name, last_name, department FROM employees WHERE status != 'Exited' ORDER BY first_name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $shifts = [];
    $employees = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shift Management - HRMS</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #ffffff; color: #000000; margin: 0; padding: 0; }
        .container { margin-left: 260px !important; max-width: calc(100% - 280px) !important; padding: 20px; box-sizing: border-box; background: #ffffff; min-height: 100vh; }
        header { border-bottom: 2px solid #b3d1ff; padding-bottom: 15px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .btn-primary { background-color: #3399ff; color: #ffffff; padding: 8px 14px; border-radius: 4px; font-size: 13px; border: none; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block; }
        .btn-primary:hover { background-color: #0066cc; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 30px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #b3d1ff; font-size: 13px; vertical-align: top; }
        th { background-color: #e6f2ff; color: #000000; }
        tr:hover { background-color: #f0f7ff; }
        .grid-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-top: 20px; }
        .form-card { background: #f0f7ff; padding: 20px; border-radius: 6px; border: 1px solid #b3d1ff; }
        .form-group { display: flex; flex-direction: column; margin-bottom: 15px; }
        .form-group label { font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #000; }
        select, input[type="text"], input[type="time"] { padding: 6px; font-size: 12px; border: 1px solid #3399ff; border-radius: 3px; width: 100%; box-sizing: border-box; }
    </style>
</head>
<body>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>

<div class="container">
    <header>
        <h1 style="margin: 0; color: #000000; font-size: 22px;">Shift Management</h1>
        <div>
            <a href="attendance.php" class="btn-primary" style="margin-right: 10px;">Back to Attendance</a>
            <a href="attendance.php" class="btn-primary">View Dashboard</a>
        </div>
    </header>

    <?php if (!empty($success_msg)): ?>
        <div style="background: #e6f2ff; color: #0044cc; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 13px; border: 1px solid #3399ff;"><?php echo $success_msg; ?></div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div style="background: #fff0f0; color: #cc0000; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 13px; border: 1px solid #ff9999;"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <!-- 1. Active Shifts Catalog (Moved to the Top) -->
    <h3 style="color: #000000; font-size: 16px; margin-top: 0;">Active Shifts Catalog</h3>
    <table>
        <thead>
            <tr>
                <th>Shift ID</th>
                <th>Shift Name</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Flexible?</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($shifts) > 0): ?>
                <?php foreach ($shifts as $shift): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($shift['shift_id']); ?></td>
                        <td><strong><?php echo htmlspecialchars($shift['shift_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($shift['start_time'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($shift['end_time'] ?? 'N/A'); ?></td>
                        <td><?php echo $shift['is_flexible'] ? '<span style="color: #009900; font-weight: bold;">Yes</span>' : 'No'; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: #555;">No shift timings defined yet.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- 2. Define New Shift Timings & Assign Shift Forms Below -->
    <div class="grid-container">
        <!-- Define New Shift Timings Form -->
        <div class="form-card">
            <h3 style="margin-top: 0; font-size: 16px; color: #000;">Define New Shift Timings</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Shift Name</label>
                    <input type="text" name="shift_name" placeholder="e.g., Evening Shift" required>
                </div>
                <div class="form-group">
                    <label>Start Time</label>
                    <input type="time" name="start_time">
                </div>
                <div class="form-group">
                    <label>End Time</label>
                    <input type="time" name="end_time">
                </div>
                <div class="form-group" style="flex-direction: row; align-items: center; gap: 8px;">
                    <input type="checkbox" name="is_flexible" id="is_flexible" value="1" style="width: auto;">
                    <label for="is_flexible" style="margin-bottom: 0; cursor: pointer;">Flexible Shift Rules</label>
                </div>
                <button type="submit" name="save_shift" class="btn-primary" style="margin-top: 10px;">Save Shift</button>
            </form>
        </div>

        <!-- Assign Shift to Employee Form -->
        <div class="form-card">
            <h3 style="margin-top: 0; font-size: 16px; color: #000;">Assign Shift to Employee</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Employee</label>
                    <select name="employee_id" required>
                        <option value="">Select Employee</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?php echo $emp['employee_id']; ?>"><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['department'] . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Select Shift Timing</label>
                    <select name="shift_id" required>
                        <option value="">Select Shift</option>
                        <?php foreach ($shifts as $shift): ?>
                            <option value="<?php echo $shift['shift_id']; ?>"><?php echo htmlspecialchars($shift['shift_name'] . ' (' . ($shift['start_time'] ?? 'Flexible') . ' - ' . ($shift['end_time'] ?? 'Flexible') . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="update_assignment" class="btn-primary" style="margin-top: 35px;">Update Assignment</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>