<?php

namespace App\Enums;

enum ShopFeature: string
{
    case Pos          = 'pos';
    case Sales        = 'sales';
    case Inventory    = 'inventory';
    case Purchases    = 'purchases';
    case Suppliers    = 'suppliers';
    case Customers    = 'customers';
    case Service      = 'service';
    case UsedPhones   = 'used_phones';
    case EmiPartners  = 'emi_partners';
    case Expenses     = 'expenses';
    case Payroll      = 'payroll';
    case Employees    = 'employees';
    case Treasury     = 'treasury';
    case Reports      = 'reports';
    case Settings     = 'settings';
    case Warranty     = 'warranty';
    case Accounting   = 'accounting';

    public function label(): string
    {
        return match($this) {
            self::Pos         => 'Point of Sale',
            self::Sales       => 'Sales',
            self::Inventory   => 'Inventory & Products',
            self::Purchases   => 'Purchases',
            self::Suppliers   => 'Suppliers',
            self::Customers   => 'Customers & CRM',
            self::Service     => 'Service & Repair',
            self::UsedPhones  => 'Used Phones',
            self::EmiPartners => 'EMI / Finance Partners',
            self::Expenses    => 'Expenses',
            self::Payroll     => 'Payroll & HR',
            self::Employees   => 'Employees',
            self::Treasury    => 'Treasury & Cash',
            self::Reports     => 'Reports & Analytics',
            self::Settings    => 'Settings',
            self::Warranty    => 'Warranty',
            self::Accounting  => 'Accounting & GL',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::Pos         => '🛒',
            self::Sales       => '📋',
            self::Inventory   => '📦',
            self::Purchases   => '🛍',
            self::Suppliers   => '🤝',
            self::Customers   => '👥',
            self::Service     => '🔧',
            self::UsedPhones  => '📱',
            self::EmiPartners => '💳',
            self::Expenses    => '💸',
            self::Payroll     => '💰',
            self::Employees   => '👤',
            self::Treasury    => '🏦',
            self::Reports     => '📊',
            self::Settings    => '⚙',
            self::Warranty    => '🛡',
            self::Accounting  => '📒',
        };
    }

    /** Features enabled by default on every new shop */
    public static function defaults(): array
    {
        return array_column(self::cases(), 'value');
    }
}