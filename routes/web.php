<?php

use Illuminate\Support\Facades\Route;

use App\Livewire\Admin\CreateShop;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\ShopDetail;
use App\Livewire\Admin\ShopList;
use App\Livewire\Purchases\CreatePurchase;
use App\Livewire\Purchases\PurchaseDetail;
use App\Livewire\Purchases\PurchaseList;
use App\Livewire\Suppliers\SupplierForm;
use App\Livewire\Suppliers\SupplierList;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\AdminTwoFactorSettingsController;
use App\Livewire\Products\ProductDetail;
use App\Livewire\Products\ProductForm;
use App\Livewire\Products\ProductList;
use App\Livewire\Settings\ShopSettings;
use App\Livewire\Customers\CustomerForm;
use App\Livewire\Customers\CustomerList;
use App\Livewire\Customers\CustomerProfile;
use App\Http\Controllers\SaleReceiptController;
use App\Livewire\Sales\SaleList;
use App\Livewire\FinancePartners\FinancePartnerDashboard;
use App\Livewire\FinancePartners\RecordSettlement;
use App\Livewire\Sales\SaleDetail;
use App\Livewire\Sales\ProcessReturn;
use App\Livewire\UsedPhones\UsedPhoneList;
use App\Livewire\UsedPhones\RecordUsedPhone;
use App\Livewire\UsedPhones\UsedPhoneDetail;
use App\Livewire\Expenses\ExpenseForm;
use App\Livewire\Expenses\ExpenseList;
use App\Livewire\Payroll\EmployeeProfileList;
use App\Livewire\Payroll\ManagePayroll;
use App\Livewire\Payroll\PayrollDashboard;
use App\Livewire\Employees\EmployeeDetail;
use App\Livewire\Employees\EmployeeForm;
use App\Livewire\Employees\EmployeeList;
use App\Livewire\Service\ServiceList;
use App\Livewire\Service\ServiceTicketDetail;
use App\Livewire\Service\ServiceTicketForm;
use App\Livewire\Reports\CustomerDueReport;
use App\Livewire\Reports\ProfitLossReport;
use App\Livewire\Reports\SalesReport;
use App\Livewire\Reports\StockValuationReport;
use App\Http\Controllers\DocumentController;
use App\Livewire\Treasury\TreasuryDashboard;
use App\Livewire\Treasury\TreasuryTransactionDetail;
use App\Livewire\Treasury\TreasuryTransactionForm;
use App\Livewire\Payroll\PayrollRunList;
use App\Livewire\Payroll\GeneratePayrollRun;
use App\Livewire\Payroll\PayrollRunDetail;
use App\Livewire\Payroll\PayrollSlipDetail;
use App\Livewire\Payroll\PayEmployeesForm;
use App\Livewire\Payroll\DepartmentManager;
use App\Livewire\Payroll\PayrollComponentManager;
use App\Livewire\Payroll\PayrollPolicyManager;
use App\Livewire\Payroll\EmployeeSalarySetup;
use App\Livewire\Payroll\PayrollLoanList;
use App\Livewire\Notifications\NotificationCenter;
use App\Livewire\Notifications\NotificationPreferences;
use App\Livewire\Payroll\PayrollReports;
use App\Livewire\Settings\{SmtpSettings, NotificationTemplateList, NotificationTemplateForm, NotificationRuleList, NotificationRuleForm};

use App\Http\Controllers\Api\DeviceTokenController;
use App\Livewire\Products\ProductLabelPrint;
use App\Livewire\Sms\SmsLogViewer;
use App\Livewire\SuperAdmin\PlatformSettings;
use App\Models\UserPushToken;
use App\Services\Notifications\Channels\FirebasePushProvider;
use App\Services\Notifications\Providers\FirebasePushProvider as ProvidersFirebasePushProvider;

