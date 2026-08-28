<?php
require_once '../config/db.php';
require_once 'leave_model.php';

$leaveModel = new LeaveModel($pdo);
$msg = '';

// Handle adding custom company/public holidays via form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_holiday'])) {
    $holidayName = trim($_POST['holiday_name']);
    $holidayDate = $_POST['holiday_date'];
    $description = trim($_POST['description']);
    $holidayType = $_POST['holiday_type'] ?? 'company'; 

    $result = $leaveModel->addPublicHoliday($holidayName, $holidayDate, $description, $holidayType);
    if ($result) {
        $msg = "Holiday saved successfully.";
    } else {
        $msg = "Error saving holiday.";
    }
}

// Automatically ensure current and next year holidays exist in PostgreSQL
$currentYear = (int)date("Y");
$leaveModel->ensureYearHolidaysExist($currentYear);
$leaveModel->ensureYearHolidaysExist($currentYear + 1);

// Fetch all holidays from database
$allHolidays = $leaveModel->getPublicHolidays();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Public & Company Holidays - HR System</title>
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
        input[type="text"], input[type="date"], select, textarea { padding: 6px 8px; font-size: 11px; border: 1px solid #cbd5e1; border-radius: 4px; width: 100%; box-sizing: border-box; background: #ffffff; color: #0f172a; }
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
        <h1>Public & Company Holidays</h1>
        <a href="leave.php" class="btn-secondary">&larr; Back</a>
    </header>

    <?php if (!empty($msg)): ?>
        <div class="alert-success"><?php echo $msg; ?></div>
    <?php endif; ?>

    <!-- INTERACTIVE HOLIDAY CALENDAR CARD -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <h3 id="calMonthYear" style="margin: 0; font-size: 13px; color: #0f172a; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px;"></h3>
            <div>
                <button type="button" onclick="changeMonth(-1)" class="btn-secondary" style="padding: 3px 8px;">&lt; Prev</button>
                <button type="button" onclick="changeMonth(1)" class="btn-secondary" style="padding: 3px 8px;">Next &gt;</button>
            </div>
        </div>

        <!-- Color Legend -->
        <div style="display: flex; gap: 15px; font-size: 10px; margin-bottom: 10px; font-weight: bold;">
            <span style="display: flex; align-items: center; gap: 4px;"><span style="width: 10px; height: 10px; background: #fee2e2; border: 1px solid #fecaca; display: inline-block;"></span> Public Holiday (Red)</span>
            <span style="display: flex; align-items: center; gap: 4px;"><span style="width: 10px; height: 10px; background: #dcfce7; border: 1px solid #bbf7d0; display: inline-block;"></span> Company Holiday (Green)</span>
        </div>

        <!-- Days Header -->
        <div style="display: grid; grid-template-columns: repeat(7, 1fr); text-align: center; font-weight: bold; font-size: 10px; color: #64748b; margin-bottom: 6px; text-transform: uppercase;">
            <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
        </div>

        <!-- Calendar Days Grid -->
        <div id="calendarDaysGrid" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px;"></div>

        <!-- Click Details Panel -->
        <div id="holidayDetailBox" style="margin-top: 15px; padding: 12px; background: #f1f5f9; border-radius: 4px; border: 1px solid #cbd5e1; display: none;">
            <strong id="displayTitle" style="font-size: 12px; color: #0f172a; text-transform: uppercase;"></strong>
            <span id="displayDate" style="font-size: 10px; color: #64748b; margin-left: 8px;"></span>
            <p id="displayDesc" style="margin: 6px 0 0 0; font-size: 11px; color: #334155;"></p>
        </div>
    </div>

    <!-- HOLIDAYS TABLE CARD -->
    <div class="card">
        <h3 style="margin-top: 0; font-size: 13px; color: #0f172a; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px;">Holidays List</h3>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($allHolidays) > 0): ?>
                    <?php foreach ($allHolidays as $h): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($h['holiday_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($h['holiday_date']); ?></td>
                            <td>
                                <span style="padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: bold; text-transform: uppercase; background: <?php echo (($h['holiday_type'] ?? 'public') === 'company') ? '#dcfce7; color: #166534;' : '#fee2e2; color: #991b1b;'; ?>">
                                    <?php echo htmlspecialchars($h['holiday_type'] ?? 'public'); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($h['description']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center; color: #64748b; padding: 15px;">No holidays found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ADD HOLIDAY CARD -->
    <div class="card">
        <h3 style="margin-top: 0; font-size: 13px; color: #0f172a; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px;">Add Holiday / Custom Event</h3>
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
                <div class="form-group">
                    <label>Holiday Type</label>
                    <select name="holiday_type">
                        <option value="public">Public Holiday (Red)</option>
                        <option value="company">Company Holiday (Green)</option>
                    </select>
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

<script>
const companyHolidays = <?php echo json_encode(array_map(function($h) {
    return [
        'name' => $h['holiday_name'],
        'date' => $h['holiday_date'],
        'type' => $h['holiday_type'] ?? 'public', // Maps holiday_type correctly for JS coloring
        'description' => $h['description'] ?? 'No description provided.'
    ];
}, $allHolidays)); ?>;

let currentDate = new Date();

function renderCalendar() {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();

    const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    document.getElementById('calMonthYear').innerText = `${monthNames[month]} ${year}`;

    const firstDayIndex = new Date(year, month, 1).getDay();
    const totalDays = new Date(year, month + 1, 0).getDate();

    let gridHTML = '';

    for (let i = 0; i < firstDayIndex; i++) {
        gridHTML += `<div style="padding: 8px; background: #f1f5f9; border-radius: 4px; opacity: 0.3;"></div>`;
    }

    for (let day = 1; day <= totalDays; day++) {
        const formattedMonth = String(month + 1).padStart(2, '0');
        const formattedDay = String(day).padStart(2, '0');
        const dateString = `${year}-${formattedMonth}-${formattedDay}`;

        const holidayMatch = companyHolidays.find(h => h.date === dateString);

        if (holidayMatch) {
            const isCompany = holidayMatch.type === 'company';
            const bgColor = isCompany ? '#dcfce7' : '#fee2e2';
            const borderColor = isCompany ? '#bbf7d0' : '#fecaca';
            const textColor = isCompany ? '#166534' : '#991b1b';

            gridHTML += `
                <div onclick='showBottomDescription(${JSON.stringify(holidayMatch)})' style="padding: 6px; background: ${bgColor}; border: 1px solid ${borderColor}; border-radius: 4px; text-align: center; cursor: pointer; min-height: 38px; box-sizing: border-box;" title="${holidayMatch.name}">
                    <span style="font-weight: bold; color: ${textColor}; font-size: 11px; display: block;">${day}</span>
                    <div style="font-size: 8px; color: ${textColor}; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 600; margin-top:2px;">${holidayMatch.name}</div>
                </div>`;
        } else {
            gridHTML += `
                <div style="padding: 6px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 4px; text-align: center; min-height: 38px; box-sizing: border-box;">
                    <span style="color: #334155; font-size: 11px; display: block;">${day}</span>
                </div>`;
        }
    }

    document.getElementById('calendarDaysGrid').innerHTML = gridHTML;
}

function changeMonth(direction) {
    currentDate.setMonth(currentDate.getMonth() + direction);
    renderCalendar();
}

function showBottomDescription(holiday) {
    const detailBox = document.getElementById('holidayDetailBox');
    document.getElementById('displayTitle').innerText = `${holiday.name} (${holiday.type.toUpperCase()} HOLIDAY)`;
    document.getElementById('displayDate').innerText = holiday.date;
    document.getElementById('displayDesc').innerText = holiday.description;
    detailBox.style.display = 'block';
}

renderCalendar();
</script>

</body>
</html>