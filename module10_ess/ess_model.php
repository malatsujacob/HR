<?php
class ESSModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function authenticateEmployee($employee_id, $pin_or_phone) {
        $stmt = $this->pdo->prepare("SELECT * FROM employees WHERE employee_id = ? AND status = 'Active'");
        $stmt->execute([$employee_id]);
        $emp = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($emp && ($emp['phone'] === $pin_or_phone || $emp['employee_id'] == $pin_or_phone)) {
            return $emp;
        }
        return false;
    }

    public function getEmployeeProfile($employee_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM employees WHERE employee_id = ?");
        $stmt->execute([$employee_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getLeaveBalances($employee_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM employee_leave_balances WHERE employee_id = ?");
        $stmt->execute([$employee_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLeaveRequests($employee_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM leave_requests WHERE employee_id = ? ORDER BY created_at DESC");
        $stmt->execute([$employee_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function applyLeave($data) {
        $stmt = $this->pdo->prepare("INSERT INTO leave_requests (employee_id, leave_type, start_date, end_date, reason, request_status, created_at) VALUES (?, ?, ?, ?, ?, 'Pending', NOW())");
        return $stmt->execute([
            $data['employee_id'],
            $data['leave_type'],
            $data['start_date'],
            $data['end_date'],
            $data['reason']
        ]);
    }

    public function cancelLeave($leave_id, $employee_id) {
        $stmt = $this->pdo->prepare("UPDATE leave_requests SET request_status = 'Cancelled' WHERE leave_id = ? AND employee_id = ? AND request_status = 'Pending'");
        return $stmt->execute([$leave_id, $employee_id]);
    }

    public function getDirectReportsPendingLeaves($manager_id) {
        // Fetches pending leave requests for employees managed by this manager ID
        $stmt = $this->pdo->prepare("
            SELECT lr.*, e.first_name, e.last_name 
            FROM leave_requests lr 
            JOIN employees e ON lr.employee_id = e.employee_id 
            WHERE e.reporting_manager_id = ? AND lr.request_status = 'Pending' 
            ORDER BY lr.created_at DESC
        ");
        $stmt->execute([$manager_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function managerReviewLeave($leave_id, $status, $manager_id) {
        $stmt = $this->pdo->prepare("UPDATE leave_requests SET request_status = ?, manager_reviewed_by = ?, review_timestamp = NOW() WHERE leave_id = ?");
        return $stmt->execute([$status, $manager_id, $leave_id]);
    }

    public function getPendingRequests() {
        $stmt = $this->pdo->query("SELECT * FROM ess_change_requests WHERE request_status = 'Pending' ORDER BY request_timestamp DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function submitChangeRequest($data) {
        $stmt = $this->pdo->prepare("INSERT INTO ess_change_requests (employee_id, field_to_update, old_value, new_value, request_timestamp, request_status) VALUES (?, ?, ?, ?, NOW(), 'Pending')");
        return $stmt->execute([
            $data['employee_id'],
            $data['field_to_update'],
            $data['old_value'],
            $data['new_value']
        ]);
    }

    public function reviewRequest($request_id, $status, $admin_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM ess_change_requests WHERE change_request_id = ?");
        $stmt->execute([$request_id]);
        $req = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($req && $req['request_status'] === 'Pending') {
            if ($status === 'Approved') {
                $field = $req['field_to_update'];
                $emp_id = $req['employee_id'];
                $new_val = $req['new_value'];

                // Bank details are strictly restricted from self-service edits per security constraints
                $allowed_fields = ['phone', 'email', 'next_of_kin', 'address'];
                if (in_array($field, $allowed_fields)) {
                    $update_stmt = $this->pdo->prepare("UPDATE employees SET $field = ? WHERE employee_id = ?");
                    $update_stmt->execute([$new_val, $emp_id]);
                }
            }

            $update_req = $this->pdo->prepare("UPDATE ess_change_requests SET request_status = ?, reviewed_by_id = ?, review_timestamp = NOW() WHERE change_request_id = ?");
            return $update_req->execute([$status, $admin_id, $request_id]);
        }
        return false;
    }
}
?>