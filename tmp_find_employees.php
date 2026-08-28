<?php
require 'config/db.php';
$tbl='employees';
$stmt = $pdo->prepare("SELECT table_name FROM information_schema.tables WHERE table_name = ? LIMIT 1");
$stmt->execute([$tbl]);
if ($stmt->fetch()){
    echo "FOUND_TABLE: employees\n";
    $col = $pdo->prepare("SELECT column_name,data_type,is_nullable,character_maximum_length FROM information_schema.columns WHERE table_name=? ORDER BY ordinal_position");
    $col->execute([$tbl]);
    while($c=$col->fetch(PDO::FETCH_ASSOC)){
        echo $c['column_name'].' | '.$c['data_type'].' | '.$c['is_nullable'].' | '.($c['character_maximum_length'] ?? '')."\n";
    }
} else {
    echo "employees table not found. Searching for similar names...\n";
    $rows = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_name ILIKE '%employee%' OR table_name ILIKE '%employees%'")->fetchAll(PDO::FETCH_ASSOC);
    foreach($rows as $r) echo "DISCOVERED: " . $r['table_name'] . "\n";
}
