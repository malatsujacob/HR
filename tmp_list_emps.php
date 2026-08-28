<?php
require 'config/db.php';
$stmt = $pdo->query("SELECT employee_id, first_name, last_name, work_email FROM employees ORDER BY employee_id DESC LIMIT 5");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($rows as $r){
    echo $r['employee_id'].' | '.$r['first_name'].' '.$r['last_name'].' | '.$r['work_email']."\n";
}
