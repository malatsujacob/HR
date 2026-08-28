<?php
// module7_payroll/index.php - Payroll central module entry
session_start();
require_once '../config/db.php';

$success_msg = '';
$error_msg = '';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS hr_settings (
            id INT PRIMARY KEY,
            hr_password VARCHAR(255) NOT NULL
        )
    ");
    $pdo->exec("
        INSERT INTO hr_settings (id, hr_password)
        SELECT 1, '1234'
        WHERE NOT EXISTS (SELECT 1 FROM hr_settings WHERE id = 1)
    ");
} catch (Exception $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hr_login'])) {
    $entered_pass = trim($_POST['hr_password_input'] ?? '');
    $stmt = $pdo->query("SELECT hr_password FROM hr_settings WHERE id = 1");
    $row_settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row_settings && $entered_pass === $row_settings['hr_password']) {
        $_SESSION['payroll_hr_logged_in'] = true;
        $_SESSION['show_payroll_login_form'] = false;
        $success_msg = "Authenticated successfully!";
    } else {
        $_SESSION['show_payroll_login_form'] = true;
        $error_msg = "Incorrect password.";
    }
}

if (isset($_GET['logout_hr'])) {
    unset($_SESSION['payroll_hr_logged_in']);
    unset($_SESSION['show_payroll_login_form']);
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

if (isset($_GET['toggle_login_form'])) {
    $_SESSION['show_payroll_login_form'] = !($_SESSION['show_payroll_login_form'] ?? false);
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

$is_hr_logged = $_SESSION['payroll_hr_logged_in'] ?? false;
$show_login_form = $_SESSION['show_payroll_login_form'] ?? false;

try {
    $latest_run = $pdo->query("SELECT run_id FROM payroll_runs ORDER BY run_id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $run_id = $latest_run['run_id'] ?? 0;

    $latest_slip = $pdo->query("SELECT payslip_id FROM employee_payslips ORDER BY payslip_id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $payslip_id = $latest_slip['payslip_id'] ?? 0;
} catch (Exception $e) {
    $run_id = 0;
    $payslip_id = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll - HRMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { 
            background-color: #ffffff; 
            color: #1e293b; 
            margin: 0; 
            font-family: Arial, sans-serif; 
        }
        .container { 
            margin-left: 260px; 
            max-width: calc(100% - 260px); 
            padding: 20px; 
            box-sizing: border-box; 
            background: #ffffff; 
            min-height: 100vh; 
        }
        header { 
            border-bottom: 2px solid #e2e8f0; 
            padding-bottom: 12px; 
            margin-bottom: 20px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        header h1 { 
            font-size: 18px; 
            font-weight: 900; 
            margin: 0; 
            color: #1e293b; 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
        }
        .brand-title { color: #2563eb; font-weight: 900; }
        .brand-title span { color: #3b82f6; }
        .card { 
            background: #eff6ff; 
            padding: 20px; 
            border-radius: 6px; 
            border: 1px solid #bfdbfe; 
            box-shadow: 0 1px 3px rgba(37, 99, 235, 0.1); 
            margin-bottom: 20px; 
            max-width: 400px; 
            box-sizing: border-box;
        }
        .box-container { 
            background: #eff6ff; 
            border: 1px solid #bfdbfe; 
            border-radius: 6px; 
            padding: 16px; 
            margin-top: 15px;
            max-width: 400px;
            box-sizing: border-box;
        }
        .links-grid { 
            display: flex; 
            flex-direction: column; 
            gap: 10px; 
            margin-top: 10px; 
            align-items: flex-start; 
        }
        .link-card { 
            background: #ffffff; 
            border: 1px solid #93c5fd;
            padding: 8px 14px; 
            border-radius: 4px; 
            display: inline-block; 
            width: 100%;
            box-sizing: border-box;
            transition: background 0.2s;
        }
        .link-card a { 
            color: #2563eb; 
            text-decoration: none; 
            font-weight: 900; 
            font-size: 11px; 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
            display: block; 
        }
        .link-card:hover { 
            background: #dbeafe; 
        }
        .btn-purple { 
            background: #7c3aed; 
            color: white; 
            padding: 6px 12px; 
            border-radius: 4px; 
            font-size: 11px; 
            font-weight: 900; 
            border: none; 
            cursor: pointer; 
            text-transform: uppercase;
        }
        .btn-red { 
            background: #dc2626; 
            color: white; 
            padding: 5px 10px; 
            border-radius: 4px; 
            font-size: 10px; 
            font-weight: 900; 
            text-decoration: none; 
            border: none; 
            cursor: pointer; 
            text-transform: uppercase;
        }
        .btn-link-action { 
            background: none; 
            border: none; 
            padding: 0; 
            font-size: 11px; 
            font-weight: 900; 
            color: #1e293b; 
            cursor: pointer; 
            text-align: left; 
            width: 100%; 
            display: block; 
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .btn-link-action:hover { color: #2563eb; }
        .login-input { 
            font-size: 11px; 
            padding: 7px 10px; 
            width: 200px; 
            box-sizing: border-box; 
            border: 1px solid #bfdbfe; 
            border-radius: 4px; 
            background: #fff; 
            color: #1e293b; 
        }
        .alert-error { 
            background: #fee2e2; 
            color: #991b1b; 
            padding: 8px; 
            border-radius: 4px; 
            margin-bottom: 12px; 
            font-size: 11px; 
            font-weight: 900; 
            text-transform: uppercase; 
            border: 1px solid #fecaca;
        }
        .alert-success { 
            background: #dcfce7; 
            color: #166534; 
            padding: 8px; 
            border-radius: 4px; 
            margin-bottom: 12px; 
            font-size: 11px; 
            font-weight: 900; 
            text-transform: uppercase; 
            border: 1px solid #bbf7d0; 
        }
    </style>
</head>
<body>

<?php 
include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php');
?>

<div class="container">
    <header>
        <h1><span class="brand-title">CHAP CHAP</span> <span class="brand-title" style="color: #3b82f6;">AFRICA</span> - Payroll</h1>
        <div>
            <?php if ($is_hr_logged): ?>
                <a href="?logout_hr=1" class="btn-red">Logout Module</a>
            <?php endif; ?>
        </div>
    </header>

    <?php if (!empty($success_msg)): ?>
        <div class="alert-success" style="max-width: 400px;"><?php echo $success_msg; ?></div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div class="alert-error" style="max-width: 400px;"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <?php if (!$is_hr_logged): ?>
        <div class="box-container">
            <?php if (!$show_login_form): ?>
                <form method="GET" style="margin: 0;">
                    <input type="hidden" name="toggle_login_form" value="1">
                    <button type="submit" class="btn-link-action">
                        🔒 Payroll
                    </button>
                </form>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <form method="GET" style="margin: 0;">
                        <input type="hidden" name="toggle_login_form" value="1">
                        <button type="submit" class="btn-link-action">
                            🔒 Payroll
                        </button>
                    </form>
                    
                    <form method="POST" style="display: flex; gap: 8px; align-items: center; margin: 0; padding-top: 5px; border-top: 1px solid #bfdbfe;">
                        <input type="password" name="hr_password_input" class="login-input" placeholder="Enter password" required autofocus>
                        <button type="submit" name="hr_login" class="btn-purple">Login</button>
                        <a href="?toggle_login_form=1" style="font-size: 11px; color: #64748b; text-decoration: none; padding: 6px 10px; font-weight: 900; text-transform: uppercase;">Cancel</a>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="card">
            <h3 style="font-size: 12px; font-weight: 900; color: #1e293b; margin-top: 0; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px;">Module Navigation</h3>
            <div class="links-grid">
                <div class="link-card">
                    <a href="dashboard.php">Payroll Dashboard</a>
                </div>
                <div class="link-card">
                    <a href="payroll.php">Payroll</a>
                </div>
                <div class="link-card">
                    <a href="disbursement_engine.php?run_id=<?php echo $run_id; ?>">Disbursement Engine</a>
                </div>
                <div class="link-card">
                    <a href="export_bank.php?run_id=<?php echo $run_id; ?>">Export Bank</a>
                </div>
                <div class="link-card">
                    <a href="payslip.php?payslip_id=<?php echo $payslip_id; ?>">Payslips</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

</body>
</html>