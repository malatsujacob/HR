<?php
session_start();
require_once '../config/db.php';

$success_msg = '';
$error_msg = '';

// 1. Ensure user is logged in
if (!isset($_SESSION['employee_id']) && !isset($_SESSION['user_id'])) {
    header("Location: ../index.php?error=unauthorized");
    exit();
}

$user_role = $_SESSION['role'] ?? '';

// 2. Strict HR & Assistant HR check + Developer Override
$is_strict_hr = in_array($user_role, ['HR', 'Assistant HR']);
$is_developer = (isset($_SESSION['is_developer']) && $_SESSION['is_developer'] === true) || ($user_role === 'Developer');

if (!$is_strict_hr && !$is_developer) {
    header("Location: ../index.php?error=access_denied");
    exit();
}

// Ensure database tables exist (keeping structural checks if needed)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS app_settings (
            setting_key VARCHAR(50) PRIMARY KEY,
            setting_value VARCHAR(50) NOT NULL
        )
    ");
    $pdo->exec("
        INSERT INTO app_settings (setting_key, setting_value)
        SELECT 'applications_open', '1'
        WHERE NOT EXISTS (SELECT 1 FROM app_settings WHERE setting_key = 'applications_open')
    ");

    // Ensure cover_letter column exists in candidates table
    $pdo->exec("ALTER TABLE candidates ADD COLUMN IF NOT EXISTS cover_letter TEXT");
} catch (Exception $e) {}

// Handle Application Portal Toggle Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_portal'])) {
    $new_status = $_POST['current_status'] == '1' ? '0' : '1';
    try {
        $stmt = $pdo->prepare("UPDATE app_settings SET setting_value = ? WHERE setting_key = 'applications_open'");
        $stmt->execute([$new_status]);
        $success_msg = $new_status == '1' ? "Application portal has been opened to the public." : "Application portal has been closed.";
    } catch (PDOException $e) {
        $error_msg = "Error updating portal status: " . $e->getMessage();
    }
}

// Fetch current application portal status
$applications_are_open = true;
try {
    $status_stmt = $pdo->query("SELECT setting_value FROM app_settings WHERE setting_key = 'applications_open'");
    $res = $status_stmt->fetch(PDO::FETCH_COLUMN);
    if ($res !== false) {
        $applications_are_open = ($res == '1');
    }
} catch (Exception $e) {}

// Handle Pipeline Stage Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stage'])) {
    $candidate_id = $_POST['candidate_id'];
    $new_stage = $_POST['pipeline_stage'];

    try {
        $stmt = $pdo->prepare("UPDATE candidates SET pipeline_stage = ? WHERE candidate_id = ?");
        $stmt->execute([$new_stage, $candidate_id]);
        
        if ($new_stage === 'Hired') {
            $cand_stmt = $pdo->prepare("SELECT * FROM candidates WHERE candidate_id = ?");
            $cand_stmt->execute([$candidate_id]);
            $cand_data = $cand_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($cand_data) {
                $job_title = 'Employee';
                if (!empty($cand_data['requisition_id'])) {
                    $req_stmt = $pdo->prepare("SELECT job_title FROM job_requisitions WHERE requisition_id = ?");
                    $req_stmt->execute([$cand_data['requisition_id']]);
                    $req_data = $req_stmt->fetch(PDO::FETCH_ASSOC);
                    $job_title = $req_data['job_title'] ?? 'Employee';
                }

                $emp_stmt = $pdo->prepare("INSERT INTO employees (first_name, last_name, email, phone_number, position, employment_status) VALUES (?, ?, ?, ?, ?, 'Active')");
                $emp_stmt->execute([
                    $cand_data['first_name'],
                    $cand_data['last_name'],
                    $cand_data['email'],
                    $cand_data['phone_number'],
                    $job_title
                ]);
            }
        }

        header("Location: /hr/module2_recruitment/candidates.php");
        exit();
    } catch (PDOException $e) {
        $error_msg = "Error updating stage: " . $e->getMessage();
    }
}

