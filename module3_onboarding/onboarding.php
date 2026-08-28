<?php
session_start();
require_once '../config/db.php';

$success_msg = '';
$error_msg = '';

// 1. Establish user roles and permissions
$user_role = $_SESSION['role'] ?? 'Employee';
$is_developer = (isset($_SESSION['is_developer']) && $_SESSION['is_developer'] === true) || ($user_role === 'Developer');
$is_strict_hr = in_array($user_role, ['HR', 'Assistant HR']);

$active_tab = $_GET['tab'] ?? 'employee';

// 2. Strict Access Control for Admin Tab
if ($active_tab === 'admin') {
    if (!$is_strict_hr && !$is_developer) {
        header("Location: onboarding.php?tab=employee&error=access_denied");
        exit();
    }
}

// Ensure sample onboarding record #1 exists
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS onboarding_checklists (
            onboard_id INT PRIMARY KEY,
            candidate_id INT,
            laptop_assigned VARCHAR(10),
            email_created VARCHAR(10),
            access_badge_ready VARCHAR(10),
            work_email VARCHAR(100),
            hr_email_message TEXT,
            requirement_doc_path VARCHAR(255),
            employment_contract_doc VARCHAR(255),
            hr_approval_status TEXT
        )
    ");
    $pdo->exec("
        INSERT INTO onboarding_checklists (onboard_id, candidate_id, laptop_assigned, email_created, access_badge_ready)
        SELECT 1, 1, 'No', 'No', 'No'
        WHERE NOT EXISTS (SELECT 1 FROM onboarding_checklists WHERE onboard_id = 1)
    ");
} catch (Exception $e) {}

// Handle HR Admin Actions (Only if authorized via role session)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($is_strict_hr || $is_developer)) {
    
    // Send Requirements
    if (isset($_POST['hr_send_requirements'])) {
        $employee_email  = trim($_POST['employee_email'] ?? '');
        $hr_instructions = trim($_POST['hr_instructions'] ?? '');
        
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

        $requirement_doc_path = $_POST['existing_requirement_doc'] ?? '';
        if (isset($_FILES['requirement_file']) && $_FILES['requirement_file']['error'] === UPLOAD_ERR_OK) {
            $requirement_doc_path = $upload_dir . time() . '_req_' . basename($_FILES['requirement_file']['name']);
            move_uploaded_file($_FILES['requirement_file']['tmp_name'], $requirement_doc_path);
        }

        $stmt = $pdo->prepare("UPDATE onboarding_checklists SET work_email = ?, hr_email_message = ?, requirement_doc_path = ? WHERE onboard_id = 1");
        $stmt->execute([$employee_email, $hr_instructions, $requirement_doc_path]);

        if (!empty($employee_email)) {
            $to = $employee_email;
            $subject = "Onboarding Requirements - Chap Chap Africa";
            $boundary = md5(time());
            $headers = "From: HR Department <hr@chapchapafrica.com>\r\nMIME-Version: 1.0\r\nContent-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";

            $message = "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\nDear New Hire,\n\n" . $hr_instructions . "\r\n\r\nBest regards,\nHR Department\r\n";
            
            if (file_exists($requirement_doc_path)) {
                $file_content = file_get_contents($requirement_doc_path);
                $encoded_content = chunk_split(base64_encode($file_content));
                $filename = basename($requirement_doc_path);
                $message .= "--{$boundary}\r\nContent-Type: application/octet-stream; name=\"{$filename}\"\r\nContent-Transfer-Encoding: base64\r\nContent-Disposition: attachment; filename=\"{$filename}\"\r\n\r\n" . $encoded_content . "\r\n";
            }
            $message .= "--{$boundary}--";
            @mail($to, $subject, $message, $headers);
            $success_msg = "Requirements sent successfully!";
        }
    }

    // Verify & Send Feedback
    if (isset($_POST['hr_verify_and_notify'])) {
        $employee_email        = trim($_POST['employee_email'] ?? '');
        $verification_feedback = trim($_POST['verification_feedback'] ?? '');

        $stmt = $pdo->prepare("UPDATE onboarding_checklists SET hr_approval_status = ? WHERE onboard_id = 1");
        $stmt->execute([$verification_feedback]);

        if (!empty($employee_email)) {
            @mail($employee_email, "Onboarding Verification Status", "HR Feedback:\n" . $verification_feedback, "From: hr@chapchapafrica.com");
        }
        $success_msg = "Verification status saved and sent!";
    }
}

