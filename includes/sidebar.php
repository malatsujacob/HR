<?php
// includes/sidebar.php - Unified Navigation Sidebar (Absolute Path Fixed Version)
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$isInsideModule = preg_match('#/module[^/]+/.+#', $requestUri);
// If we are inside a sub-folder (like moduleX/sub/page.php), we need an extra level to get back to the project root
$rootPath = $isInsideModule ? '../../' : '../';
?>

<div class="sidebar">
    <div class="brand">
        <span class="skyblue">CHAP CHAP AFRICA</span><br>
        <span class="hrms-brand">HRMS</span>
    </div>
    
    <div class="home-link">
        <a href="/hr/index.php">🏠 Main Home Dashboard</a>
    </div>

    <ul class="nav-links">
        <li><a href="/hr/index.php">All Sections</a></li>
        <li><a href="/hr/module2_recruitment/index.php">1. Recruitment Management</a></li>
        <li><a href="/hr/module4_contracts/index.php">2. Contracts Management</a></li>
        <li><a href="/hr/module3_onboarding/index.php">3. Onboarding Management</a></li>
        <li><a href="/hr/module5_attendance/index.php">4. Attendance & Shifts</a></li>
        <li><a href="/hr/module_1_employees/index.php">5. Employee Directory</a></li>
        <li><a href="/hr/module6_leave/index.php">6. Leave Management</a></li>
        <li><a href="/hr/module7_payroll/index.php">7. Payroll & Disbursement</a></li>
        <li>
            <a href="/hr/module9_training/index.php">9. Training & Development</a>
            <ul class="sub-links">
                <li><a href="/hr/module9_training/training.php">Training Schedule</a></li>
                <li><a href="/hr/module9_training/hr_login.php">Training Setup</a></li>
            </ul>
        </li>
        <li><a href="/hr/module10_ess/index.php">10. Employee ESS</a></li>
        <li><a href="/hr/module11_analytics/index.php">11. HR Analytics & Reports</a></li>
        <li><a href="/hr/module12_disciplinary/index.php">12. Disciplinary Actions</a></li>
        <li><a href="/hr/module13_offboarding/index.php">13. Offboarding & Clearance</a></li>
        <li style="margin-top: 20px; border-top: 1px solid rgba(148,163,184,0.24); padding-top: 10px;">
            <a href="/hr/logout.php" style="color: #f87171; font-weight: bold;">🚪 Log Out</a>
        </li>
    </ul>
</div>

<style>
    .sidebar {
        width: 240px;
        background-color: #0f172a;
        color: #ffffff;
        position: fixed;
        height: 100vh;
        top: 0;
        left: 0;
        padding-top: 16px;
        z-index: 9999;
        box-shadow: 2px 0 6px rgba(0,0,0,0.15);
        box-sizing: border-box;
        overflow-y: auto;
    }
    .sidebar .brand {
        padding: 0 16px 16px 16px;
        font-size: 14px;
        font-weight: bold;
        border-bottom: 1px solid rgba(148,163,184,0.24);
        text-align: center;
    }
    .sidebar .brand span.skyblue { color: #7dd3fc; }
    .sidebar .brand span.hrms-brand {
        color: #f8fafc;
        font-weight: 700;
        background-color: #1e293b;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 12px;
    }
    .sidebar ul.nav-links {
        list-style: none;
        padding: 0;
        margin: 16px 0;
    }
    .sidebar .home-link {
        padding: 0 16px 14px 16px;
    }
    .sidebar .home-link a {
        display: inline-block;
        width: 100%;
        padding: 8px 10px;
        background: #0ea5e9;
        color: #ffffff;
        text-decoration: none;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        text-align: center;
        box-sizing: border-box;
    }
    .sidebar .home-link a:hover {
        background: #0369a1;
    }
    .sidebar ul.nav-links li a {
        display: block;
        padding: 8px 16px;
        color: #cbd5e1;
        text-decoration: none;
        font-size: 11px;
        border-left: 3px solid transparent;
    }
    .sidebar ul.nav-links li a:hover {
        background-color: #1e293b;
        color: #ffffff;
        border-left-color: #0284c7;
    }
    .sidebar ul.nav-links li ul.sub-links {
        list-style: none;
        margin: 4px 0 0 0;
        padding: 0 0 0 12px;
    }
    .sidebar ul.nav-links li ul.sub-links li a {
        font-size: 10px;
        color: #94a3b8;
    }
</style>