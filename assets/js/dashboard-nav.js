const HR_MODULES = [
    { href: "module_1_employees/index.php", label: "Employee Directory" },
    { href: "module2_recruitment/index.php", label: "Recruitment Management" },
    { href: "module3_onboarding/index.php", label: "Onboarding Management" },
    { href: "module4_contracts/index.php", label: "Contracts Management" },
    { href: "module5_attendance/index.php", label: "Attendance & Shifts" },
    { href: "module6_leave/index.php", label: "Leave Management" },
    { href: "module7_payroll/index.php", label: "Payroll & Disbursement" },
    { href: "module8_performance/index.php", label: "Performance Reviews" },
    { href: "module9_training/index.php", label: "Training Management" },
    { href: "module10_ess/index.php", label: "Employee Self-Service (ESS)" },
    { href: "module11_analytics/index.php", label: "HR Analytics & Reports" },
    { href: "module12_disciplinary/index.php", label: "Disciplinary & Grievance" },
    { href: "module13_offboarding/index.php", label: "Offboarding & Clearance" }
];

function injectBackButtonStyles() {
    if (document.getElementById('dashboard-nav-styles')) return;
    const style = document.createElement('style');
    style.id = 'dashboard-nav-styles';
    style.textContent = `
        .back-button {
            display: inline-block;
            margin-bottom: 20px;
            color: #0284c7;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
        }
        .back-button:hover {
            text-decoration: underline;
        }
    `;
    document.head ? document.head.appendChild(style) : document.body.insertAdjacentElement('afterbegin', style);
}

function buildModuleList() {
    const listContainer = document.getElementById('module-list');
    if (!listContainer) return;

    listContainer.innerHTML = HR_MODULES.map(module => {
        return `<a href="${module.href}" class="module-btn">${module.label}</a>`;
    }).join('');
}

function injectVerticalStyles() {
    if (document.getElementById('dashboard-nav-styles')) return;
    const style = document.createElement('style');
    style.id = 'dashboard-nav-styles';
    style.textContent = `
        .module-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 15px;
        }
        .module-btn, .vertical-link-item a {
            display: block;
            width: 100%;
            padding: 16px 18px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            color: #0f172a;
            text-decoration: none;
            font-weight: 700;
            font-size: 15px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        }
        .module-btn:hover, .vertical-link-item a:hover {
            background: #e2effb;
            border-color: #90c7ed;
        }
        .vertical-links {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 10px;
        }
        .vertical-link-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
            background: #ffffff;
            padding: 14px 16px;
            border: 1px solid #dbeafe;
            border-radius: 10px;
        }
        .vertical-link-description {
            margin: 0;
            color: #475569;
            font-size: 13px;
        }
        .back-button {
            display: inline-block;
            margin-bottom: 20px;
            color: #0284c7;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
        }
        .back-button:hover {
            text-decoration: underline;
        }
    `;
    document.head ? document.head.appendChild(style) : document.body.insertAdjacentElement('afterbegin', style);
}

function buildModuleList() {
    const listContainer = document.getElementById('module-list');
    if (!listContainer) return;

    listContainer.innerHTML = HR_MODULES.map(module => {
        return `<a href="${module.href}" class="module-btn">${module.label}</a>`;
    }).join('');
}

function convertLinkGridsToVerticalLists() {
    const grids = document.querySelectorAll('.links-grid');
    grids.forEach(grid => {
        const vertical = document.createElement('div');
        vertical.className = 'vertical-links';

        const cards = grid.querySelectorAll('.link-card');
        cards.forEach(card => {
            const link = card.querySelector('a');
            const desc = card.querySelector('p');
            if (!link) return;

            const item = document.createElement('div');
            item.className = 'vertical-link-item';

            const newLink = document.createElement('a');
            newLink.href = link.href;
            newLink.innerHTML = link.innerHTML;
            item.appendChild(newLink);

            if (desc) {
                const p = document.createElement('p');
                p.className = 'vertical-link-description';
                p.textContent = desc.textContent;
                item.appendChild(p);
            }

            vertical.appendChild(item);
        });

        grid.replaceWith(vertical);
    });
}

function insertBackToMainButton() {
    const path = window.location.pathname.replace(/\\/g, '/');
    if (path.endsWith('/HR/') || path.endsWith('/HR/index.php') || path.endsWith('/index.php')) {
        return;
    }

    const insertTargets = [
        '.main-content',
        '.content',
        '.container',
        '.card',
        'body'
    ];

    let target = null;
    for (const selector of insertTargets) {
        target = document.querySelector(selector);
        if (target) break;
    }
    if (!target) return;

    const backButton = document.createElement('a');
    backButton.href = '/HR/index.php';
    backButton.className = 'back-button';
    backButton.textContent = '← Back to Main Dashboard';
    target.insertAdjacentElement('afterbegin', backButton);
}

window.addEventListener('DOMContentLoaded', () => {
    injectVerticalStyles();
    buildModuleList();
    convertLinkGridsToVerticalLists();
    insertBackToMainButton();
});
