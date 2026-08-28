<?php
require "config/db.php";
// Insert test exit request
$employee_id = 28;
$last_working_day = date('Y-m-d', strtotime('+1 day'));
$exit_reason = 'Test Offboard Move';
$stmt = $pdo->prepare("INSERT INTO exit_requests (employee_id, last_working_day, exit_reason, status) VALUES (?, ?, ?, 'Pending Clearance') RETURNING id");
$stmt->execute([$employee_id, $last_working_day, $exit_reason]);
$exit_id = $stmt->fetchColumn();
echo "Created exit_request id=$exit_id for emp=$employee_id\n";
// Mark all clearance checklist items as cleared to allow finalize (create default checklists)
$departments = [['IT','Return assets'],['Finance','Finalize loans'],['HR','Exit interview'],['Facilities','Return keys']];
foreach($departments as $d){ $c = $pdo->prepare("INSERT INTO clearance_checklist (exit_request_id, department_name, item_description, is_cleared) VALUES (?, ?, ?, TRUE)"); $c->execute([$exit_id, $d[0], $d[1]]); }
// Create a final settlement row so finalize flow can update it
$fs = $pdo->prepare("INSERT INTO final_settlements (exit_request_id, days_worked_final_month, daily_rate, severance_amount, loan_recovery, asset_recovery_costs, manual_adjustments, total_payable, payroll_push_status) VALUES (?, 20, 50000, 0, 0, 0, 0, 1000000, 'Pending') RETURNING id");
$fs->execute([$exit_id]); $fsid = $fs->fetchColumn(); echo "Created final_settlement id=$fsid\n";
// Now call local API finalize
$url = 'http://localhost:8000/module13_offboarding/api.php?action=finalize';
$data = json_encode(['exit_id' => intval($exit_id), 'employee_id' => intval($employee_id), 'do_not_rehire' => false]);
$opts = ['http' => ['method' => 'POST','header' => "Content-Type: application/json\r\n","content" => $data, 'timeout' => 30]];
$context = stream_context_create($opts);
$result = @file_get_contents($url, false, $context);
if ($result === false) { echo "API call failed\n"; print_r(error_get_last()); } else { echo "API response:\n"; echo $result; }
