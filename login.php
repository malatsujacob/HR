<?php
session_start();
require_once 'config/db.php'; // Adjust path if your config file is located elsewhere

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $work_email = trim($_POST['work_email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($work_email) || empty($password)) {
        $error = "Please enter both your work email and password.";
    } else {
        try {
            // Query the database for the employee using work_email
            $stmt = $pdo->prepare("SELECT * FROM employees WHERE work_email = ?");
            $stmt->execute([$work_email]);
            $employee = $stmt->fetch();

            // Check if employee exists and is active (blocks 'Exited' or suspended staff)
            if ($employee && $employee['status'] === 'Active') {
                
                // Securely verify the password hash
                if (password_verify($password, $employee['password'])) {
                    
                    // Register session variables
                    $_SESSION['employee_id'] = $employee['employee_id'];
                    $_SESSION['first_name'] = $employee['first_name'];
                    $_SESSION['last_name'] = $employee['last_name'];
                    $_SESSION['work_email'] = $employee['work_email'];
                    $_SESSION['role'] = $employee['role'] ?? 'Employee';
                    $_SESSION['department'] = $employee['department'];

                    // Role-based routing after successful login (Updated folder paths)
                    if (in_array($_SESSION['role'], ['HR', 'Assistant HR', 'CEO', 'MD'])) {
                        header('Location: module_1_employees/index.php'); // Admin / Management dashboard
                        exit;
                    } else {
                        header('Location: module_1_employees/index.php'); // Routed to your actual module folder
                        exit;
                    }

                } else {
                    $error = "Invalid password. If you forgot your password, please request a reset from HR.";
                }
            } else {
                $error = "Account not found, inactive, or exited. Please contact HR.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Chap Chap HRMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="background: #f1f5f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0;">

    <div class="card" style="width: 100%; max-width: 400px; padding: 30px; background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <div style="text-align: center; margin-bottom: 20px;">
            <h2 style="color: #1e293b; margin-bottom: 5px;">Chap Chap Africa</h2>
            <p style="font-size: 13px; color: #64748b;">HRMS Portal Login</p>
        </div>

        <?php if (!empty($error)): ?>
            <div style="background: #fee2e2; border: 1px solid #f87171; color: #991b1b; padding: 10px; border-radius: 4px; font-size: 12px; margin-bottom: 15px;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group" style="margin-bottom: 15px;">
                <label style="display: block; font-size: 12px; font-weight: bold; color: #334155; margin-bottom: 5px;">Work Email</label>
                <input type="email" name="work_email" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box;">
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; font-size: 12px; font-weight: bold; color: #334155; margin-bottom: 5px;">Password</label>
                <input type="password" name="password" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box;">
            </div>

            <button type="submit" style="width: 100%; background: #2563eb; color: white; border: none; padding: 10px; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 14px;">
                Sign In
            </button>
        </form>
    </div>

</body>
</html>