<?php
// Include the database connection
require_once '../config/db.php';

$message = '';
$error = '';
$reset_msg = '';

// Get the employee ID from the URL query string
$employee_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($employee_id <= 0) {
    header('Location: list.php');
    exit;
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Handle HR Password Reset Request
    if (isset($_POST['hr_reset_password'])) {
        $temp_password = 'TempPassword123!';
        $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("UPDATE employees SET password = ? WHERE employee_id = ?");
            $stmt->execute([$hashed_password, $employee_id]);

            // Log the password reset action
            $audit_stmt = $pdo->prepare("INSERT INTO employee_audit_logs (employee_id, action_performed, performed_by) VALUES (?, ?, ?)");
            $audit_stmt->execute([$employee_id, 'Password reset by HR', 'HR Admin']);

            $reset_msg = "Password successfully reset! Give this temporary password to the employee: <strong style='background: #fef08a; padding: 2px 6px; border-radius: 4px;'>{$temp_password}</strong>";
        } catch (PDOException $e) {
            $error = "Error resetting password: " . $e->getMessage();
        }
    }
    // 2. Handle Soft Delete Request
    elseif (isset($_POST['action']) && $_POST['action'] === 'soft_delete') {
        try {
            $stmt = $pdo->prepare("UPDATE employees SET status = 'Exited' WHERE employee_id = ?");
            $stmt->execute([$employee_id]);

            // Log the soft delete action
            $audit_stmt = $pdo->prepare("INSERT INTO employee_audit_logs (employee_id, action_performed, performed_by) VALUES (?, ?, ?)");
            $audit_stmt->execute([$employee_id, 'Employee soft-deleted (Status set to Exited)', 'HR Admin']);

            header('Location: list.php');
            exit;
        } catch (PDOException $e) {
            $error = "Error updating status: " . $e->getMessage();
        }
    } 
    // 3. Standard Profile Update
    else {
        try {
            $sql = "UPDATE employees SET 
                first_name = ?, last_name = ?, date_of_birth = ?, gender = ?, nationality = ?, marital_status = ?, 
                personal_email = ?, work_email = ?, phone_number = ?, physical_address = ?, 
                next_of_kin_name = ?, next_of_kin_relationship = ?, next_of_kin_phone = ?, next_of_kin_address = ?,
                department = ?, job_title = ?, reporting_manager = ?, work_location = ?, hire_date = ?, employment_type = ?, status = ?, role = ?
                WHERE employee_id = ?";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['date_of_birth'],
                $_POST['gender'],
                $_POST['nationality'],
                $_POST['marital_status'],
                $_POST['personal_email'],
                $_POST['work_email'],
                $_POST['phone_number'],
                $_POST['physical_address'],
                $_POST['next_of_kin_name'],
                $_POST['next_of_kin_relationship'],
                $_POST['next_of_kin_phone'],
                $_POST['next_of_kin_address'],
                $_POST['department'],
                $_POST['job_title'],
                $_POST['reporting_manager'],
                $_POST['work_location'],
                $_POST['hire_date'],
                $_POST['employment_type'],
                $_POST['status'],
                $_POST['role'],
                $employee_id
            ]);

            // Log the update action into audit logs
            $audit_stmt = $pdo->prepare("INSERT INTO employee_audit_logs (employee_id, action_performed, performed_by) VALUES (?, ?, ?)");
            $audit_stmt->execute([$employee_id, 'Employee record and role updated', 'HR Admin']);

            $message = "Employee record successfully updated!";
        } catch (PDOException $e) {
            $error = "Error updating employee: " . $e->getMessage();
        }
    }
}