// ─── Super Admin ──────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->group(function () {

    // Guest-facing (login page) — no middleware, controller handles already-logged-in redirect
    Route::get('login', [AdminLoginController::class, 'create'])->name('login');
    Route::post('login', [AdminLoginController::class, 'store'])->name('login.store');
    // 2FA challenge — no auth guard yet (pending login)
    Route::get('/2fa/challenge', [AdminLoginController::class, 'showTwoFactorChallenge'])->name('2fa.challenge');
    Route::post('/2fa/challenge', [AdminLoginController::class, 'verifyTwoFactorChallenge'])->name('2fa.verify');


    // Protected admin routes — must be authenticated via the admin guard
    Route::middleware('super_admin')->group(function () {
        Route::post('logout', [AdminLoginController::class, 'destroy'])->name('logout');
        Route::get('/2fa/recovery-codes', [AdminLoginController::class, 'showRecoveryCodesOnce'])->name('2fa.recovery-codes.show');
        Route::post('/2fa/regenerate-recovery-codes', [AdminTwoFactorSettingsController::class, 'regenerateRecoveryCodes'])->name('2fa.regenerate');
        Route::get('dashboard', Dashboard::class)->name('dashboard');
        Route::get('shops/create', CreateShop::class)->name('shops.create');
        Route::get('shops/{shop}', ShopDetail::class)->name('shops.show');
        Route::get('/settings', \App\Livewire\SuperAdmin\PlatformSettings::class)->name('settings');
        Route::get('/impersonation-logs', \App\Livewire\SuperAdmin\ImpersonationLogList::class)->name('impersonation-logs');
        Route::get('/announcements', \App\Livewire\SuperAdmin\AnnouncementManager::class)->name('announcements');
        Route::post('impersonate/{user}', [ImpersonationController::class, 'start'])->name('impersonate.start');
        Route::get('billing', \App\Livewire\SuperAdmin\BillingDashboard::class)->name('billing');
        //Route::get('/',        \App\Livewire\Admin\ShopList::class)->name('dashboard');
        Route::get('/shops',   \App\Livewire\Admin\ShopList::class)->name('shops');
        Route::get('/shops/{shop}', \App\Livewire\Admin\ShopDetail::class)->name('shops.show');
        Route::get('/billing', \App\Livewire\SuperAdmin\BillingDashboard::class)->name('billing');
        Route::get('/plans',   \App\Livewire\SuperAdmin\PlanManager::class)->name('plans');
        Route::get('/invoices',\App\Livewire\SuperAdmin\InvoiceList::class)->name('invoices');
        Route::get('/shops/{shop}/features',\App\Livewire\SuperAdmin\ShopFeatureManager::class)->name('shop-features');
    });
});

