<?php
// Include the database connection
require_once '../config/db.php';

$message = '';
$error = '';

// Handle CSV Export
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=employee_master_export_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    // CSV Header row matching database columns
    fputcsv($output, [
        'employee_id', 'first_name', 'last_name', 'date_of_birth', 'gender', 'nationality', 'marital_status', 
        'personal_email', 'work_email', 'phone_number', 'physical_address', 
        'next_of_kin_name', 'next_of_kin_relationship', 'next_of_kin_phone', 'next_of_kin_address',
        'department', 'job_title', 'reporting_manager', 'work_location', 'hire_date', 'employment_type', 'status'
    ]);
    
    // Fetch all records
    $stmt = $pdo->query("SELECT * FROM employees ORDER BY employee_id DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

// Handle CSV Import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    if ($_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['csv_file']['tmp_name'];
        $fileName = $_FILES['csv_file']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileExtension === 'csv') {
            if (($handle = fopen($fileTmpPath, 'r')) !== FALSE) {
                // Skip header row
                $header = fgetcsv($handle);
                
                $successCount = 0;
                $pdo->beginTransaction();

                try {
                    $sql = "INSERT INTO employees (
                        first_name, last_name, date_of_birth, gender, nationality, marital_status, 
                        personal_email, work_email, phone_number, physical_address, 
                        next_of_kin_name, next_of_kin_relationship, next_of_kin_phone, next_of_kin_address,
                        department, job_title, reporting_manager, work_location, hire_date, employment_type, status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $stmt = $pdo->prepare($sql);

                    while (($data = fgetcsv($handle)) !== FALSE) {
                        if (count($data) >= 21) {
                            $offset = (is_numeric($data[0]) && count($data) == 22) ? 1 : 0;
                            
                            $stmt->execute([
                                $data[$offset + 0],  // first_name
                                $data[$offset + 1],  // last_name
                                $data[$offset + 2],  // date_of_birth
                                $data[$offset + 3],  // gender
                                $data[$offset + 4],  // nationality
                                $data[$offset + 5],  // marital_status
                                $data[$offset + 6],  // personal_email
                                $data[$offset + 7],  // work_email
                                $data[$offset + 8],  // phone_number
                                $data[$offset + 9],  // physical_address
                                $data[$offset + 10], // next_of_kin_name
                                $data[$offset + 11], // next_of_kin_relationship
                                $data[$offset + 12], // next_of_kin_phone
                                $data[$offset + 13], // next_of_kin_address
                                $data[$offset + 14], // department
                                $data[$offset + 15], // job_title
                                $data[$offset + 16], // reporting_manager
                                $data[$offset + 17], // work_location
                                $data[$offset + 18], // hire_date
                                $data[$offset + 19], // employment_type
                                $data[$offset + 20]  // status
                            ]);
                            
                            $new_id = $pdo->lastInsertId();
                            // Log audit
                            $audit_stmt = $pdo->prepare("INSERT INTO employee_audit_logs (employee_id, action_performed, performed_by) VALUES (?, ?, ?)");
                            $audit_stmt->execute([$new_id, 'Imported via CSV batch', 'HR Admin']);
                            
                            $successCount++;
                        }
                    }
                    
                    fclose($handle);
                    $pdo->commit();
                    $message = "Successfully imported {$successCount} employee records!";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = "Error processing CSV data: " . $e->getMessage();
                }
            } else {
                $error = "Could not open uploaded CSV file.";
            }
        } else {
            $error = "Please upload a valid .csv file.";
        }
    } else {
        $error = "File upload failed with error code: " . $_FILES['csv_file']['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Import & Export - HR System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background-color: #f8fafc; color: #0f172a; margin: 0; font-family: Arial, sans-serif; }
        .container { margin-left: 260px; max-width: calc(100% - 280px); padding: 25px; box-sizing: border-box; background: #f8fafc; min-height: 100vh; }
        header { border-bottom: 2px solid #cbd5e1; padding-bottom: 15px; margin-bottom: 25px; }
        header h1 { font-size: 22px; font-weight: 900; margin: 0; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; }
        .card { background: #ffffff; padding: 20px; border-radius: 6px; border: 1px solid #cbd5e1; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .btn-primary { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #ffffff; padding: 7px 14px; border-radius: 4px; font-size: 12px; text-decoration: none; font-weight: 900; display: inline-block; border: none; cursor: pointer; text-transform: uppercase; letter-spacing: 0.5px; }
        .btn-secondary { background: #e2e8f0; color: #334155; padding: 6px 12px; border-radius: 4px; font-size: 12px; text-decoration: none; font-weight: 900; border: 1px solid #cbd5e1; display: inline-block; text-transform: uppercase; letter-spacing: 0.5px; }
        .alert-success { background: #dcfce7; color: #166534; padding: 12px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #bbf7d0; font-size: 13px; }
        .alert-error { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #fecaca; font-size: 13px; }
    </style>
</head>
<body>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>

<div class="container">
    <header>
        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <a href="list.php" class="btn-secondary">&larr; Back</a>
            <h1>Import / Export</h1>
        </div>
    </header>

    <?php if ($message): ?>
        <div class="alert-success"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Export Card -->
    <div class="card">
        <h3 style="color: #0284c7; margin-top: 0; font-size: 16px;">Export</h3>
        <a href="import_export.php?action=export" class="btn-primary" style="margin-top: 5px;">Download CSV</a>
    </div>

    <!-- Import Card -->
    <div class="card">
        <h3 style="color: #0284c7; margin-top: 0; font-size: 16px;">Import</h3>
        
        <form method="POST" enctype="multipart/form-data" style="margin-top: 15px;">
            <div style="margin-bottom: 15px;">
                <input type="file" name="csv_file" accept=".csv" required style="background: #ffffff; padding: 8px; border: 1px solid #cbd5e1; color: #0f172a; width: 100%; border-radius: 4px; box-sizing: border-box; font-size: 12px;">
            </div>
            <button type="submit" class="btn-primary">Upload</button>
        </form>
    </div>
</div>

</body>
</html>