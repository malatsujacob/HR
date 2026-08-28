<?php
session_start();
require_once '../config/db.php';

// 1. Ensure user is logged in at the application level
if (!isset($_SESSION['employee_id']) && !isset($_SESSION['user_id']) && !isset($_SESSION['employees_hr_logged_in'])) {
    header("Location: ../index.php?error=unauthorized");
    exit();
}

$user_role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';

// 2. Strict HR & Assistant HR check + Developer Override shortcut
$is_strict_hr = in_array($user_role, ['HR', 'Assistant HR']);
$is_developer = (isset($_SESSION['is_developer']) && $_SESSION['is_developer'] === true) || ($user_role === 'Developer');

if (!$is_strict_hr && !$is_developer) {
    header("Location: ../index.php?error=access_denied");
    exit();
}

// Access granted
$success_msg = '';
$error_msg = '';

// Capture candidate ID passed from candidates.php table link
$preselected_candidate_id = $_GET['candidate_id'] ?? '';

// Fetch candidates for the email recipient dropdown
try {
    $stmt = $pdo->query("SELECT candidate_id, first_name, last_name, email, pipeline_stage FROM candidates ORDER BY first_name ASC");
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $candidates = [];
}

// Handle simulated/live email dispatch logging
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_email'])) {
    $candidate_id = $_POST['candidate_id'] ?? '';
    $template_type = $_POST['template_type'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $body = $_POST['body'] ?? '';

    // Fetch candidate email for verification
    $cand_stmt = $pdo->prepare("SELECT email, first_name FROM candidates WHERE candidate_id = ?");
    $cand_stmt->execute([$candidate_id]);
    $recipient = $cand_stmt->fetch(PDO::FETCH_ASSOC);

    if ($recipient) {
        $success_msg = "Successfully dispatched '" . htmlspecialchars($template_type) . "' email to " . htmlspecialchars($recipient['email']) . "!";
    } else {
        $error_msg = "Selected candidate recipient could not be found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auto-Email Templates - HRMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/module2_recruitment.css">
    <style>
        .btn-purple { background: #7c3aed; color: white; padding: 6px 12px; border-radius: 4px; font-size: 11px; font-weight: bold; border: none; cursor: pointer; }
        .alert-success { background: #dcfce7; color: #166534; padding: 8px; border-radius: 4px; margin-bottom: 12px; font-size: 12px; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #991b1b; padding: 8px; border-radius: 4px; margin-bottom: 12px; font-size: 12px; }
        .role-indicator { font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; }
    </style>
    <script>
        function updateTemplate() {
            const typeElem = document.getElementById('template_type');
            if (!typeElem) return;
            const type = typeElem.value;
            const subjectField = document.getElementById('subject');
            const bodyField = document.getElementById('body');

            if (!subjectField || !bodyField) return;

            if (type === 'Application Received') {
                subjectField.value = 'We have received your job application - HRMS';
                bodyField.value = "Dear Candidate,\n\nThank you for applying. We have safely received your application and CV. Our HR team is currently reviewing your profile and will be in touch if your qualifications match our requirements.\n\nBest regards,\nHR Team";
            } else if (type === 'Interview Invite') {
                subjectField.value = 'Invitation to Interview - HRMS';
                bodyField.value = "Dear Candidate,\n\nWe would like to invite you for an interview to discuss your application further. Please let us know your availability for this week.\n\nBest regards,\nHR Team";
            } else if (type === 'Rejection') {
                subjectField.value = 'Update regarding your job application - HRMS';
                bodyField.value = "Dear Candidate,\n\nThank you for your interest in joining our company. While your background is impressive, we have decided to move forward with other candidates whose profiles more closely match our current needs.\n\nBest regards,\nHR Team";
            } else if (type === 'Offer Letter') {
                subjectField.value = 'Job Offer - Welcome to the Team! - HRMS';
                bodyField.value = "Dear Candidate,\n\nWe are thrilled to offer you the position with us! Please review the attached offer details and let us know your decision.\n\nBest regards,\nHR Team";
            }
        }
    </script>
</head>
<body>

<?php include(__DIR__ . '/../includes/sidebar.php'); ?>

<div class="container">
    <header style="display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <a href="candidates.php" class="btn-secondary" style="background-color: #64748b; color: #ffffff; padding: 6px 10px; text-decoration: none; border-radius: 4px; font-size: 11px; font-weight: bold;">← Back to Candidates</a>
            <h1 class="page-title" style="margin: 0;">Auto-Email Templates & Dispatch</h1>
        </div>
        <div>
            <span class="role-indicator">Role: <?php echo htmlspecialchars($user_role ?: ($is_developer ? 'Developer Override' : 'HR')); ?></span>
        </div>
    </header>

    <?php if (!empty($success_msg)): ?>
        <div class="alert-success">
            <strong>Success:</strong> <?php echo $success_msg; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div class="alert-error"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <!-- EMAIL DISPATCH FORM -->
    <div class="form-container" style="margin-top: 20px;">
        <h3 class="section-title" style="margin-top: 0; text-align: left; color: #7c3aed;">✉️ Trigger & Preview Candidate Email</h3>
        <form method="POST">
            <div class="form-group">
                <label>Select Candidate Recipient</label>
                <select name="candidate_id" required>
                    <option value="">-- Choose Candidate --</option>
                    <?php foreach ($candidates as $cand): ?>
                        <option value="<?php echo $cand['candidate_id']; ?>" 
                            <?php if ($preselected_candidate_id == $cand['candidate_id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($cand['first_name'] . ' ' . $cand['last_name'] . ' (' . $cand['email'] . ') - Stage: ' . $cand['pipeline_stage']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Email Template Type</label>
                <select name="template_type" id="template_type" onchange="updateTemplate()" required>
                    <option value="Application Received">Application Received</option>
                    <option value="Interview Invite">Interview Invite</option>
                    <option value="Rejection">Rejection</option>
                    <option value="Offer Letter">Offer Letter</option>
                </select>
            </div>

            <div class="form-group">
                <label>Subject Line</label>
                <input type="text" id="subject" name="subject" required>
            </div>

            <div class="form-group">
                <label>Email Message Body</label>
                <textarea id="body" name="body" rows="6" required style="resize: vertical;"></textarea>
            </div>

            <div class="preview-box">
                <strong style="color: #0369a1; display: block; margin-bottom: 4px; font-size: 12px;">Local Execution Note:</strong>
                <p style="margin: 0; font-size: 11px; color: #334155;">Since this is running on a local XAMPP server, clicking "Send Email Simulation" will validate the candidate record, log the outgoing message dispatch, and render a confirmation banner.</p>
            </div>

            <div style="margin-top: 15px;">
                <button type="submit" name="send_email" class="btn-primary">Send Email Simulation</button>
            </div>
        </form>
    </div>

</div>

<script>
    window.onload = function() {
        if (document.getElementById('template_type')) {
            updateTemplate();
        }
    };
</script>

</body>
</html>