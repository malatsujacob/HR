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

                // 1. Move employee record to offboarded_employees (transactional)
                // Ensure offboarded_employees table exists (safe subset of columns)
                $pdo->exec("CREATE TABLE IF NOT EXISTS offboarded_employees (
                    offboard_id SERIAL PRIMARY KEY,
                    original_employee_id INTEGER,
                    first_name VARCHAR(50),
                    last_name VARCHAR(50),
                    personal_email VARCHAR(100),
                    work_email VARCHAR(100),
                    phone_number VARCHAR(50),
                    department VARCHAR(50),
                    job_title VARCHAR(100),
                    hire_date DATE,
                    employment_type VARCHAR(50),
                    status VARCHAR(50),
                    profile_picture VARCHAR(255),
                    document_path VARCHAR(255),
                    bank_name VARCHAR(100),
                    account_number VARCHAR(100),
                    created_at TIMESTAMP,
                    updated_at TIMESTAMP,
                    exit_reason TEXT,
                    last_working_day DATE,
                    exit_interview_reason VARCHAR(255),
                    exit_interview_text TEXT,
                    do_not_rehire BOOLEAN DEFAULT FALSE,
                    offboarded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");

                // Fetch the employee row
                $empStmt = $pdo->prepare("SELECT * FROM employees WHERE employee_id = ?");
                $empStmt->execute([$employee_id]);
                $employee = $empStmt->fetch(PDO::FETCH_ASSOC);

                if ($employee) {
                    // Insert into offboarded_employees with exit metadata
                    $ins = $pdo->prepare("INSERT INTO offboarded_employees (
                        original_employee_id, first_name, last_name, personal_email, work_email, phone_number,
                        department, job_title, hire_date, employment_type, status, profile_picture, document_path,
                        bank_name, account_number, created_at, updated_at, exit_reason, last_working_day, exit_interview_reason,
                        exit_interview_text, do_not_rehire
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING offboard_id");

                    $ins->execute([
                        $employee['employee_id'],
                        $employee['first_name'] ?? null,
                        $employee['last_name'] ?? null,
                        $employee['personal_email'] ?? null,
                        $employee['work_email'] ?? null,
                        $employee['phone_number'] ?? null,
                        $employee['department'] ?? null,
                        $employee['job_title'] ?? null,
                        $employee['hire_date'] ?? null,
                        $employee['employment_type'] ?? null,
                        ($do_not_rehire ? 'Exited (Do Not Rehire)' : 'Exited'),
                        $employee['profile_picture'] ?? null,
                        $employee['document_path'] ?? null,
                        $employee['bank_name'] ?? null,
                        $employee['account_number'] ?? null,
                        $employee['created_at'] ?? null,
                        $employee['updated_at'] ?? null,
                        ($data['exit_reason'] ?? null),
                        ($data['last_working_day'] ?? null),
                        ($data['exit_interview_reason'] ?? null),
                        ($data['exit_interview_text'] ?? null),
                        ($do_not_rehire ? 1 : 0)
                    ]);

                    // Mark original employee record as Exited (preserve row for long-term archival)
                    $updEmp = $pdo->prepare("UPDATE employees SET status = 'Exited' WHERE employee_id = ?");
                    $updEmp->execute([$employee_id]);

                    // Create audit table if missing and insert a JSON snapshot for long-term retention
                    $pdo->exec("CREATE TABLE IF NOT EXISTS offboard_audit (
                        audit_id SERIAL PRIMARY KEY,
                        offboard_id INTEGER,
                        original_employee_id INTEGER,
                        snapshot JSONB,
                        action VARCHAR(50),
                        actor VARCHAR(100),
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )");

                    $snapshot = json_encode($employee);
                    $auditIns = $pdo->prepare("INSERT INTO offboard_audit (offboard_id, original_employee_id, snapshot, action) VALUES (?, ?, ?, 'offboarded')");
                    $auditIns->execute([$ins->fetchColumn() ?? null, $employee['employee_id'] ?? null, $snapshot]);
                } else {
                    // If employee not found, still update exit request and continue
                }

                // 2. Update exit request and blacklist flag
                $exitStmt = $pdo->prepare("UPDATE exit_requests SET status = 'Finalized / Exited', do_not_rehire_flag = ? WHERE id = ?");
                $exitStmt->execute([($do_not_rehire ? 1 : 0), $exit_id]);

                // 3. Push to Payroll (mark settlement as pushed)
                $payStmt = $pdo->prepare("UPDATE final_settlements SET payroll_push_status = 'Pushed to Payroll' WHERE exit_request_id = ?");
                $payStmt->execute([$exit_id]);

                $pdo->commit();
                echo json_encode(['status' => 'success', 'message' => 'Exit finalized. Status hook flipped to Exited, pushed to payroll, and access locked.']);
            }
            break;

        // 6. Restore an offboarded employee back to active employees
        case 'restore':
            if ($method === 'POST') {
                $data = $_POST ?: json_decode(file_get_contents('php://input'), true);
                $offboard_id = intval($data['offboard_id'] ?? 0);

                if (!$offboard_id) {
                    echo json_encode(['status' => 'error', 'message' => 'Missing offboard_id']);
                    exit;
                }

                $pdo->beginTransaction();
                try {
                    $rowStmt = $pdo->prepare("SELECT * FROM offboarded_employees WHERE offboard_id = ?");
                    $rowStmt->execute([$offboard_id]);
                    $row = $rowStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$row) {
                        throw new Exception('Offboarded record not found');
                    }

                    // If original employee row exists, update it; otherwise insert new
                    $origId = $row['original_employee_id'] ?? null;
                    if ($origId) {
                        $exist = $pdo->prepare("SELECT employee_id FROM employees WHERE employee_id = ?");
                        $exist->execute([$origId]);
                        $found = $exist->fetch(PDO::FETCH_ASSOC);
                    } else {
                        $found = false;
                    }

                    if ($found) {
                        $upd = $pdo->prepare("UPDATE employees SET first_name = ?, last_name = ?, personal_email = ?, work_email = ?, phone_number = ?, department = ?, job_title = ?, hire_date = ?, employment_type = ?, status = ?, profile_picture = ?, document_path = ?, bank_name = ?, account_number = ?, updated_at = ?, phone = ? WHERE employee_id = ?");
                        $upd->execute([
                            $row['first_name'] ?? null,
                            $row['last_name'] ?? null,
                            $row['personal_email'] ?? null,
                            $row['work_email'] ?? null,
                            $row['phone_number'] ?? null,
                            $row['department'] ?? null,
                            $row['job_title'] ?? null,
                            $row['hire_date'] ?? null,
                            $row['employment_type'] ?? null,
                            'Active',
                            $row['profile_picture'] ?? null,
                            $row['document_path'] ?? null,
                            $row['bank_name'] ?? null,
                            $row['account_number'] ?? null,
                            date('Y-m-d H:i:s'),
                            $row['phone_number'] ?? null,
                            $origId
                        ]);
                    } else {
                        $ins = $pdo->prepare("INSERT INTO employees (
                            employee_id, first_name, last_name, personal_email, work_email, phone_number,
                            department, job_title, hire_date, employment_type, status, profile_picture, document_path,
                            bank_name, account_number, created_at, updated_at, phone
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                        $ins->execute([
                            $row['original_employee_id'] ?? null,
                            $row['first_name'] ?? null,
                            $row['last_name'] ?? null,
                            $row['personal_email'] ?? null,
                            $row['work_email'] ?? null,
                            $row['phone_number'] ?? null,
                            $row['department'] ?? null,
                            $row['job_title'] ?? null,
                            $row['hire_date'] ?? null,
                            $row['employment_type'] ?? null,
                            'Active',
                            $row['profile_picture'] ?? null,
                            $row['document_path'] ?? null,
                            $row['bank_name'] ?? null,
                            $row['account_number'] ?? null,
                            $row['created_at'] ?? null,
                            $row['updated_at'] ?? null,
                            $row['phone_number'] ?? null
                        ]);
                    }

                    // Insert restore audit
                    $pdo->exec("CREATE TABLE IF NOT EXISTS offboard_audit (
                        audit_id SERIAL PRIMARY KEY,
                        offboard_id INTEGER,
                        original_employee_id INTEGER,
                        snapshot JSONB,
                        action VARCHAR(50),
                        actor VARCHAR(100),
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )");
                    $snapshot = json_encode($row);
                    $auditIns = $pdo->prepare("INSERT INTO offboard_audit (offboard_id, original_employee_id, snapshot, action) VALUES (?, ?, ?, 'restored')");
                    $auditIns->execute([$offboard_id, $row['original_employee_id'] ?? null, $snapshot]);

                    // Remove offboarded record
                    $del = $pdo->prepare("DELETE FROM offboarded_employees WHERE offboard_id = ?");
                    $del->execute([$offboard_id]);

                    $pdo->commit();
                    echo json_encode(['status' => 'success', 'message' => 'Employee restored to active employees']);
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
                }
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