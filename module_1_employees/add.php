<?php
// Include the database connection using relative path from this folder
require_once '../config/db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. Capture and hash the initial password provided by HR
        $initial_password = trim($_POST['initial_password'] ?? 'TempPassword123!');
        $hashed_password = password_hash($initial_password, PASSWORD_DEFAULT);
        $role = $_POST['role'] ?? 'Employee';

        $sql = "INSERT INTO employees (
            first_name, last_name, date_of_birth, gender, nationality, marital_status, 
            personal_email, work_email, phone_number, physical_address, 
            next_of_kin_name, next_of_kin_relationship, next_of_kin_phone, next_of_kin_address,
            department, job_title, reporting_manager, work_location, hire_date, employment_type, status,
            password, role
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )";

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
            $hashed_password,
            $role
        ]);

        // Log the action into audit logs table using employee_id
        $employee_id = $pdo->lastInsertId();
        $audit_sql = "INSERT INTO employee_audit_logs (employee_id, action_performed, performed_by) VALUES (?, ?, ?)";
        $audit_stmt = $pdo->prepare($audit_sql);
        $audit_stmt->execute([$employee_id, 'Employee record and login credentials created', 'HR Admin']);

        $message = "Employee successfully added with login credentials (Initial Password: <strong>" . htmlspecialchars($initial_password) . "</strong>)!";
    } catch (PDOException $e) {
        $error = "Error adding employee: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Employee - HR System</title>
    <!-- Link to the shared global stylesheet -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background-color: #f8fafc; color: #0f172a; margin: 0; font-family: Arial, sans-serif; }
        .container { margin-left: 260px; max-width: calc(100% - 280px); padding: 25px; box-sizing: border-box; background: #f8fafc; min-height: 100vh; }
        header { border-bottom: 2px solid #cbd5e1; padding-bottom: 15px; margin-bottom: 25px; }
        header h1 { font-size: 22px; font-weight: bold; margin: 0; color: #0f172a; }
        .card { background: #ffffff; padding: 20px; border-radius: 6px; border: 1px solid #cbd5e1; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group.full-width { grid-column: span 2; }
        .form-group label { font-size: 11px; font-weight: bold; margin-bottom: 4px; color: #475569; text-transform: uppercase; }
        input, select, textarea { padding: 7px; font-size: 12px; border: 1px solid #94a3b8; border-radius: 4px; width: 100%; box-sizing: border-box; background: #ffffff; color: #0f172a; }
        input:focus, select:focus, textarea:focus { border-color: #0284c7; outline: none; }
        .section-title { grid-column: 1 / -1; font-size: 14px; margin-top: 10px; margin-bottom: 5px; color: #0369a1; font-weight: bold; border-left: 4px solid #0284c7; padding-left: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .btn-primary { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #ffffff; padding: 9px 16px; border-radius: 4px; font-size: 13px; border: none; cursor: pointer; font-weight: bold; margin-top: 15px; grid-column: span 2; }
        .btn-secondary { background: #e2e8f0; color: #334155; padding: 6px 12px; border-radius: 4px; font-size: 12px; text-decoration: none; font-weight: bold; border: 1px solid #cbd5e1; }
        .alert-success { background: #f0fdf4; color: #166534; padding: 10px 14px; border-radius: 4px; margin-bottom: 20px; font-size: 13px; border: 1px solid #bbf7d0; font-weight: bold; }
        .alert-error { background: #fef2f2; color: #991b1b; padding: 10px 14px; border-radius: 4px; margin-bottom: 20px; font-size: 13px; border: 1px solid #fecaca; font-weight: bold; }
    </style>
</head>
<body>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>

<div class="container">
    <header>
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h1>New Employee</h1>
            <a href="list.php" class="btn-secondary">← Back to Directory</a>
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
                    <input type="text" name="first_name" required>
                </div>

                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" required>
                </div>

                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" name="date_of_birth" required>
                </div>

                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender" required>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Nationality</label>
                    <input type="text" name="nationality" required>
                </div>

                <div class="form-group">
                    <label>Marital Status</label>
                    <select name="marital_status" required>
                        <option value="Single">Single</option>
                        <option value="Married">Married</option>
                        <option value="Divorced">Divorced</option>
                        <option value="Widowed">Widowed</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Personal Email</label>
                    <input type="email" name="personal_email" required>
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone_number" required>
                </div>

                <div class="form-group full-width">
                    <label>Physical Address</label>
                    <textarea name="physical_address" rows="2" required></textarea>
                </div>

                <div class="section-title">Next of Kin Details</div>

                <div class="form-group">
                    <label>Next of Kin Name</label>
                    <input type="text" name="next_of_kin_name" required>
                </div>

                <div class="form-group">
                    <label>Relationship</label>
                    <input type="text" name="next_of_kin_relationship" required>
                </div>

                <div class="form-group">
                    <label>Next of Kin Phone</label>
                    <input type="text" name="next_of_kin_phone" required>
                </div>

                <div class="form-group full-width">
                    <label>Next of Kin Address</label>
                    <textarea name="next_of_kin_address" rows="2" required></textarea>
                </div>

                <div class="section-title">Professional & Job Details</div>

                <div class="form-group">
                    <label>Work Email</label>
                    <input type="email" name="work_email" required>
                </div>

                <!-- NEW: System Access Credentials & Role -->
                <div class="form-group">
                    <label>Initial System Password</label>
                    <input type="text" name="initial_password" value="TempPassword123!" required>
                </div>

                <div class="form-group">
                    <label>System Access Role</label>
                    <select name="role" required>
                        <option value="Employee">Employee (ESS)</option>
                        <option value="HOD">HOD (Head of Department)</option>
                        <option value="Assistant HR">Assistant HR</option>
                        <option value="HR">HR Manager</option>
                        <option value="CEO">CEO</option>
                        <option value="MD">MD (Managing Director)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Department</label>
                    <input type="text" name="department" required>
                </div>

                <div class="form-group">
                    <label>Job Title</label>
                    <input type="text" name="job_title" required>
                </div>

                <div class="form-group">
                    <label>Reporting Manager</label>
                    <input type="text" name="reporting_manager" required>
                </div>

                <div class="form-group">
                    <label>Work Location</label>
                    <input type="text" name="work_location" required>
                </div>

                <div class="form-group">
                    <label>Hire Date</label>
                    <input type="date" name="hire_date" required>
                </div>

                <div class="form-group">
                    <label>Employment Type</label>
                    <select name="employment_type" required>
                        <option value="Full-time">Full-time</option>
                        <option value="Part-time">Part-time</option>
                        <option value="Contract">Contract</option>
                        <option value="Intern">Intern</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status" required>
                        <option value="Active">Active</option>
                        <option value="On Leave">On Leave</option>
                        <option value="Suspended">Suspended</option>
                        <option value="Exited">Exited</option>
                    </select>
                </div>

                <button type="submit" class="btn-primary">Save Employee Record</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>