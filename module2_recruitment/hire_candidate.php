<?php
require_once '../config/db.php'; // Uses shared HR database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $candidate_id = $_POST['candidate_id'];

    try {
        // Start transaction for data safety
        $pdo->beginTransaction();

        // 1. Fetch candidate details
        $stmt = $pdo->prepare("SELECT * FROM candidates WHERE candidate_id = :id");
        $stmt->execute(['id' => $candidate_id]);
        $candidate = $stmt->fetch();

        if ($candidate) {
            // 2. Insert into main employees table
            $insertEmp = $pdo->prepare("
                INSERT INTO employees (first_name, last_name, work_email, phone_number, status, hire_date, created_at) 
                VALUES (:fn, :ln, :email, :phone, 'Active', CURRENT_DATE, NOW())
            ");
            $insertEmp->execute([
                'fn'    => $candidate['first_name'],
                'ln'    => $candidate['last_name'],
                'email' => $candidate['email'],
                'phone' => $candidate['phone_number']
            ]);

            // Get the newly created employee_id
            $new_employee_id = $pdo->lastInsertId();

            // 3. Update candidate record to link them and mark as Hired
            $updateCandidate = $pdo->prepare("
                UPDATE candidates 
                SET employee_id = :empid, stage = 'Hired', offer_status = 'Accepted' 
                WHERE candidate_id = :cid
            ");
            $updateCandidate->execute([
                'empid' => $new_employee_id,
                'cid'   => $candidate_id
            ]);

            // Commit transaction
            $pdo->commit();

            header("Location: /HR/module1_employees/directory.php?success=hired");
            exit();
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error converting candidate: " . $e->getMessage();
    }
}
?>