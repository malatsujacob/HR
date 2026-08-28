<?php
// make_hr.php - Quick setup for your custom HR credentials with required fields
require_once 'config/db.php';

$my_email = "jack@gmail.com";
$new_password = "Jack.02.+ma";
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

try {
    // 1. Check if an employee with this email already exists
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE work_email = ?");
    $stmt->execute([$my_email]);
    $admin = $stmt->fetch();

    if ($admin) {
        // Just update role, status, and password for the existing user
        $update = $pdo->prepare("UPDATE employees SET role = 'HR', status = 'Active', password = ? WHERE work_email = ?");
        $update->execute([$hashed_password, $my_email]);
        echo "<h3 style='color: green; font-family: sans-serif;'>Success! Existing account ($my_email) updated to HR admin.</h3>";
    } else {
        // Insert with all required NOT NULL fields filled out to satisfy the database constraint
        $insert = $pdo->prepare("
            INSERT INTO employees (
                first_name, last_name, work_email, password, role, status, department,
                date_of_birth, gender, nationality, marital_status, personal_email, phone_number, physical_address,
                next_of_kin_name, next_of_kin_relationship, next_of_kin_phone, next_of_kin_address,
                job_title, reporting_manager, work_location, hire_date, employment_type
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, 
                ?, ?, ?, ?, ?
            )
        ");
        
        $insert->execute([
            'Jack', 'Admin', $my_email, $hashed_password, 'HR', 'Active', 'Management',
            '1990-01-01', 'Male', 'Ugandan', 'Single', $my_email, '0700000000', 'Kampala',
            'Next of Kin', 'Family', '0700000000', 'Kampala',
            'System Administrator', 'Self', 'Kampala Office', '2026-01-01', 'Full-time'
        ]);
        
        echo "<h3 style='color: green; font-family: sans-serif;'>Success! Created new HR admin account for $my_email.</h3>";
    }

    echo "<p style='font-family: sans-serif;'>Email: <strong>$my_email</strong></p>";
    echo "<p style='font-family: sans-serif;'>Password: <strong>$new_password</strong></p>";
    echo "<p style='font-family: sans-serif;'><a href='login.php'>Click here to go to the Login Page</a></p>";

} catch (PDOException $e) {
    echo "<h3 style='color: red; font-family: sans-serif;'>Database Error: " . $e->getMessage() . "</h3>";
}
?>