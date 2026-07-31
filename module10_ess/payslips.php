<?php
require_once '../config/db.php';
require_once 'ess_model.php';

$essModel = new ESSModel($pdo);
session_start();

if (!isset($_SESSION['ess_emp_id'])) {
    header("Location: ess.php");
    exit;
}

$current_employee = $essModel->getEmployeeProfile($_SESSION['ess_emp_id']);
$employee_id = $_SESSION['ess_emp_id'];

// Fetch past 12 months payslips
$payslips_stmt = $pdo->prepare("SELECT * FROM employee_payslips WHERE employee_id = ? ORDER BY created_at DESC LIMIT 12");
$payslips_stmt->execute([$employee_id]);
$payslips = $payslips_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch annual tax summaries
$tax_stmt = $pdo->prepare("SELECT * FROM employee_tax_summaries WHERE employee_id = ? ORDER BY tax_year DESC");
$tax_stmt->execute([$employee_id]);
$tax_summaries = $tax_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslips & Finance - ESS HRMS</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #ffffff; color: #000000; margin: 0; padding: 0; }
        .container { margin-left: 260px !important; max-width: calc(100% - 280px) !important; padding: 25px; box-sizing: border-box; background: #ffffff; min-height: 100vh; }
        header { border-bottom: 2px solid #b3d1ff; padding-bottom: 15px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 30px; background: #ffffff; border: 1px solid #b3d1ff; border-radius: 4px; overflow: hidden; }
        th, td { padding: 12px 14px; text-align: left; border-bottom: 1px solid #b3d1ff; font-size: 13px; vertical-align: middle; }
        th { background: linear-gradient(180deg, #e6f2ff 0%, #cce0ff 100%); color: #0f172a; font-weight: bold; }
        tr:hover { background-color: #f8fafc; }

        .btn-primary { background: linear-gradient(135deg, #3399ff 0%, #0066cc 100%); color: #ffffff; padding: 6px 12px; border-radius: 4px; font-size: 12px; border: none; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block; }
        .section-title { font-size: 15px; margin-top: 25px; margin-bottom: 12px; color: #0066cc; font-weight: bold; border-left: 4px solid #ff6600; padding-left: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .nav-btn { background: #e6f2ff; color: #0066cc; padding: 8px 14px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: bold; border: 1px solid #b3d1ff; }
        .nav-btn:hover { background: #0066cc; color: #ffffff; }
    </style>
</head>
<body>

<?php 
include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php');
?>

<div class="container">
    <header>
        <div>
            <h1 style="margin: 0; font-size: 22px; color: #0f172a;">Payroll & Finance</h1>
            <small style="color: #64748b;">Payslip History & Tax Certificates (Module 7 Integration)</small>
        </div>
        <div>
            <a href="dashboard.php" class="nav-btn">Back to Dashboard</a>
        </div>
    </header>

    <!-- 1. HISTORICAL PAYSLIPS (READ-ONLY) -->
    <div class="section-title">Payslip History (Previous 12 Months)</div>
    <table>
        <thead>
            <tr>
                <th>Pay Period</th>
                <th>Gross Pay (UGX)</th>
                <th>Net Pay (UGX)</th>
                <th>Generated Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($payslips) > 0): ?>
                <?php foreach ($payslips as $ps): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($ps['pay_period']); ?></strong></td>
                        <td><?php echo number_format($ps['gross_pay'], 2); ?></td>
                        <td><span style="color: #047857; font-weight: bold;"><?php echo number_format($ps['net_pay'], 2); ?></span></td>
                        <td><small><?php echo htmlspecialchars($ps['created_at']); ?></small></td>
                        <td>
                            <a href="<?php echo htmlspecialchars($ps['pdf_file_path'] ?? '#'); ?>" class="btn-primary" target="_blank">Download PDF</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: #64748b; padding: 20px;">No historical payslip records found for your account.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- 2. ANNUAL TAX SUMMARY (P60 EQUIVALENT) -->
    <div class="section-title">Annual Tax Summaries</div>
    <table>
        <thead>
            <tr>
                <th>Tax Year</th>
                <th>Total Tax Paid (UGX)</th>
                <th>Tax Certificate</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($tax_summaries) > 0): ?>
                <?php foreach ($tax_summaries as $ts): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($ts['tax_year']); ?></strong></td>
                        <td><?php echo number_format($ts['total_tax_paid'], 2); ?></td>
                        <td>
                            <a href="<?php echo htmlspecialchars($ts['certificate_pdf_path'] ?? '#'); ?>" class="btn-primary" target="_blank">Download Tax Certificate</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3" style="text-align: center; color: #64748b; padding: 20px;">No annual tax summaries available.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>