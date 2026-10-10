/**
 * Route Map - Single source of truth for all admin SPA routes
 * path -> { template, title, menu, scripts[] }
 */
(function(window) {
    'use strict';

    var paths = window.APP_PATHS || {
        ADMIN_BASE: '/admin',
        TEMPLATES_BASE: '/templates'
    };

    var ADMIN = paths.ADMIN_BASE;
    var TPL = paths.TEMPLATES_BASE;

    var ROUTES = {};

    ROUTES[ADMIN + '/dashboard'] = {
        template: TPL + '/dashboard.html',
        title: 'Dashboard',
        menu: 'dashboard',
        scripts: ['https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js']
    };

    // HR / Payroll
    ROUTES[ADMIN + '/employees']           = { template: TPL + '/employees.html',           title: 'Employees',             menu: 'employees' };
    ROUTES[ADMIN + '/employee-new']        = { template: TPL + '/employee-new.html',        title: 'Add Employee',          menu: 'employee-new' };
    ROUTES[ADMIN + '/hr/grades']           = { template: TPL + '/hr-grades.html',           title: 'Grade Setup',           menu: 'hr-grades' };
    ROUTES[ADMIN + '/hr/salary-setup']     = { template: TPL + '/hr-salary-setup.html',     title: 'Basic Salary Setup',    menu: 'hr-salary-setup' };
    ROUTES[ADMIN + '/hr/allowances']       = { template: TPL + '/hr-allowances.html',       title: 'Allowance / Deduction', menu: 'hr-allowances' };
    ROUTES[ADMIN + '/hr/salary-generate']      = { template: TPL + '/hr-salary-generate.html',     title: 'Generate Salary',       menu: 'hr-salary-generate' };
    ROUTES[ADMIN + '/hr/salary-generate-all']  = { template: TPL + '/hr-salary-generate-all.html', title: 'Generate All Salaries', menu: 'hr-salary-generate-all' };
    ROUTES[ADMIN + '/hr/salaries']             = { template: TPL + '/hr-salaries.html',            title: 'Salary History',        menu: 'hr-salaries' };
    ROUTES[ADMIN + '/reports/payroll']         = { template: TPL + '/report-payroll.html',         title: 'Payroll Report',        menu: 'report-payroll' };

    // Accounts
    ROUTES[ADMIN + '/accounts/asset-categories']     = { template: TPL + '/acc-asset-categories.html',     title: 'Assets Category',     menu: 'acc-asset-cat' };
    ROUTES[ADMIN + '/accounts/liability-categories'] = { template: TPL + '/acc-liability-categories.html', title: 'Liabilities Category',menu: 'acc-liab-cat' };
    ROUTES[ADMIN + '/accounts/income-categories']    = { template: TPL + '/acc-income-categories.html',    title: 'Income Category',     menu: 'acc-inc-cat' };
    ROUTES[ADMIN + '/accounts/expense-categories']   = { template: TPL + '/acc-expense-categories.html',   title: 'Expense Category',    menu: 'acc-exp-cat' };
    ROUTES[ADMIN + '/accounts/income']               = { template: TPL + '/acc-income-manage.html',        title: 'Income Manage',       menu: 'acc-income' };
    ROUTES[ADMIN + '/accounts/expense']              = { template: TPL + '/acc-expense-manage.html',       title: 'Expense Manage',      menu: 'acc-expense' };
    ROUTES[ADMIN + '/accounts/liability']            = { template: TPL + '/acc-liability-manage.html',     title: 'Liabilities Manage',  menu: 'acc-liability' };
    ROUTES[ADMIN + '/accounts/transfer']             = { template: TPL + '/acc-fund-transfer.html',        title: 'Fund Transfer',       menu: 'acc-transfer' };
    ROUTES[ADMIN + '/accounts/report/trial-balance'] = { template: TPL + '/acc-report-trial-balance.html', title: 'Trial Balance Report', menu: 'acc-rep-trial' };
    ROUTES[ADMIN + '/accounts/report/balance-sheet'] = { template: TPL + '/acc-report-balance-sheet.html', title: 'Balance Sheet',        menu: 'acc-rep-bs' };
    ROUTES[ADMIN + '/accounts/report/income']        = { template: TPL + '/acc-report-income.html',        title: 'Income Report',        menu: 'acc-rep-income' };
    ROUTES[ADMIN + '/accounts/report/expense']       = { template: TPL + '/acc-report-expense.html',       title: 'Expense Report',       menu: 'acc-rep-expense' };
    ROUTES[ADMIN + '/accounts/report/income-vs-expense'] = { template: TPL + '/acc-report-income-vs-expense.html', title: 'Income vs Expense',    menu: 'acc-rep-ive' };
    ROUTES[ADMIN + '/accounts/report/fund-transfer']     = { template: TPL + '/acc-report-fund-transfer.html',     title: 'Fund Transfer Report', menu: 'acc-rep-ft' };

    // Attendance
    ROUTES[ADMIN + '/attendance/holiday-types'] = { template: TPL + '/att-holiday-types.html', title: 'Holiday Type',        menu: 'att-holiday-types' };
    ROUTES[ADMIN + '/attendance/holidays']      = { template: TPL + '/att-holidays.html',      title: 'Holiday',             menu: 'att-holidays' };
    ROUTES[ADMIN + '/attendance/holiday-setup'] = { template: TPL + '/att-holiday-setup.html', title: 'Monthly Holiday Setup', menu: 'att-holidays' };
    ROUTES[ADMIN + '/attendance/settings']      = { template: TPL + '/att-settings.html',      title: 'Attendance Settings', menu: 'att-settings' };
    ROUTES[ADMIN + '/attendance/leave-types']   = { template: TPL + '/att-leave-types.html',   title: 'Leave Type',          menu: 'att-leave-types' };
    ROUTES[ADMIN + '/attendance/daily']         = { template: TPL + '/att-daily.html',         title: 'Daily Attendance',    menu: 'att-daily' };
    ROUTES[ADMIN + '/attendance/leaves']        = { template: TPL + '/att-leaves.html',        title: 'Leave',               menu: 'att-leaves' };
    ROUTES[ADMIN + '/attendance/movements']     = { template: TPL + '/att-movements.html',     title: 'Movement Register',   menu: 'att-movements' };
    ROUTES[ADMIN + '/attendance/report']         = { template: TPL + '/att-report-daily.html',   title: 'Attendance Report',         menu: 'att-report' };
    ROUTES[ADMIN + '/attendance/report-monthly'] = { template: TPL + '/att-report-monthly.html', title: 'Monthly Attendance Report', menu: 'att-report-monthly' };

    // Configuration / Settings
    ROUTES[ADMIN + '/users'] = { template: TPL + '/users.html', title: 'User Information', menu: 'users' };
    ROUTES[ADMIN + '/roles'] = { template: TPL + '/roles.html', title: 'User Roles', menu: 'roles' };
    ROUTES[ADMIN + '/user-permissions'] = { template: TPL + '/user-permissions.html', title: 'Role Permissions', menu: 'roles' };
    ROUTES[ADMIN + '/basic-settings'] = { template: TPL + '/settings-basic.html', title: 'Institute Information', menu: 'basic-settings' };
    ROUTES[ADMIN + '/menus'] = { template: TPL + '/menus.html', title: 'Menu Management', menu: 'menus' };
    ROUTES[ADMIN + '/profile']   = { template: TPL + '/profile.html',   title: 'My Profile',         menu: 'profile' };
    ROUTES[ADMIN + '/locations'] = { template: TPL + '/locations.html', title: 'Branches / Locations', menu: 'locations' };
    ROUTES[ADMIN + '/ui-settings'] = { template: TPL + '/ui-settings.html', title: 'UI Color Settings', menu: 'ui-settings' };

    // SMS Module
    ROUTES[ADMIN + '/sms-dashboard'] = { template: TPL + '/sms-dashboard.html', title: 'SMS Dashboard', menu: 'sms-dashboard' };
    ROUTES[ADMIN + '/sms-send']      = { template: TPL + '/sms-send.html',      title: 'Send SMS',       menu: 'sms-send' };
    ROUTES[ADMIN + '/sms-templates'] = { template: TPL + '/sms-templates.html', title: 'SMS Templates',  menu: 'sms-templates' };
    ROUTES[ADMIN + '/sms-contacts']  = { template: TPL + '/sms-contacts.html',  title: 'SMS Contacts',   menu: 'sms-contacts' };
    ROUTES[ADMIN + '/sms-groups']    = { template: TPL + '/sms-groups.html',    title: 'SMS Groups',     menu: 'sms-groups' };
    ROUTES[ADMIN + '/sms-campaigns']= { template: TPL + '/sms-campaigns.html',  title: 'SMS Campaigns',  menu: 'sms-campaigns' };
    ROUTES[ADMIN + '/sms-scheduled']  = { template: TPL + '/sms-scheduled.html',    title: 'Scheduled SMS',  menu: 'sms-scheduled' };
    ROUTES[ADMIN + '/sms-voice']      = { template: TPL + '/sms-voice.html',        title: 'Voice SMS',      menu: 'sms-voice' };
    ROUTES[ADMIN + '/sms-automation'] = { template: TPL + '/sms-automation.html',   title: 'SMS Automation', menu: 'sms-automation' };
    ROUTES[ADMIN + '/sms-packages']   = { template: TPL + '/sms-packages.html',     title: 'SMS Packages',   menu: 'sms-packages' };
    ROUTES[ADMIN + '/sms-transactions'] = { template: TPL + '/sms-transactions.html', title: 'SMS Transactions', menu: 'sms-transactions' };
    ROUTES[ADMIN + '/sms-reports']    = { template: TPL + '/sms-reports.html',      title: 'SMS Reports',    menu: 'sms-reports' };
    ROUTES[ADMIN + '/sms-logs']      = { template: TPL + '/sms-logs.html',      title: 'SMS Logs',       menu: 'sms-logs' };

    // ---------------------------------------------------------------
    // New Life modules (templates/ and api/ files named student-, certificate-, practical-, web-; tables in training_XXXXXX)
    // ---------------------------------------------------------------
    var NL_JS = [(paths.ROOT || '') + '/assets/js/nl-helpers.js'];
    var NL_CERT_JS = NL_JS.concat([(paths.ROOT || '') + '/assets/js/certificate-print.js']);

    // Training -> Students
    ROUTES[ADMIN + '/students/courses']    = { template: TPL + '/student-courses.html',    title: 'Course Setup',       menu: 'courses',            scripts: NL_JS };
    ROUTES[ADMIN + '/students/admission']  = { template: TPL + '/student-admission.html',  title: 'New Admission',      menu: 'student-admission',  scripts: NL_JS };
    ROUTES[ADMIN + '/students/list']       = { template: TPL + '/student-list.html',           title: 'Student List',       menu: 'students-list',      scripts: NL_JS };
    ROUTES[ADMIN + '/students/view']       = { template: TPL + '/student-view.html',       title: 'Student Details',    menu: 'students-list',      scripts: NL_JS };
    ROUTES[ADMIN + '/students/payments']   = { template: TPL + '/student-payments.html',   title: 'Fee Collection',     menu: 'student-payments',   scripts: NL_JS };
    ROUTES[ADMIN + '/students/report-due'] = { template: TPL + '/student-report-due.html', title: 'Student Due Report', menu: 'report-student-due', scripts: NL_JS };

    // Training -> Certificates
    ROUTES[ADMIN + '/certificates']        = { template: TPL + '/certificate-list.html',       title: 'Student Certificates', menu: 'certificate-manage', scripts: NL_CERT_JS };
    ROUTES[ADMIN + '/certificates/old']    = { template: TPL + '/certificate-old.html',      title: 'Old Certificates',     menu: 'old-certificates',   scripts: NL_JS };

    // Training -> Practical Jobs (job fee is taken from the student's deposit)
    var NL_PRACTICAL_JS = NL_JS.concat([(paths.ROOT || '') + '/assets/js/practical-print.js']);
    ROUTES[ADMIN + '/practical/categories'] = { template: TPL + '/practical-categories.html', title: 'Job Price & Limits', menu: 'practical-categories', scripts: NL_JS };
    ROUTES[ADMIN + '/practical/jobs']       = { template: TPL + '/practical-jobs.html',       title: 'Daily Job Register', menu: 'practical-jobs',       scripts: NL_PRACTICAL_JS };
    ROUTES[ADMIN + '/practical/dashboard']  = { template: TPL + '/practical-dashboard.html',  title: 'Practical Dashboard',  menu: 'practical-dashboard',  scripts: NL_PRACTICAL_JS };
    ROUTES[ADMIN + '/practical/returns']    = { template: TPL + '/practical-returns.html',    title: 'Job Return / Retrain', menu: 'practical-returns',    scripts: NL_PRACTICAL_JS };
    ROUTES[ADMIN + '/practical/out']        = { template: TPL + '/practical-out.html',        title: 'Out of Student',       menu: 'practical-out',        scripts: NL_PRACTICAL_JS };
    ROUTES[ADMIN + '/practical/store']      = { template: TPL + '/practical-store.html',      title: 'Store Management',     menu: 'practical-store',      scripts: NL_PRACTICAL_JS };
    ROUTES[ADMIN + '/practical/companies']  = { template: TPL + '/practical-companies.html',  title: 'Companies',            menu: 'practical-companies',  scripts: NL_JS };
    ROUTES[ADMIN + '/practical/analytics']  = { template: TPL + '/practical-analytics.html',  title: 'Analytics & Reports',  menu: 'practical-analytics',  scripts: NL_PRACTICAL_JS };

    // Website content (Settings -> General Settings)
    ROUTES[ADMIN + '/website/branding']    = { template: TPL + '/web-branding.html', title: 'Logo & Branding',  menu: 'web-branding', scripts: NL_JS };
    ROUTES[ADMIN + '/website/sliders']     = { template: TPL + '/web-sliders.html',  title: 'Hero Sliders',     menu: 'web-sliders',  scripts: NL_JS };
    ROUTES[ADMIN + '/website/videos']      = { template: TPL + '/web-videos.html',   title: 'Videos',           menu: 'web-videos',   scripts: NL_JS };
    ROUTES[ADMIN + '/website/reviews']     = { template: TPL + '/web-reviews.html',  title: 'Reviews',          menu: 'web-reviews',  scripts: NL_JS };
    ROUTES[ADMIN + '/website/programs']    = { template: TPL + '/web-programs.html', title: 'Programs',         menu: 'web-programs', scripts: NL_JS };
    ROUTES[ADMIN + '/website/messages']    = { template: TPL + '/web-messages.html', title: 'Contact Messages', menu: 'web-messages', scripts: NL_JS };

    // Additions to HR / Attendance / Settings
    ROUTES[ADMIN + '/hr/employee-documents'] = { template: TPL + '/hr-employee-documents.html', title: 'Employee Documents', menu: 'employee-documents', scripts: NL_JS };
    ROUTES[ADMIN + '/attendance/import']     = { template: TPL + '/att-device-import.html',  title: 'Device Import',      menu: 'att-import',         scripts: NL_JS };
    ROUTES[ADMIN + '/general-settings']      = { template: TPL + '/settings-general.html',   title: 'General Configuration', menu: 'general-settings',   scripts: NL_JS };

    window.ROUTES = ROUTES;
    window.DEFAULT_ROUTE = ADMIN + '/dashboard';
})(window);