// Fetch candidates
$candidates = [];
try {
    $stmt = $pdo->query("SELECT c.*, r.job_title FROM candidates c LEFT JOIN job_requisitions r ON c.requisition_id = r.requisition_id ORDER BY c.candidate_id DESC");
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_msg = "Error fetching candidates: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Pipeline</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/module2_recruitment.css">
    <style>
        body { background-color: #f1f5f9; color: #1e293b; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .page-wrapper { max-width: 1350px; margin: 0 auto; padding: 30px 20px; }
        @media (min-width: 768px) { .page-wrapper { margin-left: 260px; } }
        .app-header { display: flex; justify-content: space-between; align-items: center; background: #ffffff; padding: 20px 25px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        .card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .card-title { font-size: 18px; font-weight: 600; color: #0f172a; margin-top: 0; margin-bottom: 20px; }
        .btn-custom-primary { background: #7c3aed; color: white; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; border: none; cursor: pointer; }
        .btn-custom-primary:hover { background: #6d28d9; }
        .table-responsive { width: 100%; overflow-x: auto; }
        .styled-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
        .styled-table th { background-color: #f8fafc; color: #475569; font-weight: 600; padding: 14px 16px; border-bottom: 2px solid #e2e8f0; }
        .styled-table td { padding: 14px 16px; border-bottom: 1px solid #e2e8f0; color: #334155; vertical-align: middle; }
        .link-cv { color: #0284c7; text-decoration: none; font-weight: 600; font-size: 13px; }
        .link-cv:hover { text-decoration: underline; }
        .btn-email-cand { background: #2563eb; color: white; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; text-decoration: none; display: inline-block; }
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .badge-new-application { background: #e0f2fe; color: #0369a1; }
        .badge-screened { background: #fef9c3; color: #854d0e; }
        .badge-shortlisted { background: #f3e8ff; color: #6b21a8; }
        .badge-interviewed { background: #ffedd5; color: #9a3412; }
        .badge-offered { background: #dcfce7; color: #166534; }
        .badge-hired { background: #d1fae5; color: #065f46; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }
        .alert-success { background: #dcfce7; color: #166534; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
        .alert-error { background: #fee2e2; color: #991b1b; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
    </style>
</head>
<body>

<?php include(__DIR__ . '/../includes/sidebar.php'); ?>

<div class="page-wrapper">
    <header class="app-header">
        <div style="display: flex; align-items: center; gap: 15px;">
            <a href="index.php" style="background-color: #64748b; color: #ffffff; padding: 8px 14px; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: 600;">← Back</a>
            <h1 style="margin: 0; font-size: 22px; font-weight: 700; color: #0f172a;">Candidate Pipeline Management</h1>
        </div>
        
        <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
            <span style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Role: <?php echo htmlspecialchars($user_role ?: 'Developer Override'); ?></span>
            
            <!-- Portal Toggle Form Button -->
            <form method="POST" style="margin: 0;">
                <input type="hidden" name="current_status" value="<?php echo $applications_are_open ? '1' : '0'; ?>">
                <button type="submit" name="toggle_portal" style="background: <?php echo $applications_are_open ? '#10b981' : '#ef4444'; ?>; color: white; padding: 8px 14px; border-radius: 6px; font-size: 13px; font-weight: 600; border: none; cursor: pointer;">
                    <?php echo $applications_are_open ? '🟢 Portal Open (Click to Close)' : '🔴 Portal Closed (Click to Open)'; ?>
                </button>
            </form>

            <a href="apply.php" target="_blank" style="background: #0ea5e9; color: white; padding: 8px 14px; border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration: none;">👁️ Preview Public Form</a>
        </div>
    </header>

    <?php if (!empty($success_msg)): ?>
        <div class="alert-success"><strong>Success:</strong> <?php echo $success_msg; ?></div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div class="alert-error"><strong>Error:</strong> <?php echo $error_msg; ?></div>
    <?php endif; ?>

    <!-- HR Pipeline View Section -->
    <div class="card">
        <h2 class="card-title">📋 Candidate Pipeline Records</h2>
        
        <div class="table-responsive">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Position</th>
                        <th>Source</th>
                        <th>Cover Letter</th>
                        <th>View CV</th>
                        <th>Email Candidate</th>
                        <th>Stage</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($candidates) > 0): ?>
                        <?php foreach ($candidates as $cand): ?>
                            <tr>
                                <td><strong>#<?php echo htmlspecialchars($cand['candidate_id']); ?></strong></td>
                                <td><strong style="color: #0f172a;"><?php echo htmlspecialchars($cand['first_name'] . ' ' . $cand['last_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($cand['email'] ?: 'No Email'); ?></td>
                                <td><?php echo htmlspecialchars($cand['phone_number'] ?: 'No Phone'); ?></td>
                                <td><?php echo htmlspecialchars($cand['job_title'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($cand['source']); ?></td>
                                <td>
                                    <?php if (!empty($cand['cover_letter'])): ?>
                                        <button type="button" onclick="alert('Cover Letter for <?php echo htmlspecialchars(addslashes($cand['first_name'] . ' ' . $cand['last_name'])); ?>:\n\n<?php echo htmlspecialchars(addslashes($cand['cover_letter'])); ?>');" style="background: #f3e8ff; color: #6b21a8; border: none; padding: 5px 10px; border-radius: 4px; font-weight: 600; cursor: pointer; font-size: 12px;">💬 View Note</button>
                                    <?php else: ?>
                                        <span style="color: #94a3b8; font-style: italic;">No Note</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($cand['cv_file_path'])): ?>
                                        <a href="../<?php echo htmlspecialchars($cand['cv_file_path']); ?>" target="_blank" class="link-cv">📄 View CV</a>
                                    <?php else: ?>
                                        <span style="color: #94a3b8; font-style: italic;">No CV</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($cand['email'])): ?>
                                        <a href="emails.php?candidate_id=<?php echo $cand['candidate_id']; ?>" class="btn-email-cand">✉️ Email</a>
                                    <?php else: ?>
                                        <span style="color: #94a3b8; font-style: italic;">No Email</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $cand['pipeline_stage'])); ?>">
                                        <?php echo htmlspecialchars($cand['pipeline_stage']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display: flex; gap: 6px; align-items: center; margin: 0;">
                                        <input type="hidden" name="candidate_id" value="<?php echo $cand['candidate_id']; ?>">
                                        <select name="pipeline_stage" style="padding: 6px 8px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 4px;">
                                            <option value="New Application" <?php if($cand['pipeline_stage']=='New Application') echo 'selected'; ?>>New</option>
                                            <option value="Screened" <?php if($cand['pipeline_stage']=='Screened') echo 'selected'; ?>>Screened</option>
                                            <option value="Shortlisted" <?php if($cand['pipeline_stage']=='Shortlisted') echo 'selected'; ?>>Shortlisted</option>
                                            <option value="Interviewed" <?php if($cand['pipeline_stage']=='Interviewed') echo 'selected'; ?>>Interviewed</option>
                                            <option value="Offered" <?php if($cand['pipeline_stage']=='Offered') echo 'selected'; ?>>Offered</option>
                                            <option value="Hired" <?php if($cand['pipeline_stage']=='Hired') echo 'selected'; ?>>Hired</option>
                                            <option value="Rejected" <?php if($cand['pipeline_stage']=='Rejected') echo 'selected'; ?>>Rejected</option>
                                        </select>
                                        <button type="submit" name="update_stage" class="btn-custom-primary" style="padding: 6px 10px; font-size: 12px; background-color: #f97316;">Save</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" style="text-align: center; color: #64748b; padding: 30px; font-style: italic;">No candidates found in the database.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>