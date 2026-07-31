<?php
// module7_payroll/payroll.php
require_once '../config/db.php';
require_once 'payroll_model.php';

$payrollModel = new PayrollModel($pdo);
$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_approval'])) {
        $payrollModel->updateFinanceApproval($_POST['run_id'], $_POST['status']);
        $msg = "Updated successfully.";
    }
}

$runs = $payrollModel->getPayrollRuns();

$run_id = $_GET['run_id'] ?? 0;
if ($run_id <= 0 && !empty($runs)) {
    $run_id = $runs[0]['run_id'];
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
        body { background-color: #ffffff; color: #000000; margin: 0; font-family: Arial, sans-serif; }
        .container { margin-left: 260px; max-width: calc(100% - 280px); padding: 20px; box-sizing: border-box; background: #ffffff; min-height: 100vh; }
        header { border-bottom: 2px solid #b3d1ff; padding-bottom: 12px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        header h1 { font-size: 18px; font-weight: 900; margin: 0; color: #000000; text-transform: uppercase; letter-spacing: 0.5px; }
        .brand-title { color: #dc2626; }
        .brand-title span { color: #2563eb; }
        .card { background: #f0f7ff; padding: 15px; border-radius: 6px; border: 1px solid #b3d1ff; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 11px; }
        th, td { padding: 8px 10px; text-align: left; border-bottom: 1px solid #b3d1ff; vertical-align: top; color: #000000; }
        th { background-color: #e1effe; color: #000000; font-weight: bold; text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px; }
        tr:hover { background-color: #e8f2ff; }
        .btn-primary { background: #3399ff; color: #ffffff; padding: 5px 10px; border-radius: 4px; font-size: 11px; text-decoration: none; font-weight: 900; display: inline-block; border: none; cursor: pointer; text-transform: uppercase; letter-spacing: 0.5px; }
        .btn-primary:hover { background: #0066cc; }
        select, input[type="text"], input[type="date"] { padding: 6px 8px; font-size: 11px; border: 1px solid #b3d1ff; border-radius: 4px; width: 100%; box-sizing: border-box; background: #ffffff; color: #000000; }
        .alert-success { background: #d1fae5; color: #064e3b; padding: 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #065f46; font-size: 11px; }
        .alert-error { background: #fee2e2; color: #7f1d1d; padding: 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #991b1b; font-size: 11px; }
    </style>
</head>
<body>

<?php 
include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php');
?>

<div class="container">
    <header>
        <h1><span class="brand-title">CHAP CHAP</span> <span class="brand-title" style="color: #2563eb;">AFRICA</span> - Payroll</h1>
        <div>
            <a href="dashboard.php" class="btn-primary">Dashboard</a>
        </div>
    </header>

    <?php if (!empty($msg)): ?>
        <div class="alert-success"><?php echo $msg; ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div style="margin-bottom: 20px;">
        <?php include('disbursement_engine.php'); ?>
    </div>

    <div class="card">
        <h3 style="margin-top: 0; font-size: 13px; color: #000000; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px;">Runs</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Month</th>
                    <th>Basic</th>
                    <th>Overtime</th>
                    <th>Deductions</th>
                    <th>Net</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($runs) > 0): ?>
                    <?php foreach ($runs as $r): ?>
                        <tr>
                            <td><strong>#<?php echo $r['run_id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($r['run_month']); ?></td>
                            <td><?php echo number_format($r['total_basic_salary'], 2); ?></td>
                            <td><?php echo number_format($r['total_overtime'], 2); ?></td>
                            <td><?php echo number_format($r['total_deductions'], 2); ?></td>
                            <td><strong style="color: #0066cc;"><?php echo number_format($r['total_net_pay'], 2); ?></strong></td>
                            <td>
                                <span style="color: <?php echo $r['status'] == 'Approved' ? '#059669' : '#d97706'; ?>; font-weight: bold;">
                                    <?php echo htmlspecialchars($r['status']); ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" style="display: flex; gap: 4px;">
                                    <input type="hidden" name="run_id" value="<?php echo $r['run_id']; ?>">
                                    <select name="status" style="font-size: 10px; padding: 3px;">
                                        <option value="Approved">Approve</option>
                                        <option value="Rejected">Reject</option>
                                    </select>
                                    <button type="submit" name="update_approval" class="btn-primary" style="padding: 3px 6px; font-size: 10px;">Save</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: #555555; padding: 15px;">No records.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>