<?php
require_once '../config/db.php';

class TrainingModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function addCourse($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO training_catalog (course_name, venue_location, trainer_provider, start_time, end_time, category, description)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['course_name'],
            $data['venue_location'],
            $data['trainer_provider'],
            $data['start_time'],
            $data['end_time'],
            $data['category'],
            $data['description']
        ]);
    }

    public function enrollEmployee($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO training_enrollments (training_id, employee_id, completion_status, nomination_type)
            VALUES (?, ?, 'Enrolled', 'Manual')
        ");
        return $stmt->execute([
            $data['training_id'],
            $data['employee_id']
        ]);
    }

    public function updateProgress($data) {
        $stmt = $this->pdo->prepare("
            UPDATE training_enrollments 
            SET completion_status = ?, score_result = ?
            WHERE enrollment_id = ?
        ");
        return $stmt->execute([
            $data['completion_status'],
            $data['score_result'],
            $data['enrollment_id']
        ]);
    }

    public function getCatalog() {
        $stmt = $this->pdo->query("SELECT * FROM training_catalog ORDER BY training_id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEnrollments() {
        $stmt = $this->pdo->query("
            SELECT te.*, tc.course_name, tc.venue_location, tc.trainer_provider, tc.start_time, tc.end_time, tc.category, e.first_name, e.last_name, e.department 
            FROM training_enrollments te
            JOIN training_catalog tc ON te.training_id = tc.training_id
            JOIN employees e ON te.employee_id = e.employee_id
            ORDER BY te.enrollment_id DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEnrollmentsByEmployee($employee_id) {
        $stmt = $this->pdo->prepare("
            SELECT te.*, tc.course_name, tc.venue_location, tc.trainer_provider, tc.start_time, tc.end_time, tc.category, e.first_name, e.last_name, e.department 
            FROM training_enrollments te
            JOIN training_catalog tc ON te.training_id = tc.training_id
            JOIN employees e ON te.employee_id = e.employee_id
            WHERE te.employee_id = ?
            ORDER BY te.enrollment_id DESC
        ");
        $stmt->execute([$employee_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}