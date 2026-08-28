<?php
// moduleX_contracts/contracts.php
session_start();
require_once '../config/db.php';

$success_msg = '';
$error_msg = '';

// 1. Ensure user is logged in
if (!isset($_SESSION['employee_id']) && !isset($_SESSION['user_id'])) {
    header("Location: ../index.php?error=unauthorized");
    exit();
}

$user_role = $_SESSION['role'] ?? '';

// 2. Strict HR & Assistant HR check (plus a developer override if you use a specific developer session/flag)
$is_strict_hr = in_array($user_role, ['HR', 'Assistant HR']);
$is_developer = (isset($_SESSION['is_developer']) && $_SESSION['is_developer'] === true) || ($user_role === 'Developer'); // Adjust this condition if your dev shortcut uses a different session flag

if (!$is_strict_hr && !$is_developer) {
    header("Location: ../index.php?error=access_denied");
    exit();
}

// Handle form submission to create a contract record in employee_contracts
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_contract'])) {
    $employee_name = trim($_POST['employee_name']);
    $department = trim($_POST['department']);
    $job_title = trim($_POST['job_title']);
    $monthly_salary = $_POST['monthly_salary'];
    $contract_start_date = $_POST['start_date'];
    $expiry_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;

    try {
        $stmt = $pdo->prepare("INSERT INTO employee_contracts (employee_name, department, job_title, monthly_salary, contract_start_date, expiry_date, contract_status) VALUES (?, ?, ?, ?, ?, ?, 'Active')");
        $stmt->execute([$employee_name, $department, $job_title, $monthly_salary, $contract_start_date, $expiry_date]);

        header("Location: contracts.php?success=created");
        exit();
    } catch (PDOException $e) {
        $error = "Error creating contract record: " . $e->getMessage();
    }
}

// Catch success message from redirect
if (isset($_GET['success']) && $_GET['success'] === 'created') {
    $success_msg = "Contract record saved successfully!";
}

// Fetch all employee contract records
$contracts = [];
try {
    $stmt = $pdo->query("SELECT * FROM employee_contracts ORDER BY contract_id DESC");
    $contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $contracts = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contracts Management - HRMS</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #ffffff;
            color: #1e293b;
            margin: 0;
            padding: 0;
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
            padding-bottom: 10px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .page-title {
            margin: 0;
            color: #1e293b;
            font-size: 16px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .brand-title {
            color: #2563eb;
            font-weight: 900;
        }
        .section-title {
            font-size: 12px;
            font-weight: 900;
            color: #1e293b;
            margin-top: 0;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-left: 3px solid #2563eb;
            padding-left: 6px;
        }
        .btn-primary {
            background-color: #2563eb;
            color: white;
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 11px;
            border: none;
            cursor: pointer;
            font-weight: 900;
            text-transform: uppercase;
        }
        .btn-primary:hover {
            background-color: #1d4ed8;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            margin-bottom: 20px;
            font-size: 11px;
            background: #ffffff;
            border: 1px solid #bfdbfe;
            border-radius: 4px;
            overflow: hidden;
        }
        th, td {
            padding: 8px 10px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        th {
            background-color: #eff6ff;
            color: #1e293b;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        tr:hover {
            background-color: #f8fafc;
        }
        .form-container {
            background: #eff6ff;
            padding: 16px;
            border-radius: 6px;
            border: 1px solid #bfdbfe;
            margin-top: 15px;
            box-sizing: border-box;
        }
        .form-group {
            margin-bottom: 10px;
        }
        .form-group label {
            display: block;
            margin-bottom: 4px;
            font-weight: 900;
            font-size: 11px;
            color: #1e293b;
            text-transform: uppercase;
        }
        .form-group input {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #bfdbfe;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 11px;
            background: #ffffff;
            color: #1e293b;
        }
        .form-group input:focus {
            border-color: #2563eb;
            outline: none;
        }
        .badge {
            padding: 3px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 900;
            text-transform: uppercase;
        }
        .badge-active { background-color: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
        .alert-success { background: #dcfce7; color: #166534; padding: 8px; border-radius: 4px; margin-bottom: 12px; font-size: 11px; font-weight: 900; text-transform: uppercase; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #991b1b; padding: 8px; border-radius: 4px; margin-bottom: 12px; font-size: 11px; font-weight: 900; text-transform: uppercase; }
    </style>
</head>
<body>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>

<div class="container">
    <header>
        <h1 class="page-title"><span class="brand-title">CHAP CHAP</span> <span class="brand-title" style="color: #3b82f6;">AFRICA</span> - Contracts Management</h1>
        <div style="display: flex; gap: 10px; align-items: center;">
            <span style="font-size: 11px; font-weight: 900; color: #64748b; text-transform: uppercase;">Role: <?php echo htmlspecialchars($user_role ?: 'Developer Override'); ?></span>
            <a href="/HR/index.php" style="background-color: #64748b; color: #ffffff; padding: 6px 10px; text-decoration: none; border-radius: 4px; font-size: 11px; font-weight: 900; text-transform: uppercase;">← Back</a>
        </div>
    </header>

    <?php if (!empty($success_msg)): ?>
        <div class="alert-success"><?php echo $success_msg; ?></div>
    <?php endif; ?>

    <?php if (isset($error) || !empty($error_msg)): ?>
        <div class="alert-error"><?php echo htmlspecialchars($error ?? $error_msg); ?></div>
    <?php endif; ?>

    <!-- Active Contracts Table -->
    <h2 class="section-title">Active Contracts</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Employee Name</th>
                <th>Department</th>
                <th>Job Title</th>
                <th>Salary</th>
                <th>Start Date</th>
                <th>Expiry Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($contracts) > 0): ?>
                <?php foreach ($contracts as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['contract_id'] ?? 'N/A'); ?></td>
                        <td><strong><?php echo htmlspecialchars($row['employee_name'] ?? 'N/A'); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['department'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($row['job_title'] ?? 'N/A'); ?></td>
                        <td><strong>UGX <?php echo number_format($row['monthly_salary'] ?? 0, 2); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['contract_start_date'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($row['expiry_date'] ?? 'Permanent / N/A'); ?></td>
                        <td>
                            <span class="badge badge-active">
                                <?php echo htmlspecialchars($row['contract_status'] ?? 'Active'); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align: center; color: #64748b; font-weight: 900; text-transform: uppercase;">No contract records found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Add Contract Form -->
    <div class="form-container">
        <h3 class="section-title">Add New Employee Contract</h3>
        
        <form method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Employee Full Name</label>
                    <input type="text" name="employee_name" required>
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <input type="text" name="department" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Job Title</label>
                    <input type="text" name="job_title" required>
                </div>
                <div class="form-group">
                    <label>Monthly Salary (UGX)</label>
                    <input type="number" step="0.01" name="monthly_salary" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Contract Start Date</label>
                    <input type="date" name="start_date" required>
                </div>
                <div class="form-group">
                    <label>Expiry Date (Optional)</label>
                    <input type="date" name="end_date">
                </div>
            </div>

            <div style="margin-top: 15px;">
                <button type="submit" name="create_contract" class="btn-primary">Save Contract Record</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>