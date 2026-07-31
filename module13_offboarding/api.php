<?php
// module13_offboarding/api.php - RESTful API for Exit & Clearance
require_once '../config/db.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET['action'] ?? '';

try {
    switch ($path) {
        // 1. Initiate Exit
        case 'initiate':
            if ($method === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $employee_id = intval($data['employee_id'] ?? 0);
                $last_working_day = $data['last_working_day'] ?? '';
                $exit_reason = $data['exit_reason'] ?? '';

                if (!$employee_id || !$last_working_day || !$exit_reason) {
                    echo json_encode(['status' => 'error', 'message' => 'Missing mandatory fields']);
                    exit;
                }

                $pdo->beginTransaction();

                // Insert exit request
                $stmt = $pdo->prepare("INSERT INTO exit_requests (employee_id, last_working_day, exit_reason, status) VALUES (?, ?, ?, 'Pending Clearance') RETURNING id");
                $stmt->execute([$employee_id, $last_working_day, $exit_reason]);
                $exit_id = $stmt->fetchColumn();

                // Generate default clearance checklist items (IT, Finance, HR, Facilities)
                $departments = [
                    ['IT', 'Return company laptop, access card, and revoke email access'],
                    ['Finance', 'Clear outstanding loans, salary advances, and travel imprest'],
                    ['HR', 'Complete exit interview and sign NDA/handover notes'],
                    ['Facilities', 'Return office keys, parking sticker, and desk space clearance']
                ];

                foreach ($departments as $dept) {
                    $chkStmt = $pdo->prepare("INSERT INTO clearance_checklist (exit_request_id, department_name, item_description, is_cleared) VALUES (?, ?, ?, FALSE)");
                    $chkStmt->execute([$exit_id, $dept[0], $dept[1]]);
                }

                $pdo->commit();
                echo json_encode(['status' => 'success', 'exit_id' => $exit_id, 'message' => 'Exit initiated successfully and clearance checklists generated.']);
            }
            break;

        // 2. List Pending Clearances
        case 'pending':
            if ($method === 'GET') {
                $stmt = $pdo->query("SELECT er.*, cc.department_name, cc.is_cleared FROM exit_requests er JOIN clearance_checklist cc ON er.id = cc.exit_request_id WHERE er.status = 'Pending Clearance'");
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['status' => 'success', 'data' => $results]);
            }
            break;

        // 3. Department Ticks Clearance Box
        case 'clear':
            if ($method === 'PUT') {
                $data = json_decode(file_get_contents('php://input'), true);
                $checklist_id = intval($data['checklist_id'] ?? 0);
                $cleared_by = $data['cleared_by'] ?? 'Department Head';
                $notes = $data['notes'] ?? '';

                $stmt = $pdo->prepare("UPDATE clearance_checklist SET is_cleared = TRUE, cleared_by = ?, cleared_at = CURRENT_TIMESTAMP, notes = ? WHERE id = ?");
                $stmt->execute([$cleared_by, $notes, $checklist_id]);

                echo json_encode(['status' => 'success', 'message' => 'Department clearance updated successfully.']);
            }
            break;

        // 4. Calculate Final Settlement (Unlocked only when 100% cleared)
        case 'calculate-settlement':
            if ($method === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $exit_id = intval($data['exit_id'] ?? 0);

                // Check if all departments are cleared
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM clearance_checklist WHERE exit_request_id = ? AND is_cleared = FALSE");
                $checkStmt->execute([$exit_id]);
                $pendingCount = $checkStmt->fetchColumn();

                if ($pendingCount > 0) {
                    echo json_encode(['status' => 'error', 'message' => 'Clearance lock active: All departments must clear the employee before calculation.']);
                    exit;
                }

                $days_worked = floatval($data['days_worked_final_month'] ?? 20);
                $daily_rate = floatval($data['daily_rate'] ?? 50000); 
                $severance = floatval($data['severance_amount'] ?? 0);
                $loan_recovery = floatval($data['loan_recovery'] ?? 0);
                $asset_recovery = floatval($data['asset_recovery_costs'] ?? 0);
                $manual_adjustments = floatval($data['manual_adjustments'] ?? 0);

                $total_payable = ($days_worked * $daily_rate) + $severance - $loan_recovery - $asset_recovery + $manual_adjustments;

                $stmt = $pdo->prepare("INSERT INTO final_settlements (exit_request_id, days_worked_final_month, daily_rate, severance_amount, loan_recovery, asset_recovery_costs, manual_adjustments, total_payable, payroll_push_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending') RETURNING id");
                $stmt->execute([$exit_id, $days_worked, $daily_rate, $severance, $loan_recovery, $asset_recovery, $manual_adjustments, $total_payable]);
                $settlement_id = $stmt->fetchColumn();

                $upd = $pdo->prepare("UPDATE exit_requests SET status = 'Ready for Settlement' WHERE id = ?");
                $upd->execute([$exit_id]);

                echo json_encode(['status' => 'success', 'settlement_id' => $settlement_id, 'total_payable' => $total_payable, 'message' => 'Final settlement calculated successfully.']);
            }
            break;

        // 5. Finalize Exit (The Status Hook Trigger)
        case 'finalize':
            if ($method === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $exit_id = intval($data['exit_id'] ?? 0);
                $employee_id = intval($data['employee_id'] ?? 0);
                $do_not_rehire = isset($data['do_not_rehire']) ? (bool)$data['do_not_rehire'] : false;

                $pdo->beginTransaction();

                // 1. Flip the Status Hook in Module 1
                $hookStmt = $pdo->prepare("UPDATE employees SET status = 'Exited' WHERE id = ?");
                $hookStmt->execute([$employee_id]);

                // 2. Update exit request and blacklist flag
                $exitStmt = $pdo->prepare("UPDATE exit_requests SET status = 'Finalized / Exited', do_not_rehire_flag = ? WHERE id = ?");
                $exitStmt->execute([($do_not_rehire ? 1 : 0), $exit_id]);

                // 3. Push to Payroll
                $payStmt = $pdo->prepare("UPDATE final_settlements SET payroll_push_status = 'Pushed to Payroll' WHERE exit_request_id = ?");
                $payStmt->execute([$exit_id]);

                $pdo->commit();
                echo json_encode(['status' => 'success', 'message' => 'Exit finalized. Status hook flipped to Exited, pushed to payroll, and access locked.']);
            }
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid API endpoint']);
            break;
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>