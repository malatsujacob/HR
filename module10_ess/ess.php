<?php
session_start();

// Correct paths to configuration and leave model
require_once '../config/db.php'; 
require_once '../module6_leave/leave_model.php';

$leaveModel = new LeaveModel($pdo);

$error = '';
$success_msg = '';

// Handle Logout - Destroys session and redirects back to the main index hub page
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header("Location: index.php");
    exit();
}

// Handle Login Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_user'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM employees WHERE personal_email = ? OR work_email = ? LIMIT 1");
            $stmt->execute([$email, $email]);
            $emp = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($emp) {
                $storedPassword = $emp['password'] ?? '';
                
                // Check if password matches via password_hash or default plaintext '1234' (or empty)
                $isPasswordValid = false;
                if ($password === '1234' && ($storedPassword === '1234' || empty($storedPassword) || password_verify('1234', $storedPassword))) {
                    $isPasswordValid = true;
                } elseif (password_verify($password, $storedPassword)) {
                    $isPasswordValid = true;
                }

                if ($isPasswordValid) {
                    $_SESSION['ess_emp_id'] = $emp['employee_id'];
                    header("Location: ess.php");
                    exit();
                } else {
                    $error = "Invalid password. Default temporary password is 1234.";
                }
            } else {
                $error = "No employee found with that email address.";
            }
        } catch (\Exception $e) {
            $error = "Login error: " . $e->getMessage();
        }
    }
}

// Check if user is logged in
$emp_id = $_SESSION['ess_emp_id'] ?? null;
$employeeData = [];

if ($emp_id) {
    try {
        // Fetch main employee details
        $stmt = $pdo->prepare("SELECT * FROM employees WHERE employee_id = ?");
        $stmt->execute([$emp_id]);
        $employeeData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$employeeData) {
            unset($_SESSION['ess_emp_id']);
            header("Location: ess.php");
            exit();
        }

        // Fetch contract & salary details
        $contractStmt = $pdo->prepare("
            SELECT monthly_salary AS salary, contract_start_date, expiry_date AS contract_end_date, 
                   contract_status, working_hours_per_week, shift_pattern, department, job_title 
            FROM employee_contracts WHERE employee_id = ? LIMIT 1
        ");
        $contractStmt->execute([$emp_id]);
        $contractData = $contractStmt->fetch(PDO::FETCH_ASSOC);

        if ($contractData) {
            $employeeData = array_merge($employeeData, $contractData);
        }

        // Handle Password Update / Reset Form Submission from inside the portal
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            $storedPassword = $employeeData['password'] ?? '';

            // Verify current password input
            $isCurrentValid = false;
            if ($current_password === '1234' && ($storedPassword === '1234' || empty($storedPassword))) {
                $isCurrentValid = true;
            } elseif (password_verify($current_password, $storedPassword)) {
                $isCurrentValid = true;
            }

            if (!$isCurrentValid) {
                $error = "Incorrect current password entered.";
            } elseif (empty($new_password) || strlen($new_password) < 4) {
                $error = "New password must be at least 4 characters long.";
            } elseif ($new_password !== $confirm_password) {
                $error = "New passwords do not match.";
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update = $pdo->prepare("UPDATE employees SET password = ?, must_change_password = FALSE WHERE employee_id = ?");
                if ($update->execute([$hashed_password, $emp_id])) {
                    $success_msg = "Password updated successfully! You can continue using your account.";
                    $employeeData['password'] = $hashed_password;
                } else {
                    $error = "Database error updating password.";
                }
            }
        }

        // Handle Leave Submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_leave'])) {
            $upload_file = null;
            if (!empty($_FILES['medical_certificate']['name'])) {
                $target_dir = "../uploads/";
                if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
                $upload_file = $target_dir . basename($_FILES['medical_certificate']['name']);
                move_uploaded_file($_FILES['medical_certificate']['tmp_name'], $upload_file);
            }

            $fullName = trim(($employeeData['first_name'] ?? '') . ' ' . ($employeeData['last_name'] ?? ''));
            $deptName = trim($employeeData['department'] ?? 'Administration');

            $result = $leaveModel->applyLeave([
                'employee_name' => $fullName,
                'department' => $deptName,
                'leave_type' => trim($_POST['leave_type']),
                'start_date' => $_POST['start_date'],
                'end_date' => $_POST['end_date'],
                'reason' => trim($_POST['reason']),
                'medical_certificate' => $upload_file
            ]);

            if ($result === true) {
                $success_msg = "Leave application submitted successfully!";
            } else {
                $error = $result;
            }
        }

    } catch (\Exception $e) {
        $error = "Error loading data: " . $e->getMessage();
    }
}

