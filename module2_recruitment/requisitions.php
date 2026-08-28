<?php
// module2_recruitment/requisitions.php
session_start();
require_once '../config/db.php';

$success_msg = '';
$error_msg = '';

// 1. Ensure user is logged in via main system authentication
if (!isset($_SESSION['employee_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_role = $_SESSION['role'] ?? '';

// Define role permissions based on your system rules
$is_hr_or_exec = in_array($user_role, ['HR', 'Assistant HR', 'CEO', 'MD']);
$is_hod_or_above = in_array($user_role, ['HR', 'Assistant HR', 'CEO', 'MD', 'HOD']);

// Handle form actions via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Handle job requisition creation (Allowed for HOD, HR, Execs)
    if (isset($_POST['create_requisition'])) {
        if (!$is_hod_or_above) {
            $error_msg = "You are not authorized for this action.";
        } else {
            $job_title = trim($_POST['job_title'] ?? '');
            $department = trim($_POST['department'] ?? '');
            $urgency = strtolower(trim($_POST['urgency'] ?? 'medium'));
            $reason = trim($_POST['description'] ?? '');

            if (!empty($job_title) && !empty($department) && !empty($reason)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO job_requisitions (job_title, department, urgency, reason, status) VALUES (?, ?, ?, ?, 'Open')");
                    $stmt->execute([$job_title, $department, $urgency, $reason]);
                    
                    header("Location: requisitions.php?success=created");
                    exit();
                } catch (PDOException $e) {
                    $error_msg = "Error creating requisition: " . $e->getMessage();
                }
            } else {
                $error_msg = "Please fill in all required requisition fields.";
            }
        }
    }

    // Handle requisition status updates (Allowed ONLY for HR / Assistant HR / Execs)
    if (isset($_POST['update_status'])) {
        if (!$is_hr_or_exec) {
            $error_msg = "You are not authorized for this action.";
        } else {
            $requisition_id = $_POST['requisition_id'] ?? '';
            $new_status = $_POST['status'] ?? '';

            try {
                $stmt = $pdo->prepare("UPDATE job_requisitions SET status = ? WHERE requisition_id = ?");
                $stmt->execute([$new_status, $requisition_id]);
                header("Location: requisitions.php?success=updated");
                exit();
            } catch (PDOException $e) {
                $error_msg = "Error updating status: " . $e->getMessage();
            }
        }
    }
}

// Catch redirect success banners
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'created') $success_msg = "Job requisition submitted successfully!";
    if ($_GET['success'] === 'updated') $success_msg = "Requisition status updated successfully!";
}

