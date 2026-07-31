<?php
// Include the database connection
require_once '../config/db.php';

// Get the employee ID from the URL query string
$employee_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($employee_id <= 0) {
    header('Location: list.php');
    exit;
}

try {
    // Fetch employee details using employee_id
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE employee_id = ?");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch();

    if (!$employee) {
        die("Employee not found.");
    }

    // Fetch audit logs for this employee
    $stmt = $pdo->prepare("SELECT * FROM employee_audit_logs WHERE employee_id = :id");
    $stmt->execute(['id' => $employee_id]);
    $audit_logs = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = "Error loading profile: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Profile - <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .profile-section {
            background-color: var(--panel-black);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .profile-section h3 {
            color: var(--light-blue);
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 8px;
            margin-top: 0;
            font-size: 16px;
        }
        .info-row {
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
        }
        .info-label {
            color: var(--text-muted);
            font-weight: 600;
            font-size: 14px;
        }
        .info-value {
            color: var(--text-light);
            font-size: 14px;
            text-align: right;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            font-size: 13px;
        }
        th {
            color: var(--accent-blue);
            background-color: #161616;
        }
    </style>
</head>
<body>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>

<div class="container" style="max-width: 1000px; margin-left: 280px;">
    <header>
        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <a href="list.php" class="btn-secondary">&larr; Back to Directory</a>
            <h1 style="margin: 0;">Profile: <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></h1>
        </div>
    </header>

    <?php if (isset($error)): ?>
        <div class="alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="profile-grid">
        <!-- Personal Info -->
        <div class="profile-section">
            <h3>Personal Information</h3>
            <div class="info-row"><span class="info-label">Full Name:</span><span class="info-value"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></span></div>
            <div class="info-row"><span class="info-label">Date of Birth:</span><span class="info-value"><?php echo htmlspecialchars($employee['date_of_birth']); ?></span></div>
            <div class="info-row"><span class="info-label">Gender:</span><span class="info-value"><?php echo htmlspecialchars($employee['gender']); ?></span></div>
            <div class="info-row"><span class="info-label">Nationality:</span><span class="info-value"><?php echo htmlspecialchars($employee['nationality']); ?></span></div>
            <div class="info-row"><span class="info-label">Marital Status:</span><span class="info-value"><?php echo htmlspecialchars($employee['marital_status']); ?></span></div>
            <div class="info-row"><span class="info-label">Personal Email:</span><span class="info-value"><?php echo htmlspecialchars($employee['personal_email']); ?></span></div>
            <div class="info-row"><span class="info-label">Phone Number:</span><span class="info-value"><?php echo htmlspecialchars($employee['phone_number']); ?></span></div>
            <div class="info-row"><span class="info-label">Physical Address:</span><span class="info-value"><?php echo htmlspecialchars($employee['physical_address']); ?></span></div>
        </div>

        <!-- Professional Info -->
        <div class="profile-section">
            <h3>Professional Details</h3>
            <div class="info-row"><span class="info-label">Work Email:</span><span class="info-value"><?php echo htmlspecialchars($employee['work_email']); ?></span></div>
            <div class="info-row"><span class="info-label">Department:</span><span class="info-value"><?php echo htmlspecialchars($employee['department']); ?></span></div>
            <div class="info-row"><span class="info-label">Job Title:</span><span class="info-value"><?php echo htmlspecialchars($employee['job_title']); ?></span></div>
            <div class="info-row"><span class="info-label">Manager:</span><span class="info-value"><?php echo htmlspecialchars($employee['reporting_manager']); ?></span></div>
            <div class="info-row"><span class="info-label">Work Location:</span><span class="info-value"><?php echo htmlspecialchars($employee['work_location']); ?></span></div>
            <div class="info-row"><span class="info-label">Hire Date:</span><span class="info-value"><?php echo htmlspecialchars($employee['hire_date']); ?></span></div>
            <div class="info-row"><span class="info-label">Employment Type:</span><span class="info-value"><?php echo htmlspecialchars($employee['employment_type']); ?></span></div>
            <div class="info-row"><span class="info-label">Status:</span><span class="info-value"><?php echo htmlspecialchars($employee['status']); ?></span></div>
        </div>
    </div>

    <!-- Next of Kin -->
    <div class="profile-section">
        <h3>Next of Kin Details</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <div class="info-row"><span class="info-label">Name:</span><span class="info-value"><?php echo htmlspecialchars($employee['next_of_kin_name']); ?></span></div>
                <div class="info-row"><span class="info-label">Relationship:</span><span class="info-value"><?php echo htmlspecialchars($employee['next_of_kin_relationship']); ?></span></div>
            </div>
            <div>
                <div class="info-row"><span class="info-label">Phone:</span><span class="info-value"><?php echo htmlspecialchars($employee['next_of_kin_phone']); ?></span></div>
                <div class="info-row"><span class="info-label">Address:</span><span class="info-value"><?php echo htmlspecialchars($employee['next_of_kin_address']); ?></span></div>
            </div>
        </div>
    </div>

    <!-- Audit Trail -->
    <div class="profile-section">
        <h3>Audit Logs</h3>
        <table>
            <thead>
                <tr>
                    <th>Action Performed</th>
                    <th>Performed By</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($audit_logs)): ?>
                    <?php foreach ($audit_logs as $log): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($log['action_performed']); ?></td>
                            <td><?php echo htmlspecialchars($log['performed_by']); ?></td>
                            <td><?php echo htmlspecialchars($log['performed_at'] ?? $log['created_at'] ?? $log['action_date'] ?? 'N/A'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" style="text-align: center; color: var(--text-muted);">No audit history found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>