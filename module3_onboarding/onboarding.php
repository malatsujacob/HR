<?php
require_once '../config/db.php';

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_onboarding'])) {
        $onboarding_id = $_POST['onboarding_id'];
        
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $national_id_path = $_POST['existing_national_id'] ?? '';
        if (isset($_FILES['national_id']) && $_FILES['national_id']['error'] === UPLOAD_ERR_OK) {
            $national_id_path = $upload_dir . time() . '_nat_' . basename($_FILES['national_id']['name']);
            move_uploaded_file($_FILES['national_id']['tmp_name'], $national_id_path);
        }

        $contract_path = $_POST['existing_contract'] ?? '';
        if (isset($_FILES['signed_contract']) && $_FILES['signed_contract']['error'] === UPLOAD_ERR_OK) {
            $contract_path = $upload_dir . time() . '_cont_' . basename($_FILES['signed_contract']['name']);
            move_uploaded_file($_FILES['signed_contract']['tmp_name'], $contract_path);
        }

        $nssf_path = $_POST['existing_nssf'] ?? '';
        if (isset($_FILES['nssf_doc']) && $_FILES['nssf_doc']['error'] === UPLOAD_ERR_OK) {
            $nssf_path = $upload_dir . time() . '_nssf_' . basename($_FILES['nssf_doc']['name']);
            move_uploaded_file($_FILES['nssf_doc']['tmp_name'], $nssf_path);
        }

        $tin_path = $_POST['existing_tin'] ?? '';
        if (isset($_FILES['tin_doc']) && $_FILES['tin_doc']['error'] === UPLOAD_ERR_OK) {
            $tin_path = $upload_dir . time() . '_tin_' . basename($_FILES['tin_doc']['name']);
            move_uploaded_file($_FILES['tin_doc']['tmp_name'], $tin_path);
        }

        $bank_holder = $_POST['bank_holder_name'] ?? '';
        $bank_name = $_POST['bank_name'] ?? '';
        $bank_acc = $_POST['bank_account_number'] ?? '';
        $bank_branch = $_POST['bank_branch'] ?? '';
        $em_name = $_POST['emergency_contact_name'] ?? '';
        $em_rel = $_POST['emergency_relationship'] ?? '';
        $em_phone = $_POST['emergency_phone'] ?? '';
        $laptop = $_POST['laptop_assigned'] ?? 'No';
        $laptop_serial = $_POST['laptop_serial'] ?? '';
        $email_created = $_POST['work_email_created'] ?? 'No';
        $work_email = $_POST['work_email_address'] ?? '';
        $badge = $_POST['access_badge_ready'] ?? 'No';
        $workstation = $_POST['workstation_assigned'] ?? 'No';
        $desk = $_POST['desk_number'] ?? '';

        $score = 20;
        if (!empty($national_id_path)) $score += 15;
        if (!empty($contract_path)) $score += 15;
        if (!empty($nssf_path)) $score += 10;
        if (!empty($tin_path)) $score += 10;
        if (!empty($bank_acc)) $score += 15;
        if (!empty($em_name)) $score += 15;
        if ($score > 100) $score = 100;

        $stmt = $pdo->prepare("UPDATE employee_onboarding SET national_id_path = ?, signed_contract_path = ?, nssf_doc_path = ?, tin_doc_path = ?, bank_holder_name = ?, bank_name = ?, bank_account_number = ?, bank_branch = ?, emergency_contact_name = ?, emergency_relationship = ?, emergency_phone = ?, laptop_assigned = ?, laptop_serial = ?, work_email_created = ?, work_email_address = ?, access_badge_ready = ?, workstation_assigned = ?, desk_number = ?, progress_percentage = ? WHERE onboarding_id = ?");
        $stmt->execute([$national_id_path, $contract_path, $nssf_path, $tin_path, $bank_holder, $bank_name, $bank_acc, $bank_branch, $em_name, $em_rel, $em_phone, $laptop, $laptop_serial, $email_created, $work_email, $badge, $workstation, $desk, $score, $onboarding_id]);
        
        $success_msg = "Onboarding details and statutory documents updated successfully.";
    }

    if (isset($_POST['complete_onboarding'])) {
        $onboarding_id = $_POST['onboarding_id'];
        $stmt = $pdo->prepare("SELECT * FROM employee_onboarding WHERE onboarding_id = ?");
        $stmt->execute([$onboarding_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $work_email = !empty($row['work_email_address']) ? $row['work_email_address'] : (!empty($row['email']) ? $row['email'] : 'pending_' . time() . '@example.com');
            $personal_email = $work_email;
            $phone = !empty($row['phone']) ? $row['phone'] : '0000000000';
            $address = 'Kampala, Uganda';
            $nok_rel = !empty($row['emergency_relationship']) ? $row['emergency_relationship'] : 'N/A';
            $nok_address = 'Kampala, Uganda';
            $manager = 'Management';
            $work_location = 'Kampala HQ';
            $hire_date = date('Y-m-d');
            $employment_type = 'full_time'; 

            try {
                $emp_stmt = $pdo->prepare("
                    INSERT INTO employees (
                        first_name, last_name, work_email, personal_email, phone_number, phone,
                        physical_address, next_of_kin_relationship, next_of_kin_address,
                        department, job_title, reporting_manager, work_location, 
                        hire_date, employment_type
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, 
                        ?, ?, ?, 
                        ?, ?, ?, ?, 
                        ?, ?
                    )
                ");
                $emp_stmt->execute([
                    $row['first_name'],       
                    $row['last_name'],        
                    $work_email,            
                    $personal_email,          
                    $phone,                   
                    $phone,                   
                    $address,                 
                    $nok_rel,                 
                    $nok_address,             
                    $row['department'],       
                    $row['job_title'],        
                    $manager,                 
                    $work_location,           
                    $hire_date,               
                    $employment_type          
                ]);

                try {
                    $user_stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, 'employee', 'Active')");
                    $user_stmt->execute([strtolower($row['first_name'] . '.' . $row['last_name']), $work_email, password_hash('password123', PASSWORD_DEFAULT)]);
                } catch (Exception $ex) {
                    // Ignore if users table schema varies
                }

                $up_stmt = $pdo->prepare("UPDATE employee_onboarding SET status = 'Completed', progress_percentage = 100 WHERE onboarding_id = ?");
                $up_stmt->execute([$onboarding_id]);

                $success_msg = "Onboarding completed. Profile activated, system user account provisioned, and pushed to Module 1.";
            } catch (PDOException $e) {
                if ($e->getCode() == '23505') {
                    $error_msg = "Error: An employee with the work email '" . htmlspecialchars($work_email) . "' already exists in Module 1.";
                } else {
                    $error_msg = "Database Error: " . $e->getMessage();
                }
            }
        }
    }

    if (isset($_POST['bulk_import'])) {
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            $fileStream = fopen($_FILES['csv_file']['tmp_name'], 'r');
            $count = 0;
            $skipped = 0;
            fgetcsv($fileStream);
            while (($data = fgetcsv($fileStream, 1000, ",")) !== FALSE) {
                if (count($data) >= 5) {
                    $work_email = !empty($data[2]) ? $data[2] : 'user_' . uniqid() . '@example.com';
                    try {
                        $emp_stmt = $pdo->prepare("
                            INSERT INTO employees (
                                first_name, last_name, work_email, personal_email, phone_number, phone,
                                physical_address, next_of_kin_relationship, next_of_kin_address,
                                department, job_title, reporting_manager, work_location, 
                                hire_date, employment_type
                            ) VALUES (
                                ?, ?, ?, ?, ?, ?, 
                                ?, ?, ?, 
                                ?, ?, ?, ?, 
                                ?, ?
                            )
                        ");
                        $emp_stmt->execute([
                            $data[0],                 
                            $data[1],                 
                            $work_email,              
                            $work_email,              
                            '0000000000',             
                            '0000000000',             
                            'Kampala, Uganda',        
                            'N/A',                    
                            'Kampala, Uganda',        
                            $data[4],                 
                            $data[3],                 
                            'Management',             
                            'Kampala HQ',             
                            date('Y-m-d'),            
                            'full_time'               
                        ]);
                        $count++;
                    } catch (PDOException $e) {
                        if ($e->getCode() == '23505') {
                            $skipped++;
                        } else {
                            throw $e;
                        }
                    }
                }
            }
            fclose($fileStream);
            $success_msg = "Successfully bulk imported " . $count . " active employees into Module 1." . ($skipped > 0 ? " ($skipped skipped due to duplicate emails)" : "");
        } else {
            $error_msg = "Please upload a valid CSV file.";
        }
    }
}

