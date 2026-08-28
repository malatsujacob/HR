<?php
// module10_ess/ess_model.php

class ESSModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Fetch employee profile details by ID
     */
    public function getEmployeeProfile($employee_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM employees WHERE employee_id = ?");
        $stmt->execute([$employee_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch leave balances for an employee
     */
    public function getLeaveBalances($employee_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM leave_balances WHERE employee_id = ? LIMIT 1");
            $stmt->execute([$employee_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Fetch leave requests history for an employee
     */
    public function getLeaveRequests($employee_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM leave_requests WHERE employee_id = ? ORDER BY created_at DESC");
            $stmt->execute([$employee_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * 1. Verify first-time login using Email and Phone Number (with TRIM to handle accidental spaces)
     */
    public function verifyFirstTimeSetup($identifier, $secret) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM employees 
            WHERE (TRIM(work_email) = TRIM(?) OR TRIM(personal_email) = TRIM(?)) 
              AND TRIM(phone) = TRIM(?) 
              AND (password IS NULL OR password = '')
        ");
        $stmt->execute([$identifier, $identifier, $secret]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 2. Authenticate regular users by Email and hashed Password
     */
    public function authenticateByPassword($identifier, $secret) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM employees 
            WHERE (TRIM(work_email) = TRIM(?) OR TRIM(personal_email) = TRIM(?))
        ");
        $stmt->execute([$identifier, $identifier]);
        $emp = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($emp && !empty($emp['password'])) {
            if (password_verify($secret, $emp['password'])) {
                return $emp;
            }
        }
        return false;
    }

    /**
     * 3. Save a new user-created password during first-time setup
     */
    public function saveNewPassword($employee_id, $new_password, $confirm_password) {
        if (empty($new_password) || strlen($new_password) < 6) {
            return "Password must be at least 6 characters long.";
        }

        if ($new_password !== $confirm_password) {
            return "Passwords do not match.";
        }

        $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare("
            UPDATE employees 
            SET password = ? 
            WHERE employee_id = ?
        ");
        
        return $stmt->execute([$hashedPassword, $employee_id]);
    }
}
?>