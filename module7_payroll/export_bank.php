<?php
// module7_payroll/export_bank.php
require_once '../config/db.php';

$run_id = $_GET['run_id'] ?? 0;

if ($run_id <= 0) {
    $stmt = $pdo->query("SELECT run_id FROM payroll_runs ORDER BY run_id DESC LIMIT 1");
    $latest_run = $stmt->fetch(PDO::FETCH_ASSOC);
    $run_id = $latest_run['run_id'] ?? 0;
}

$stmt = $pdo->prepare("
    SELECT p.*, e.first_name, e.last_name, e.bank_name, e.account_number, r.run_month
    FROM employee_payslips p
    JOIN employees e ON p.employee_id = e.employee_id
    JOIN payroll_runs r ON p.run_id = r.run_id
    WHERE p.run_id = ?
");
$stmt->execute([$run_id]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($records)) {
    die("No records found for bank export. Please ensure payroll has been executed.");
}

// Set headers for CSV download export
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=bank_export_run_' . $run_id . '.csv');

$output = fopen('php://output', 'w');
// Output CSV column headers matching bank schema
fputcsv($output, ['Employee Name', 'Bank Name', 'Account Number', 'Net Pay (UGX)', 'Period']);

foreach ($records as $row) {
    fputcsv($output, [
        $row['first_name'] . ' ' . $row['last_name'],
        $row['bank_name'] ?? 'Standard Bank',
        $row['account_number'] ?? '0000000000',
        $row['net_pay'],
        $row['run_month']
    ]);
}
fclose($output);
exit;
?>