// Fetch current employee details
try {
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE employee_id = ?");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch();

    if (!$employee) {
        die("Employee not found.");
    }
} catch (PDOException $e) {
    die("Error loading record: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Employee - <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>

<div class="container" style="margin-left: 280px;">
    <header>
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h1>Edit Employee: <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></h1>
            <a href="view.php?id=<?php echo $employee_id; ?>" class="back-link" style="margin-top: 0;">&larr; Back to Profile</a>
        </div>
    </header>

    <?php if ($message): ?>
        <div class="alert-success"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($reset_msg): ?>
        <div style="background: #fefce8; border: 1px solid #fde047; color: #854d0e; padding: 12px; border-radius: 4px; font-size: 13px; margin-bottom: 20px; font-weight: bold;">
            <?php echo $reset_msg; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <form method="POST" action="">
            <div class="form-grid">
                
                <div class="section-title">Personal Information</div>
                
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($employee['first_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($employee['last_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" name="date_of_birth" value="<?php echo htmlspecialchars($employee['date_of_birth']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender" required>
                        <option value="Male" <?php echo $employee['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo $employee['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo $employee['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Nationality</label>
                    <input type="text" name="nationality" value="<?php echo htmlspecialchars($employee['nationality']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Marital Status</label>
                    <select name="marital_status" required>
                        <option value="Single" <?php echo $employee['marital_status'] === 'Single' ? 'selected' : ''; ?>>Single</option>
                        <option value="Married" <?php echo $employee['marital_status'] === 'Married' ? 'selected' : ''; ?>>Married</option>
                        <option value="Divorced" <?php echo $employee['marital_status'] === 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                        <option value="Widowed" <?php echo $employee['marital_status'] === 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Personal Email</label>
                    <input type="email" name="personal_email" value="<?php echo htmlspecialchars($employee['personal_email']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone_number" value="<?php echo htmlspecialchars($employee['phone_number']); ?>" required>
                </div>

                <div class="form-group full-width">
                    <label>Physical Address</label>
                    <textarea name="physical_address" rows="2" required><?php echo htmlspecialchars($employee['physical_address']); ?></textarea>
                </div>

                <div class="section-title">Next of Kin Details</div>

                <div class="form-group">
                    <label>Next of Kin Name</label>
                    <input type="text" name="next_of_kin_name" value="<?php echo htmlspecialchars($employee['next_of_kin_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Relationship</label>
                    <input type="text" name="next_of_kin_relationship" value="<?php echo htmlspecialchars($employee['next_of_kin_relationship']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Next of Kin Phone</label>
                    <input type="text" name="next_of_kin_phone" value="<?php echo htmlspecialchars($employee['next_of_kin_phone']); ?>" required>
                </div>

                <div class="form-group full-width">
                    <label>Next of Kin Address</label>
                    <textarea name="next_of_kin_address" rows="2" required><?php echo htmlspecialchars($employee['next_of_kin_address']); ?></textarea>
                </div>

                <div class="section-title">Professional & Job Details</div>

                <div class="form-group">
                    <label>Work Email</label>
                    <input type="email" name="work_email" value="<?php echo htmlspecialchars($employee['work_email']); ?>" required>
                </div>

                <!-- NEW: System Access Role Selection -->
                <div class="form-group">
                    <label>System Access Role</label>
                    <select name="role" required>
                        <option value="Employee" <?php echo ($employee['role'] ?? '') === 'Employee' ? 'selected' : ''; ?>>Employee (ESS)</option>
                        <option value="HOD" <?php echo ($employee['role'] ?? '') === 'HOD' ? 'selected' : ''; ?>>HOD (Head of Department)</option>
                        <option value="Assistant HR" <?php echo ($employee['role'] ?? '') === 'Assistant HR' ? 'selected' : ''; ?>>Assistant HR</option>
                        <option value="HR" <?php echo ($employee['role'] ?? '') === 'HR' ? 'selected' : ''; ?>>HR Manager</option>
                        <option value="CEO" <?php echo ($employee['role'] ?? '') === 'CEO' ? 'selected' : ''; ?>>CEO</option>
                        <option value="MD" <?php echo ($employee['role'] ?? '') === 'MD' ? 'selected' : ''; ?>>MD (Managing Director)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Department</label>
                    <input type="text" name="department" value="<?php echo htmlspecialchars($employee['department']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Job Title</label>
                    <input type="text" name="job_title" value="<?php echo htmlspecialchars($employee['job_title']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Reporting Manager</label>
                    <input type="text" name="reporting_manager" value="<?php echo htmlspecialchars($employee['reporting_manager']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Work Location</label>
                    <input type="text" name="work_location" value="<?php echo htmlspecialchars($employee['work_location']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Hire Date</label>
                    <input type="date" name="hire_date" value="<?php echo htmlspecialchars($employee['hire_date']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Employment Type</label>
                    <select name="employment_type" required>
                        <option value="Full-time" <?php echo $employee['employment_type'] === 'Full-time' ? 'selected' : ''; ?>>Full-time</option>
                        <option value="Part-time" <?php echo $employee['employment_type'] === 'Part-time' ? 'selected' : ''; ?>>Part-time</option>
                        <option value="Contract" <?php echo $employee['employment_type'] === 'Contract' ? 'selected' : ''; ?>>Contract</option>
                        <option value="Intern" <?php echo $employee['employment_type'] === 'Intern' ? 'selected' : ''; ?>>Intern</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status" required>
                        <option value="Active" <?php echo $employee['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="On Leave" <?php echo $employee['status'] === 'On Leave' ? 'selected' : ''; ?>>On Leave</option>
                        <option value="Suspended" <?php echo $employee['status'] === 'Suspended' ? 'selected' : ''; ?>>Suspended</option>
                        <option value="Exited" <?php echo $employee['status'] === 'Exited' ? 'selected' : ''; ?>>Exited</option>
                    </select>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 20px; grid-column: span 2;">
                    <button type="submit" class="btn-primary">Update Changes</button>
                </div>
            </div>
        </form>

        <!-- HR Password Reset Form Section -->
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #cbd5e1;">
            <h3 style="font-size: 13px; color: #b45309; text-transform: uppercase; margin-bottom: 5px;">🔒 Security & Password Reset</h3>
            <p style="font-size: 12px; color: #475569; margin-bottom: 10px;">If this employee forgot their password, HR can reset it to a temporary default here.</p>
            
            <form method="POST" action="" onsubmit="return confirm('Are you sure you want to reset this employee\'s password to the default temporary value?');">
                <button type="submit" name="hr_reset_password" style="background-color: #f59e0b; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 11px; text-transform: uppercase;">
                    🔑 Reset Employee Password
                </button>
            </form>
        </div>

        <!-- Soft Delete Form -->
        <form method="POST" action="" onsubmit="return confirm('Are you sure you want to soft-delete this employee? Their status will be set to Exited.');" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #cbd5e1;">
            <input type="hidden" name="action" value="soft_delete">
            <button type="submit" style="background-color: #dc3545; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 11px; text-transform: uppercase;">Soft Delete Employee</button>
        </form>
    </div>
</div>

</body>
</html>