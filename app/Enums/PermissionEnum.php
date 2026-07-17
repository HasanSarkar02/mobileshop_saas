<?php

namespace App\Enums;

enum PermissionEnum: string
{
    // Dashboard
    case DashboardView = 'dashboard.view';

    // Products & Inventory
    case ProductsView = 'products.view';
    case ProductsManage = 'products.manage';
    case StockAdjust = 'stock.adjust';
    case StockTransfer = 'stock.transfer';
    case StockConfirmTransfer = 'stock.confirm_transfer'; 
    case InventoryView      = 'inventory.view';
    case InventoryCreate    = 'inventory.create';
    case InventoryEdit      = 'inventory.edit';
    case InventoryDelete    = 'inventory.delete';

    // IMEI / Serialized Units
    case ImeiView = 'imei.view';
    case ImeiCorrect = 'imei.correct'; // edit an IMEI after entry — high risk, keep restricted
    case ImeiChangeStatus = 'imei.change_status'; // flag lost / stolen / defective
    case ImeiBulkImport = 'imei.bulk_import'; // register IMEIs while receiving stock

    // Branches
    case BranchesManage = 'branches.manage';

    // POS / Sales
    case SalesCreate = 'sales.create';
    case SalesView = 'sales.view';
    case SalesVoid = 'sales.void';
    case SalesRefund = 'sales.refund';
    case SalesApplyDiscount = 'sales.apply_discount';
    case SalesExport        = 'sales.export';

    // Customers
    case CustomersView = 'customers.view';
    case CustomersManage = 'customers.manage';
    case CustomersRecordDuePayment = 'customers.record_due_payment';
    case CustomersWriteOffDue = 'customers.write_off_due';

    // Suppliers & Purchases
    case SuppliersManage = 'suppliers.manage';
    case SuppliersPayment   = 'suppliers.payment';
    case PurchasesView = 'purchases.view';
    case PurchasesCreate = 'purchases.create';
    case PurchasesApprove = 'purchases.approve';
    case PurchasesManage    = 'purchases.manage';
    case PurchasesReturn    = 'purchases.return';

    case FinancePartnersWriteOff = 'finance_partners.write_off'; // shortfall / bad debt — sensitive

    // Warranty
    case WarrantyView = 'warranty.view';
    case WarrantyApproveClaim = 'warranty.approve_claim'; // approve as free / covered
    case WarrantyClaimSupplier = 'warranty.claim_supplier'; // initiate supplier RMA

    // Service / Repair
    case ServiceView = 'service.view';
    case ServiceManage = 'service.manage';
    case ServicePayment     = 'service.payment';

    // Expenses
    case ExpensesView = 'expenses.view';
    case ExpensesCreate = 'expenses.create';
    case ExpensesApprove = 'expenses.approve';
    case ExpensesVoid       = 'expenses.void';
    case ExpensesExport     = 'expenses.export';

    // Used Phones
    case UsedPhonesView     = 'used_phones.view';
    case UsedPhonesManage   = 'used_phones.manage';

    // Finance Partners
    case EmiView            = 'emi.view';
    case EmiSettle          = 'emi.settle';

    // Payroll
    case PayrollManage = 'payroll.manage';

    // Accounting & Reports
    case AccountingViewBasicReports = 'accounting.view_basic_reports'; // daily sales, stock
    case AccountingViewFullReports = 'accounting.view_full_reports';   // P&L, balance sheet, ledgers
    case AccountingManageEntries = 'accounting.manage_entries';        // new manual entries
    case AccountingReverseEntry = 'accounting.reverse_entry';          // reverse a posted entry
    case AccountingManagePeriodLocks = 'accounting.manage_period_locks'; // lock/unlock a period
    case ReportsExport = 'reports.export';

    // Employees & Roles
    case EmployeesView = 'employees.view';
    case EmployeesManage = 'employees.manage';
    case EmployeesManagePermissions = 'employees.manage_permissions';
    case RolesManage = 'roles.manage'; 
    case EmployeesPermissions = 'employees.permissions';

    // ── Treasury ──────────────────────────────────────────────────────────────
    case TreasuryView       = 'treasury.view';
    case TreasuryTransfer   = 'treasury.create_transfer';   // A-type + petty cash
    case TreasuryEquity     = 'treasury.create_equity';     // B-type (capital, drawings)
    case TreasuryAdjust     = 'treasury.create_adjustment'; // C-type (cash over/short)
    case TreasuryBankFinance= 'treasury.create_bank_finance'; // D-type (bank, loans)
    case TreasuryApprove    = 'treasury.approve';
    case TreasuryReverse    = 'treasury.reverse';

