<?php
require_once '../config/db.php';
require_once 'ess_model.php';

$essModel = new ESSModel($pdo);
$msg = '';
session_start();

if (isset($_POST['ess_login'])) {
    $emp = $essModel->authenticateEmployee($_POST['login_employee_id'], $_POST['login_credential']);
    if ($emp) {
        $_SESSION['ess_emp_id'] = $emp['employee_id'];
        $_SESSION['ess_emp_name'] = ($emp['first_name'] ?? 'Worker') . ' ' . ($emp['last_name'] ?? '');
        $_SESSION['is_manager'] = !empty($emp['is_manager']) ? true : false;
        $msg = "Login successful.";
    } else {
        $msg = "Invalid credentials or inactive account.";
    }
} elseif (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ess.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_request'])) {
        $essModel->submitChangeRequest($_POST);
        $msg = "Update request submitted.";
    } elseif (isset($_POST['apply_leave'])) {
        $essModel->applyLeave($_POST);
        $msg = "Leave application submitted.";
    } elseif (isset($_POST['cancel_leave_id'])) {
        $essModel->cancelLeave($_POST['cancel_leave_id'], $_SESSION['ess_emp_id']);
        $msg = "Leave request cancelled.";
    } elseif (isset($_POST['manager_review_action'])) {
        $status = $_POST['action_type'] === 'approve' ? 'Approved' : 'Rejected';
        $essModel->managerReviewLeave($_POST['leave_id'], $status, $_SESSION['ess_emp_id']);
        $msg = "Leave request " . strtolower($status) . ".";
    } elseif (isset($_POST['review_action'])) {
        $status = $_POST['action_type'] === 'approve' ? 'Approved' : 'Rejected';
        $essModel->reviewRequest($_POST['change_request_id'], $status, $_POST['admin_id']);
        $msg = "Request " . strtolower($status) . ".";
    }
}

