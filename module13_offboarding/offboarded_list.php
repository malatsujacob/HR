<?php
require_once '../config/db.php';

$stmt = $pdo->query("SELECT * FROM offboarded_employees ORDER BY offboarded_at DESC");
$offboarded = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Offboarded Employees | HRMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .container{padding:20px}
        table{width:100%;border-collapse:collapse}
        th,td{padding:8px;border:1px solid #e2e8f0;text-align:left}
        .btn{padding:6px 10px;border-radius:4px;text-decoration:none;display:inline-block}
        .btn-restore{background:#16a34a;color:white}
    </style>
</head>
<body>
<?php include(__DIR__ . '/../includes/sidebar.php'); ?>
<div class="container">
    <h1>Offboarded Employees</h1>
    <p>List of employees moved to the offboarded archive. You can restore records back to active employees.</p>

    <?php if (count($offboarded) === 0): ?>
        <div>No offboarded records found.</div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Job Title</th>
                    <th>Offboarded At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($offboarded as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['offboard_id']); ?></td>
                    <td><?php echo htmlspecialchars(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')); ?></td>
                    <td><?php echo htmlspecialchars($row['work_email'] ?? $row['personal_email'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['department'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['job_title'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['offboarded_at'] ?? ''); ?></td>
                    <td>
                        <form method="POST" action="api.php?action=restore" style="display:inline">
                            <input type="hidden" name="offboard_id" value="<?php echo htmlspecialchars($row['offboard_id']); ?>">
                            <button type="submit" class="btn btn-restore">Restore</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
