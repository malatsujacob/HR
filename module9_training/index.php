<?php
// module9_training/index.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userRole = strtolower(trim($_SESSION['user_role'] ?? ''));
$isHRAdmin = in_array($userRole, ['admin', 'hr'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Hub - HRMS</title>
    <style>
        body { background: #ffffff; color: #1e293b; margin: 0; font-family: Arial, sans-serif; }
        .container { margin-left: 260px; max-width: 600px; padding: 20px; box-sizing: border-box; background: #ffffff; min-height: 100vh; }
        header { border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
        header h1 { font-size: 16px; font-weight: 900; margin: 0; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px; }
        .brand-title { color: #2563eb; font-weight: 900; }
        .grid-matrix { display: flex; flex-direction: column; gap: 15px; }
        .card { background: #eff6ff; padding: 12px 16px; border-radius: 6px; border: 1px solid #bfdbfe; margin-bottom: 0; }
        .card h2 { font-size: 12px; margin-top: 0; color: #2563eb; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 900; border-left: 3px solid #2563eb; padding-left: 6px; margin-bottom: 8px; }
        .card p { margin: 0 0 10px; color: #2563eb; line-height: 1.4; font-size: 11px; font-weight: 900; text-transform: uppercase; }
        .btn { display: inline-block; background: #2563eb; color: #ffffff; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-weight: 900; font-size: 11px; text-transform: uppercase; border: none; cursor: pointer; }
        .btn:hover { background: #1d4ed8; }
        .badge { display: inline-block; padding: 3px 6px; border-radius: 4px; background: #dbeafe; color: #2563eb; font-size: 9px; font-weight: 900; text-transform: uppercase; margin-bottom: 8px; border: 1px solid #bfdbfe; }
    </style>
</head>
<body>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>

<div class="container">
    <header>
        <h1><span class="brand-title">CHAP CHAP</span> <span class="brand-title" style="color: #3b82f6;">AFRICA</span> - Training Hub</h1>
    </header>

    <div class="grid-matrix">
        <div class="card">
            <span class="badge"><?php echo $isHRAdmin ? 'HR / Admin' : 'Employee'; ?></span>
            <h2>Training Schedule</h2>
            <p>View scheduled sessions.</p>
            <a href="training.php" class="btn">Open Schedule</a>
        </div>

        <div class="card">
            <span class="badge">HR / Admin</span>
            <h2>HR Management</h2>
            <p>Manage courses & enrollments.</p>
            <a href="hr_login.php" class="btn">Open Management</a>
        </div>
    </div>
</div>

</body>
</html>