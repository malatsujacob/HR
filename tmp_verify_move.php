<?php
require "config/db.php";
$id = 28;
$s = $pdo->prepare('SELECT * FROM employees WHERE employee_id=?');
$s->execute([$id]);
$e = $s->fetchAll(PDO::FETCH_ASSOC);
echo 'employees rows:'.count($e)."\n";
$s2=$pdo->prepare('SELECT * FROM offboarded_employees WHERE original_employee_id=?');
$s2->execute([$id]);
$o=$s2->fetchAll(PDO::FETCH_ASSOC);
echo 'offboarded rows:'.count($o)."\n";
if(count($o)>0) { print_r(array_slice($o[0],0,6)); }
