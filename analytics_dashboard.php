<?php
// analytics_dashboard.php - Module 11: HR Analytics & Reporting (The Executive Brain)
require_once 'config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Analytics & Reporting | Executive Brain</title>
    <style>
        :root {
            --bg-light: #f0f9ff;          /* Clean Light Blue Background */
            --surface-white: #ffffff;      /* Crisp White Containers */
            --border-color: #bae6fd;       /* Soft Light Blue Border */
            --text-primary: #0f172a;       /* Dark Navy Text */
            --text-secondary: #334155;     /* Muted Slate Text */
            --accent-skyblue: #0284c7;     /* Vibrant Sky Blue Accent */
            --accent-orange: #f97316;      /* Orange Filter Button */
            --accent-red: #dc2626;         /* Red HRMS Text */
        }

        body {
            font-family: Arial, sans-serif;
            background-color: var(--bg-light);
            color: var(--text-primary);
            margin: 0;
            padding: 20px;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 12px;
            margin-bottom: 20px;
            background-color: var(--bg-light);
        }

        .header-container h1 {
            margin: 0;
            font-size: 22px;
            background-color: var(--bg-light);
            color: var(--text-primary);
            letter-spacing: 0.5px;
        }

        .header-container h1 span.skyblue {
            color: var(--accent-skyblue);
        }

        /* Styled Red HRMS at the end */
        .header-container h1 span.hrms-brand {
            color: var(--accent-red);
            font-weight: 800;
            background-color: #fee2e2;
            padding: 2px 6px;
            border-radius: 4px;
            border: 1px solid #fecaca;
            margin-left: 6px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            margin-top: 10px;
        }

        .section-title {
            font-size: 15px;
            background-color: var(--bg-light);
            color: var(--text-primary);
            border-left: 3px solid var(--accent-skyblue);
            padding: 4px 8px;
            font-weight: bold;
        }

        /* Orange Filter Button & Toggle Drawer Container */
        .filter-container {
            position: relative;
            display: inline-block;
        }

        .filter-btn {
            background-color: var(--accent-orange);
            color: #ffffff;
            border: none;
            padding: 6px 14px;
            font-size: 13px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }

        .filter-btn:hover {
            opacity: 0.9;
        }

        /* Hidden text box that appears on click */
        .filter-input-drawer {
            display: none;
            position: absolute;
            right: 0;
            top: 35px;
            background-color: var(--surface-white);
            border: 1px solid var(--border-color);
            padding: 10px;
            border-radius: 6px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            z-index: 10;
            width: 240px;
        }

        .filter-input-drawer input {
            width: 100%;
            padding: 6px;
            font-size: 13px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            outline: none;
            box-sizing: border-box;
        }

        .filter-input-drawer input:focus {
            border-color: var(--accent-skyblue);
        }

        .filter-hint {
            font-size: 11px;
            color: var(--text-secondary);
            margin-top: 5px;
            line-height: 1.3;
        }

        /* Drill-Down Data Table Section */
        .data-table-container {
            background-color: var(--surface-white);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 25px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th, td {
            padding: 9px 12px;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background-color: #e0f2fe;
            color: var(--text-primary);
            font-size: 13px;
        }

        td {
            color: var(--text-secondary);
            font-size: 13px;
        }

        tr:hover {
            background-color: #f8fafc;
        }

        /* Minimalist Boxes for Metrics */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }

        .kpi-card {
            background-color: var(--surface-white);
            border: 1px solid var(--border-color);
            padding: 10px 12px;
            border-radius: 6px;
            text-align: left;
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
        }

        .kpi-card h3 {
            margin: 0 0 4px 0;
            font-size: 11px;
            color: var(--text-secondary);
            font-weight: normal;
            background-color: var(--surface-white);
        }

        .kpi-card .value {
            font-size: 17px;
            font-weight: bold;
            color: var(--accent-skyblue);
        }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header-container">
        <h1><span class="skyblue">CHAP CHAP AFRICA</span> | <span class="hrms-brand">HRMS</span></h1>
        <div>
            <span style="font-size: 12px; color: var(--text-secondary);">YTD</span>
        </div>
    </div>

    <!-- Error notice if DB fails -->
    <?php if (isset($db_error)): ?>
        <div style="background-color: rgba(2, 132, 199, 0.1); border: 1px solid var(--accent-skyblue); color: var(--text-primary); padding: 8px; border-radius: 4px; margin-bottom: 15px; font-size: 12px;">
            Database Error: <?php echo htmlspecialchars($db_error); ?>
        </div>
    <?php endif; ?>

    <!-- 1. Drill-Down Data Table Section with Orange Toggle Filter Button -->
    <div class="section-header">
        <div class="section-title">Drill-Down & Filtering</div>
        <div class="filter-container">
            <button id="toggleFilterBtn" class="filter-btn">Filter Record</button>
            <div id="filterDrawer" class="filter-input-drawer">
                <input type="text" id="manualFilterInput" placeholder="Type to filter...">
                <div class="filter-hint">Type any name, department (e.g., Sales), ID, or detail to filter records instantly.</div>
            </div>
        </div>
    </div>
    <div class="data-table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Dept</th>
                    <th>Details</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody id="drilldown-table-body">
                <tr>
                    <td colspan="5" style="text-align: center; padding: 15px;">Loading...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- 2. Minimalist Boxed Metrics Grid -->
    <div class="section-title" style="margin-top: 20px; margin-bottom: 10px;">Metrics</div>
    <div class="kpi-grid">
        <div class="kpi-card">
            <h3>Headcount</h3>
            <div class="value" id="kpi-headcount">--</div>
        </div>
        <div class="kpi-card">
            <h3>Turnover</h3>
            <div class="value" id="kpi-turnover">--%</div>
        </div>
        <div class="kpi-card">
            <h3>Open Req</h3>
            <div class="value" id="kpi-open-reqs">--</div>
        </div>
        <div class="kpi-card">
            <h3>Leave</h3>
            <div class="value" id="kpi-pending-leave">--</div>
        </div>
        <div class="kpi-card">
            <h3>Payroll (UGX)</h3>
            <div class="value" id="kpi-payroll">--</div>
        </div>
        <div class="kpi-card">
            <h3>Training</h3>
            <div class="value" id="kpi-training">--%</div>
        </div>
    </div>

    <!-- JavaScript for Toggle Drawer, Data Fetching, and Instant Filtering -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let allEmployees = [];

            // Toggle filter drawer on button click
            const toggleBtn = document.getElementById('toggleFilterBtn');
            const drawer = document.getElementById('filterDrawer');
            const filterInput = document.getElementById('manualFilterInput');

            toggleBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                if (drawer.style.display === 'block') {
                    drawer.style.display = 'none';
                } else {
                    drawer.style.display = 'block';
                    filterInput.focus();
                }
            });

            // Close drawer when clicking outside
            document.addEventListener('click', function(e) {
                if (!drawer.contains(e.target) && e.target !== toggleBtn) {
                    drawer.style.display = 'none';
                }
            });

            // 1. Fetch KPI Metrics
            fetch('module11_analytics/api/kpi.php')
                .then(response => response.json())
                .then(result => {
                    if (result.status === "success") {
                        const data = result.data;
                        document.getElementById('kpi-headcount').textContent = data.active_headcount;
                        document.getElementById('kpi-turnover').textContent = data.monthly_turnover_rate + '%';
                        document.getElementById('kpi-open-reqs').textContent = data.open_requisitions;
                        document.getElementById('kpi-pending-leave').textContent = data.pending_leave_requests;
                        document.getElementById('kpi-payroll').textContent = Number(data.monthly_payroll_cost).toLocaleString();
                        document.getElementById('kpi-training').textContent = data.training_completion_rate + '%';
                    }
                })
                .catch(error => console.error('Error fetching KPI data:', error));

            // 2. Fetch Drilldown Records
            fetch('module11_analytics/api/drilldown.php')
                .then(response => response.json())
                .then(result => {
                    if (result.status === "success" && result.employees) {
                        allEmployees = result.employees;
                        renderTable(allEmployees);
                    }
                })
                .catch(error => console.error('Error fetching drilldown data:', error));

            function renderTable(dataArray) {
                const tbody = document.getElementById('drilldown-table-body');
                tbody.innerHTML = ''; 

                if (dataArray.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 15px;">No matching records found.</td></tr>';
                    return;
                }

                dataArray.forEach(emp => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${emp.employee_id}</td>
                        <td>${emp.name}</td>
                        <td>${emp.department}</td>
                        <td>${emp.status_details}</td>
                        <td>${emp.action_date}</td>
                    `;
                    tbody.appendChild(row);
                });
            }

            // 3. Instant Manual Filter Input Listener
            filterInput.addEventListener('keyup', function() {
                const query = filterInput.value.toLowerCase().trim();
                const filtered = allEmployees.filter(emp => {
                    return emp.employee_id.toLowerCase().includes(query) ||
                           emp.name.toLowerCase().includes(query) ||
                           emp.department.toLowerCase().includes(query) ||
                           emp.status_details.toLowerCase().includes(query) ||
                           emp.action_date.toLowerCase().includes(query);
                });
                renderTable(filtered);
            });
        });
    </script>
</body>
</html>