// Fetch all job requisitions (Visible to HR, Assistant HR, Execs)
$requisitions = [];
if ($is_hr_or_exec) {
    try {
        $stmt = $pdo->query("SELECT * FROM job_requisitions ORDER BY requisition_id DESC");
        $requisitions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_msg = "Error fetching requisitions: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Requisitions - HRMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/module2_recruitment.css">
    <style>
        .btn-purple { background: #7c3aed; color: white; padding: 7px 12px; border-radius: 4px; font-size: 11px; font-weight: bold; border: none; cursor: pointer; }
        .btn-red { background: #dc2626; color: white; padding: 5px 10px; border-radius: 4px; font-size: 10px; font-weight: bold; text-decoration: none; border: none; cursor: pointer; }
        .panel-box { background: #f8fafc; padding: 14px; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 20px; }
        .alert-success { background: #dcfce7; color: #166534; padding: 8px; border-radius: 4px; margin-bottom: 12px; font-size: 12px; }
        .alert-error { background: #fee2e2; color: #991b1b; padding: 8px; border-radius: 4px; margin-bottom: 12px; font-size: 12px; }
    </style>
</head>
<body>

<?php include(__DIR__ . '/../includes/sidebar.php'); ?>

<div class="container">
    <header style="display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <a href="index.php" style="background-color: #64748b; color: #ffffff; padding: 6px 10px; text-decoration: none; border-radius: 4px; font-size: 11px; font-weight: bold;">← Back</a>
            <h1 class="page-title" style="margin: 0;">Job Requisitions</h1>
        </div>
        
        <div>
            <span style="font-size: 11px; color: #64748b; font-weight: bold; margin-right: 10px;">Role: <?php echo htmlspecialchars($user_role); ?></span>
            <a href="../logout.php" class="btn-red">Logout</a>
        </div>
    </header>

    <?php if (!empty($success_msg)): ?>
        <div class="alert-success" style="margin-top: 15px;"><?php echo $success_msg; ?></div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div class="alert-error" style="margin-top: 15px;"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <!------------------------------------------------------------------
     SECTION 1: CREATE NEW REQUISITION 
    ------------------------------------------------------------------->
    <?php if ($is_hod_or_above): ?>
        <div class="form-container" style="margin-bottom: 30px;">
            <h2 class="section-title" style="margin-top: 0; color: #7c3aed;">➕ Create New Job Requisition</h2>
            <form method="POST">
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label>Job Title</label>
                        <input list="job_title_options" type="text" name="job_title" placeholder="Job Title" required>
                        <datalist id="job_title_options">
                            <option value="Software Engineer">
                            <option value="Sales Executive">
                            <option value="HR Business Partner">
                            <option value="Customer Success Manager">
                            <option value="Data Analyst">
                            <option value="Product Manager">
                            <option value="Finance Analyst">
                        </datalist>
                    </div>
                    <div class="form-group">
                        <label>Department</label>
                        <input list="department_options" type="text" name="department" placeholder="Department" required>
                        <datalist id="department_options">
                            <option value="Sales">
                            <option value="Engineering">
                            <option value="Human Resources">
                            <option value="Marketing">
                            <option value="Operations">
                            <option value="Finance">
                            <option value="Customer Support">
                        </datalist>
                    </div>
                    <div class="form-group">
                        <label>Urgency</label>
                        <select name="urgency" required>
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Description / Reason</label>
                    <textarea name="description" rows="3" placeholder="Reason for requisition..." required></textarea>
                </div>
                <button type="submit" name="create_requisition" class="btn-primary">Submit Requisition</button>
            </form>
        </div>
    <?php else: ?>
        <div class="panel-box" style="border-left: 4px solid #dc2626;">
            <p style="margin: 0; font-size: 12px; color: #dc2626; font-weight: bold;">⚠️ You are not authorized for this file.</p>
        </div>
    <?php endif; ?>

    <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 30px 0;">

    <!------------------------------------------------------------------
     SECTION 2: EXISTING REQUISITIONS TABLE
    ------------------------------------------------------------------->
    <h2 class="section-title">Existing Requisitions</h2>

    <?php if (!$is_hr_or_exec): ?>
        <div class="panel-box" style="border-left: 4px solid #dc2626;">
            <p style="margin: 0; font-size: 12px; color: #dc2626; font-weight: bold;">⚠️ You are not authorized for this file.</p>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Job Title</th>
                    <th>Department</th>
                    <th>Urgency</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($requisitions) > 0): ?>
                    <?php foreach ($requisitions as $req): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($req['requisition_id']); ?></td>
                            <td><strong><?php echo htmlspecialchars($req['job_title']); ?></strong></td>
                            <td><?php echo htmlspecialchars($req['department']); ?></td>
                            <td><?php echo htmlspecialchars($req['urgency']); ?></td>
                            <td style="max-width: 250px; font-size: 12px; color: #334155;"><?php echo htmlspecialchars($req['reason']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo strtolower($req['status']); ?>">
                                    <?php echo htmlspecialchars($req['status']); ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" style="display: flex; gap: 4px; align-items: center;">
                                    <input type="hidden" name="requisition_id" value="<?php echo $req['requisition_id']; ?>">
                                    <select name="status" style="padding: 3px; font-size: 11px;">
                                        <option value="Open" <?php if($req['status']=='Open') echo 'selected'; ?>>Open</option>
                                        <option value="Approved" <?php if($req['status']=='Approved') echo 'selected'; ?>>Approved</option>
                                        <option value="Closed" <?php if($req['status']=='Closed') echo 'selected'; ?>>Closed</option>
                                    </select>
                                    <button type="submit" name="update_status" class="btn-primary" style="padding: 3px 6px; font-size: 11px; background-color: #f97316;">Update</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #64748b;">No requisitions found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>

</div>

</body>
</html>