// Handle Employee Upload (Open to everyone)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['employee_upload_requirements'])) {
    $upload_dir = '../uploads/';
    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

    $completed_doc_path = $_POST['existing_completed_doc'] ?? '';
    if (isset($_FILES['completed_requirement_file']) && $_FILES['completed_requirement_file']['error'] === UPLOAD_ERR_OK) {
        $completed_doc_path = $upload_dir . time() . '_completed_' . basename($_FILES['completed_requirement_file']['name']);
        if (move_uploaded_file($_FILES['completed_requirement_file']['tmp_name'], $completed_doc_path)) {
            $stmt = $pdo->prepare("UPDATE onboarding_checklists SET employment_contract_doc = ?, hr_approval_status = NULL WHERE onboard_id = 1");
            $stmt->execute([$completed_doc_path]);
            $success_msg = "Document uploaded successfully for HR review.";
        }
    }
}

try {
    $row = $pdo->query("SELECT * FROM onboarding_checklists WHERE onboard_id = 1")->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $row = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Onboarding Portal - HRMS</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 0; }
        .container { margin: 25px auto; margin-left: 270px; max-width: 900px; padding: 20px; background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 18px; }
        .page-title { margin: 0; color: #0f172a; font-size: 16px; font-weight: 700; text-transform: uppercase; }
        .nav-tabs { display: flex; gap: 8px; }
        .tab-btn { padding: 6px 14px; border-radius: 4px; font-size: 11px; font-weight: bold; text-decoration: none; border: 1px solid #cbd5e1; background: #f1f5f9; color: #475569; }
        .tab-btn.active { background: #0284c7; color: #fff; border-color: #0284c7; }
        .btn-primary { background: #0284c7; color: white; padding: 7px 12px; border-radius: 4px; font-size: 11px; font-weight: bold; border: none; cursor: pointer; width: 100%; margin-top: 6px; }
        .btn-purple { background: #7c3aed; color: white; padding: 7px 12px; border-radius: 4px; font-size: 11px; font-weight: bold; border: none; cursor: pointer; width: 100%; margin-top: 6px; }
        .panel-box { background: #fff; padding: 16px; border: 1px solid #e2e8f0; border-radius: 6px; }
        input[type="file"], input[type="email"], textarea { font-size: 11px; padding: 7px; margin-top: 4px; width: 100%; box-sizing: border-box; border: 1px solid #cbd5e1; border-radius: 4px; background: #fff; }
        textarea { resize: vertical; height: 50px; }
        label { font-size: 11px; font-weight: bold; color: #334155; display: block; margin-top: 8px; }
        .alert-success { background: #dcfce7; color: #166534; padding: 8px; border-radius: 4px; margin-bottom: 12px; font-size: 11px; }
        .alert-error { background: #fee2e2; color: #991b1b; padding: 8px; border-radius: 4px; margin-bottom: 12px; font-size: 11px; }
    </style>
</head>
<body>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>

<div class="container">
    <header>
        <h1 class="page-title">Onboarding Management</h1>
        <div class="nav-tabs">
            <a href="onboarding.php?tab=employee" class="tab-btn <?php echo ($active_tab == 'employee') ? 'active' : ''; ?>">Employee View</a>
            <a href="onboarding.php?tab=admin" class="tab-btn <?php echo ($active_tab == 'admin') ? 'active' : ''; ?>">Admin (HR) View</a>
        </div>
    </header>

    <?php if (!empty($success_msg)): ?>
        <div class="alert-success"><?php echo $success_msg; ?></div>
    <?php endif; ?>

    <?php if (!empty($error_msg) || isset($_GET['error'])): ?>
        <div class="alert-error"><?php echo $error_msg ?: "Access Denied: Admin view restricted to HR, Assistant HR, and Developers."; ?></div>
    <?php endif; ?>

    <!-- EMPLOYEE PORTAL TAB -->
    <?php if ($active_tab == 'employee'): ?>
        <div class="panel-box" style="border-top: 3px solid #16a34a;">
            <h3 style="margin-top:0; color:#16a34a; font-size:13px;">Employee Onboarding Portal</h3>
            
            <?php if(!empty($row['hr_approval_status'])): ?>
                <div style="background:#f0fdf4; border:1px solid #86efac; color:#166534; padding:10px; border-radius:4px; font-size:11px; margin-bottom:10px;">
                    <strong>✅ HR Status:</strong> <?php echo nl2br(htmlspecialchars($row['hr_approval_status'])); ?>
                </div>
            <?php elseif(!empty($row['employment_contract_doc'])): ?>
                <div style="background:#fef9c3; border:1px solid #fde047; color:#854d0e; padding:10px; border-radius:4px; font-size:11px; margin-bottom:10px;">
                    ⏳ Document submitted. Awaiting HR verification.
                </div>
            <?php endif; ?>

            <label>1. HR Requirements Form:</label>
            <?php if(!empty($row['requirement_doc_path'])): ?>
                <a href="<?php echo htmlspecialchars($row['requirement_doc_path']); ?>" target="_blank" style="font-size:11px; font-weight:bold; color:#0284c7; display:block; padding:6px; background:#f0f9ff; border:1px solid #bae6fd; text-align:center; border-radius:4px; margin-bottom:10px; text-decoration:none;">📥 Download Requirements File</a>
            <?php else: ?>
                <p style="font-size:11px; color:#64748b; margin:4px 0;">No requirements file published yet.</p>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="existing_completed_doc" value="<?php echo htmlspecialchars($row['employment_contract_doc'] ?? ''); ?>">
                <label>2. Upload Completed Requirements:</label>
                <input type="file" name="completed_requirement_file" accept=".pdf,.csv" required>
                <button type="submit" name="employee_upload_requirements" class="btn-primary">Submit Completed Requirements</button>
            </form>
        </div>

    <!-- ADMIN TAB -->
    <?php elseif ($active_tab == 'admin'): ?>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
            <span style="font-size:11px; font-weight:bold; color:#7c3aed;">Logged in as: <?php echo htmlspecialchars($user_role); ?> <?php echo $is_developer ? '(Developer Access)' : ''; ?></span>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <div class="panel-box" style="border-top: 3px solid #0284c7;">
                <h3 style="margin-top:0; color:#0284c7; font-size:13px;">Review Submissions</h3>
                <?php if(!empty($row['employment_contract_doc'])): ?>
                    <a href="<?php echo htmlspecialchars($row['employment_contract_doc']); ?>" target="_blank" style="font-size:11px; color:#0284c7; font-weight:bold; display:block; margin-bottom:8px;">📥 View Candidate Submission</a>
                    <form method="POST">
                        <input type="hidden" name="employee_email" value="<?php echo htmlspecialchars($row['work_email'] ?? ''); ?>">
                        <label>Verification Feedback:</label>
                        <textarea name="verification_feedback" required><?php echo htmlspecialchars($row['hr_approval_status'] ?? 'Approved successfully!'); ?></textarea>
                        <button type="submit" name="hr_verify_and_notify" class="btn-primary">Save & Send Status</button>
                    </form>
                <?php else: ?>
                    <p style="font-size:11px; color:#64748b;">No candidate submissions to review.</p>
                <?php endif; ?>
            </div>

            <div class="panel-box" style="border-top: 3px solid #7c3aed;">
                <h3 style="margin-top:0; color:#7c3aed; font-size:13px;">Send Requirements Form</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="existing_requirement_doc" value="<?php echo htmlspecialchars($row['requirement_doc_path'] ?? ''); ?>">
                    <label>Requirements File (PDF/CSV):</label>
                    <input type="file" name="requirement_file" accept=".pdf,.csv">
                    <label>Candidate Email:</label>
                    <input type="email" name="employee_email" value="<?php echo htmlspecialchars($row['work_email'] ?? ''); ?>" required>
                    <label>Instructions:</label>
                    <textarea name="hr_instructions"><?php echo htmlspecialchars($row['hr_email_message'] ?? ''); ?></textarea>
                    <button type="submit" name="hr_send_requirements" class="btn-purple">Send Requirements</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

</div>

</body>
</html>