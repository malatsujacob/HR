<?php
// module9_training/training_model.php
require_once '../config/db.php';

class TrainingModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    private function getTableColumns($table) {
        $stmt = $this->pdo->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = ?");
        $stmt->execute([$table]);
        $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return array_map('strtolower', $cols ?: []);
    }

    public function getCatalog() {
        $stmt = $this->pdo->query("SELECT * FROM training_catalog ORDER BY training_id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEnrollmentsByEmployee($employeeId) {
        $stmt = $this->pdo->prepare(
            "SELECT te.*, tc.course_name, tc.venue_location, tc.trainer_provider, tc.start_time, tc.end_time, tc.category
             FROM training_enrollments te
             JOIN training_catalog tc ON te.training_id = tc.training_id
             WHERE te.employee_id = ?
             ORDER BY te.enrollment_id DESC"
        );
        $stmt->execute([$employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEnrollments() {
        $stmt = $this->pdo->query(
            "SELECT te.*, tc.course_name, tc.venue_location, tc.trainer_provider, tc.start_time, tc.end_time, tc.category, e.first_name, e.last_name
             FROM training_enrollments te
             JOIN training_catalog tc ON te.training_id = tc.training_id
             JOIN employees e ON te.employee_id = e.employee_id
             ORDER BY te.enrollment_id DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addCourse($data) {
        $available = $this->getTableColumns('training_catalog');
        $fields = [];
        $values = [];
        $placeholders = [];

        $mapping = [
            'course_name' => $data['course_name'] ?? null,
            'category' => $data['category'] ?? null,
            'venue_location' => $data['venue_location'] ?? null,
            'trainer_provider' => $data['trainer_provider'] ?? null,
            'start_time' => $data['start_time'] ?? null,
            'end_time' => $data['end_time'] ?? null,
            'description' => $data['description'] ?? '',
            'department' => $data['department'] ?? null,
            'score_tracking' => isset($data['score_tracking']) ? 1 : 0,
        ];

        foreach ($mapping as $col => $val) {
            if (in_array($col, $available, true)) {
                $fields[] = $col;
                $placeholders[] = '?';
                $values[] = $val;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = 'INSERT INTO training_catalog (' . implode(',', $fields) . ') VALUES (' . implode(',', $placeholders) . ')';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($values);
    }

    public function trainingExists($trainingId) {
        $stmt = $this->pdo->prepare('SELECT 1 FROM training_catalog WHERE training_id = ? LIMIT 1');
        $stmt->execute([$trainingId]);
        return (bool) $stmt->fetchColumn();
    }

    public function enrollEmployee($data) {
        $trainingId = $data['training_id'];
        $employeeId = $data['employee_id'] ?? null;
        $nominationType = $data['nomination_type'] ?? 'Manual';

        if (!$trainingId || !$employeeId) return false;

        if (!$this->trainingExists((int)$trainingId)) {
            return false;
        }

        if (is_array($employeeId)) {
            $ids = array_filter(array_map('trim', $employeeId));
        } else {
            $ids = array_filter(array_map('trim', explode(',', (string)$employeeId)));
        }

        if (empty($ids)) {
            return false;
        }

        $available = $this->getTableColumns('training_enrollments');

        $fields = ['training_id', 'employee_id'];
        if (in_array('completion_status', $available, true)) $fields[] = 'completion_status';
        if (in_array('nomination_type', $available, true)) $fields[] = 'nomination_type';

        $placeholders = '(' . implode(',', array_fill(0, count($fields), '?')) . ')';
        $sql = 'INSERT INTO training_enrollments (' . implode(',', $fields) . ') VALUES ' . implode(',', array_fill(0, count($ids), $placeholders));
        $params = [];
        foreach ($ids as $id) {
            $params[] = $trainingId;
            $params[] = $id;
            if (in_array('completion_status', $available, true)) $params[] = 'Enrolled';
            if (in_array('nomination_type', $available, true)) $params[] = $nominationType;
        }

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function updateProgress($data) {
        $stmt = $this->pdo->prepare(
            "UPDATE training_enrollments SET completion_status = ?, score_result = ? WHERE enrollment_id = ?"
        );
        return $stmt->execute([
            $data['completion_status'],
            $data['score_result'],
            $data['enrollment_id']
        ]);
    }
}