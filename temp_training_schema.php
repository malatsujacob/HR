<?php
$dsn = "pgsql:host=localhost;port=5432;dbname=HR;";
$pdo = new PDO($dsn, 'postgres', 'Jack.02.+ma');
$tables = ['training_catalog', 'training_enrollments'];
foreach ($tables as $table) {
    echo "--- $table ---\n";
    $stmt = $pdo->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name='".$table."' ORDER BY ordinal_position");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo $row['column_name'] . ': ' . $row['data_type'] . "\n";
    }
}