$tab = $_GET['tab'] ?? 'profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Self Service Portal</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f4f6f9; margin: 0; padding: 20px; color: #333; }
        .container { max-width: 1000px; margin: auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px; margin-bottom: 20px; }
        .header h2 { margin: 0; font-size: 22px; color: #1e293b; }
        .header p { margin: 5px 0 0 0; color: #64748b; font-size: 14px; }
        .nav-links { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 25px; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px; }
        .nav-links a { padding: 8px 14px; background: #f1f5f9; color: #475569; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: 500; transition: all 0.2s; }
        .nav-links a:hover { background: #e2e8f0; }
        .nav-links a.active { background: #2563eb; color: #fff; }
        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
        .info-group { background: #f8fafc; padding: 14px; border-radius: 6px; border: 1px solid #e2e8f0; }
        .info-group label { display: block; font-size: 11px; color: #64748b; text-transform: uppercase; margin-bottom: 4px; font-weight: 600; }
        .info-group span { font-size: 14px; color: #1e293b; font-weight: 600; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 15px; background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; }
        .data-table th, .data-table td { padding: 12px; border-bottom: 1px solid #e2e8f0; font-size: 13px; text-align: left; }
        .data-table th { background: #f8fafc; color: #475569; text-transform: uppercase; font-size: 11px; }
        .alert-error { background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 6px; font-size: 13px; margin-bottom: 15px; border: 1px solid #fecaca; }
        .alert-success { background: #dcfce7; color: #166534; padding: 10px; border-radius: 6px; font-size: 13px; margin-bottom: 15px; border: 1px solid #bbf7d0; }
        .form-control { width: 100%; padding: 10px; margin-top: 5px; margin-bottom: 15px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-size: 13px; background: #ffffff; color: #0f172a; }
        .btn-primary { background-color: #2563eb; color: #ffffff; padding: 10px 16px; border-radius: 6px; font-size: 13px; border: none; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block; }
        .btn-primary:hover { background-color: #1d4ed8; }
        .login-box { max-width: 400px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); border: 1px solid #e2e8f0; }
    </style>
</head>
<body>

<?php if (!$emp_id): ?>
    <!-- LOGIN SCREEN -->
    <div class="login-box">
        <h2 style="margin-top: 0; color: #1e293b; text-align: center;">Employee Portal Login</h2>
        <p style="text-align: center; color: #64748b; font-size: 13px; margin-bottom: 20px;">Enter your email and password to access your portal</p>

        <?php if (!empty($error)): ?>
            <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <label style="font-size: 11px; font-weight: bold; color: #475569; text-transform: uppercase;">Email Address</label>
            <input type="email" name="email" class="form-control" value="" autocomplete="off" required>

            <label style="font-size: 11px; font-weight: bold; color: #475569; text-transform: uppercase;">Password</label>
            <input type="password" name="password" class="form-control" value="" autocomplete="new-password" required>

            <button type="submit" name="login_user" class="btn-primary" style="width: 100%;">Sign In</button>
        </form>
    </div>

<?php else: ?>
    <!-- DASHBOARD SCREEN -->
    <div class="container">
        <div class="header">
            <div>
                <h2>Employee Portal Dashboard</h2>
                <p>Welcome back, <strong><?php echo htmlspecialchars(($employeeData['first_name'] ?? 'User') . ' ' . ($employeeData['last_name'] ?? '')); ?></strong>!</p>
            </div>
            <div>
                <a href="ess.php?action=logout" class="btn-primary" style="background: #dc2626; padding: 6px 12px; font-size: 12px;">Logout</a>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($success_msg)): ?>
            <div class="alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
        <?php endif; ?>

        <!-- Navigation Tabs -->
        <div class="nav-links">
            <a href="ess.php?tab=profile" class="<?php echo ($tab === 'profile') ? 'active' : ''; ?>">Dashboard Home / Profile</a>
            <a href="ess.php?tab=employment" class="<?php echo ($tab === 'employment') ? 'active' : ''; ?>">Employment & Department</a>
            <a href="ess.php?tab=contract" class="<?php echo ($tab === 'contract') ? 'active' : ''; ?>">Contract & Salary</a>
            <a href="ess.php?tab=my_training" class="<?php echo ($tab === 'my_training') ? 'active' : ''; ?>">My Trainings</a>
            <a href="ess.php?tab=leave" class="<?php echo ($tab === 'leave') ? 'active' : ''; ?>">Leave Request</a>
            <a href="ess.php?tab=job_requisitions" class="<?php echo ($tab === 'job_requisitions') ? 'active' : ''; ?>">Job Requisitions</a>
            <a href="ess.php?tab=hod_leave_approval" class="<?php echo ($tab === 'hod_leave_approval') ? 'active' : ''; ?>">HOD Leave Approvals</a>
            <a href="ess.php?tab=payslips" class="<?php echo ($tab === 'payslips') ? 'active' : ''; ?>">My Payslips</a>
            <a href="ess.php?tab=change_password" class="<?php echo ($tab === 'change_password') ? 'active' : ''; ?>">Change Password</a>
        </div>

        <!-- Tab Contents -->
        <div class="content-body">
            <?php if ($tab === 'change_password'): ?>
                <div style="max-width: 500px; margin: 0 auto; background: #f8fafc; border: 1px solid #e2e8f0; padding: 25px; border-radius: 6px;">
                    <h3 style="margin-top: 0; color: #1e293b;">Change Password</h3>
                    <p style="font-size: 13px; color: #64748b;">You can update your password here whenever you want.</p>
                    
                    <form method="POST" autocomplete="off">
                        <label style="font-size: 11px; font-weight: bold; color: #475569; text-transform: uppercase;">Current Password</label>
                        <input type="password" name="current_password" class="form-control" autocomplete="new-password" required>

                        <label style="font-size: 11px; font-weight: bold; color: #475569; text-transform: uppercase;">New Password</label>
                        <input type="password" name="new_password" class="form-control" autocomplete="new-password" required>

                        <label style="font-size: 11px; font-weight: bold; color: #475569; text-transform: uppercase;">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" autocomplete="new-password" required>

                        <button type="submit" name="update_password" class="btn-primary" style="width: 100%;">Update Password</button>
                    </form>
                </div>

            <?php elseif ($tab === 'profile'): ?>
                <h3>Personal Information</h3>
                <div class="info-grid">
                    <div class="info-group"><label>First Name</label><span><?php echo htmlspecialchars($employeeData['first_name'] ?? 'N/A'); ?></span></div>
                    <div class="info-group"><label>Last Name</label><span><?php echo htmlspecialchars($employeeData['last_name'] ?? 'N/A'); ?></span></div>
                    <div class="info-group"><label>Date of Birth</label><span><?php echo htmlspecialchars($employeeData['date_of_birth'] ?? 'N/A'); ?></span></div>
                    <div class="info-group"><label>Gender</label><span><?php echo htmlspecialchars($employeeData['gender'] ?? 'N/A'); ?></span></div>
                    <div class="info-group"><label>Marital Status</label><span><?php echo htmlspecialchars($employeeData['marital_status'] ?? 'N/A'); ?></span></div>
                    <div class="info-group"><label>Personal Email</label><span><?php echo htmlspecialchars($employeeData['personal_email'] ?? 'N/A'); ?></span></div>
                    <div class="info-group"><label>Phone Number</label><span><?php echo htmlspecialchars($employeeData['phone'] ?? 'N/A'); ?></span></div>
                    <div class="info-group"><label>Physical Address</label><span><?php echo htmlspecialchars($employeeData['physical_address'] ?? 'N/A'); ?></span></div>
                </div>

            <?php elseif ($tab === 'employment'): ?>
                <h3>Employment & Department Details</h3>
                <div class="info-grid">
                    <div class="info-group"><label>Department</label><span><?php echo htmlspecialchars($employeeData['department'] ?? 'N/A'); ?></span></div>
                    <div class="info-group"><label>Job Title</label><span><?php echo htmlspecialchars($employeeData['job_title'] ?? $employeeData['job_role'] ?? 'N/A'); ?></span></div>
                    <div class="info-group"><label>Employment Status</label><span><?php echo htmlspecialchars($employeeData['employment_status'] ?? 'N/A'); ?></span></div>
                    <div class="info-group"><label>Hire Date</label><span><?php echo htmlspecialchars($employeeData['hire_date'] ?? 'N/A'); ?></span></div>
                </div>

            <?php elseif ($tab === 'contract'): ?>
                <h3>Contract & Salary Information</h3>
                <div class="info-grid">
                    <div class="info-group"><label>Monthly Salary</label><span>UGX <?php echo number_format((float)($employeeData['salary'] ?? 0), 2); ?></span></div>
                    <div class="info-group"><label>Contract Status</label><span><?php echo htmlspecialchars($employeeData['contract_status'] ?? 'Active'); ?></span></div>
                    <div class="info-group"><label>Contract Start Date</label><span><?php echo htmlspecialchars($employeeData['contract_start_date'] ?? 'N/A'); ?></span></div>
                    <div class="info-group"><label>Contract Expiry Date</label><span><?php echo htmlspecialchars($employeeData['contract_end_date'] ?? 'N/A'); ?></span></div>
                    <div class="info-group"><label>Working Hours / Week</label><span><?php echo htmlspecialchars($employeeData['working_hours_per_week'] ?? 'N/A'); ?> Hours</span></div>
                    <div class="info-group"><label>Shift Pattern</label><span><?php echo htmlspecialchars($employeeData['shift_pattern'] ?? 'N/A'); ?></span></div>
                </div>

            <?php elseif ($tab === 'my_training'): ?>
                <h3>My Assigned Trainings & Progress</h3>
                <?php
                $myTrainings = [];
                try {
                    $stmt = $pdo->prepare("
                        SELECT c.course_name, c.category, c.venue_location, c.trainer_provider, 
                               c.start_time, c.end_time, e.completion_status, e.score_result, e.enrollment_date
                        FROM training_enrollments e
                        JOIN training_catalog c ON e.training_id = c.training_id
                        WHERE e.employee_id = ?
                        ORDER BY e.enrollment_date DESC
                    ");
                    $stmt->execute([$emp_id]);
                    $myTrainings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (\PDOException $e) {}
                ?>

                <?php if (empty($myTrainings)): ?>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 25px; border-radius: 6px; text-align: center;">
                        <p style="color: #64748b; font-size: 14px; margin: 0;">You are currently not enrolled in any training sessions.</p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Course Name</th>
                                <th>Category</th>
                                <th>Schedule</th>
                                <th>Venue / Link</th>
                                <th>Status</th>
                                <th>Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($myTrainings as $row): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['course_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['category'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['start_time']); ?><br><small style="color: #94a3b8;">to <?php echo htmlspecialchars($row['end_time']); ?></small></td>
                                <td>
                                    <?php if (!empty($row['venue_location'])): ?>
                                        <a href="<?php echo htmlspecialchars($row['venue_location']); ?>" target="_blank" style="color: #2563eb; text-decoration: none; font-weight: 500;">Access Location</a>
                                    <?php else: ?>
                                        <span style="color: #94a3b8;">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="background: #e0f2fe; color: #0369a1; padding: 4px 8px; border-radius: 4px; font-weight: 600; font-size: 11px;">
                                        <?php echo htmlspecialchars($row['completion_status'] ?? 'Enrolled'); ?>
                                    </span>
                                </td>
                                <td><strong><?php echo htmlspecialchars($row['score_result'] ?? 'Pending'); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

            <?php elseif ($tab === 'leave'): ?>
                <h3>Leave Request Management</h3>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 20px; border-radius: 6px; margin-top: 15px;">
                    <h4 style="margin-top: 0; color: #1e293b;">New Leave Application</h4>
                    <form method="POST" enctype="multipart/form-data">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div>
                                <label style="font-size: 11px; font-weight: bold; color: #475569; text-transform: uppercase;">Leave Type</label>
                                <input type="text" name="leave_type" class="form-control" required>
                            </div>
                            <div>
                                <label style="font-size: 11px; font-weight: bold; color: #475569; text-transform: uppercase;">Medical Certificate (Optional)</label>
                                <input type="file" name="medical_certificate" class="form-control">
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div>
                                <label style="font-size: 11px; font-weight: bold; color: #475569; text-transform: uppercase;">Start Date</label>
                                <input type="date" name="start_date" class="form-control" required>
                            </div>
                            <div>
                                <label style="font-size: 11px; font-weight: bold; color: #475569; text-transform: uppercase;">End Date</label>
                                <input type="date" name="end_date" class="form-control" required>
                            </div>
                        </div>

                        <label style="font-size: 11px; font-weight: bold; color: #475569; text-transform: uppercase;">Reason for Leave</label>
                        <textarea name="reason" class="form-control" rows="3" required></textarea>

                        <button type="submit" name="apply_leave" class="btn-primary">Submit Request</button>
                    </form>
                </div>

                <h4 style="margin-top: 25px; color: #1e293b;">My Leave History</h4>
                <?php
                $allRequests = $leaveModel->getLeaveRequests();
                $myFullName = trim(($employeeData['first_name'] ?? '') . ' ' . ($employeeData['last_name'] ?? ''));
                
                $employeeLeaveHistory = array_filter($allRequests, function($row) use ($myFullName) {
                    return strcasecmp(trim($row['employee_name']), $myFullName) === 0;
                });
                ?>

                <?php if (empty($employeeLeaveHistory)): ?>
                    <p style="color: #64748b; font-size: 13px;">No past leave requests found.</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr><th>Type & Dates</th><th>Reason & Cert</th><th>Status & Comments</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employeeLeaveHistory as $l): ?>
                            <tr>
                                <td>
                                    <strong style="color: #0284c7;"><?php echo htmlspecialchars($l['leave_type']); ?></strong><br>
                                    <span style="font-size: 11px; color: #475569;"><?php echo htmlspecialchars($l['start_date']); ?> to <?php echo htmlspecialchars($l['end_date']); ?></span>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($l['reason']); ?><br>
                                    <?php if (!empty($l['medical_certificate'])): ?>
                                        <a href="<?php echo htmlspecialchars($l['medical_certificate']); ?>" target="_blank" style="font-size: 11px; color: #0284c7;">View Certificate</a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="font-weight: bold; color: <?php echo ($l['status'] == 'Approved' ? '#166534' : ($l['status'] == 'Rejected' ? '#991b1b' : '#b45309')); ?>;">
                                        <?php echo htmlspecialchars($l['status']); ?>
                                    </span><br>
                                    <small style="color: #64748b;"><?php echo htmlspecialchars($l['manager_comment'] ?? ''); ?></small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

            <?php elseif ($tab === 'job_requisitions'): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <div>
                        <h3 style="margin:0; color:#1e293b;">Department Job Requisitions</h3>
                        <p style="font-size:13px; color:#64748b; margin:3px 0 0 0;">Manage headcount requests sent to HR.</p>
                    </div>
                    <button type="button" onclick="openRequisitionModal()" class="btn-primary">+ New Requisition</button>
                </div>

                <table class="data-table">
                    <thead>
                        <tr><th>Job Title</th><th>Department</th><th>Urgency</th><th>Description / Reason</th><th>Status</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                        <?php 
                        $dept_name_filter = $employeeData['department'] ?? '';
                        try {
                            $req_stmt = $pdo->prepare("SELECT * FROM job_requisitions WHERE department = ? ORDER BY req_id DESC");
                            $req_stmt->execute([$dept_name_filter]);
                            $requisitions = $req_stmt->fetchAll(PDO::FETCH_ASSOC);
                        } catch (\Exception $e) {
                            $requisitions = [];
                        }
                        
                        if (!empty($requisitions)): foreach ($requisitions as $req): 
                        ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($req['job_title'] ?? ''); ?></strong></td>
                                <td><?php echo htmlspecialchars($req['department'] ?? ''); ?></td>
                                <td><span style="color:#b45309; font-weight:600;"><?php echo htmlspecialchars($req['urgency'] ?? 'Medium'); ?></span></td>
                                <td><?php echo htmlspecialchars($req['description'] ?? ''); ?></td>
                                <td><span style="background:#e0f2fe; color:#0369a1; padding:3px 6px; border-radius:4px; font-weight:bold; font-size:11px;"><?php echo htmlspecialchars($req['status'] ?? 'Pending'); ?></span></td>
                                <td><?php echo htmlspecialchars($req['created_at'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="6" style="text-align:center; color:#64748b;">No job requisitions submitted yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- POPUP MODAL CONTAINER -->
                <div id="requisitionModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
                    <div style="background:#fff; width:100%; max-width:550px; padding:25px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.15); position:relative;">
                        <h3 style="margin-top:0; color:#1e293b;">Create New Job Requisition</h3>
                        
                        <form action="../module_recruitment/training_onboarding.php?action=submit_requisition" method="POST">
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                <div>
                                    <label style="font-size: 11px; font-weight: bold; color: #475569; text-transform: uppercase;">Job Title</label>
                                    <input type="text" name="job_title" class="form-control" placeholder="Job Title" required>
                                </div>
                                <div>
                                    <label style="font-size: 11px; font-weight: bold; color: #475569; text-transform: uppercase;">Department</label>
                                    <input type="text" name="department" class="form-control" value="<?php echo htmlspecialchars($employeeData['department'] ?? ''); ?>" placeholder="Department" required>
                                </div>
                            </div>

                            <label style="font-size: 11px; font-weight: bold; color: #475569; text-transform: uppercase;">Urgency</label>
                            <select name="urgency" class="form-control" required>
                                <option value="Low">Low</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="High">High</option>
                                <option value="Critical">Critical</option>
                            </select>

                            <label style="font-size: 11px; font-weight: bold; color: #475569; text-transform: uppercase;">Description / Reason</label>
                            <textarea name="description" class="form-control" rows="4" placeholder="Reason for requisition..." required></textarea>

                            <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:15px;">
                                <button type="button" onclick="closeRequisitionModal()" class="btn-primary" style="background:#cbd5e1; color:#334155;">Cancel</button>
                                <button type="submit" class="btn-primary">Submit Requisition</button>
                            </div>
                        </form>
                    </div>
                </div>

                <script>
                    function openRequisitionModal() { document.getElementById('requisitionModal').style.display = 'flex'; }
                    function closeRequisitionModal() { document.getElementById('requisitionModal').style.display = 'none'; }
                </script>

            <?php elseif ($tab === 'hod_leave_approval'): ?>
                <h3>Department Leave Approvals (HOD Portal)</h3>
                <p style="font-size: 13px; color: #64748b;">Review and action leave applications submitted by members of your department.</p>
                
                <?php
                $deptName = trim($employeeData['department'] ?? '');
                $pendingDeptLeaves = [];
                try {
                    $allLeaves = $leaveModel->getLeaveRequests();
                    $pendingDeptLeaves = array_filter($allLeaves, function($l) use ($deptName) {
                        return strcasecmp(trim($l['department'] ?? ''), $deptName) === 0;
                    });
                } catch (\Exception $e) {}
                ?>

                <?php if (empty($pendingDeptLeaves)): ?>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 25px; border-radius: 6px; text-align: center; margin-top: 15px;">
                        <p style="color: #64748b; font-size: 14px; margin: 0;">No pending leave requests found for your department.</p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr><th>Employee</th><th>Leave Details</th><th>Reason</th><th>Status / Action</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingDeptLeaves as $dLeave): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($dLeave['employee_name']); ?></strong></td>
                                <td>
                                    <span style="color: #0284c7; font-weight:600;"><?php echo htmlspecialchars($dLeave['leave_type']); ?></span><br>
                                    <small style="color: #475569;"><?php echo htmlspecialchars($dLeave['start_date']); ?> to <?php echo htmlspecialchars($dLeave['end_date']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($dLeave['reason']); ?></td>
                                <td>
                                    <span style="font-weight: bold; color: <?php echo ($dLeave['status'] == 'Approved' ? '#166534' : ($dLeave['status'] == 'Rejected' ? '#991b1b' : '#b45309')); ?>;">
                                        <?php echo htmlspecialchars($dLeave['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

            <?php elseif ($tab === 'payslips'): ?>
                <h3>My Cleared Payslips</h3>
                <?php
                $payslipsList = [];
                try {
                    $pStmt = $pdo->prepare("SELECT payroll_id, pay_period, net_pay, generated_at FROM payroll_records WHERE employee_id = ? ORDER BY payroll_id DESC");
                    $pStmt->execute([$emp_id]);
                    $payslipsList = $pStmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (\Exception $e) {}
                ?>

                <?php if (empty($payslipsList)): ?>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 25px; border-radius: 6px; text-align: center; margin-top: 15px;">
                        <p style="color: #64748b; font-size: 14px; margin: 0;">No salary clearances or payslips available yet.</p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr><th>Pay Period</th><th>Net Pay (UGX)</th><th>Generated Date</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payslipsList as $p): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($p['pay_period']); ?></strong></td>
                                <td>UGX <?php echo number_format($p['net_pay'], 2); ?></td>
                                <td><?php echo htmlspecialchars($p['generated_at'] ?? 'N/A'); ?></td>
                                <td>
                                    <a href="../module7_payroll/payslip.php?payslip_id=<?php echo $p['payroll_id']; ?>" target="_blank" class="btn-primary" style="padding: 5px 10px; font-size: 12px;">View / Download PDF</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

</body>
</html>