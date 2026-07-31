<?php
require_once '../config/db.php';

try {
    $total_onboarding = $pdo->query("SELECT COUNT(*) FROM employee_onboarding")->fetchColumn();
    $completed = $pdo->query("SELECT COUNT(*) FROM employee_onboarding WHERE status = 'Completed'")->fetchColumn();
    $in_progress = $total_onboarding - $completed;

    $missing_ids = $pdo->query("SELECT COUNT(*) FROM employee_onboarding WHERE (national_id_path IS NULL OR national_id_path = '') AND status != 'Completed'")->fetchColumn();
    $missing_contracts = $pdo->query("SELECT COUNT(*) FROM employee_onboarding WHERE (signed_contract_path IS NULL OR signed_contract_path = '') AND status != 'Completed'")->fetchColumn();
    $missing_nssf = $pdo->query("SELECT COUNT(*) FROM employee_onboarding WHERE (nssf_doc_path IS NULL OR nssf_doc_path = '') AND status != 'Completed'")->fetchColumn();
    $missing_tin = $pdo->query("SELECT COUNT(*) FROM employee_onboarding WHERE (tin_doc_path IS NULL OR tin_doc_path = '') AND status != 'Completed'")->fetchColumn();
    $missing_bank = $pdo->query("SELECT COUNT(*) FROM employee_onboarding WHERE (bank_account_number IS NULL OR bank_account_number = '') AND status != 'Completed'")->fetchColumn();

    $dept_stmt = $pdo->query("SELECT department, COUNT(*) as count FROM employee_onboarding GROUP BY department");
    $dept_data = $dept_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $total_onboarding = 0;
    $completed = 0;
    $in_progress = 0;
    $missing_ids = 0;
    $missing_contracts = 0;
    $missing_nssf = 0;
    $missing_tin = 0;
    $missing_bank = 0;
    $dept_data = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Onboarding Dashboard - hrms</title>
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
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        .card {
            background: #ffffff;
            padding: 16px;
            border-radius: 6px;
            border: 1px solid #bae6fd;
            text-align: center;
            box-shadow: 0 2px 6px rgba(2, 132, 199, 0.03);
        }
        .card h3 {
            margin: 0 0 8px 0;
            font-size: 12px;
            color: #0369a1;
            text-transform: uppercase;
            font-weight: 800;
        }
        .card p {
            margin: 0;
            font-size: 22px;
            font-weight: bold;
            color: #0284c7;
        }
        .chart-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .chart-box {
            background: #ffffff;
            padding: 18px;
            border-radius: 6px;
            border: 1px solid #bae6fd;
            margin-bottom: 20px;
            box-shadow: 0 2px 6px rgba(2, 132, 199, 0.03);
        }
        .chart-box h3 {
            margin-top: 0;
            color: #0369a1;
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            border-bottom: 1px solid #e0f2fe;
            padding-bottom: 8px;
        }
        .btn-secondary {
            background-color: #0284c7;
            color: #ffffff;
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            white-space: nowrap;
        }
        .btn-secondary:hover {
            background-color: #0369a1;
        }
    </style>
</head>
<body>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>

<div class="container">
    <header>
        <div style="width: 120px;"></div>
        <h1 class="page-title">Onboarding Analytics Dashboard</h1>
        <a href="index.php" class="btn-secondary">← Back Hub</a>
    </header>

    <div class="card-grid">
        <div class="card">
            <h3>Total Records</h3>
            <p><?php echo $total_onboarding; ?></p>
        </div>
        <div class="card">
            <h3>Completed Activations</h3>
            <p style="color: #16a34a;"><?php echo $completed; ?></p>
        </div>
        <div class="card">
            <h3>In Progress</h3>
            <p style="color: #ca8a04;"><?php echo $in_progress; ?></p>
        </div>
    </div>

    <div class="chart-grid">
        <div class="chart-box">
            <h3>Pending Documents (Bottlenecks)</h3>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li style="padding: 8px 0; border-bottom: 1px solid #e0f2fe; font-size: 12px; color: #334155;">
                    Missing National IDs: <span style="float: right; font-weight: bold; color: #dc2626;"><?php echo $missing_ids; ?></span>
                </li>
                <li style="padding: 8px 0; border-bottom: 1px solid #e0f2fe; font-size: 12px; color: #334155;">
                    Missing Signed Contracts: <span style="float: right; font-weight: bold; color: #dc2626;"><?php echo $missing_contracts; ?></span>
                </li>
                <li style="padding: 8px 0; border-bottom: 1px solid #e0f2fe; font-size: 12px; color: #334155;">
                    Missing NSSF Details: <span style="float: right; font-weight: bold; color: #dc2626;"><?php echo $missing_nssf; ?></span>
                </li>
                <li style="padding: 8px 0; border-bottom: 1px solid #e0f2fe; font-size: 12px; color: #334155;">
                    Missing URA TIN: <span style="float: right; font-weight: bold; color: #dc2626;"><?php echo $missing_tin; ?></span>
                </li>
                <li style="padding: 8px 0; font-size: 12px; color: #334155;">
                    Missing Bank Forms: <span style="float: right; font-weight: bold; color: #dc2626;"><?php echo $missing_bank; ?></span>
                </li>
            </ul>
        </div>

        <div class="chart-box">
            <h3>New Hires by Department</h3>
            <?php if (count($dept_data) > 0): ?>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <?php foreach ($dept_data as $dept): ?>
                        <li style="padding: 8px 0; border-bottom: 1px solid #e0f2fe; font-size: 12px; color: #334155;">
                            <strong><?php echo htmlspecialchars($dept['department'] ?? 'Unassigned'); ?>:</strong> 
                            <span style="float: right; font-weight: bold; color: #0284c7;"><?php echo $dept['count']; ?> New Hires</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p style="color: #64748b; font-size: 12px; margin: 0;">No department data available yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>