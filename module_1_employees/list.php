<?php
require_once '../config/db.php';

// Fetch all employees from the database using employee_id
try {
    $stmt = $pdo->query("SELECT * FROM employees ORDER BY employee_id DESC");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching employees: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Directory - HR System</title>
    <!-- Link to the shared global stylesheet -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background-color: #f8fafc; color: #0f172a; margin: 0; font-family: Arial, sans-serif; }
        .container { margin-left: 260px; max-width: calc(100% - 280px); padding: 25px; box-sizing: border-box; background: #f8fafc; min-height: 100vh; }
        header { border-bottom: 2px solid #cbd5e1; padding-bottom: 15px; margin-bottom: 25px; }
        header h2 { font-size: 22px; font-weight: bold; margin: 0 0 15px 0; color: #0f172a; }
        .card { background: #ffffff; padding: 20px; border-radius: 6px; border: 1px solid #cbd5e1; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 12px; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background-color: #f1f5f9; color: #334155; font-weight: bold; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }
        tr:hover { background-color: #f8fafc; }
        .btn-primary { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #ffffff; padding: 7px 14px; border-radius: 4px; font-size: 12px; text-decoration: none; font-weight: bold; display: inline-block; }
        .btn-secondary { background: #e2e8f0; color: #334155; padding: 6px 12px; border-radius: 4px; font-size: 12px; text-decoration: none; font-weight: bold; border: 1px solid #cbd5e1; display: inline-block; }
        .actions a { margin-right: 8px; text-decoration: none; color: #0284c7; font-size: 12px; font-weight: bold; }
        .actions a:hover { text-decoration: underline; }
        .actions a.delete { color: #dc2626; }
        .actions a.delete:hover { color: #b91c1c; }
    </style>
</head>
<body>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>

<div class="container">
    <header>
        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <a href="index.php" class="btn-secondary">← Back to Employee Module</a>
            <div style="display: flex; gap: 10px; align-items: center;">
                <a href="import_export.php" class="btn-secondary">Import / Export</a>
                <a href="add.php" class="btn-primary">+ Add New Employee</a>
            </div>
        </div>
    </header>

    <div class="card">
        <h2>Employee Directory</h2>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Job Title</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($employees) > 0): ?>
                    <?php foreach ($employees as $emp): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($emp['employee_id']); ?></td>
                            <td><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($emp['work_email'] ?? $emp['email']); ?></td>
                            <td><?php echo htmlspecialchars($emp['department']); ?></td>
                            <td><?php echo htmlspecialchars($emp['job_title']); ?></td>
                            <td class="actions">
                                <a href="view.php?id=<?php echo $emp['employee_id']; ?>">View</a>
                                <a href="edit.php?id=<?php echo $emp['employee_id']; ?>">Edit</a>
                                <a href="delete.php?id=<?php echo $emp['employee_id']; ?>" class="delete" onclick="return confirm('Are you sure you want to delete this employee?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: #64748b; padding: 20px;">No employees found in the directory.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>