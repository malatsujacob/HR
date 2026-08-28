<?php
require 'config/db.php';
$tables = ['candidates', 'job_requisitions'];
foreach ($tables as $t) {
    echo "TABLE: $t\n";
    $stmt = $pdo->prepare("SELECT column_name,data_type,is_nullable,character_maximum_length FROM information_schema.columns WHERE table_name=? ORDER BY ordinal_position");
    $stmt->execute([$t]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['column_name'] . ' | ' . $row['data_type'] . ' | ' . $row['is_nullable'] . ' | ' . ($row['character_maximum_length'] ?? '') . "\n";
    }
    echo "\n";
}
