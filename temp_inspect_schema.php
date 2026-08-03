<?php
$pdo = new PDO('pgsql:host=localhost;port=5432;dbname=HR;','postgres','Jack.02.+ma');
$tables = ['employees','training_catalog','training_enrollments'];
foreach ($tables as $table) {
    echo "--- $table ---\n";
    $stmt = $pdo->prepare('SELECT column_name, data_type FROM information_schema.columns WHERE table_name = ? ORDER BY ordinal_position');
    $stmt->execute([$table]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo $row['column_name'] . ': ' . $row['data_type'] . "\n";
    }
}
$stmt = $pdo->query('SELECT employee_id, first_name, last_name FROM employees ORDER BY employee_id ASC LIMIT 10');
echo "\n--- employees sample ---\n";
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo implode(' | ', $row) . "\n";
}
