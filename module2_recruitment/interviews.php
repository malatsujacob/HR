<?php
require_once '../config/db.php';

// Handle interview scheduling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_interview'])) {
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
        $error = "Error scheduling interview: " . $e->getMessage();
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
        $error = "Error updating status: " . $e->getMessage();
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
        .badge-scheduled { background-color: #f59e0b; color: white; }
        .badge-completed { background-color: #22c55e; color: white; }
        .badge-cancelled { background-color: #ef4444; color: white; }
    </style>
</head>
<body>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>

<div class="container">
    <header>
        <a href="index.php" class="btn-secondary" style="background-color: #64748b; color: #ffffff; padding: 6px 10px; text-decoration: none; border-radius: 4px; font-size: 11px; font-weight: bold;">← Back</a>
        <h1 class="page-title">Interview Management</h1>
        <div style="width: 50px;"></div>
    </header>

    <?php if (isset($error)): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 8px; border-radius: 4px; margin-bottom: 12px; font-size: 12px;"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Scheduled Interviews Table on Top -->
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

    <!-- Schedule New Interview Form at the Bottom -->
    <div class="form-container">
        <h2 class="section-title" style="margin-top: 0;">Schedule New Interview</h2>
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