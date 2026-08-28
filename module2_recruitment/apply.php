<?php
require_once '../config/db.php';

// Check if applications are open or closed by HR
$applications_are_open = true;
try {
    $status_stmt = $pdo->query("SELECT setting_value FROM app_settings WHERE setting_key = 'applications_open'");
    $res = $status_stmt->fetch(PDO::FETCH_COLUMN);
    if ($res !== false) {
        $applications_are_open = ($res == '1');
    }
} catch (Exception $e) {}

// Ensure cover_letter column exists in candidates table
try {
    $pdo->exec("ALTER TABLE candidates ADD COLUMN IF NOT EXISTS cover_letter TEXT NULL");
} catch (Exception $e) {}

$success_msg = '';
$error_msg = '';

// Handle Candidate Application Submission (Only process if open)
if ($applications_are_open && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_candidate'])) {
    $job_title_input = trim($_POST['job_title_input']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    $source = trim($_POST['source']);
    $cover_letter = trim($_POST['cover_letter']);

    // Handle CV file upload
    $cv_file_path = '';
    if (isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['cv_file']['tmp_name'];
        $file_name = $_FILES['cv_file']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if ($file_ext === 'pdf') {
            $upload_dir = '../uploads/cvs/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $new_file_name = uniqid('cv_') . '.' . $file_ext;
            $destination = $upload_dir . $new_file_name;
            if (move_uploaded_file($file_tmp, $destination)) {
                $cv_file_path = 'uploads/cvs/' . $new_file_name;
            }
        }
    }

    try {
        $req_id_val = null;
        $req_check = $pdo->prepare("SELECT requisition_id FROM job_requisitions WHERE job_title LIKE ? LIMIT 1");
        $req_check->execute(['%' . $job_title_input . '%']);
        $matched_req = $req_check->fetch(PDO::FETCH_ASSOC);
        if ($matched_req) {
            $req_id_val = $matched_req['requisition_id'];
        }

        $stmt = $pdo->prepare("INSERT INTO candidates (requisition_id, first_name, last_name, email, phone_number, source, cv_file_path, cover_letter, pipeline_stage) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'New Application')");
        $stmt->execute([$req_id_val, $first_name, $last_name, $email, $phone_number, $source, $cv_file_path, $cover_letter]);
        
        $success_msg = "Your application has been submitted successfully!";
    } catch (PDOException $e) {
        $error_msg = "Error submitting application: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Application Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/module2_recruitment.css">
    <style>
        body { background-color: #f1f5f9; color: #1e293b; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .public-wrapper { max-width: 800px; margin: 40px auto; padding: 20px; }
        /* Full red rectangular border frame around the card */
        .card { background: #ffffff; border: 3px solid #dc2626; border-radius: 10px; padding: 30px; box-shadow: 0 4px 6px rgba(220, 38, 38, 0.1); }
        .card-title { font-size: 20px; font-weight: 600; color: #7c3aed; margin-top: 0; margin-bottom: 20px; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 13px; font-weight: 600; color: #475569; }
        .form-group input, .form-group select, .form-group textarea { padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; background-color: #f8fafc; color: #1e293b; font-family: inherit; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #7c3aed; background-color: #ffffff; box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1); }
        .btn-custom-primary { background: #7c3aed; color: white; padding: 12px 24px; border-radius: 6px; font-size: 14px; font-weight: 600; border: none; cursor: pointer; width: 100%; }
        .btn-custom-primary:hover { background: #6d28d9; }
        .alert-success { background: #dcfce7; color: #166534; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #991b1b; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; border: 1px solid #fecaca; }
    </style>
</head>
<body>

<div class="public-wrapper">
    <div class="card">
        <h2 class="card-title"><strong>Job Application form</strong></h2>
        
        <?php if (!$applications_are_open): ?>
            <div style="text-align: center; padding: 40px 20px;">
                <div style="font-size: 48px; margin-bottom: 15px;">🔒</div>
                <h3 style="color: #1e293b; margin-bottom: 10px;">Applications Are Currently Closed</h3>
                <p style="color: #64748b; font-size: 14px;">We are no longer accepting submissions for open positions at this time. Please check back later.</p>
            </div>
        <?php else: ?>
            <p style="font-size: 14px; color: #64748b; margin-bottom: 25px;">Please fill in the form to apply for the position with our company.</p>

            <?php if (!empty($success_msg)): ?>
                <div class="alert-success"><strong>Success:</strong> <?php echo $success_msg; ?></div>
            <?php endif; ?>

            <?php if (!empty($error_msg)): ?>
                <div class="alert-error"><strong>Error:</strong> <?php echo $error_msg; ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group">
                        <label><strong>Job Title</strong></label>
                        <input type="text" name="job_title_input" required placeholder="e.g. Software Engineer">
                    </div>
                    <div class="form-group">
                        <label><strong>First Name</strong></label>
                        <input type="text" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label><strong>Last Name</strong></label>
                        <input type="text" name="last_name" required>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label><strong>Email Address</strong></label>
                        <input type="email" name="email" required>
                        <span style="font-size: 12px; color: #64748b; margin-top: 2px;">We'll never share your email with anyone else.</span>
                    </div>
                    <div class="form-group">
                        <label><strong>Phone Number</strong></label>
                        <input type="text" name="phone_number" required>
                    </div>
                    <div class="form-group">
                        <label><strong>Source</strong></label>
                        <input list="source_options" name="source" required placeholder="Select or type">
                        <datalist id="source_options">
                            <option value="LinkedIn">
                            <option value="Indeed">
                            <option value="Referral">
                            <option value="Careers Page">
                            <option value="Agency">
                        </datalist>
                    </div>
                </div>

                <div class="form-grid" style="grid-template-columns: 1fr; margin-bottom: 20px;">
                    <div class="form-group">
                        <label><strong>Cover Letter / Why You Are Fit for the Job</strong></label>
                        <textarea name="cover_letter" rows="4" placeholder="Briefly describe your experience, skills, and why you are a great fit for this role..."></textarea>
                    </div>
                </div>

                <div class="form-grid" style="grid-template-columns: 1fr; margin-bottom: 25px;">
                    <div class="form-group">
                        <label><strong>Upload CV (PDF format only)</strong></label>
                        <input type="file" name="cv_file" accept=".pdf" required style="padding: 12px; background: #fff;">
                    </div>
                </div>

                <button type="submit" name="add_candidate" class="btn-custom-primary">Submit Application</button>
            </form>
        <?php endif; ?>
    </div>
</div>

</body>
</html>