<?php
require_once '../config/db.php';

class LeaveModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getActiveEmployees() {
        $stmt = $this->pdo->query("SELECT employee_id, first_name, last_name, department, hire_date, status FROM employees WHERE status != 'Exited' ORDER BY first_name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLeaveTypes() {
        return [
            'Annual Leave',
            'Sick Leave',
            'Maternity/Paternity',
            'Unpaid Leave',
            'Compensatory Off',
            'Study Leave'
        ];
    }

    public function getLeaveRequests() {
        $this->checkAndEscalateRequests();
        $stmt = $this->pdo->query("
            SELECT lr.*, e.first_name, e.last_name, e.department 
            FROM employee_leaves lr 
            JOIN employees e ON lr.employee_id = e.employee_id 
            ORDER BY lr.request_id DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fetch all holidays from PostgreSQL
    public function getPublicHolidays() {
        $stmt = $this->pdo->query("SELECT * FROM public_holidays ORDER BY holiday_date ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Auto-Generate and Ensure Holidays Exist for Any Year Dynamically
    public function ensureYearHolidaysExist($year) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM public_holidays WHERE EXTRACT(YEAR FROM holiday_date) = ? AND holiday_type = 'public'");
        $stmt->execute([$year]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            return; // Already generated for this year
        }

        // Calculate Easter movable dates for this specific year
        $easterTimestamp = easter_date($year);
        $goodFriday = date('Y-m-d', strtotime('-2 days', $easterTimestamp));
        $easterMonday = date('Y-m-d', strtotime('+1 day', $easterTimestamp));

        // Standard national public holidays for Uganda (Dynamic Year)
        $holidays = [
            ['New Year\'s Day', "{$year}-01-01", 'National Public Holiday', 'public'],
            ['NRM Liberation Day', "{$year}-01-26", 'National Public Holiday', 'public'],
            ['Archbishop Janani Luwum Day', "{$year}-02-16", 'Memorial Public Holiday', 'public'],
            ['International Women\'s Day', "{$year}-03-08", 'National Public Holiday', 'public'],
            ['Good Friday', $goodFriday, 'Christian Holiday', 'public'],
            ['Easter Monday', $easterMonday, 'Christian Holiday', 'public'],
            ['Labour Day', "{$year}-05-01", 'National Public Holiday', 'public'],
            ['Uganda Martyrs\' Day', "{$year}-06-03", 'National Public Holiday commemorating the Christian martyrs', 'public'],
            ['National Heroes\' Day', "{$year}-06-09", 'National Public Holiday', 'public'],
            ['Independence Day', "{$year}-10-09", 'Uganda Independence Day (1962)', 'public'],
            ['Christmas Day', "{$year}-12-25", 'Christian Holiday', 'public'],
            ['Boxing Day', "{$year}-12-26", 'National Public Holiday', 'public']
        ];

        $insertStmt = $this->pdo->prepare("
            INSERT INTO public_holidays (holiday_name, holiday_date, description, holiday_type) 
            VALUES (?, ?, ?, ?) 
            ON CONFLICT (holiday_date) 
            DO UPDATE SET 
                holiday_name = EXCLUDED.holiday_name, 
                description = EXCLUDED.description, 
                holiday_type = EXCLUDED.holiday_type
        ");

        foreach ($holidays as $h) {
            $insertStmt->execute($h);
        }
    }

    public function addPublicHoliday($name, $date, $description, $holidayType = 'company') {
        $stmt = $this->pdo->prepare("
            INSERT INTO public_holidays (holiday_name, holiday_date, description, holiday_type) 
            VALUES (?, ?, ?, ?) 
            ON CONFLICT (holiday_date) 
            DO UPDATE SET 
                holiday_name = EXCLUDED.holiday_name, 
                description = EXCLUDED.description, 
                holiday_type = EXCLUDED.holiday_type
        ");
        return $stmt->execute([$name, $date, $description, $holidayType]);
    }

    public function getEmployeeLeaveBalance($employee_id) {
        $stmt = $this->pdo->prepare("SELECT hire_date FROM employees WHERE employee_id = ?");
        $stmt->execute([$employee_id]);
        $hire_date = $stmt->fetchColumn();

        $stmt_bal = $this->pdo->prepare("SELECT * FROM employee_leave_balances WHERE employee_id = ? AND year = EXTRACT(YEAR FROM CURRENT_DATE)");
        $stmt_bal->execute([$employee_id]);
        $balance = $stmt_bal->fetch(PDO::FETCH_ASSOC);

        if (!$balance) {
            return [
                'total_allocated' => 20,
                'days_used' => 0,
                'days_remaining' => 20,
                'hire_date' => $hire_date
            ];
        }
        
        return $balance;
    }

    public function applyLeave($data) {
        $emp_check = $this->pdo->prepare("SELECT status FROM employees WHERE employee_id = ?");
        $emp_check->execute([$data['employee_id']]);
        $status = $emp_check->fetchColumn();

        if ($status === 'Exited') {
            return "Error: Exited employees cannot apply for leave.";
        }

        $holiday_check = $this->pdo->prepare("SELECT holiday_name FROM public_holidays WHERE holiday_date = ?");
        $holiday_check->execute([$data['start_date']]);
        $holiday_name = $holiday_check->fetchColumn();

        if ($holiday_name) {
            return "Error / Auto-Reject: Cannot apply for leave on a holiday (" . $holiday_name . ").";
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO employee_leaves (employee_id, leave_type, start_date, end_date, reason, medical_certificate, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'Pending')
        ");
        $stmt->execute([
            $data['employee_id'], $data['leave_type'], $data['start_date'], 
            $data['end_date'], $data['reason'], $data['medical_certificate']
        ]);

        return true;
    }

    public function updateLeaveStatus($request_id, $status, $comment) {
        $stmt = $this->pdo->prepare("UPDATE employee_leaves SET status = ?, manager_comment = ? WHERE request_id = ?");
        return $stmt->execute([$status, $comment, $request_id]);
    }

    private function checkAndEscalateRequests() {
        $stmt = $this->pdo->prepare("
            UPDATE employee_leaves 
            SET manager_comment = COALESCE(manager_comment, '') || ' [Auto-Escalated to HR Manager: Pending > 3 Days]'
            WHERE status = 'Pending' 
            AND created_at < (CURRENT_TIMESTAMP - INTERVAL '3 days')
            AND (manager_comment NOT LIKE '%Auto-Escalated%')
        ");
        $stmt->execute();
    }
}
?>