<?php
require_once 'config/db.php';

try {
    $stmt = $pdo->prepare("
        SELECT column_name, data_type, is_nullable, column_default 
        FROM information_schema.columns 
        WHERE table_name = 'employees'
        ORDER BY ordinal_position;
    ");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>Employees Table Constraints:</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Nullable?</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        $color = ($col['is_nullable'] === 'NO' && $col['column_default'] === null) ? 'red' : 'green';
        echo "<tr style='color: {$color};'>";
        echo "<td>{$col['column_name']}</td>";
        echo "<td>{$col['data_type']}</td>";
        echo "<td>{$col['is_nullable']}</td>";
        echo "<td>" . ($col['column_default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}