<?php
require_once '../config/db.php';

$employee_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($employee_id <= 0) {
    header('Location: list.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['employee_doc'])) {
    $file = $_FILES['employee_doc'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        if (in_array($file['type'], $allowedTypes)) {
            $uploadDir = '../../uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileName = time() . '_' . basename($file['name']);
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $dbPath = '../../uploads/' . $fileName;
                
                $stmt = $pdo->prepare("UPDATE employees SET document_path = ? WHERE employee_id = ?");
                $stmt->execute([$dbPath, $employee_id]);
                
                $audit = $pdo->prepare("INSERT INTO employee_audit_logs (employee_id, action_performed, performed_by) VALUES (?, ?, ?)");
                $audit->execute([$employee_id, 'Document uploaded', 'HR Admin']);
                
                $message = "Document uploaded successfully!";
            } else {
                $error = "Failed to move uploaded file.";
            }
        } else {
            $error = "Invalid file type. Only JPG, PNG, and PDF are allowed.";
        }
    } else {
        $error = "Upload error code: " . $file['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Document</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>
<div class="container" style="max-width: 600px; margin-left: 280px;">
    <h2>Upload Employee Document</h2>
    <?php if ($message): ?><div class="alert-success"><?php echo $message; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert-error"><?php echo $error; ?></div><?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data" class="card">
        <div class="form-group">
            <label>Select Document (PDF, JPG, PNG)</label>
            <input type="file" name="employee_doc" required style="width:100%; padding:10px; background:#161616; color:#fff; border:1px solid var(--border-color);">
        </div>
        <button type="submit" class="btn-primary" style="margin-top:15px;">Upload</button>
        <a href="view.php?id=<?php echo $employee_id; ?>" class="back-link" style="display:block; margin-top:15px;">&larr; Back to Profile</a>
    </form>
</div>
</body>
</html>