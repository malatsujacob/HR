<?php
require_once '../config/db.php';

class AttendanceModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getActiveEmployees() {
        $stmt = $this->pdo->query("SELECT employee_id, first_name, last_name, department FROM employees WHERE status != 'Exited' ORDER BY first_name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAttendanceLogs($limit = 50) {
        $stmt = $this->pdo->prepare("
            SELECT a.*, e.first_name, e.last_name, e.department 
            FROM employee_attendance a 
            JOIN employees e ON a.employee_id = e.employee_id 
            ORDER BY a.attendance_date DESC, a.attendance_id DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function logAttendance($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO employee_attendance (employee_id, attendance_date, check_in_time, check_out_time, total_hours_worked, overtime_hours, lateness_minutes, early_departure_minutes, status, clock_in_method, shift_type, manual_adjustment_reason, overtime_multiplier) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON CONFLICT (employee_id, attendance_date) 
            DO UPDATE SET check_in_time = EXCLUDED.check_in_time, check_out_time = EXCLUDED.check_out_time, total_hours_worked = EXCLUDED.total_hours_worked, overtime_hours = EXCLUDED.overtime_hours, lateness_minutes = EXCLUDED.lateness_minutes, early_departure_minutes = EXCLUDED.early_departure_minutes, status = EXCLUDED.status, clock_in_method = EXCLUDED.clock_in_method, shift_type = EXCLUDED.shift_type, manual_adjustment_reason = EXCLUDED.manual_adjustment_reason, overtime_multiplier = EXCLUDED.overtime_multiplier
        ");
        return $stmt->execute([
            $data['employee_id'], $data['attendance_date'], $data['check_in'], $data['check_out'], 
            $data['total_hours'], $data['overtime_hours'], $data['lateness_minutes'], 
            $data['early_departure_minutes'], $data['status'], $data['method'], 
            $data['shift_type'], $data['manual_reason'], $data['overtime_multiplier']
        ]);
    }
}
?>