<?php
require 'config/db.php';
$search = ['employees','offboarded_employees','offboarding','offboard','clearance','clearances'];
foreach($search as $t){
    $stmt = $pdo->prepare("SELECT table_name FROM information_schema.tables WHERE table_name = ? LIMIT 1");
    $stmt->execute([$t]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        echo "FOUND_TABLE: " . $row['table_name'] . "\n";
        $col = $pdo->prepare("SELECT column_name,data_type,is_nullable,character_maximum_length FROM information_schema.columns WHERE table_name=? ORDER BY ordinal_position");
        $col->execute([$t]);
        while ($c = $col->fetch(PDO::FETCH_ASSOC)){
            echo $c['column_name'] . ' | ' . $c['data_type'] . ' | ' . $c['is_nullable'] . ' | ' . ($c['character_maximum_length'] ?? '') . "\n";
        }
        echo "\n";
    }
}
// Also list any table names containing 'offboard' or 'clear' for discovery
$stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_name ILIKE '%offboard%' OR table_name ILIKE '%clear%' OR table_name ILIKE '%exit%' OR table_name ILIKE '%separation%'");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($rows as $r){ echo "DISCOVERED: " . $r['table_name'] . "\n"; }
