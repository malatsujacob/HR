<?php
session_start();
require_once '../config/db.php'; // Adjust if your config folder is located elsewhere relative to the ESS module

if (!isset($_SESSION['ess_emp_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($new_password) || strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } else {
        // Securely hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update database and clear the mandatory change flag
        $update = $pdo->prepare("UPDATE employees SET password = ?, must_change_password = 0 WHERE employee_id = ?");
        if ($update->execute([$hashed_password, $_SESSION['ess_emp_id']])) {
            $success = "Password updated successfully! Redirecting to your dashboard...";
            header("refresh:2;url=ess.php");
            exit();
        } else {
            $error = "Database error updating password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Default Password - Employee Portal</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); width: 100%; max-width: 400px; border: 1px solid #e2e8f0; }
        h3 { margin-top: 0; color: #1e293b; font-size: 20px; }
        p { font-size: 13px; color: #64748b; line-height: 1.4; }
        .form-control { width: 100%; padding: 10px; margin-top: 5px; margin-bottom: 15px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-size: 13px; background: #ffffff; color: #0f172a; }
        .btn { background: #2563eb; color: white; border: none; padding: 10px; width: 100%; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 13px; }
        .btn:hover { background: #1d4ed8; }
        .alert-error { background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 6px; font-size: 13px; margin-bottom: 15px; border: 1px solid #fecaca; }
        .alert-success { background: #dcfce7; color: #166534; padding: 10px; border-radius: 6px; font-size: 13px; margin-bottom: 15px; border: 1px solid #bbf7d0; }
        label { font-size: 11px; font-weight: bold; color: #475569; text-transform: uppercase; }
    </style>
</head>
<body>
<div class="card">
    <h3>Security Update Required</h3>
    <p>You are logging in with a default password. Please choose a new, secure password to continue accessing your employee portal.</p>

    <?php if (!empty($error)): ?>
        <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>New Password</label>
        <input type="password" name="new_password" class="form-control" placeholder="At least 6 characters" required>

        <label>Confirm New Password</label>
        <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter new password" required>

        <button type="submit" class="btn">Update Password & Proceed</button>
    </form>
</div>
</body>
</html>