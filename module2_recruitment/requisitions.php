<?php
require_once '../config/db.php';

// Handle job requisition creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_requisition'])) {
    $job_title = $_POST['job_title'];
    $department = $_POST['department'];
    $urgency = strtolower($_POST['urgency']); // Converted to lowercase to match PostgreSQL ENUM
    $reason = $_POST['description']; // Mapped to the database 'reason' column

    try {
        $stmt = $pdo->prepare("INSERT INTO job_requisitions (job_title, department, urgency, reason, status) VALUES (?, ?, ?, ?, 'Open')");
        $stmt->execute([$job_title, $department, $urgency, $reason]);
        header("Location: requisitions.php");
        exit();
    } catch (PDOException $e) {
        $error = "Error creating requisition: " . $e->getMessage();
    }
}

// Handle requisition status updates or deletion
if (isset($_POST['update_status'])) {
    $requisition_id = $_POST['requisition_id'];
    $new_status = $_POST['status'];

    try {
        $stmt = $pdo->prepare("UPDATE job_requisitions SET status = ? WHERE requisition_id = ?");
        $stmt->execute([$new_status, $requisition_id]);
        header("Location: requisitions.php");
        exit();
    } catch (PDOException $e) {
        $error = "Error updating status: " . $e->getMessage();
    }
}

// Fetch all job requisitions
try {
    $stmt = $pdo->query("SELECT * FROM job_requisitions ORDER BY requisition_id DESC");
    $requisitions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching requisitions: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Requisitions - HRMS</title>
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
        .badge-open { background-color: #22c55e; color: white; }
        .badge-approved { background-color: #0ea5e9; color: white; }
        .badge-closed { background-color: #64748b; color: white; }
    </style>
</head>
<body>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>

<div class="container">
    <header>
        <a href="index.php" class="btn-secondary" style="background-color: #64748b; color: #ffffff; padding: 6px 10px; text-decoration: none; border-radius: 4px; font-size: 11px; font-weight: bold;">← Back</a>
        <h1 class="page-title">Job Requisitions</h1>
        <div style="width: 50px;"></div>
    </header>

    <?php if (isset($error)): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 8px; border-radius: 4px; margin-bottom: 12px; font-size: 12px;"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Existing Requisitions on Top -->
    <h2 class="section-title">Existing Requisitions</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Job Title</th>
                <th>Department</th>
                <th>Urgency</th>
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
                    <td colspan="6" style="text-align: center; color: #64748b;">No requisitions found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Create New Requisition Form at the Bottom -->
    <div class="form-container">
        <h2 class="section-title" style="margin-top: 0;">Create New Job Requisition</h2>
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
                <label>Description</label>
                <textarea name="description" rows="3" placeholder="Description..." required></textarea>
            </div>
            <button type="submit" name="create_requisition" class="btn-primary">Submit Requisition</button>
        </form>
    </div>
</div>

</body>
</html>