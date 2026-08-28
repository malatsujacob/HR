<?php
session_start();
require_once '../config/db.php';

$success_msg = '';
$error_msg = '';

// Handle interview scheduling (Admin authentication removed)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['schedule_interview'])) {
        $candidate_input = trim($_POST['candidate_id']);
        $interviewer_name = trim($_POST['interviewer_name']);
        $interview_date = $_POST['interview_date'];
        $interview_time = $_POST['interview_time'];
        $location_type = $_POST['location_type'];

        // Resolve candidate ID safely (auto-create candidate if typed manually and doesn't exist yet)
        $candidate_id = null;
        if (is_numeric($candidate_input)) {
            $candidate_id = $candidate_input;
            // Verify existence
            $check_stmt = $pdo->prepare("SELECT candidate_id FROM candidates WHERE candidate_id = ?");
            $check_stmt->execute([$candidate_id]);
            if (!$check_stmt->fetch()) {
                $candidate_id = null;
            }
        }

        if (!$candidate_id) {
            $name_parts = explode(' ', $candidate_input, 2);
            $first_name = $name_parts[0];
            $last_name = $name_parts[1] ?? '';

            $check_stmt = $pdo->prepare("SELECT candidate_id FROM candidates WHERE LOWER(first_name) = LOWER(?) AND LOWER(last_name) = LOWER(?)");
            $check_stmt->execute([$first_name, $last_name]);
            $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $candidate_id = $existing['candidate_id'];
            } else {
                $ins_cand = $pdo->prepare("INSERT INTO candidates (first_name, last_name, pipeline_stage) VALUES (?, ?, 'Interviewed')");
                $ins_cand->execute([$first_name, $last_name]);
                $candidate_id = $pdo->lastInsertId();
            }
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO interviews (candidate_id, interviewer_name, interview_date, interview_time, location_type, status) VALUES (?, ?, ?, ?, ?, 'Scheduled')");
            $stmt->execute([$candidate_id, $interviewer_name, $interview_date, $interview_time, $location_type]);
            
            // Update candidate stage to Interviewed
            $up_cand = $pdo->prepare("UPDATE candidates SET pipeline_stage = 'Interviewed' WHERE candidate_id = ?");
            $up_cand->execute([$candidate_id]);

            header("Location: interviews.php");
            exit();
        } catch (PDOException $e) {
            $error_msg = "Error scheduling interview: " . $e->getMessage();
        }
    }

    // Handle interview status update
    if (isset($_POST['update_interview_status'])) {
        $interview_id = $_POST['interview_id'];
        $new_status = $_POST['interview_status'];

        try {
            $stmt = $pdo->prepare("UPDATE interviews SET status = ? WHERE interview_id = ?");
            $stmt->execute([$new_status, $interview_id]);
            header("Location: interviews.php");
            exit();
        } catch (PDOException $e) {
            $error_msg = "Error updating status: " . $e->getMessage();
        }
    }
}

// Fetch candidates for dropdown / manual entry
try {
    $cand_stmt = $pdo->query("SELECT candidate_id, first_name, last_name FROM candidates ORDER BY first_name ASC");
    $candidates = $cand_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $candidates = [];
}