        // Settings
    case SettingsManage = 'settings.manage';

    case NotificationsManageSettings = 'notifications.manage_settings';


    // ── Payroll (Enterprise) ───────────────────────────────────────────────────
    case PayrollView             = 'payroll.view';
    case PayrollGenerate         = 'payroll.generate';
    case PayrollReview           = 'payroll.review';
    case PayrollApprove          = 'payroll.approve';
    case PayrollPay              = 'payroll.pay';
    case PayrollReverse          = 'payroll.reverse';
    case PayrollManageStructure  = 'payroll.manage_structure';
    case PayrollManageComponents = 'payroll.manage_components';
    case PayrollManageLoans      = 'payroll.manage_loans';
    case PayrollExport           = 'payroll.export';
    case PayrollViewSalary       = 'payroll.view_salary';
    case PayrollManageDepartments= 'payroll.manage_departments';


    case NotificationsManageTemplates = 'notifications.manage_templates';
    case NotificationsManageRules = 'notifications.manage_rules';

    // NOTE: "Owner's Drawing" is intentionally NOT a permission here.
    // It's gated purely by $user->isOwner() in code — too sensitive to
    // ever risk assigning to an employee by mistake.

    public function label(): string
    {
        return match ($this) {
            self::DashboardView => 'View dashboard',
            self::ProductsView => 'View products',
            self::ProductsManage => 'Add / edit / delete products',
            self::InventoryView => 'View Inventory',
            self::InventoryCreate => 'Create Inventory',
            self::InventoryEdit => 'Edit Inventory Item',
            self::InventoryDelete => 'Delete Inventory Item',
            self::StockAdjust => 'Adjust stock quantity',
            self::StockTransfer => 'Transfer stock between branches',
            self::StockConfirmTransfer => 'Confirm receipt of a stock transfer',
            self::ImeiView => 'Look up units by IMEI',
            self::ImeiCorrect => 'Correct an IMEI number after entry',
            self::ImeiChangeStatus => 'Flag a unit as lost / stolen / defective',
            self::ImeiBulkImport => 'Register IMEIs while receiving stock',
            self::BranchesManage => 'Add / edit / deactivate branches',
            self::SalesCreate => 'Create sales (POS)',
            self::SalesView => 'View sales history',
            self::SalesVoid => 'Void / cancel a sale',
            self::SalesRefund => 'Process refunds & returns',
            self::SalesExport => 'Export Sales',
            self::SalesApplyDiscount => 'Apply special discount on sale',
            self::CustomersView => 'View customers',
            self::CustomersManage => 'Add / edit customers',
            self::CustomersRecordDuePayment => 'Record a customer due payment',
            self::CustomersWriteOffDue => "Write off a customer's bad debt",
            self::SuppliersManage => 'Manage suppliers',
            self::SuppliersPayment => 'Record Payment',
            self::PurchasesView => 'View purchases',
            self::PurchasesManage => 'Manage Purchase module',
            self::PurchasesReturn => 'Return purchased items',
            self::PurchasesCreate => 'Create purchase entries',
            self::PurchasesApprove => 'Approve purchase orders',
            self::FinancePartnersWriteOff => 'Write off a finance partner shortfall',
            self::WarrantyView => 'Check warranty eligibility / status',
            self::WarrantyApproveClaim => 'Approve a warranty repair claim',
            self::WarrantyClaimSupplier => 'Initiate a supplier RMA claim',
            self::ServiceView => 'View service / repair jobs',
            self::ServiceManage => 'Manage service / repair jobs',
            self::ServicePayment => 'Record payments',
            self::ExpensesView => 'View expenses',
            self::ExpensesCreate => 'Create expense entries',
            self::ExpensesApprove => 'Approve expenses',
            self::ExpensesVoid => 'Void an expense',
            self::ExpensesExport=> 'Export Expense Report',
            self::PayrollManage => 'Manage employee salary / payroll',
            self::AccountingViewBasicReports => 'View basic reports (daily sales, stock)',
            self::AccountingViewFullReports => 'View full financial reports (P&L, balance sheet)',
            self::AccountingManageEntries => 'Make manual accounting adjustments',
            self::AccountingReverseEntry => 'Reverse a posted journal entry',
            self::AccountingManagePeriodLocks => 'Lock / unlock an accounting period',
            self::ReportsExport => 'Export reports (PDF / Excel)',
            self::EmployeesView => 'View employees',
            self::EmployeesManage => 'Add / edit / activate / deactivate employees',
            self::EmployeesManagePermissions => 'Assign roles & permissions to employees',
            self::RolesManage => 'Create / edit / delete custom roles',
            self::TreasuryView        => 'View treasury transactions & cash position',
            self::TreasuryTransfer    => 'Create internal transfers (account, branch, bank, wallet)',
            self::TreasuryEquity      => 'Create owner capital, drawings & partner transactions',
            self::TreasuryAdjust      => 'Create cash adjustments (over/short/opening balance)',
            self::TreasuryBankFinance => 'Create bank charges, loans & interest entries',
            self::TreasuryApprove     => 'Approve or reject pending treasury transactions',
            self::TreasuryReverse     => 'Reverse completed treasury transactions',
            self::SettingsManage => 'Manage shop settings',
            self::NotificationsManageSettings => 'Manage notification channel & delivery settings',
            self::PayrollView             => 'View payroll runs and reports',
            self::PayrollGenerate         => 'Generate payroll runs',
            self::PayrollReview           => 'Submit payroll for review',
            self::PayrollApprove          => 'Approve or reject payroll',
            self::PayrollPay              => 'Process salary payments',
            self::PayrollReverse          => 'Reverse payroll payments or runs',
            self::PayrollManageStructure  => 'Setup employee salary structures',
            self::PayrollManageComponents => 'Manage payroll components and policies',
            self::PayrollManageLoans      => 'Disburse and manage employee loans',
            self::PayrollExport           => 'Export payroll reports and CSV',
            self::PayrollViewSalary       => 'View sensitive salary amounts',
            self::PayrollManageDepartments=> 'Create and manage departments',
            self::UsedPhonesView => 'View Used Phones',
            self::UsedPhonesManage => 'Manage Used phone entry/edit',
            self::EmiView => 'Manage finance partners (TopPay, PalmPay, etc.)',
            self::EmiSettle => 'Record finance partner settlements',
            self::EmployeesPermissions => 'Manage Employee Permissions',
            self::NotificationsManageTemplates => 'Manage notification templates',
            self::NotificationsManageRules => 'Manage notification rules',

        };
    }

