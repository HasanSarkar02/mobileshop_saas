<?php

namespace App\Enums;

enum NotificationCategory: string
{
    case Sales = 'sales';
    case Returns = 'returns';
    case Purchases = 'purchases';
    case Suppliers = 'suppliers';
    case Customers = 'customers';
    case FinancePartners = 'finance_partners';
    case Inventory = 'inventory';
    case Warranty = 'warranty';
    case Service = 'service';
    case Expenses = 'expenses';
    case Payroll = 'payroll';
    case Treasury = 'treasury';
    case Accounting = 'accounting';
    case Employees = 'employees';
    case UsedPhones = 'used_phones';
    case System = 'system';
    case Security = 'security';

    public function label(): string
    {
        return match ($this) {
            self::Sales => 'Sales',
            self::Returns => 'Returns',
            self::Purchases => 'Purchases',
            self::Suppliers => 'Suppliers',
            self::Customers => 'Customers',
            self::FinancePartners => 'Finance Partners / EMI',
            self::Inventory => 'Inventory',
            self::Warranty => 'Warranty',
            self::Service => 'Service & Repair',
            self::Expenses => 'Expenses',
            self::Payroll => 'Payroll',
            self::Treasury => 'Treasury',
            self::Accounting => 'Accounting',
            self::Employees => 'Employees',
            self::UsedPhones => 'Used Phones',
            self::System => 'System',
            self::Security => 'Security',
        };
    }

    /** Semantic icon key — maps to whatever icon set the frontend uses. */
    public function icon(): string
    {
        return match ($this) {
            self::Sales => 'shopping-cart',
            self::Returns => 'rotate-ccw',
            self::Purchases => 'package',
            self::Suppliers => 'truck',
            self::Customers => 'users',
            self::FinancePartners => 'credit-card',
            self::Inventory => 'boxes',
            self::Warranty => 'shield-check',
            self::Service => 'wrench',
            self::Expenses => 'receipt',
            self::Payroll => 'banknote',
            self::Treasury => 'landmark',
            self::Accounting => 'book-open',
            self::Employees => 'user-cog',
            self::UsedPhones => 'smartphone',
            self::System => 'settings',
            self::Security => 'lock',
        };
    }
}