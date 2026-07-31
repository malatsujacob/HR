<?php
require_once '../config/db.php';

// Handle standalone template download request
if (isset($_GET['download_template'])) {
    $candidate_name = isset($_GET['cand_name']) && trim($_GET['cand_name']) !== '' ? trim($_GET['cand_name']) : '[Candidate Name]';
    $position_title = isset($_GET['pos_title']) && trim($_GET['pos_title']) !== '' ? trim($_GET['pos_title']) : '[Job Title]';
    $salary_offered = isset($_GET['salary']) && trim($_GET['salary']) !== '' ? number_format((float)$_GET['salary'], 2) : '[Salary Amount]';
    $start_date = isset($_GET['start_dt']) && trim($_GET['start_dt']) !== '' ? trim($_GET['start_dt']) : '[Start Date]';

    if (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Description: File Transfer');
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="Chap_Chap_Africa_Offer_Letter.html"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    echo "<!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Offer Letter - Chap Chap Africa</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; padding: 40px; max-width: 800px; margin: auto; }
            .header { text-align: center; border-bottom: 2px solid #0056b3; padding-bottom: 20px; margin-bottom: 30px; }
            .company-name { font-size: 24px; font-weight: bold; color: #0056b3; }
            .company-name span { color: #dc3545; }
        </style>
    </head>
    <body>
        <div class='header'>
            <div class='company-name'><span style='color: #2563eb;'>CHAP CHAP</span> <span style='color: #dc2626;'>AFRICA</span></div>
            <p>Official Employment Offer Letter</p>
        </div>
        <p>Date: " . date('F j, Y') . "</p>
        <p>Dear <strong>" . htmlspecialchars($candidate_name) . "</strong>,</p>
        <p>We are thrilled to offer you the position of <strong>" . htmlspecialchars($position_title) . "</strong> at Chap Chap Africa. We were deeply impressed by your skills and background, and we are confident you will make a fantastic addition to our team.</p>
        <p><strong>Key Terms of Employment:</strong></p>
        <ul>
            <li><strong>Position:</strong> " . htmlspecialchars($position_title) . "</li>
            <li><strong>Proposed Salary:</strong> UGX " . $salary_offered . " per annum</li>
            <li><strong>Expected Start Date:</strong> " . htmlspecialchars($start_date) . "</li>
        </ul>
        <p>Please review this offer and confirm your acceptance by signing below.</p>
        <br><br>
        <p>Sincerely,</p>
        <p><strong>Human Resources Department</strong><br>Chap Chap Africa</p>
    </body>
    </html>";
    exit();
}

// Handle offer creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_offer'])) {
    $candidate_input = trim($_POST['candidate_name']);
    $salary_offered = $_POST['salary_offered'];
    $position_title = trim($_POST['position_title']);
    $start_date = $_POST['start_date'];

    // Split manual candidate name into first and last name
    $name_parts = explode(' ', $candidate_input, 2);
    $first_name = $name_parts[0];
    $last_name = $name_parts[1] ?? '';

    // Check if candidate already exists, else insert new candidate
    $check_stmt = $pdo->prepare("SELECT candidate_id FROM candidates WHERE LOWER(first_name) = LOWER(?) AND LOWER(last_name) = LOWER(?)");
    $check_stmt->execute([$first_name, $last_name]);
    $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $candidate_id = $existing['candidate_id'];
    } else {
        $ins_cand = $pdo->prepare("INSERT INTO candidates (first_name, last_name, pipeline_stage) VALUES (?, ?, 'Offered')");
        $ins_cand->execute([$first_name, $last_name]);
        $candidate_id = $pdo->lastInsertId();
    }

    $upload_dir = '../../uploads/offers/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_name = 'offer_' . uniqid() . '.html';
    $destination = $upload_dir . $file_name;

    $letter_content = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Offer Letter - Chap Chap Africa</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; padding: 40px; max-width: 800px; margin: auto; }
            .header { text-align: center; border-bottom: 2px solid #0056b3; padding-bottom: 20px; margin-bottom: 30px; }
            .company-name { font-size: 24px; font-weight: bold; color: #0056b3; }
            .company-name span { color: #dc3545; }
        </style>
    </head>
    <body>
        <div class='header'>
            <div class='company-name'><span style='color: #2563eb;'>CHAP CHAP</span> <span style='color: #dc2626;'>AFRICA</span></div>
            <p>Official Employment Offer Letter</p>
        </div>
        <p>Date: " . date('F j, Y') . "</p>
        <p>Dear <strong>" . htmlspecialchars($candidate_input) . "</strong>,</p>
        <p>We are thrilled to offer you the position of <strong>" . htmlspecialchars($position_title) . "</strong> at Chap Chap Africa. We were deeply impressed by your skills and background, and we are confident you will make a fantastic addition to our team.</p>
        <p><strong>Key Terms of Employment:</strong></p>
        <ul>
            <li><strong>Position:</strong> " . htmlspecialchars($position_title) . "</li>
            <li><strong>Proposed Salary:</strong> UGX " . number_format($salary_offered, 2) . " per annum</li>
            <li><strong>Expected Start Date:</strong> " . htmlspecialchars($start_date) . "</li>
        </ul>
        <p>Please review this offer and confirm your acceptance by signing below.</p>
        <br><br>
        <p>Sincerely,</p>
        <p><strong>Human Resources Department</strong><br>Chap Chap Africa</p>
    </body>
    </html>
    ";

    file_put_contents($destination, $letter_content);
    $offer_letter_path = 'uploads/offers/' . $file_name;

    try {
        $stmt = $pdo->prepare("INSERT INTO offers (candidate_id, salary_offered, offer_letter_path, offer_status) VALUES (?, ?, ?, 'Pending')");
        $stmt->execute([$candidate_id, $salary_offered, $offer_letter_path]);
        
        $up_cand = $pdo->prepare("UPDATE candidates SET pipeline_stage = 'Offered' WHERE candidate_id = ?");
        $up_cand->execute([$candidate_id]);

        header("Location: offers.php");
        exit();
    } catch (PDOException $e) {
        $error = "Error creating offer: " . $e->getMessage();
    }
}

// Handle offer status update
if (isset($_POST['update_offer_status'])) {
    $offer_id = $_POST['offer_id'];
    $candidate_id = $_POST['candidate_id'];
    $new_status = $_POST['offer_status'];

    try {
        $stmt = $pdo->prepare("UPDATE offers SET offer_status = ? WHERE offer_id = ?");
        $stmt->execute([$new_status, $offer_id]);

        if ($new_status === 'Accepted') {
            $cand_stmt = $pdo->prepare("UPDATE candidates SET pipeline_stage = 'Hired' WHERE candidate_id = ?");
            $cand_stmt->execute([$candidate_id]);
        }

        header("Location: offers.php");
        exit();
    } catch (PDOException $e) {
        $error = "Error updating offer status: " . $e->getMessage();
    }
}

// Fetch all offers
try {
    $stmt = $pdo->query("SELECT o.*, c.first_name, c.last_name, c.email FROM offers o JOIN candidates c ON o.candidate_id = c.candidate_id ORDER BY o.offer_id DESC");
    $offers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $offers = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offer Management - HRMS</title>
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
        .badge-pending { background-color: #f59e0b; color: white; }
        .badge-accepted { background-color: #22c55e; color: white; }
        .badge-rejected { background-color: #ef4444; color: white; }
        .badge-ignored { background-color: #64748b; color: white; }
        .template-preview-box {
            background: #f0f9ff;
            border: 1px dashed #7dd3fc;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .cca-brand {
            font-weight: 800;
            letter-spacing: 0.5px;
        }
    </style>
    <script>
        function downloadLetterTemplate(e) {
            e.preventDefault();
            const candName = document.getElementById('candidate_name_input').value;
            const posTitle = document.getElementById('position_input').value;
            const salary = document.getElementById('salary_input').value;
            const startDt = document.getElementById('start_date_input').value;

            if (!candName) {
                alert('Please enter the candidate name first.');
                return;
            }
            
            window.location.href = `offers.php?download_template=1&cand_name=${encodeURIComponent(candName)}&pos_title=${encodeURIComponent(posTitle)}&salary=${encodeURIComponent(salary)}&start_dt=${encodeURIComponent(startDt)}`;
        }
    </script>
</head>
<body>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>

<div class="container">
    <header>
        <a href="index.php" class="btn-secondary" style="background-color: #64748b; color: #ffffff; padding: 6px 10px; text-decoration: none; border-radius: 4px; font-size: 11px; font-weight: bold;">← Back</a>
        <h1 class="page-title">Offer Management</h1>
        <div style="width: 50px;"></div>
    </header>

    <?php if (isset($error)): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 8px; border-radius: 4px; margin-bottom: 12px; font-size: 12px;"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Candidate Offers Status Table on Top -->
    <h2 class="section-title">Candidate Offers Status</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Candidate</th>
                <th>Job Title</th>
                <th>Salary Offered</th>
                <th>Offer Letter</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($offers) > 0): ?>
                <?php foreach ($offers as $off): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($off['offer_id']); ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($off['first_name'] . ' ' . $off['last_name']); ?></strong><br>
                            <small style="color: #64748b; font-size: 10px;"><?php echo htmlspecialchars($off['email'] ?? 'Manual Entry'); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($off['job_title'] ?? 'N/A'); ?></td>
                        <td><strong>UGX <?php echo number_format($off['salary_offered'], 2); ?></strong></td>
                        <td>
                            <?php if (!empty($off['offer_letter_path'])): ?>
                                <a href="../../<?php echo htmlspecialchars($off['offer_letter_path']); ?>" target="_blank" style="color: #0284c7; text-decoration: none; font-weight: bold;">View / Download</a>
                            <?php else: ?>
                                No Letter
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo strtolower($off['offer_status']); ?>">
                                <?php echo htmlspecialchars($off['offer_status']); ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" style="display: flex; gap: 4px; align-items: center;">
                                <input type="hidden" name="offer_id" value="<?php echo $off['offer_id']; ?>">
                                <input type="hidden" name="candidate_id" value="<?php echo $off['candidate_id']; ?>">
                                <select name="offer_status" style="padding: 3px; font-size: 11px;">
                                    <option value="Pending" <?php if($off['offer_status']=='Pending') echo 'selected'; ?>>Pending</option>
                                    <option value="Accepted" <?php if($off['offer_status']=='Accepted') echo 'selected'; ?>>Accepted</option>
                                    <option value="Rejected" <?php if($off['offer_status']=='Rejected') echo 'selected'; ?>>Rejected</option>
                                    <option value="Ignored" <?php if($off['offer_status']=='Ignored') echo 'selected'; ?>>Ignored</option>
                                </select>
                                <button type="submit" name="update_offer_status" class="btn-primary" style="padding: 3px 6px; font-size: 11px; background-color: #f97316;">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: #64748b;">No offers issued yet.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Generate Offer Form at the Bottom -->
    <div class="form-container">
        <h2 class="section-title" style="margin-top: 0;">Generate <span class="cca-brand"><span style="color: #2563eb;">CHAP CHAP</span> <span style="color: #dc2626;">AFRICA</span></span> Job Offer</h2>
        
        <div class="template-preview-box">
            <div>
                <p style="margin: 0 0 4px 0; font-size: 12px; color: #0369a1; font-weight: bold;">Chap Chap Africa Letter Generator:</p>
                <p style="margin: 0; font-size: 11px; color: #334155;">Type candidate details below manually, and download their personalized formal offer letter instantly.</p>
            </div>
            <button type="button" onclick="downloadLetterTemplate(event)" class="btn-primary" style="background-color: #22c55e; white-space: nowrap;">📥 Download Letter</button>
        </div>

        <form method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Candidate Name (Manual Entry)</label>
                    <input type="text" id="candidate_name_input" name="candidate_name" required>
                </div>
                <div class="form-group">
                    <label>Position / Job Title</label>
                    <input type="text" id="position_input" name="position_title" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Salary Offered (UGX)</label>
                    <input type="number" step="0.01" id="salary_input" name="salary_offered" required>
                </div>
                <div class="form-group">
                    <label>Expected Start Date</label>
                    <input type="date" id="start_date_input" name="start_date" required>
                </div>
            </div>

            <button type="submit" name="create_offer" class="btn-primary">Save Offer Record & Generate Letter</button>
        </form>
    </div>
</div>

</body>
</html>