// Fetch scheduled interviews
try {
    $stmt = $pdo->query("SELECT i.*, c.first_name, c.last_name, c.email FROM interviews i JOIN candidates c ON i.candidate_id = c.candidate_id ORDER BY i.interview_date DESC, i.interview_time DESC");
    $interviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $interviews = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interview Management - HRMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/module2_recruitment.css">
    <style>
        .btn-purple { background: #7c3aed; color: white; padding: 6px 12px; border-radius: 4px; font-size: 11px; font-weight: bold; border: none; cursor: pointer; }
        .box-container { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 15px; }
        .alert-success { background: #dcfce7; color: #166534; padding: 8px; border-radius: 4px; margin-bottom: 12px; font-size: 12px; }
        .alert-error { background: #fee2e2; color: #991b1b; padding: 8px; border-radius: 4px; margin-bottom: 12px; font-size: 12px; }
    </style>
</head>
<body>

<?php include(__DIR__ . '/../includes/sidebar.php'); ?>

<div class="container">
    <header style="display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <a href="index.php" class="btn-secondary" style="background-color: #64748b; color: #ffffff; padding: 6px 10px; text-decoration: none; border-radius: 4px; font-size: 11px; font-weight: bold;">← Back</a>
            <h1 class="page-title" style="margin: 0;">Interview Management</h1>
        </div>
    </header>

    <?php if (!empty($success_msg)): ?>
        <div class="alert-success"><?php echo $success_msg; ?></div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div class="alert-error"><?php echo $error_msg; ?></div>
    <?php elseif (isset($error)): ?>
        <div class="alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Scheduled Interviews Table -->
    <h2 class="section-title">Scheduled Interviews</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Candidate</th>
                <th>Interviewer</th>
                <th>Date & Time</th>
                <th>Location / Mode</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($interviews) > 0): ?>
                <?php foreach ($interviews as $inv): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($inv['interview_id']); ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($inv['first_name'] . ' ' . $inv['last_name']); ?></strong><br>
                            <small style="color: #64748b; font-size: 10px;"><?php echo htmlspecialchars($inv['email']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($inv['interviewer_name']); ?></td>
                        <td><?php echo htmlspecialchars($inv['interview_date'] . ' ' . $inv['interview_time']); ?></td>
                        <td><?php echo htmlspecialchars($inv['location_type']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo strtolower($inv['status']); ?>">
                                <?php echo htmlspecialchars($inv['status']); ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" style="display: flex; gap: 4px; align-items: center;">
                                <input type="hidden" name="interview_id" value="<?php echo $inv['interview_id']; ?>">
                                <select name="interview_status" style="padding: 3px; font-size: 11px;">
                                    <option value="Scheduled" <?php if($inv['status']=='Scheduled') echo 'selected'; ?>>Scheduled</option>
                                    <option value="Completed" <?php if($inv['status']=='Completed') echo 'selected'; ?>>Completed</option>
                                    <option value="Cancelled" <?php if($inv['status']=='Cancelled') echo 'selected'; ?>>Cancelled</option>
                                </select>
                                <button type="submit" name="update_interview_status" class="btn-primary" style="padding: 3px 6px; font-size: 11px; background-color: #f97316;">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: #64748b;">No interviews scheduled yet.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 30px 0;">

    <!-- SCHEDULE NEW INTERVIEW FORM (Always Visible) -->
    <div class="form-container">
        <h2 class="section-title" style="margin-top: 0; color: #7c3aed;">➕ Schedule New Interview</h2>
        <form method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Candidate (Select or Type Name)</label>
                    <input list="candidate_options" type="text" name="candidate_id" required>
                    <datalist id="candidate_options">
                        <?php foreach ($candidates as $cand): ?>
                            <option value="<?php echo $cand['candidate_id']; ?>"><?php echo htmlspecialchars($cand['first_name'] . ' ' . $cand['last_name']); ?></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="form-group">
                    <label>Interviewer Name</label>
                    <input type="text" name="interviewer_name" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Interview Date</label>
                    <input type="date" name="interview_date" required>
                </div>
                <div class="form-group">
                    <label>Interview Time</label>
                    <input type="time" name="interview_time" required>
                </div>
                <div class="form-group">
                    <label>Location / Mode</label>
                    <select name="location_type" required>
                        <option value="Online / Zoom">Online / Zoom</option>
                        <option value="Office Boardroom">Office Boardroom</option>
                        <option value="Phone Call">Phone Call</option>
                    </select>
                </div>
            </div>

            <button type="submit" name="schedule_interview" class="btn-primary">Schedule Interview</button>
        </form>
    </div>

</div>

</body>
</html>