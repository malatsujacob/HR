<?php
require_once '../config/db.php';
require_once 'leave_model.php';

$leaveModel = new LeaveModel($pdo);
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['apply_leave'])) {
        $upload_file = null;
        if (!empty($_FILES['medical_certificate']['name'])) {
            $target_dir = "../uploads/";
            if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
            $upload_file = $target_dir . basename($_FILES['medical_certificate']['name']);
            move_uploaded_file($_FILES['medical_certificate']['tmp_name'], $upload_file);
        }

        $result = $leaveModel->applyLeave([
            'employee_name' => trim($_POST['employee_name']),
            'department' => trim($_POST['department']),
            'leave_type' => trim($_POST['leave_type']),
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'reason' => trim($_POST['reason']),
            'medical_certificate' => $upload_file
        ]);

        if ($result === true) {
            $success_msg = "Submitted successfully.";
        } else {
            $error_msg = $result;
        }
    } elseif (isset($_POST['update_status'])) {
        $leaveModel->updateLeaveStatus($_POST['request_id'], $_POST['approval_status'], trim($_POST['manager_comment']));
        $success_msg = "Status updated.";
    }
}

$leave_requests = $leaveModel->getLeaveRequests();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Management - HR System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background-color: #f8fafc; color: #0f172a; margin: 0; font-family: Arial, sans-serif; }
        .container { margin-left: 260px; max-width: calc(100% - 280px); padding: 20px; box-sizing: border-box; background: #f8fafc; min-height: 100vh; }
        header { border-bottom: 2px solid #cbd5e1; padding-bottom: 12px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        header h1 { font-size: 18px; font-weight: 900; margin: 0; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; }
        .card { background: #ffffff; padding: 15px; border-radius: 6px; border: 1px solid #cbd5e1; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 11px; }
        th, td { padding: 8px 10px; text-align: left; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        th { background-color: #f1f5f9; color: #334155; font-weight: bold; text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px; }
        tr:hover { background-color: #f8fafc; }
        .btn-primary { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #ffffff; padding: 5px 10px; border-radius: 4px; font-size: 11px; text-decoration: none; font-weight: 900; display: inline-block; border: none; cursor: pointer; text-transform: uppercase; letter-spacing: 0.5px; }
        .btn-secondary { background: #e2e8f0; color: #334155; padding: 5px 10px; border-radius: 4px; font-size: 11px; text-decoration: none; font-weight: 900; border: 1px solid #cbd5e1; display: inline-block; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-size: 11px; font-weight: bold; margin-bottom: 4px; color: #334155; text-transform: uppercase; }
        select, input[type="text"], input[type="date"], input[type="file"], textarea { padding: 6px 8px; font-size: 11px; border: 1px solid #cbd5e1; border-radius: 4px; width: 100%; box-sizing: border-box; background: #ffffff; color: #0f172a; }
        textarea { resize: vertical; min-height: 60px; }
        .alert-success { background: #dcfce7; color: #166534; padding: 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #bbf7d0; font-size: 11px; }
        .alert-error { background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #fecaca; font-size: 11px; }
    </style>
</head>
<body>

<?php 
include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php');
?>

<div class="container">
    <header>
        <h1>Leave Management</h1>
        <div>
            <a href="holidays.php" class="btn-secondary" style="margin-right: 8px;">Holidays</a>
            <a href="dashboard.php" class="btn-primary">Dashboard</a>
        </div>
    </header>

    <?php if (!empty($success_msg)): ?>
        <div class="alert-success"><?php echo $success_msg; ?></div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div class="alert-error"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <div class="card">
        <h3 style="margin-top: 0; font-size: 13px; color: #0f172a; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px;">Leave Requests</h3>
        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Type & Dates</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($leave_requests) > 0): ?>
                    <?php foreach ($leave_requests as $row): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($row['employee_name']); ?></strong><br>
                                <span style="color: #64748b; font-size: 10px;"><?php echo htmlspecialchars($row['department']); ?></span>
                            </td>
                            <td>
                                <strong style="color: #0284c7;"><?php echo htmlspecialchars($row['leave_type']); ?></strong><br>
                                <?php echo htmlspecialchars($row['start_date']); ?> to <?php echo htmlspecialchars($row['end_date']); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($row['reason']); ?><br>
                                <?php if (!empty($row['medical_certificate'])): ?>
                                    <a href="<?php echo htmlspecialchars($row['medical_certificate']); ?>" target="_blank" style="font-size: 10px; color: #0284c7;">View Cert</a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong style="color: <?php echo $row['status'] == 'Approved' ? '#166534' : ($row['status'] == 'Rejected' ? '#991b1b' : '#b45309'); ?>;"><?php echo htmlspecialchars($row['status']); ?></strong><br>
                                <span style="color: #64748b; font-size: 10px;"><?php echo htmlspecialchars($row['manager_comment'] ?? ''); ?></span>
                            </td>
                            <td>
                                <form method="POST" style="display: flex; flex-direction: column; gap: 4px;">
                                    <input type="hidden" name="request_id" value="<?php echo $row['request_id']; ?>">
                                    <select name="approval_status">
                                        <option value="Approved">Approve</option>
                                        <option value="Rejected">Reject</option>
                                    </select>
                                    <input type="text" name="manager_comment" placeholder="Comment">
                                    <button type="submit" name="update_status" class="btn-primary" style="padding: 3px 6px; font-size: 10px;">Save</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #64748b; padding: 15px;">No requests found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3 style="margin-top: 0; font-size: 13px; color: #0f172a; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px;">Submit Request</h3>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="form-group">
                    <label>Employee Name</label>
                    <input type="text" name="employee_name" required>
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <input type="text" name="department" required>
                </div>
                <div class="form-group">
                    <label>Leave Type</label>
                    <input type="text" name="leave_type" required>
                </div>
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date" required>
                </div>
                <div class="form-group">
                    <label>End Date</label>
                    <input type="date" name="end_date" required>
                </div>
                <div class="form-group">
                    <label>Medical Cert</label>
                    <input type="file" name="medical_certificate">
                </div>
            </div>
            <div class="form-group" style="margin-top: 12px;">
                <label>Reason</label>
                <textarea name="reason"></textarea>
            </div>
            <button type="submit" name="apply_leave" class="btn-primary" style="margin-top: 12px;">Submit</button>
        </form>
    </div>
</div>

</body>
</html>