$pending_requests = $essModel->getPendingRequests();
$current_employee = isset($_SESSION['ess_emp_id']) ? $essModel->getEmployeeProfile($_SESSION['ess_emp_id']) : null;
$leave_balances = $current_employee ? $essModel->getLeaveBalances($current_employee['employee_id']) : [];
$my_leaves = $current_employee ? $essModel->getLeaveRequests($current_employee['employee_id']) : [];
$manager_pending_leaves = ($current_employee && !empty($_SESSION['is_manager'])) ? $essModel->getDirectReportsPendingLeaves($current_employee['employee_id']) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ESS Portal - HRMS</title>
    <style>
        body { background: #ffffff; color: #1e293b; margin: 0; font-family: Arial, sans-serif; }
        .container { margin-left: 260px; max-width: calc(100% - 260px); padding: 20px; box-sizing: border-box; background: #ffffff; min-height: 100vh; }
        header { border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        header h1 { font-size: 18px; font-weight: 900; margin: 0; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px; }
        .brand-title { color: #2563eb; font-weight: 900; }
        .card { background: #eff6ff; padding: 16px; border-radius: 6px; border: 1px solid #bfdbfe; margin-bottom: 20px; }
        .card h2 { font-size: 13px; margin-top: 0; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 900; border-left: 3px solid #2563eb; padding-left: 8px; margin-bottom: 12px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-size: 10px; font-weight: 900; margin-bottom: 4px; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; }
        input, select { padding: 6px; font-size: 11px; border: 1px solid #93c5fd; border-radius: 4px; width: 100%; box-sizing: border-box; background: #ffffff; color: #1e293b; }
        input:focus, select:focus { border-color: #2563eb; outline: none; }
        .btn-primary { background: #2563eb; color: #ffffff; padding: 6px 12px; border-radius: 4px; font-size: 11px; border: none; cursor: pointer; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-danger { background: #dc2626; color: #ffffff; padding: 6px 10px; border-radius: 4px; font-size: 11px; border: none; cursor: pointer; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px; }
        .btn-danger:hover { background: #b91c1c; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 20px; background: #ffffff; border: 1px solid #bfdbfe; border-radius: 4px; overflow: hidden; }
        th, td { padding: 8px 10px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 11px; }
        th { background: #eff6ff; color: #1e293b; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px; }
        tr:hover { background-color: #f8fafc; }
        .msg { background: #ecfdf5; color: #047857; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 11px; font-weight: 900; border: 1px solid #a7f3d0; text-transform: uppercase; letter-spacing: 0.5px; }
        .comparison-catalog { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 12px; }
        .comparison-card { background: #ffffff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 12px; }
        .comparison-pane { background: #f8fafc; padding: 8px; border-radius: 4px; border: 1px solid #e2e8f0; }
    </style>
</head>
<body>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>

<div class="container">
    <header>
        <h1><span class="brand-title">CHAP CHAP</span> <span class="brand-title" style="color: #3b82f6;">AFRICA</span> - ESS Portal</h1>
        <?php if ($current_employee): ?>
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 11px; font-weight: 900; color: #475569; text-transform: uppercase;">ID: #<?php echo $current_employee['employee_id']; ?></span>
                <a href="ess.php?logout=true" class="btn-danger" style="text-decoration: none;">Logout</a>
            </div>
        <?php endif; ?>
    </header>

    <?php if (!empty($msg)): ?>
        <div class="msg"><?php echo $msg; ?></div>
    <?php endif; ?>

    <?php if (!$current_employee): ?>
        <div class="card">
            <h2>Mobile Login</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Employee ID</label>
                        <input type="number" name="login_employee_id" placeholder="ID..." required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="login_credential" placeholder="Phone..." required>
                    </div>
                </div>
                <button type="submit" name="ess_login" class="btn-primary" style="margin-top: 10px;">Login</button>
            </form>
        </div>
    <?php else: ?>
        <div class="card">
            <h2>Profile Particulars</h2>
            <div class="form-grid" style="font-size: 11px; font-weight: 900; color: #1e293b;">
                <div>Name: <span style="font-weight: normal; color: #475569;"><?php echo htmlspecialchars(($current_employee['first_name'] ?? '') . ' ' . ($current_employee['last_name'] ?? '')); ?></span></div>
                <div>Phone: <span style="font-weight: normal; color: #475569;"><?php echo htmlspecialchars($current_employee['phone'] ?? 'N/A'); ?></span></div>
                <div>Email: <span style="font-weight: normal; color: #475569;"><?php echo htmlspecialchars($current_employee['email'] ?? 'N/A'); ?></span></div>
                <div>Next of Kin: <span style="font-weight: normal; color: #475569;"><?php echo htmlspecialchars($current_employee['next_of_kin'] ?? 'N/A'); ?></span></div>
                <div>Bank / Mobile Money: <span style="color: #dc2626;">🔒 Restricted</span></div>
            </div>
        </div>

        <div class="card">
            <h2>Submit Update Request</h2>
            <form method="POST">
                <input type="hidden" name="employee_id" value="<?php echo $current_employee['employee_id']; ?>">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Field</label>
                        <input type="text" name="field_to_update" placeholder="e.g. phone" required>
                    </div>
                    <div class="form-group">
                        <label>Old Value</label>
                        <input type="text" name="old_value" placeholder="Current..." required>
                    </div>
                    <div class="form-group">
                        <label>New Value</label>
                        <input type="text" name="new_value" placeholder="New..." required>
                    </div>
                </div>
                <button type="submit" name="submit_request" class="btn-primary" style="margin-top: 10px;">Submit Request</button>
            </form>
        </div>

        <div class="card">
            <h2>Leave Balances</h2>
            <div class="form-grid" style="font-size: 11px; font-weight: 900;">
                <?php if (count($leave_balances) > 0): ?>
                    <?php foreach ($leave_balances as $lb): ?>
                        <div><?php echo htmlspecialchars($lb['leave_type']); ?>: <span style="font-weight: normal; color: #475569;"><?php echo htmlspecialchars($lb['remaining']); ?> days left</span></div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="color: #475569; font-weight: normal;">No leave balances found.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <h2>Apply for Leave</h2>
            <form method="POST">
                <input type="hidden" name="employee_id" value="<?php echo $current_employee['employee_id']; ?>">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Type</label>
                        <select name="leave_type" required>
                            <option value="Annual">Annual</option>
                            <option value="Sick">Sick</option>
                            <option value="Maternity">Maternity</option>
                            <option value="Compensatory">Compensatory</option>
                        </select>
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
                        <label>Reason</label>
                        <input type="text" name="reason" placeholder="Reason..." required>
                    </div>
                </div>
                <button type="submit" name="apply_leave" class="btn-primary" style="margin-top: 10px;">Apply Leave</button>
            </form>
        </div>

        <h2 style="font-size: 13px; text-transform: uppercase; font-weight: 900; color: #1e293b; margin-top: 20px;">My Leave History</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Type</th>
                    <th>Dates</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($my_leaves) > 0): ?>
                    <?php foreach ($my_leaves as $ml): ?>
                        <tr>
                            <td>#<?php echo $ml['leave_id']; ?></td>
                            <td><?php echo htmlspecialchars($ml['leave_type']); ?></td>
                            <td><?php echo htmlspecialchars($ml['start_date'] . ' to ' . $ml['end_date']); ?></td>
                            <td><?php echo htmlspecialchars($ml['reason']); ?></td>
                            <td><strong><?php echo htmlspecialchars($ml['request_status']); ?></strong></td>
                            <td>
                                <?php if ($ml['request_status'] === 'Pending'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="cancel_leave_id" value="<?php echo $ml['leave_id']; ?>">
                                        <button type="submit" class="btn-danger" style="padding: 3px 6px;">Cancel</button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: #64748b;">N/A</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align: center; color: #64748b;">No leave history.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if (!empty($_SESSION['is_manager'])): ?>
            <h2 style="font-size: 13px; text-transform: uppercase; font-weight: 900; color: #1e293b; margin-top: 20px;">Manager: Direct Reports Leave</h2>
            <table>
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Type</th>
                        <th>Dates</th>
                        <th>Reason</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($manager_pending_leaves) > 0): ?>
                        <?php foreach ($manager_pending_leaves as $mpl): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($mpl['first_name'] . ' ' . $mpl['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($mpl['leave_type']); ?></td>
                                <td><?php echo htmlspecialchars($mpl['start_date'] . ' to ' . $mpl['end_date']); ?></td>
                                <td><?php echo htmlspecialchars($mpl['reason']); ?></td>
                                <td>
                                    <form method="POST" style="display: inline-flex; gap: 4px;">
                                        <input type="hidden" name="leave_id" value="<?php echo $mpl['leave_id']; ?>">
                                        <input type="hidden" name="action_type" value="approve">
                                        <button type="submit" name="manager_review_action" class="btn-primary" style="padding: 3px 6px;">Approve</button>
                                    </form>
                                    <form method="POST" style="display: inline-flex; gap: 4px;">
                                        <input type="hidden" name="leave_id" value="<?php echo $mpl['leave_id']; ?>">
                                        <input type="hidden" name="action_type" value="reject">
                                        <button type="submit" name="manager_review_action" class="btn-danger" style="padding: 3px 6px;">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align: center; color: #64748b;">No pending requests.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>

    <h2 style="font-size: 13px; text-transform: uppercase; font-weight: 900; color: #1e293b; margin-top: 20px;">HR Admin: Staging Catalog</h2>
    <?php if (count($pending_requests) > 0): ?>
        <div class="comparison-catalog">
            <?php foreach ($pending_requests as $req): ?>
                <div class="comparison-card">
                    <div style="font-size: 10px; font-weight: 900; color: #475569; margin-bottom: 8px; text-transform: uppercase;">
                        Req #<?php echo $req['change_request_id']; ?> | Emp #<?php echo $req['employee_id']; ?>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 10px;">
                        <div class="comparison-pane">
                            <div style="font-size: 9px; font-weight: 900; color: #dc2626; text-transform: uppercase;">Old</div>
                            <div style="font-size: 11px; font-weight: bold; color: #dc2626; word-break: break-all;"><?php echo htmlspecialchars($req['old_value'] ?: 'None'); ?></div>
                        </div>
                        <div class="comparison-pane">
                            <div style="font-size: 9px; font-weight: 900; color: #047857; text-transform: uppercase;">New</div>
                            <div style="font-size: 11px; font-weight: bold; color: #047857; word-break: break-all;"><?php echo htmlspecialchars($req['new_value']); ?></div>
                        </div>
                    </div>
                    <form method="POST" style="display: flex; gap: 6px;">
                        <input type="hidden" name="change_request_id" value="<?php echo $req['change_request_id']; ?>">
                        <input type="hidden" name="admin_id" value="1">
                        <button type="submit" name="review_action" value="approve" class="btn-primary" style="flex: 1;">Approve</button>
                        <button type="submit" name="review_action" value="reject" class="btn-danger" style="flex: 1;">Reject</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="background: #f8fafc; border: 1px solid #bfdbfe; padding: 15px; text-align: center; border-radius: 6px; color: #64748b; font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px;">
            No pending change requests.
        </div>
    <?php endif; ?>
</div>

</body>
</html>