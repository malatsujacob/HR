<?php
// includes/sidebar.php - Unified Navigation Sidebar (Fixed Layout Version)
$sidebarVariant = 'master';
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$moduleIndexUrl = '/HR/index.php';
$moduleName = 'Main Dashboard';
if (preg_match('#/HR/(module[^/]+)/#', $requestUri, $matches)) {
    $moduleFolder = $matches[1];
    $sidebarVariant = 'module';
    $moduleIndexUrl = '/HR/' . $moduleFolder . '/index.php';
    $moduleName = ucwords(str_replace(['_', '-'], [' ', ' '], preg_replace('/(module)(\d+)/i', '$1 $2', $moduleFolder)));
}
?>

<div class="sidebar sidebar-<?php echo $sidebarVariant; ?>">
    <div class="brand">
        <span class="skyblue">CHAP CHAP AFRICA</span><br>
        <span class="hrms-brand">HRMS</span>
    </div>
    <?php if ($sidebarVariant === 'module'): ?>
        <div class="home-link">
            <a href="<?php echo $moduleIndexUrl; ?>">← Back to <?php echo htmlspecialchars($moduleName); ?></a>
        </div>
    <?php endif; ?>
    <ul class="nav-links">
        <li><a href="/HR/index.php">All Sections</a></li>
        <li><a href="/HR/module2_recruitment/index.php">1. Recruitment Management</a></li>
        <li><a href="/HR/module4_contracts/index.php">2. Contracts Management</a></li>
        <li><a href="/HR/module3_onboarding/index.php">3. Onboarding Management</a></li>
        <li><a href="/HR/module5_attendance/index.php">4. Attendance & Shifts</a></li>
        <li><a href="/HR/module_1_employees/index.php">5. Employee Directory</a></li>
        <li><a href="/HR/module6_leave/index.php">6. Leave Management</a></li>
        <li><a href="/HR/module7_payroll/index.php">7. Payroll & Disbursement (UGX)</a></li>
        <li><a href="/HR/module8_performance/index.php">8. Performance Reviews</a></li>
        <li><a href="/HR/module9_training/index.php">9. Training Management</a></li>
        <li><a href="/HR/module10_ess/index.php">10. Employee Self-Service (ESS)</a></li>
        <li><a href="/HR/module11_analytics/index.php">11. HR Analytics & Reports</a></li>
        <li><a href="/HR/module12_disciplinary/index.php">12. Disciplinary & Grievance</a></li>
        <li><a href="/HR/module13_offboarding/index.php">13. Offboarding & Clearance</a></li>
    </ul>
</div>

<style>
    .sidebar {
        width: 200px;
        background-color: rgba(15,23,42,0.95);
        color: #ffffff;
        position: fixed;
        height: 100%;
        top: 0;
        left: 0;
        padding-top: 16px;
        z-index: 9999;
        box-shadow: 1px 0 4px rgba(0,0,0,0.12);
        box-sizing: border-box;
    }
    .sidebar .brand {
        padding: 0 16px 16px 16px;
        font-size: 15px;
        font-weight: bold;
        border-bottom: 1px solid rgba(148,163,184,0.24);
        text-align: center;
    }
    .sidebar .brand span.skyblue { color: #7dd3fc; }
    .sidebar .brand span.hrms-brand {
        color: #f8fafc;
        font-weight: 700;
        background-color: rgba(15,23,42,0.72);
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 13px;
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
        padding: 10px 12px;
        background: #0ea5e9;
        color: #ffffff;
        text-decoration: none;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
    }
    .sidebar .home-link a:hover {
        background: #0369a1;
    }
    .sidebar ul.nav-links li a {
        display: block;
        padding: 10px 16px;
        color: #cbd5e1;
        text-decoration: none;
        font-size: 12px;
        border-left: 3px solid transparent;
    }
    .sidebar ul.nav-links li a:hover {
        background-color: #1e293b;
        color: #ffffff;
        border-left-color: #0284c7;
    }

    /* Module variant styling */
    .sidebar-module {
        background-color: #1f2937;
    }
    .sidebar-module .brand {
        background-color: #111827;
        border-bottom-color: #334155;
    }
    .sidebar-module .brand span.skyblue { color: #7dd3fc; }
    .sidebar-module .brand span.hrms-brand { color: #f8fafc; background-color: #0f172a; border-color: #64748b; }
    .sidebar-module ul.nav-links li a {
        color: #e2e8f0;
    }
    .sidebar-module ul.nav-links li a:hover {
        background-color: #111827;
        color: #ffffff;
        border-left-color: #7dd3fc;
    }
</style>