    /** Used later to group checkboxes when Owner assigns permissions to an employee. */
    public function group(): string
    {
        return match (true) {
            $this === self::DashboardView => 'Dashboard',
            in_array($this, [self::ProductsView, self::ProductsManage, self::StockAdjust, self::StockTransfer, self::StockConfirmTransfer]) => 'Products & Inventory',
            in_array($this, [self::ImeiView, self::ImeiCorrect, self::ImeiChangeStatus, self::ImeiBulkImport]) => 'IMEI & Serialized Units',
            $this === self::BranchesManage => 'Branches',
            in_array($this, [self::SalesCreate, self::SalesView, self::SalesVoid, self::SalesRefund, self::SalesApplyDiscount]) => 'POS & Sales',
            in_array($this, [self::CustomersView, self::CustomersManage, self::CustomersRecordDuePayment, self::CustomersWriteOffDue]) => 'Customers',
            in_array($this, [self::SuppliersManage, self::PurchasesView, self::PurchasesCreate, self::PurchasesApprove]) => 'Suppliers & Purchases',
            in_array($this, [self::FinancePartnersWriteOff, self::EmiView, self::EmiSettle]) => 'EMI / Finance Partners',
            in_array($this, [self::WarrantyView, self::WarrantyApproveClaim, self::WarrantyClaimSupplier]) => 'Warranty',
            in_array($this, [self::ServiceView, self::ServiceManage]) => 'Service & Repair',
            in_array($this, [self::ExpensesView, self::ExpensesCreate, self::ExpensesApprove]) => 'Expenses',
            in_array($this, [self::PayrollView, self::PayrollManage, self::PayrollManageDepartments]) => 'Payroll',
            in_array($this, [self::AccountingViewBasicReports, self::AccountingViewFullReports, self::AccountingManageEntries, self::AccountingReverseEntry, self::AccountingManagePeriodLocks, self::ReportsExport]) => 'Accounting & Reports',
            in_array($this, [self::EmployeesView, self::EmployeesManage, self::EmployeesManagePermissions, self::RolesManage]) => 'Employees & Roles',
            in_array($this, [self::TreasuryView, self::TreasuryTransfer, self::TreasuryEquity, self::TreasuryAdjust, self::TreasuryBankFinance, self::TreasuryApprove, self::TreasuryReverse]) => 'Treasury',
            $this === self::SettingsManage => 'Settings',
            in_array($this, [self::NotificationsManageSettings, self::NotificationsManageTemplates, self::NotificationsManageRules]) => 'Notifications',
            default => 'Other',
        };
    }
}