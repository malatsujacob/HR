<?php
// Direct database configuration for PostgreSQL (Chap Chap HR)
$host = 'localhost';
$db   = 'HR';
$user = 'postgres';
$pass = 'Jack.02.+ma';
$port = '5432';

echo "<h2>Chap Chap HR - Database Structure & Connection Audit</h2>";

try {
    // 1. Establish direct connection to PostgreSQL with your real password
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "<p style='color: green;'><strong>Successfully connected to the database!</strong></p>";

    // 2. Fetch all existing tables in your PostgreSQL database
    $query = "SELECT table_name 
              FROM information_schema.tables 
              WHERE table_schema = 'public' 
              ORDER BY table_name;";
    
    $stmt = $pdo->query($query);
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tables)) {
        echo "<p style='color: red;'>No tables found in the database. Please check your database name.</p>";
        exit();
    }

    echo "<h3>1. Detected Tables in Your Database (" . count($tables) . " found):</h3>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li><strong>" . htmlspecialchars($table) . "</strong></li>";
    }
    echo "</ul>";

    // 3. Check structure and columns for each table to ensure employee_id or tracking exists
    echo "<h3>2. Module Table Column Audit:</h3>";
    echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f2f2f2;'><th>Table Name</th><th>Columns Present</th><th>Employee Link Status</th></tr>";

    foreach ($tables as $table) {
        $colQuery = "SELECT column_name 
                     FROM information_schema.columns 
                     WHERE table_schema = 'public' AND table_name = :table_name;";
        $colStmt = $pdo->prepare($colQuery);
        $colStmt->execute(['table_name' => $table]);
        $columns = $colStmt->fetchAll(PDO::FETCH_COLUMN);

        $hasEmployeeId = in_array('employee_id', $columns) ? "<span style='color: green;'>✔ Connected (employee_id found)</span>" : "<span style='color: orange;'>⚠ Missing employee_id link</span>";

        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($table) . "</strong></td>";
        echo "<td>" . implode(', ', $columns) . "</td>";
        echo "<td>" . $hasEmployeeId . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<p style='color: green; margin-top: 20px;'><strong>Audit Complete!</strong> Refresh this page anytime to check your table structures.</p>";

} catch (PDOException $e) {
    echo "<p style='color: red;'><strong>Database Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>