<?php
// module7_payroll/process_disbursements.php
require_once '../config/db.php';

$run_id = $_POST['run_id'] ?? 0;

if ($run_id > 0) {
    // Fetch all pending slips for this run
    $stmt = $pdo->prepare("SELECT * FROM employee_payslips WHERE run_id = ? AND disbursement_status = 'Pending'");
    $stmt->execute([$run_id]);
    $slips = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($slips as $slip) {
        // TODO: Insert your bank or mobile money API cURL request here
        // $api_success = callUgandanBankApi($slip['target_account'], $slip['net_pay']);

        // Assuming API call is successful:
        $update_status = $pdo->prepare("UPDATE employee_payslips SET disbursement_status = 'Success' WHERE payslip_id = ?");
        $update_status->execute([$slip['payslip_id']]);
    }

    header("Location: payroll.php?msg=DisbursementsProcessed");
    exit;
}
?>