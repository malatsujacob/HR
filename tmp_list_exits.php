<?php
require 'config/db.php';
$stmt = $pdo->query("SELECT id, employee_id, last_working_day, exit_reason, status FROM exit_requests ORDER BY id DESC LIMIT 10");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($rows as $r){
    echo $r['id'] . ' | emp:' . $r['employee_id'] . ' | ' . $r['status'] . ' | ' . ($r['exit_reason'] ?? '') . ' | lwd:' . ($r['last_working_day'] ?? '') . "\n";
}
