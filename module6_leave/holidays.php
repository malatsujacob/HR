<?php
require_once '../config/db.php';
require_once 'leave_model.php';

$leaveModel = new LeaveModel($pdo);
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_holiday'])) {
    $result = $leaveModel->addPublicHoliday(trim($_POST['holiday_name']), $_POST['holiday_date'], trim($_POST['description']));
    if ($result) {
        $msg = "Added successfully.";
    } else {
        $msg = "Error adding holiday.";
    }
}

$holidays = $leaveModel->getPublicHolidays();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Public Holidays - HR System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background-color: #f8fafc; color: #0f172a; margin: 0; font-family: Arial, sans-serif; }
        .container { margin-left: 260px; max-width: calc(100% - 280px); padding: 20px; box-sizing: border-box; background: #f8fafc; min-height: 100vh; }
        header { border-bottom: 2px solid #cbd5e1; padding-bottom: 12px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        header h1 { font-size: 18px; font-weight: 900; margin: 0; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; }
        .card { background: #ffffff; padding: 15px; border-radius: 6px; border: 1px solid #cbd5e1; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 11px; }
        th, td { padding: 8px 10px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background-color: #f1f5f9; color: #334155; font-weight: bold; text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px; }
        tr:hover { background-color: #f8fafc; }
        .btn-primary { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #ffffff; padding: 5px 10px; border-radius: 4px; font-size: 11px; text-decoration: none; font-weight: 900; display: inline-block; border: none; cursor: pointer; text-transform: uppercase; letter-spacing: 0.5px; }
        .btn-secondary { background: #e2e8f0; color: #334155; padding: 5px 10px; border-radius: 4px; font-size: 11px; text-decoration: none; font-weight: 900; border: 1px solid #cbd5e1; display: inline-block; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-size: 11px; font-weight: bold; margin-bottom: 4px; color: #334155; text-transform: uppercase; }
        input[type="text"], input[type="date"], textarea { padding: 6px 8px; font-size: 11px; border: 1px solid #cbd5e1; border-radius: 4px; width: 100%; box-sizing: border-box; background: #ffffff; color: #0f172a; }
        textarea { resize: vertical; min-height: 60px; }
        .alert-success { background: #dcfce7; color: #166534; padding: 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #bbf7d0; font-size: 11px; }
    </style>
</head>
<body>

<?php 
include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php');
?>

<div class="container">
    <header>
        <h1>Public Holidays</h1>
        <a href="leave.php" class="btn-secondary">&larr; Back</a>
    </header>

    <?php if (!empty($msg)): ?>
        <div class="alert-success"><?php echo $msg; ?></div>
    <?php endif; ?>

    <div class="card">
        <h3 style="margin-top: 0; font-size: 13px; color: #0f172a; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px;">Holidays</h3>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Date</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($holidays) > 0): ?>
                    <?php foreach ($holidays as $h): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($h['holiday_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($h['holiday_date']); ?></td>
                            <td><?php echo htmlspecialchars($h['description']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" style="text-align: center; color: #64748b; padding: 15px;">No holidays.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3 style="margin-top: 0; font-size: 13px; color: #0f172a; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px;">Add Holiday</h3>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="holiday_name" required>
                </div>
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="holiday_date" required>
                </div>
            </div>
            <div class="form-group" style="margin-top: 12px;">
                <label>Description</label>
                <textarea name="description"></textarea>
            </div>
            <button type="submit" name="add_holiday" class="btn-primary" style="margin-top: 12px;">Save</button>
        </form>
    </div>
</div>

</body>
</html>