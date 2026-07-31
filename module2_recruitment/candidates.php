<?php
require_once '../config/db.php';

// Handle candidate creation / application upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_candidate'])) {
    $requisition_id = $_POST['requisition_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $source = $_POST['source'];

    // Handle CV file upload
    $cv_file_path = '';
    if (isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['cv_file']['tmp_name'];
        $file_name = $_FILES['cv_file']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if ($file_ext === 'pdf') {
            $upload_dir = '../../uploads/cvs/';
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
        $stmt = $pdo->prepare("INSERT INTO candidates (requisition_id, first_name, last_name, email, phone_number, source, cv_file_path, pipeline_stage) VALUES (?, ?, ?, ?, ?, ?, ?, 'New Application')");
        $stmt->execute([$requisition_id, $first_name, $last_name, $email, $phone_number, $source, $cv_file_path]);
        header("Location: candidates.php");
        exit();
    } catch (PDOException $e) {
        $error = "Error adding candidate: " . $e->getMessage();
    }
}

// Handle candidate pipeline stage update
if (isset($_POST['update_stage'])) {
    $candidate_id = $_POST['candidate_id'];
    $new_stage = $_POST['pipeline_stage'];

    try {
        $stmt = $pdo->prepare("UPDATE candidates SET pipeline_stage = ? WHERE candidate_id = ?");
        $stmt->execute([$new_stage, $candidate_id]);
        
        // Auto-push data to Module 3 (Onboarding) if candidate is marked as Hired
        if ($new_stage === 'Hired') {
            $cand_stmt = $pdo->prepare("SELECT * FROM candidates WHERE candidate_id = ?");
            $cand_stmt->execute([$candidate_id]);
            $cand_data = $cand_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($cand_data) {
                $req_stmt = $pdo->prepare("SELECT job_title FROM job_requisitions WHERE requisition_id = ?");
                $req_stmt->execute([$cand_data['requisition_id']]);
                $req_data = $req_stmt->fetch(PDO::FETCH_ASSOC);
                $job_title = $req_data['job_title'] ?? 'Employee';

                // Automatically insert into Module 3 employees table
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

        header("Location: candidates.php");
        exit();
    } catch (PDOException $e) {
        $error = "Error updating stage: " . $e->getMessage();
    }
}

// Fetch open/approved job requisitions for the dropdown
try {
    $req_stmt = $pdo->query("SELECT requisition_id, job_title, department FROM job_requisitions WHERE status IN ('Open', 'Approved') ORDER BY job_title ASC");
    $requisitions = $req_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $requisitions = [];
}

// Fetch all candidates with their associated job title
try {
    $stmt = $pdo->query("SELECT c.*, r.job_title FROM candidates c LEFT JOIN job_requisitions r ON c.requisition_id = r.requisition_id ORDER BY c.candidate_id DESC");
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching candidates: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Management - HRMS</title>
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
            margin-top: 25px;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            margin-bottom: 25px;
            font-size: 12px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #e0f2fe;
        }
        th {
            background-color: #f0f9ff;
            color: #0369a1;
            font-weight: bold;
        }
        tr:hover {
            background-color: #f8fafc;
        }
        .form-container {
            background: #ffffff;
            padding: 20px;
            border-radius: 6px;
            border: 1px solid #bae6fd;
            box-shadow: 0 2px 6px rgba(2, 132, 199, 0.03);
            margin-top: 15px;
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
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 7px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 12px;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: #0284c7;
            outline: none;
            box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.1);
        }
        .badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
        }
        .badge-new-application { background-color: #0ea5e9; color: white; }
        .badge-screened { background-color: #f59e0b; color: white; }
        .badge-shortlisted { background-color: #f97316; color: white; }
        .badge-interviewed { background-color: #6366f1; color: white; }
        .badge-offered { background-color: #14b8a6; color: white; }
        .badge-hired { background-color: #22c55e; color: white; }
        .badge-rejected { background-color: #ef4444; color: white; }
    </style>
</head>
<body>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>

<div class="container">
    <header>
        <a href="index.php" class="btn-secondary" style="background-color: #64748b; color: #ffffff; padding: 6px 10px; text-decoration: none; border-radius: 4px; font-size: 11px; font-weight: bold;">← Back</a>
        <h1 class="page-title">Candidate Management</h1>
        <div style="width: 50px;"></div>
    </header>

    <?php if (isset($error)): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 8px; border-radius: 4px; margin-bottom: 12px; font-size: 12px;"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Existing Candidates Table on Top -->
    <h2 class="section-title">Candidate Pipeline</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Candidate Name</th>
                <th>Position</th>
                <th>Source</th>
                <th>CV</th>
                <th>Stage</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($candidates) > 0): ?>
                <?php foreach ($candidates as $cand): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($cand['candidate_id']); ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($cand['first_name'] . ' ' . $cand['last_name']); ?></strong><br>
                            <small style="color: #64748b; font-size: 10px;"><?php echo htmlspecialchars($cand['email']); ?> | <?php echo htmlspecialchars($cand['phone_number']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($cand['job_title'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($cand['source']); ?></td>
                        <td>
                            <?php if (!empty($cand['cv_file_path'])): ?>
                                <a href="../../<?php echo htmlspecialchars($cand['cv_file_path']); ?>" target="_blank" style="color: #0284c7; text-decoration: none; font-weight: bold;">View CV</a>
                            <?php else: ?>
                                <span style="color: #94a3b8;">No CV</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $cand['pipeline_stage'])); ?>">
                                <?php echo htmlspecialchars($cand['pipeline_stage']); ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" style="display: flex; gap: 4px; align-items: center;">
                                <input type="hidden" name="candidate_id" value="<?php echo $cand['candidate_id']; ?>">
                                <select name="pipeline_stage" style="padding: 3px; font-size: 11px;">
                                    <option value="New Application" <?php if($cand['pipeline_stage']=='New Application') echo 'selected'; ?>>New Application</option>
                                    <option value="Screened" <?php if($cand['pipeline_stage']=='Screened') echo 'selected'; ?>>Screened</option>
                                    <option value="Shortlisted" <?php if($cand['pipeline_stage']=='Shortlisted') echo 'selected'; ?>>Shortlisted</option>
                                    <option value="Interviewed" <?php if($cand['pipeline_stage']=='Interviewed') echo 'selected'; ?>>Interviewed</option>
                                    <option value="Offered" <?php if($cand['pipeline_stage']=='Offered') echo 'selected'; ?>>Offered</option>
                                    <option value="Hired" <?php if($cand['pipeline_stage']=='Hired') echo 'selected'; ?>>Hired</option>
                                    <option value="Rejected" <?php if($cand['pipeline_stage']=='Rejected') echo 'selected'; ?>>Rejected</option>
                                </select>
                                <button type="submit" name="update_stage" class="btn-primary" style="padding: 3px 6px; font-size: 11px; background-color: #f97316;">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: #64748b;">No candidates found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Add Candidate Form at the Bottom -->
    <div class="form-container">
        <h2 class="section-title" style="margin-top: 0;">Add New Candidate Application</h2>
        <form method="POST" enctype="multipart/form-data">
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Job Requisition</label>
                    <select name="requisition_id" required>
                        <option value="">Select Requisition</option>
                        <?php foreach ($requisitions as $req): ?>
                            <option value="<?php echo $req['requisition_id']; ?>">
                                <?php echo htmlspecialchars($req['job_title'] . ' (' . $req['department'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" required>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" required>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone_number" required>
                </div>
                <div class="form-group">
                    <label>Source</label>
                    <input list="source_options" name="source" required>
                    <datalist id="source_options">
                        <option value="LinkedIn">
                        <option value="Indeed">
                        <option value="Referral">
                        <option value="Careers Page">
                        <option value="Agency">
                        <option value="Job Fair">
                        <option value="Internal Promotion">
                        <option value="Other">
                    </datalist>
                </div>
            </div>
            <div class="form-group">
                <label>CV / Resume (PDF)</label>
                <input type="file" name="cv_file" accept=".pdf" required style="background: #f8fafc; padding: 5px;">
            </div>
            <button type="submit" name="add_candidate" class="btn-primary">Save Candidate</button>
        </form>
    </div>
</div>

</body>
</html>