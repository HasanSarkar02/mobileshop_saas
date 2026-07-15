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

// ─── Super Admin ──────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->group(function () {

    // Guest-facing (login page) — no middleware, controller handles already-logged-in redirect
    Route::get('login', [AdminLoginController::class, 'create'])->name('login');
    Route::post('login', [AdminLoginController::class, 'store'])->name('login.store');

    // Protected admin routes — must be authenticated via the admin guard
    Route::middleware('super_admin')->group(function () {
        Route::post('logout', [AdminLoginController::class, 'destroy'])->name('logout');
        Route::get('dashboard', Dashboard::class)->name('dashboard');
        Route::get('shops', ShopList::class)->name('shops.index');
        Route::get('shops/create', CreateShop::class)->name('shops.create');
        Route::get('shops/{shop}', ShopDetail::class)->name('shops.show');
        Route::post('impersonate/{user}', [ImpersonationController::class, 'start'])->name('impersonate.start');
    });
});

// ─── Shop App (Owner + Employee) ──────────────────────────────────────────────
// ─── Shop App (Owner + Employee) ──────────────────────────────────────────────
Route::middleware(['auth:web'])->group(function () {

    Route::get('/', fn() => redirect()->route('dashboard'));
    Route::livewire('dashboard', \App\Livewire\Dashboard::class)->name('dashboard');

    // Impersonation stop
    Route::post('impersonation/stop', [ImpersonationController::class, 'stop'])->name('impersonation.stop');

    // Products
    Route::livewire('products', ProductList::class)->name('products.index');
    Route::livewire('products/create', ProductForm::class)->name('products.create');
    Route::livewire('products/{product}/edit', ProductForm::class)->name('products.edit');
    Route::livewire('products/{product}', ProductDetail::class)->name('products.show');

    // Suppliers
    Route::livewire('suppliers', SupplierList::class)->name('suppliers.index');
    Route::livewire('suppliers/create', SupplierForm::class)->name('suppliers.create');
    Route::livewire('suppliers/{supplier}/edit', SupplierForm::class)->name('suppliers.edit');
    Route::livewire('suppliers/{supplier}', \App\Livewire\Suppliers\SupplierProfile::class)->name('suppliers.show');

    // Purchases
    Route::livewire('purchases', PurchaseList::class)->name('purchases.index');
    Route::livewire('purchases/create', CreatePurchase::class)->name('purchases.create');
    Route::livewire('purchases/{purchase}', PurchaseDetail::class)->name('purchases.show');
    Route::livewire('purchases/{purchase}/return',\App\Livewire\Purchases\ProcessPurchaseReturn::class)->name('purchases.return');

    // Settings
    Route::livewire('settings', ShopSettings::class)->name('settings');
    Route::livewire('settings/activity-log', \App\Livewire\Settings\ActivityLogViewer::class)->name('settings.activity-log');


    // Customers
    Route::livewire('customers', CustomerList::class)->name('customers.index');
    Route::livewire('customers/create', CustomerForm::class)->name('customers.create');
    Route::livewire('customers/{customer}/edit', CustomerForm::class)->name('customers.edit');
    Route::livewire('customers/{customer}', CustomerProfile::class)->name('customers.show');

    // POS
    Route::livewire('pos', \App\Livewire\Pos::class)->name('pos');

    // Sales
    Route::livewire('sales', SaleList::class)->name('sales.index');
    Route::get('sales/{sale}/receipt', [SaleReceiptController::class, 'show'])->name('sales.receipt');


    // Finance Partners 
    Route::livewire('finance-partners', FinancePartnerDashboard::class)->name('finance-partners.index');
    Route::livewire('finance-partners/{partner}/settlement', RecordSettlement::class)->name('finance-partners.record-settlement');


    //Sale Details and Returns
    Route::livewire('sales/{sale}', SaleDetail::class)->name('sales.show');
    Route::livewire('sales/{sale}/return', ProcessReturn::class)->name('sales.return');


    // Used Phones
    Route::livewire('used-phones', UsedPhoneList::class)->name('used-phones.index');
    Route::livewire('used-phones/buy', RecordUsedPhone::class)->name('used-phones.create');
    Route::livewire('used-phones/{acquisition}', UsedPhoneDetail::class)->name('used-phones.show');



    // Expenses
    Route::livewire('expenses', ExpenseList::class)->name('expenses.index');
    Route::livewire('expenses/create', ExpenseForm::class)->name('expenses.create');

    // Payroll
    // Route::livewire('payroll', PayrollDashboard::class)->name('payroll.index');

    Route::prefix('payroll')->name('payroll.')->group(function () {
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

        Route::livewire('payroll/employees', EmployeeProfileList::class)->name('payroll.employees');
        Route::livewire('payroll/{run}', ManagePayroll::class)->name('payroll.manage');


    // Employees
    Route::livewire('employees', EmployeeList::class)->name('employees.index');
    Route::livewire('employees/create', EmployeeForm::class)->name('employees.create');
    Route::livewire('employees/{employee}/edit', EmployeeForm::class)->name('employees.edit');
    Route::livewire('employees/{employee}', EmployeeDetail::class)->name('employees.show');



    // Service Module
    Route::livewire('service', ServiceList::class)->name('service.index');
    Route::livewire('service/create', ServiceTicketForm::class)->name('service.create');
    Route::livewire('service/{ticket}/edit', ServiceTicketForm::class)->name('service.edit');
    Route::livewire('service/{ticket}', ServiceTicketDetail::class)->name('service.show');



    Route::prefix('reports')->name('reports.')->group(function () {
    // existing:
    Route::livewire('profit-loss',     ProfitLossReport::class)->name('pl');
    Route::livewire('sales',           SalesReport::class)->name('sales');
    Route::livewire('stock-valuation', StockValuationReport::class)->name('stock');
    Route::livewire('customer-due',    CustomerDueReport::class)->name('customer-due');

    // NEW:
    Route::livewire('expenses',        \App\Livewire\Reports\ExpenseReport::class)->name('expenses');
    Route::livewire('service',         \App\Livewire\Reports\ServiceReport::class)->name('service');
    Route::livewire('used-phones',     \App\Livewire\Reports\UsedPhoneReport::class)->name('used-phones');
    Route::livewire('imei-ledger',     \App\Livewire\Reports\ImeiLedgerReport::class)->name('imei-ledger');

    // Print/PDF/CSV:
    Route::get('profit-loss/print',    [DocumentController::class, 'profitLossPrint'])->name('pl.print');
    Route::get('profit-loss/pdf',      [DocumentController::class, 'profitLossPdf'])->name('pl.pdf');
    Route::get('profit-loss/csv',      [DocumentController::class, 'profitLossCsv'])->name('pl.csv');
    Route::get('expenses/print',       [DocumentController::class, 'expenseReportPrint'])->name('expenses.print');
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
    Route::prefix('reports')->name('reports.')->group(function () {
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
    });


    Route::prefix('treasury')->name('treasury.')->group(function () {
        Route::livewire('',                TreasuryDashboard::class)->name('index');
        Route::livewire('create',          TreasuryTransactionForm::class)->name('create');
        Route::livewire('{transaction}',   TreasuryTransactionDetail::class)->name('show');
        Route::livewire('{transaction}/edit', \App\Livewire\Treasury\TreasuryTransactionEdit::class)->name('edit');
        Route::livewire('opening-balance',\App\Livewire\Treasury\OpeningBalanceWizard::class)->name('opening-balance');
    });

    //Notifications
    Route::livewire('notifications', NotificationCenter::class)->name('notifications.index');
    Route::livewire('notifications/preferences', NotificationPreferences::class)->name('notifications.preferences');

    
});