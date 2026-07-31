<?php
// Include the database connection
require_once '../config/db.php';

$message = '';
$error = '';

// Get the employee ID from the URL query string
$employee_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($employee_id <= 0) {
    header('Location: list.php');
    exit;
}

// Handle Form Submission for Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if action is a soft delete request
    if (isset($_POST['action']) && $_POST['action'] === 'soft_delete') {
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
    } else {
        // Standard profile update
        try {
            $sql = "UPDATE employees SET 
                first_name = ?, last_name = ?, date_of_birth = ?, gender = ?, nationality = ?, marital_status = ?, 
                personal_email = ?, work_email = ?, phone_number = ?, physical_address = ?, 
                next_of_kin_name = ?, next_of_kin_relationship = ?, next_of_kin_phone = ?, next_of_kin_address = ?,
                department = ?, job_title = ?, reporting_manager = ?, work_location = ?, hire_date = ?, employment_type = ?, status = ?
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
                $employee_id
            ]);

            // Log the update action into audit logs
            $audit_stmt = $pdo->prepare("INSERT INTO employee_audit_logs (employee_id, action_performed, performed_by) VALUES (?, ?, ?)");
            $audit_stmt->execute([$employee_id, 'Employee record updated', 'HR Admin']);

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

                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn-primary">Update Changes</button>
                </div>
            </div>
        </form>

        <!-- Soft Delete Form -->
        <form method="POST" action="" onsubmit="return confirm('Are you sure you want to soft-delete this employee? Their status will be set to Exited.');" style="margin-top: 15px;">
            <input type="hidden" name="action" value="soft_delete">
            <button type="submit" style="background-color: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold;">Soft Delete Employee</button>
        </form>
    </div>
</div>

</body>
</html>