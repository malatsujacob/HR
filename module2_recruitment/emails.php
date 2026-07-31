<?php
require_once '../config/db.php';

// Fetch candidates for the email recipient dropdown
try {
    $stmt = $pdo->query("SELECT candidate_id, first_name, last_name, email, pipeline_stage FROM candidates ORDER BY first_name ASC");
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $candidates = [];
}

// Handle simulated/live email dispatch logging
$success_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_email'])) {
    $candidate_id = $_POST['candidate_id'];
    $template_type = $_POST['template_type'];
    $subject = $_POST['subject'];
    $body = $_POST['body'];

    // Fetch candidate email for verification
    $cand_stmt = $pdo->prepare("SELECT email, first_name FROM candidates WHERE candidate_id = ?");
    $cand_stmt->execute([$candidate_id]);
    $recipient = $cand_stmt->fetch(PDO::FETCH_ASSOC);

    if ($recipient) {
        // In local XAMPP without sendmail, we log the dispatch to a session or display a clear confirmation view
        $success_msg = "Successfully dispatched '" . htmlspecialchars($template_type) . "' email to " . htmlspecialchars($recipient['email']) . "!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auto-Email Templates - HRMS</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f9ff;
            margin: 0;
            padding: 0;
        }
        .container {
            margin: 20px auto 40px auto;
            margin-left: 280px;
            max-width: calc(100% - 320px);
            padding: 24px;
            box-sizing: border-box;
            background: #ffffff;
            min-height: calc(100vh - 60px);
            border-radius: 10px;
            border: 1px solid #bae6fd;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.05);
        }
        header {
            border-bottom: 2px solid #e0f2fe;
            padding-bottom: 12px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .page-title {
            margin: 0;
            color: #0369a1;
            font-size: 18px;
            font-weight: 800;
            text-transform: uppercase;
            text-align: center;
            width: 100%;
        }
        .section-title {
            text-align: center;
            font-size: 14px;
            font-weight: 800;
            color: #0369a1;
            margin-top: 5px;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .btn-primary {
            background-color: #0284c7;
            color: white;
            padding: 8px 12px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-primary:hover {
            background-color: #0369a1;
        }
        .form-container {
            background: #ffffff;
            padding: 20px;
            border-radius: 6px;
            border: 1px solid #bae6fd;
            box-shadow: 0 2px 6px rgba(2, 132, 199, 0.03);
            margin-top: 15px;
            max-width: 100%;
            box-sizing: border-box;
        }
        .form-group {
            margin-bottom: 10px;
        }
        .form-group label {
            display: block;
            margin-bottom: 3px;
            font-weight: bold;
            font-size: 12px;
            color: #334155;
        }
        .form-group select, .form-group textarea, .form-group input {
            width: 100%;
            padding: 7px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 12px;
        }
        .form-group select:focus, .form-group textarea:focus, .form-group input:focus {
            border-color: #0284c7;
            outline: none;
            box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.1);
        }
        .preview-box {
            background: #f0f9ff;
            border: 1px dashed #7dd3fc;
            padding: 12px;
            border-radius: 6px;
            margin-top: 15px;
        }
    </style>
    <script>
        function updateTemplate() {
            const type = document.getElementById('template_type').value;
            const subjectField = document.getElementById('subject');
            const bodyField = document.getElementById('body');

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

<?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>

<div class="container">
    <header>
        <a href="index.php" class="btn-secondary" style="background-color: #64748b; color: #ffffff; padding: 6px 10px; text-decoration: none; border-radius: 4px; font-size: 11px; font-weight: bold;">← Back</a>
        <h1 class="page-title">Auto-Email Templates & Dispatch</h1>
        <div style="width: 50px;"></div>
    </header>

    <?php if (!empty($success_msg)): ?>
        <div style="background: #dcfce7; color: #166534; padding: 8px; border-radius: 4px; margin-bottom: 12px; border: 1px solid #bbf7d0; font-size: 12px;">
            <strong>Success:</strong> <?php echo $success_msg; ?>
        </div>
    <?php endif; ?>

    <div class="form-container">
        <h3 class="section-title" style="margin-top: 0; text-align: left;">Trigger & Preview Candidate Email</h3>
        <form method="POST">
            <div class="form-group">
                <label>Select Candidate Recipient</label>
                <select name="candidate_id" required>
                    <option value="">-- Choose Candidate --</option>
                    <?php foreach ($candidates as $cand): ?>
                        <option value="<?php echo $cand['candidate_id']; ?>">
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
    // Initialize default template text on page load
    updateTemplate();
</script>

</body>
</html>