// ─── Shop App (Owner + Employee) ──────────────────────────────────────────────
Route::middleware(['auth:web'])->group(function () {

    Route::get('/', fn() => redirect()->route('dashboard'));
    Route::livewire('dashboard', \App\Livewire\Dashboard::class)->name('dashboard');
    Route::get('/profile', \App\Livewire\Profile\OwnerProfile::class)->name('profile');

    // Impersonation stop
    Route::post('impersonation/stop', [ImpersonationController::class, 'stop'])->name('impersonation.stop')->middleware('impersonation.timeout');

    // Products
    Route::prefix('products')->name('products.')->middleware('feature:inventory')->group(function () {
        Route::livewire('/', ProductList::class)->name('index');
        Route::livewire('/create', ProductForm::class)->name('create');
        Route::livewire('/{product}/edit', ProductForm::class)->name('edit');
        Route::livewire('/{product}', ProductDetail::class)->name('show');
    });
    // Suppliers
    Route::prefix('suppliers')->name('suppliers.')->middleware('feature:suppliers')->group(function () {
        Route::livewire('/', SupplierList::class)->name('index');
        Route::livewire('/create', SupplierForm::class)->name('create');
        Route::livewire('/{supplier}/edit', SupplierForm::class)->name('edit');
        Route::livewire('/{supplier}', \App\Livewire\Suppliers\SupplierProfile::class)->name('show');
    });
    // Purchases
    Route::prefix('purchases')->name('purchases.')->middleware('feature:purchases')->group(function () {
        Route::livewire('/', PurchaseList::class)->name('index');
        Route::livewire('/create', CreatePurchase::class)->name('create');
        Route::livewire('/{purchase}', PurchaseDetail::class)->name('show');
        Route::livewire('/{purchase}/return',\App\Livewire\Purchases\ProcessPurchaseReturn::class)->name('return');
    });

    // Settings
    Route::livewire('settings', ShopSettings::class)->name('settings')->middleware('feature:settings');
    Route::livewire('settings/activity-log', \App\Livewire\Settings\ActivityLogViewer::class)->name('settings.activity-log')->middleware('feature:settings');
    Route::get('settings/subscription',\App\Livewire\Settings\MySubscription::class)->name('settings.subscription');

    Route::livewire('settings/notifications/smtp', SmtpSettings::class)->name('settings.smtp');
    Route::livewire('settings/notifications/templates', NotificationTemplateList::class)->name('settings.notification-templates');
    Route::livewire('settings/notifications/templates/{eventType}/{channel}/edit', NotificationTemplateForm::class)->name('settings.notification-templates.edit');
    Route::livewire('settings/notifications/rules', NotificationRuleList::class)->name('settings.notification-rules');
    Route::livewire('settings/notifications/rules/create', NotificationRuleForm::class)->name('settings.notification-rules.create');
    Route::livewire('settings/notifications/rules/{rule}/edit', NotificationRuleForm::class)->name('settings.notification-rules.edit');

    Route::get('/sms/logs', SmsLogViewer::class)->name('sms.logs');


    // Customers
    Route::prefix('customers')->name('customers.')->middleware('feature:customers')->group(function () {
        Route::livewire('/', CustomerList::class)->name('index');
        Route::livewire('/create', CustomerForm::class)->name('create');
        Route::livewire('/{customer}/edit', CustomerForm::class)->name('edit');
        Route::livewire('/{customer}', CustomerProfile::class)->name('show');
    });
    
    // POS
    Route::livewire('pos', \App\Livewire\Pos::class)->name('pos')->middleware('feature:pos');

    // Sales
    Route::prefix('sales')->name('sales.')->middleware('feature:sales')->group(function () {
        Route::livewire('/', SaleList::class)->name('index');
        Route::get('/{sale}/receipt', [SaleReceiptController::class, 'show'])->name('receipt');
        Route::livewire('/{sale}', SaleDetail::class)->name('show');
        Route::livewire('/{sale}/return', ProcessReturn::class)->name('return');
    });

    // Finance Partners 
    Route::prefix('finance-partners')->name('finance-partners.')->middleware('feature:emi_partners')->group(function () {
        Route::livewire('/', FinancePartnerDashboard::class)->name('index');
        Route::livewire('/{partner}/settlement', RecordSettlement::class)->name('record-settlement');
    });


    // Used Phones
    Route::prefix('used-phones')->name('used-phones.')->middleware('feature:used_phones')->group(function () {
        Route::livewire('/', UsedPhoneList::class)->name('index');
        Route::livewire('/buy', RecordUsedPhone::class)->name('create');
        Route::livewire('/{acquisition}', UsedPhoneDetail::class)->name('show');
    });


    // Expenses
    Route::prefix('expenses')->name('expenses.')->middleware('feature:expenses')->group(function () {
        Route::livewire('/', ExpenseList::class)->name('index');
        Route::livewire('/create', ExpenseForm::class)->name('create');
    });
    // Payroll
    // Route::livewire('payroll', PayrollDashboard::class)->name('payroll.index');

    Route::prefix('payroll')->name('payroll.')->middleware('feature:payroll')->group(function () {
        Route::get('/',                       PayrollDashboard::class)->name('index');
        Route::get('/runs',                   PayrollRunList::class)->name('runs');
        Route::get('/runs/generate',          GeneratePayrollRun::class)->name('generate');
        Route::get('/runs/{run}',             PayrollRunDetail::class)->name('run.show');
        Route::get('/slips/{slip}',           PayrollSlipDetail::class)->name('slip.show');
        Route::get('/pay/{slip}',             PayEmployeesForm::class)->name('pay');
        Route::get('/setup/departments',      DepartmentManager::class)->name('departments');
        Route::get('/setup/components',       PayrollComponentManager::class)->name('components');
        Route::get('/setup/policies',         PayrollPolicyManager::class)->name('policies');
        Route::get('/setup/salary/{user}',    EmployeeSalarySetup::class)->name('salary.setup');
        Route::get('/reports',                PayrollReports::class)->name('reports');
        Route::get('/loans',                  PayrollLoanList::class)->name('loans');
    });

        // Route::livewire('payroll/employees', EmployeeProfileList::class)->name('payroll.employees');
        // Route::livewire('payroll/{run}', ManagePayroll::class)->name('payroll.manage');


    // Employees
    Route::prefix('employees')->name('employees.')->middleware('feature:employees')->group(function () {
        Route::livewire('/', EmployeeList::class)->name('index');
        Route::livewire('/create', EmployeeForm::class)->name('create');
        Route::livewire('/{employee}/edit', EmployeeForm::class)->name('edit');
        Route::livewire('/{employee}', EmployeeDetail::class)->name('show');
    });


    // Service Module
    Route::prefix('service')->name('service.')->middleware('feature:service')->group(function () {
        Route::livewire('/', ServiceList::class)->name('index');
        Route::livewire('/create', ServiceTicketForm::class)->name('create');
        Route::livewire('/{ticket}/edit', ServiceTicketForm::class)->name('edit');
        Route::livewire('/{ticket}', ServiceTicketDetail::class)->name('show');
    });


    Route::prefix('reports')->name('reports.')->group(function () {
        Route::livewire('profit-loss',     ProfitLossReport::class)->name('pl');
        Route::livewire('sales',           SalesReport::class)->name('sales');
        Route::livewire('stock-valuation', StockValuationReport::class)->name('stock');
        Route::livewire('customer-due',    CustomerDueReport::class)->name('customer-due');

        Route::livewire('expenses',        \App\Livewire\Reports\ExpenseReport::class)->name('expenses');
        Route::livewire('service',         \App\Livewire\Reports\ServiceReport::class)->name('service');
        Route::livewire('used-phones',     \App\Livewire\Reports\UsedPhoneReport::class)->name('used-phones');
        Route::livewire('imei-ledger',     \App\Livewire\Reports\ImeiLedgerReport::class)->name('imei-ledger');

        // Print/PDF/CSV:
        Route::get('profit-loss/print',    [DocumentController::class, 'profitLossPrint'])->name('pl.print');
        Route::get('profit-loss/pdf',      [DocumentController::class, 'profitLossPdf'])->name('pl.pdf');
        Route::get('profit-loss/csv',      [DocumentController::class, 'profitLossCsv'])->name('pl.csv');
        Route::get('expenses/print',       [DocumentController::class, 'expenseReportPrint'])->name('expenses.print');

        Route::get('trial-balance',    \App\Livewire\Reports\TrialBalanceReport::class)->name('trial-balance');
        Route::get('balance-sheet',    \App\Livewire\Reports\BalanceSheetReport::class)->name('balance-sheet');
        Route::get('general-ledger',   \App\Livewire\Reports\GeneralLedgerReport::class)->name('general-ledger');
        Route::get('supplier-ledger',  \App\Livewire\Reports\SupplierLedgerReport::class)->name('supplier-ledger');
    
    });



    // ── Documents ─────────────────────────────────────────────────────────────
    Route::prefix('documents')->name('documents.')->group(function () {

        // Sale Invoice
        Route::get('sale/{sale}',         [DocumentController::class, 'saleInvoice'])->name('sale');
        Route::get('sale/{sale}/pdf',     [DocumentController::class, 'saleInvoicePdf'])->name('sale.pdf');

        // Credit Note
        Route::get('credit-note/{creditNote}',     [DocumentController::class, 'creditNote'])->name('credit-note');
        Route::get('credit-note/{creditNote}/pdf', [DocumentController::class, 'creditNotePdf'])->name('credit-note.pdf');

        // Purchase Invoice
        Route::get('purchase/{purchase}',     [DocumentController::class, 'purchaseInvoice'])->name('purchase');
        Route::get('purchase/{purchase}/pdf', [DocumentController::class, 'purchaseInvoicePdf'])->name('purchase.pdf');

        // Service Invoice
        Route::get('service/{ticket}',     [DocumentController::class, 'serviceInvoice'])->name('service-invoice');
        Route::get('service/{ticket}/pdf', [DocumentController::class, 'serviceInvoicePdf'])->name('service-invoice.pdf');

        // Warranty Slip
        Route::get('warranty/{unit}', [DocumentController::class, 'warrantySlip'])->name('warranty');

        // Payroll Sheet
        Route::get('payroll/{run}',     [DocumentController::class, 'payrollSheet'])->name('payroll');
        Route::get('payroll/{run}/pdf', [DocumentController::class, 'payrollSheetPdf'])->name('payroll.pdf');

        // Used Phone Receipt
        Route::get('used-phone/{acquisition}',     [DocumentController::class, 'usedPhoneReceipt'])->name('used-phone');
        Route::get('used-phone/{acquisition}/pdf', [DocumentController::class, 'usedPhoneReceiptPdf'])->name('used-phone.pdf');

        // Supplier Statement
        Route::get('documents/supplier/{supplier}/statement',[\App\Http\Controllers\DocumentController::class, 'supplierStatementPrint'])->name('supplier-statement');
        Route::get('documents/supplier/{supplier}/statement/pdf',[\App\Http\Controllers\DocumentController::class, 'supplierStatementPdf'])->name('supplier-statement.pdf');
        
        //Payroll Slip
        Route::get('documents/payroll-slip/{slip}',[\App\Http\Controllers\DocumentController::class, 'payrollSlipPrint'])->name('payroll-slip');
        Route::get('documents/payroll-slip/{slip}/pdf',[\App\Http\Controllers\DocumentController::class, 'payrollSlipPdf'])->name('payroll-slip.pdf');

        // Payroll Register
        Route::get('documents/payroll-register/{run}',[\App\Http\Controllers\DocumentController::class, 'payrollRegisterPrint'])->name('payroll-register');
        Route::get('documents/payroll-register/{run}/pdf',[\App\Http\Controllers\DocumentController::class, 'payrollRegisterPdf'])->name('payroll-register.pdf');
    });

    // ── Report Print/Export ────────────────────────────────────────────────────
    Route::prefix('reports')->name('reports.')->middleware('feature:reports')->group(function () {
        // P&L
        Route::get('profit-loss/print', [DocumentController::class, 'profitLossPrint'])->name('pl.print');
        Route::get('profit-loss/pdf',   [DocumentController::class, 'profitLossPdf'])->name('pl.pdf');
        Route::get('profit-loss/csv',   [DocumentController::class, 'profitLossCsv'])->name('pl.csv');

        Route::livewire('account-statement',\App\Livewire\Reports\AccountStatementReport::class)->name('account-statement');
        Route::livewire('cash-flow',\App\Livewire\Reports\CashFlowReport::class)->name('cash-flow');
        // Print / PDF / CSV
        Route::get('account-statement/print',[\App\Http\Controllers\DocumentController::class, 'accountStatementPrint'])->name('account-statement.print');
        Route::get('account-statement/pdf',[\App\Http\Controllers\DocumentController::class, 'accountStatementPdf'])->name('account-statement.pdf');
        Route::get('cash-flow/print',[\App\Http\Controllers\DocumentController::class, 'cashFlowPrint'])->name('cash-flow.print');

        Route::get('sales/print',            [DocumentController::class, 'salesReportPrint'])->name('sales.print');
        Route::get('stock-valuation/print',  [DocumentController::class, 'stockValuationPrint'])->name('stock.print');
        Route::get('customer-due/print',     [DocumentController::class, 'customerDuePrint'])->name('customer-due.print');
        Route::get('expenses/print',         [DocumentController::class, 'expenseReportPrint'])->name('expenses.print');
        Route::get('service/print',          [DocumentController::class, 'serviceReportPrint'])->name('service.print');
        Route::get('imei-ledger/print',      [DocumentController::class, 'imeiLedgerPrint'])->name('imei-ledger.print');
        Route::get('trial-balance/print',  [DocumentController::class, 'trialBalancePrint'])->name('trial-balance.print');
        Route::get('balance-sheet/print',  [DocumentController::class, 'balanceSheetPrint'])->name('balance-sheet.print');
        Route::get('general-ledger/print', [DocumentController::class, 'generalLedgerPrint'])->name('general-ledger.print');
    });


    Route::prefix('treasury')->name('treasury.')->middleware('feature:treasury')->group(function () {
        Route::livewire('',                TreasuryDashboard::class)->name('index');
        Route::livewire('create',          TreasuryTransactionForm::class)->name('create');
        Route::livewire('{transaction}',   TreasuryTransactionDetail::class)->name('show');
        Route::livewire('{transaction}/edit', \App\Livewire\Treasury\TreasuryTransactionEdit::class)->name('edit');
        Route::livewire('opening-balance',\App\Livewire\Treasury\OpeningBalanceWizard::class)->name('opening-balance');
    });

    //Notifications
    Route::livewire('notifications', NotificationCenter::class)->name('notifications.index');
    Route::livewire('notifications/preferences', NotificationPreferences::class)->name('notifications.preferences');
    Route::get('inventory/adjustments',\App\Livewire\Inventory\StockAdjustmentLog::class)->name('inventory.adjustments')->middleware('feature:inventory');

    Route::prefix('api')->middleware('auth')->group(function () {
        Route::post('/device-token', [DeviceTokenController::class, 'register']);
        Route::delete('/device-token', [DeviceTokenController::class, 'remove']);
    });

    Route::get('/products/{product}/labels/print', ProductLabelPrint::class)->name('products.labels.print');


});