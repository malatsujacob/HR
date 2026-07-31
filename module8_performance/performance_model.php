<?php
require_once '../config/db.php';

class PerformanceModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getEligibleEmployees() {
        $stmt = $this->pdo->query("
            SELECT employee_id, first_name, last_name, department, status AS employment_status 
            FROM employees 
            WHERE status::text IN ('Active', 'Confirmed')
            ORDER BY first_name ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createGoal($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO performance_goals (employee_id, goal_title, description, cycle, status)
            VALUES (?, ?, ?, ?, 'In Progress')
        ");
        return $stmt->execute([
            $data['employee_id'],
            $data['goal_title'],
            $data['description'],
            $data['cycle']
        ]);
    }

    public function getGoalsByEmployee($employee_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM performance_goals WHERE employee_id = ? ORDER BY goal_id DESC");
        $stmt->execute([$employee_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function submitAppraisal($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO employee_appraisals (
                employee_id, review_cycle, self_rating, self_summary, 
                manager_rating, manager_comments, improvement_suggestions, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            RETURNING appraisal_id
        ");
        
        $rating = intval($data['manager_rating']);
        $status = ($rating <= 2) ? 'PIP Assigned' : 'Completed';

        $stmt->execute([
            $data['employee_id'],
            $data['review_cycle'],
            $data['self_rating'],
            $data['self_summary'],
            $rating,
            $data['manager_comments'],
            $data['improvement_suggestions'],
            $status
        ]);
        
        $appraisal_id = $stmt->fetchColumn();

        if ($rating <= 2) {
            $pip_stmt = $this->pdo->prepare("
                INSERT INTO performance_improvement_plans (appraisal_id, employee_id, action_items, deadline_date)
                VALUES (?, ?, ?, CURRENT_DATE + INTERVAL '30 days')
            ");
            $pip_stmt->execute([
                $appraisal_id,
                $data['employee_id'],
                "Auto-generated PIP due to low rating (" . $rating . "): " . $data['improvement_suggestions']
            ]);

            $train_stmt = $this->pdo->prepare("
                INSERT INTO training_recommendations (employee_id, course_name, status)
                VALUES (?, 'Performance Remediation & Core Skills', 'Pending Enrollment')
            ");
            try {
                $train_stmt->execute([$data['employee_id']]);
            } catch (Exception $e) {
                // Ignore if training recommendations table does not exist yet
            }
        }

        return true;
    }

    public function submit360Feedback($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO performance_360_feedback (employee_id, reviewer_role, rating, feedback_comments, is_anonymous)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['employee_id'],
            $data['reviewer_role'],
            $data['rating'],
            $data['feedback_comments'],
            isset($data['is_anonymous']) ? true : false
        ]);
    }

    public function getAllAppraisals() {
        $stmt = $this->pdo->query("
            SELECT a.*, e.first_name, e.last_name, e.department 
            FROM employee_appraisals a
            JOIN employees e ON a.employee_id = e.employee_id
            ORDER BY a.appraisal_id DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActivePIPs() {
        $stmt = $this->pdo->query("
            SELECT p.*, e.first_name, e.last_name, e.department 
            FROM performance_improvement_plans p
            JOIN employees e ON p.employee_id = e.employee_id
            WHERE p.status = 'Active'
            ORDER BY p.pip_id DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>