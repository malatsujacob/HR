<?php
// module7_payroll/payroll_model.php

class PayrollModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getPayrollRuns() {
        $stmt = $this->pdo->query("SELECT * FROM payroll_runs ORDER BY run_id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPayrollRunById($run_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM payroll_runs WHERE run_id = ?");
        $stmt->execute([$run_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateFinanceApproval($run_id, $status) {
        $stmt = $this->pdo->prepare("UPDATE payroll_runs SET status = ? WHERE run_id = ?");
        return $stmt->execute([$status, $run_id]);
    }

    public function getPayslipsByRun($run_id) {
        $stmt = $this->pdo->prepare("
            SELECT p.*, e.first_name, e.last_name, e.department, e.bank_name, e.account_number 
            FROM employee_payslips p
            JOIN employees e ON p.employee_id = e.employee_id
            WHERE p.run_id = ?
        ");
        $stmt->execute([$run_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>