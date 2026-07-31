<?php
// Direct database configuration for PostgreSQL (Chap Chap HR)
$host = 'localhost';
$db   = 'HR';
$user = 'postgres';
$pass = 'Jack.02.+ma';
$port = '5432';

echo "<h2>Chap Chap HR - Database Auto-Patcher</h2>";

try {
    // Connect to the database
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$db;", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // The tables that were skipped and need the employee_id column
    $tables_to_patch = [
        'final_settlements',
        'clearance_checklist',
        'disciplinary_cases',
        'grievances',
        'payroll_runs'
    ];

    echo "<ul>";
    foreach ($tables_to_patch as $table) {
        try {
            // Force the column addition safely without strict constraints
            $sql = "ALTER TABLE $table ADD COLUMN IF NOT EXISTS employee_id INT;";
            $pdo->exec($sql);
            echo "<li style='color: green;'>✔ Successfully updated table: <strong>$table</strong></li>";
        } catch (PDOException $e) {
            // If PostgreSQL skips it, we will catch the exact error message here
            echo "<li style='color: red;'>⚠ Failed on <strong>$table</strong>. Error: " . htmlspecialchars($e->getMessage()) . "</li>";
        }
    }
    echo "</ul>";

    echo "<p style='color: blue;'><strong>Patching Complete!</strong> <a href='check_and_connect.php'>Click here to go back to your Audit page and check the results.</a></p>";

} catch (PDOException $e) {
    echo "<p style='color: red;'><strong>Connection Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>