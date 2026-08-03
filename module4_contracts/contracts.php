<?php
require_once '../config/db.php';

// Handle form submission to create a contract / employee record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_contract'])) {
    $full_name = trim($_POST['full_name']);
    $department = trim($_POST['department']);
    $job_title = trim($_POST['job_title']);
    $salary = $_POST['salary'];
    $start_date = $_POST['start_date'];

    // Split full name into first and last name
    $name_parts = explode(' ', $full_name, 2);
    $first_name = $name_parts[0];
    $last_name = $name_parts[1] ?? '';

    try {
        $stmt = $pdo->prepare("INSERT INTO employees (first_name, last_name, department, position, salary, hire_date, employment_status) VALUES (?, ?, ?, ?, ?, ?, 'Active')");
        $stmt->execute([$first_name, $last_name, $department, $job_title, $salary, $start_date]);

        header("Location: contracts.php");
        exit();
    } catch (PDOException $e) {
        $error = "Error creating contract record: " . $e->getMessage();
    }
}

// Fetch all employee contract records
try {
    $stmt = $pdo->query("SELECT * FROM employees ORDER BY employee_id DESC");
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
    <title>Contracts Management - hrms</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f9ff;
            margin: 0;
            padding: 0;
        }
        .container {
            margin: 20px auto 40px auto;
            margin-left: 280px;
            max-width: calc(100% - 320px);
            padding: 24px;
            box-sizing: border-box;
            background: #ffffff;
            min-height: calc(100vh - 60px);
            border-radius: 10px;
            border: 1px solid #bae6fd;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.05);
        }
        header {
            border-bottom: 2px solid #e0f2fe;
            padding-bottom: 12px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .page-title {
            margin: 0;
            color: #0369a1;
            font-size: 18px;
            font-weight: 800;
            text-transform: uppercase;
            text-align: center;
            width: 100%;
        }
        .section-title {
            text-align: center;
            font-size: 14px;
            font-weight: 800;
            color: #0369a1;
            margin-top: 5px;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .btn-primary {
            background-color: #0284c7;
            color: white;
            padding: 8px 12px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-primary:hover {
            background-color: #0369a1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            margin-bottom: 25px;
            font-size: 12px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #e0f2fe;
        }
        th {
            background-color: #f0f9ff;
            color: #0369a1;
            font-weight: bold;
        }
        tr:hover {
            background-color: #f8fafc;
        }
        .form-container {
            background: #ffffff;
            padding: 20px;
            border-radius: 6px;
            border: 1px solid #bae6fd;
            box-shadow: 0 2px 6px rgba(2, 132, 199, 0.03);
            margin-top: 15px;
            max-width: 100%;
            box-sizing: border-box;
        }
        .form-group {
            margin-bottom: 10px;
        }
        .form-group label {
            display: block;
            margin-bottom: 3px;
            font-weight: bold;
            font-size: 12px;
            color: #334155;
        }
        .form-group input {
            width: 100%;
            padding: 7px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 12px;
            background: #ffffff;
            color: #0f172a;
        }
        .form-group input:focus {
            border-color: #0284c7;
            outline: none;
            box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.1);
        }
        .badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
        }
        .badge-active { background-color: #22c55e; color: white; }
    </style>
</head>
<body>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>

<div class="container">
    <header>
        <a href="/HR/index.php" style="background-color: #64748b; color: #ffffff; padding: 6px 10px; text-decoration: none; border-radius: 4px; font-size: 11px; font-weight: bold;">← Back</a>
        <h1 class="page-title">Contracts Management</h1>
        <div style="width: 50px;"></div>
    </header>

    <?php if (isset($error)): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 8px; border-radius: 4px; margin-bottom: 12px; font-size: 12px;"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <h2 class="section-title">Active Contracts</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Employee Name</th>
                <th>Department</th>
                <th>Position</th>
                <th>Salary</th>
                <th>Hire Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($contracts) > 0): ?>
                <?php foreach ($contracts as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['employee_id']); ?></td>
                        <td><strong><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['department'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($row['position'] ?? 'N/A'); ?></td>
                        <td><strong>UGX <?php echo number_format($row['salary'] ?? 0, 2); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['hire_date'] ?? 'N/A'); ?></td>
                        <td>
                            <span class="badge badge-active">
                                <?php echo htmlspecialchars($row['employment_status'] ?? 'Active'); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: #64748b;">No contract records found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="form-container">
        <h3 class="section-title" style="margin-top: 0; text-align: left;">Add New Employee Contract</h3>
        
        <form method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Employee Full Name</label>
                    <input type="text" name="full_name" required>
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <input type="text" name="department" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Job Title / Position</label>
                    <input type="text" name="job_title" required>
                </div>
                <div class="form-group">
                    <label>Salary (UGX)</label>
                    <input type="number" step="0.01" name="salary" required>
                </div>
            </div>

            <div class="form-group">
                <label>Start Date / Hire Date</label>
                <input type="date" name="start_date" required>
            </div>

            <div style="margin-top: 15px;">
                <button type="submit" name="create_contract" class="btn-primary">Save Contract Record</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>