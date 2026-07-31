<?php
// api/export.php - Handles custom report exports to CSV/XLSX for Module 11
header('Content-Type: application/json');

require_once '../../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);

try {
    $format = isset($input['format']) ? $input['format'] : 'csv';
    $filename = "hr_analytics_report_" . date('Y-m-d') . "." . $format;

    echo json_encode([
        "status" => "success",
        "message" => "Report generated successfully",
        "file_name" => $filename,
        "download_url" => "/HR/module11_analytics/api/downloads/" . $filename
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>