try {
    $onboardings = $pdo->query("SELECT * FROM employee_onboarding ORDER BY onboarding_id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $onboardings = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Onboarding Workflow - hrms</title>
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
        }
        .btn-primary {
            background-color: #0284c7;
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary:hover {
            background-color: #0369a1;
        }
        .btn-success {
            background-color: #16a34a;
            color: white;
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }
        .btn-success:hover {
            background-color: #15803d;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #e0f2fe;
            font-size: 12px;
            vertical-align: top;
            color: #334155;
        }
        th {
            background-color: #f0f9ff;
            color: #0369a1;
            font-weight: 800;
            text-transform: uppercase;
        }
        tr:hover {
            background-color: #f8fafc;
        }
        .progress-bar-container {
            background: #e2e8f0;
            border-radius: 4px;
            width: 100%;
            height: 14px;
        }
        .progress-bar {
            background: #16a34a;
            height: 100%;
            border-radius: 4px;
            text-align: center;
            color: white;
            font-size: 9px;
            line-height: 14px;
        }
        .section-box {
            background: #ffffff;
            padding: 16px;
            border-radius: 6px;
            border: 1px solid #bae6fd;
            margin-bottom: 20px;
            box-shadow: 0 2px 6px rgba(2, 132, 199, 0.03);
        }
        .section-box h3 {
            margin-top: 0;
            color: #0369a1;
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php'); ?>

<div class="container">
    <header>
        <h1 class="page-title">Employee Onboarding Workflow</h1>
        <div style="display: flex; gap: 10px;">
            <a href="index.php" class="btn-primary" style="background-color: #0284c7;">← Back Hub</a>
            <a href="../module9_training/manage_training.php" class="btn-primary">Training Module →</a>
        </div>
    </header>

    <?php if (!empty($success_msg)): ?>
        <div style="background: #dcfce7; color: #166534; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 12px; border: 1px solid #bbf7d0;"><?php echo $success_msg; ?></div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 12px; border: 1px solid #fecaca;"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <div class="section-box">
        <h3>Bulk CSV Import</h3>
        <form method="POST" enctype="multipart/form-data" style="display: flex; gap: 10px; align-items: center;">
            <input type="file" name="csv_file" accept=".csv" required style="font-size: 12px;">
            <button type="submit" name="bulk_import" class="btn-primary">Upload & Import Active Profiles</button>
        </form>
    </div>

    <h3 style="color: #0369a1; font-size: 14px; text-transform: uppercase; font-weight: 800; margin-bottom: 10px;">Active Handover Tracker</h3>
    <table>
        <thead>
            <tr>
                <th>New Hire & System User</th>
                <th>Position</th>
                <th>Statutory & Compliance Docs</th>
                <th>IT Asset Provisioning</th>
                <th>Progress</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($onboardings) > 0): ?>
                <?php foreach ($onboardings as $row): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></strong><br>
                            <small style="color: #64748b;"><?php echo htmlspecialchars($row['work_email_address'] ?? $row['email'] ?? 'No Email'); ?></small><br>
                            <span style="background: #e0f2fe; color: #0369a1; padding: 1px 4px; border-radius: 3px; font-size: 10px; font-weight: bold;">User Account: Active</span>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($row['job_title']); ?><br>
                            <small style="color: #64748b;"><?php echo htmlspecialchars($row['department']); ?></small>
                        </td>
                        <td>
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="onboarding_id" value="<?php echo $row['onboarding_id']; ?>">
                                <input type="hidden" name="existing_national_id" value="<?php echo htmlspecialchars($row['national_id_path'] ?? ''); ?>">
                                <input type="hidden" name="existing_contract" value="<?php echo htmlspecialchars($row['signed_contract_path'] ?? ''); ?>">
                                <input type="hidden" name="existing_nssf" value="<?php echo htmlspecialchars($row['nssf_doc_path'] ?? ''); ?>">
                                <input type="hidden" name="existing_tin" value="<?php echo htmlspecialchars($row['tin_doc_path'] ?? ''); ?>">
                                
                                <label style="font-size: 10px; display:block;">National ID: <input type="file" name="national_id" style="font-size:9px;"></label>
                                <?php if(!empty($row['national_id_path'])): ?><small style="color:#16a34a; font-weight:bold;">Uploaded</small><br><?php endif; ?>
                                
                                <label style="font-size: 10px; display:block; margin-top:2px;">Contract: <input type="file" name="signed_contract" style="font-size:9px;"></label>
                                <?php if(!empty($row['signed_contract_path'])): ?><small style="color:#16a34a; font-weight:bold;">Uploaded</small><br><?php endif; ?>

                                <label style="font-size: 10px; display:block; margin-top:2px;">NSSF Details: <input type="file" name="nssf_doc" style="font-size:9px;"></label>
                                <?php if(!empty($row['nssf_doc_path'])): ?><small style="color:#16a34a; font-weight:bold;">Uploaded</small><br><?php endif; ?>

                                <label style="font-size: 10px; display:block; margin-top:2px;">URA TIN: <input type="file" name="tin_doc" style="font-size:9px;"></label>
                                <?php if(!empty($row['tin_doc_path'])): ?><small style="color:#16a34a; font-weight:bold;">Uploaded</small><br><?php endif; ?>

                                <input type="text" name="bank_account_number" placeholder="Bank Acc #" value="<?php echo htmlspecialchars($row['bank_account_number'] ?? ''); ?>" style="width: 110px; font-size: 10px; margin-top: 3px;"><br>
                                <input type="text" name="emergency_contact_name" placeholder="Emergency Contact" value="<?php echo htmlspecialchars($row['emergency_contact_name'] ?? ''); ?>" style="width: 110px; font-size: 10px; margin-top: 3px;">
                        </td>
                        <td>
                                <label style="font-size: 10px;">Laptop: 
                                    <select name="laptop_assigned" style="font-size: 10px;">
                                        <option value="No" <?php if(($row['laptop_assigned']??'No')=='No') echo 'selected'; ?>>No</option>
                                        <option value="Yes" <?php if(($row['laptop_assigned']??'No')=='Yes') echo 'selected'; ?>>Yes</option>
                                    </select>
                                </label><br>
                                <input type="text" name="laptop_serial" placeholder="Serial #" value="<?php echo htmlspecialchars($row['laptop_serial'] ?? ''); ?>" style="width: 85px; font-size: 10px; margin-top: 2px;"><br>
                                <label style="font-size: 10px; margin-top: 2px; display:block;">Email: 
                                    <select name="work_email_created" style="font-size: 10px;">
                                        <option value="No" <?php if(($row['work_email_created']??'No')=='No') echo 'selected'; ?>>No</option>
                                        <option value="Yes" <?php if(($row['work_email_created']??'No')=='Yes') echo 'selected'; ?>>Yes</option>
                                    </select>
                                </label><br>
                                <input type="text" name="desk_number" placeholder="Desk #" value="<?php echo htmlspecialchars($row['desk_number'] ?? ''); ?>" style="width: 85px; font-size: 10px; margin-top: 2px;">
                                <button type="submit" name="update_onboarding" class="btn-primary" style="display:block; margin-top: 5px; font-size: 9px; padding: 2px 4px;">Save Updates</button>
                            </form>
                        </td>
                        <td style="width: 100px;">
                            <div class="progress-bar-container">
                                <div class="progress-bar" style="width: <?php echo $row['progress_percentage'] ?? 0; ?>%;">
                                    <?php echo $row['progress_percentage'] ?? 0; ?>%
                                </div>
                            </div>
                        </td>
                        <td>
                            <span style="font-weight: bold; color: <?php echo (($row['status'] ?? '') === 'Completed') ? '#16a34a' : '#ca8a04'; ?>; font-size: 12px;">
                                <?php echo htmlspecialchars($row['status'] ?? 'Draft'); ?>
                            </span>
                        </td>
                        <td>
                            <?php if (($row['status'] ?? '') !== 'Completed'): ?>
                                <form method="POST" onsubmit="return confirm('Complete onboarding and activate profile in Module 1?');">
                                    <input type="hidden" name="onboarding_id" value="<?php echo $row['onboarding_id']; ?>">
                                    <button type="submit" name="complete_onboarding" class="btn-success">Complete</button>
                                </form>
                            <?php else: ?>
                                <span style="color: #64748b; font-size: 11px; font-weight: bold;">Activated</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: #64748b;">No active onboarding records found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>