<?php
// module7_payroll/execute_payroll.php
require_once '../config/db.php';
require_once 'payroll_model.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payroll_period = $_POST['payroll_period'] ?? date('F Y');
    
    try {
        $payrollModel = new PayrollModel($pdo);
        
        $stmt = $pdo->prepare("INSERT INTO payroll_runs (run_month, status, total_basic_salary, total_overtime, total_deductions, total_net_pay) VALUES (?, 'Pending', 0, 0, 0, 0)");
        $stmt->execute([$payroll_period]);
        $run_id = $pdo->lastInsertId();

        $emp_stmt = $pdo->query("SELECT * FROM employees");
        $employees = $emp_stmt->fetchAll(PDO::FETCH_ASSOC);

        $total_basic = 0;
        $total_overtime = 0;
        $total_deductions = 0;
        $total_net = 0;

        foreach ($employees as $emp) {
            $base = $emp['base_salary'] ?? 2500000.00;
            $overtime = 150000.00;
            $paye = $base * 0.15;
            $pension = $base * 0.05;
            $health = 50000.00;
            $deductions = $paye + $pension + $health;
            $net = ($base + $overtime) - $deductions;

            $total_basic += $base;
            $total_overtime += $overtime;
            $total_deductions += $deductions;
            $total_net += $net;

            // Added disbursement_status column tracking
            $slip_stmt = $pdo->prepare("
                INSERT INTO employee_payslips 
                (run_id, employee_id, base_salary, overtime_amount, paye_tax, pension_deduction, health_insurance, net_pay, disbursement_type, target_account, disbursement_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Bank Transfer (UGX)', '100020304050', 'Pending')
            ");
            $slip_stmt->execute([$run_id, $emp['employee_id'], $base, $overtime, $paye, $pension, $health, $net]);
        }

        $update_run = $pdo->prepare("
            UPDATE payroll_runs 
            SET total_basic_salary = ?, total_overtime = ?, total_deductions = ?, total_net_pay = ? 
            WHERE run_id = ?
        ");
        $update_run->execute([$total_basic, $total_overtime, $total_deductions, $total_net, $run_id]);

        header("Location: payroll.php?success=executed");
        exit;

    } catch (Exception $e) {
        die("Error executing payroll: " . $e->getMessage());
    }
} else {
    header("Location: payroll.php");
    exit;
}
?>