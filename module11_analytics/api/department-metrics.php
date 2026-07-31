<?php
// api/department-metrics.php - Feeds department chart data for Module 11
header('Content-Type: application/json');

require_once '../../config/db.php';

$metric = isset($_GET['metric']) ? $_GET['metric'] : 'turnover';

try {
    $data = [
        [ "department" => "Sales", "value" => 4.5 ],
        [ "department" => "Engineering", "value" => 1.2 ],
        [ "department" => "Operations", "value" => 2.8 ],
        [ "department" => "Human Resources", "value" => 2.0 ]
    ];

    echo json_encode([
        "status" => "success",
        "metric" => $metric,
        "data" => $data
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>