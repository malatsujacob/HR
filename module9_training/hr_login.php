<?php
// module9_training/hr_login.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['user_role'] = 'hr';
    header('Location: manage_training.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Login - HRMS</title>
    <style>
        body { background: #ffffff; color: #1e293b; margin: 0; font-family: Arial, sans-serif; }
        .container { margin-left: 260px; max-width: 600px; padding: 20px; box-sizing: border-box; background: #ffffff; min-height: 100vh; }
        header { border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
        header h1 { font-size: 16px; font-weight: 900; margin: 0; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px; }
        .brand-title { color: #2563eb; font-weight: 900; }
        .card { background: #eff6ff; padding: 12px 16px; border-radius: 6px; border: 1px solid #bfdbfe; margin-bottom: 15px; }
        .card h2 { font-size: 12px; margin-top: 0; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 900; border-left: 3px solid #2563eb; padding-left: 6px; margin-bottom: 8px; }
        .card p { margin: 0 0 8px; color: #1e293b; line-height: 1.4; font-size: 11px; font-weight: 900; text-transform: uppercase; }
        .btn { display: inline-block; background: #2563eb; color: #ffffff; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-weight: 900; font-size: 11px; text-transform: uppercase; border: none; cursor: pointer; }
        .btn:hover { background: #1d4ed8; }
        a { color: #2563eb; text-decoration: none; font-weight: 900; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>

<div class="container">
    <header>
        <h1><span class="brand-title">CHAP CHAP</span> <span class="brand-title" style="color: #3b82f6;">AFRICA</span> - HR Login</h1>
    </header>

    <div class="card">
        <h2>HR Login</h2>
        <p>Click below to sign in as HR.</p>
        <form method="POST">
            <button type="submit" class="btn">Login</button>
        </form>
        <p style="margin-top: 8px; font-size: 10px; color: #64748b;">Go back to <a href="index.php">Training Hub</a>.</p>
    </div>
</div>

</body>
</html>