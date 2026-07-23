<?php

namespace App\Services;

use App\Enums\PermissionEnum;
use App\Models\Shop;
use App\Models\User;
use App\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class ShopRoleProvisioner
{
    /**
     * Default role templates auto-created for every new shop.
     * Owner gets every permission. The rest are starting points —
     * the shop owner can freely rename / edit / delete them later.
     */
    private const DEFAULT_ROLES = [
        'Owner' => '*',
        'Manager' =>[
            PermissionEnum::CustomersView,
            PermissionEnum::SalesCreate,
            PermissionEnum::SalesView,
            PermissionEnum::ServiceView,
            PermissionEnum::PurchasesView,
            PermissionEnum::InventoryView,

        ],
        'Cashier' => [
            PermissionEnum::DashboardView,
            PermissionEnum::ProductsView,
            PermissionEnum::ImeiView,
            PermissionEnum::SalesCreate,
            PermissionEnum::SalesView,
            PermissionEnum::CustomersView,
            PermissionEnum::CustomersManage,
            PermissionEnum::CustomersRecordDuePayment,
            PermissionEnum::EmiSettle,
        ],
        'Inventory Manager' => [
            PermissionEnum::DashboardView,
            PermissionEnum::ProductsView,
            PermissionEnum::ProductsManage,
            PermissionEnum::StockAdjust,
            PermissionEnum::StockTransfer,
            PermissionEnum::StockConfirmTransfer,
            PermissionEnum::ImeiView,
            PermissionEnum::ImeiBulkImport,
            PermissionEnum::SuppliersManage,
            PermissionEnum::PurchasesView,
            PermissionEnum::PurchasesCreate,
            PermissionEnum::SalesView,
        ],
        'Accountant' => [
            PermissionEnum::DashboardView,
            PermissionEnum::ExpensesView,
            PermissionEnum::ExpensesCreate,
            PermissionEnum::PayrollView,
            PermissionEnum::AccountingViewBasicReports,
            PermissionEnum::AccountingViewFullReports,
            PermissionEnum::AccountingReverseEntry,
            PermissionEnum::ReportsExport,
            PermissionEnum::FinancePartnersWriteOff,
            PermissionEnum::CustomersWriteOffDue,
        ],
        'Service Technicial' =>[
            PermissionEnum::ServiceView,
            PermissionEnum::ServiceManage,
            PermissionEnum::ServicePayment,
            PermissionEnum::InventoryView,
        ],
        'Sales Staff' => [
            PermissionEnum::CustomersView,
            PermissionEnum::CustomersManage,
            PermissionEnum::SalesView,
            PermissionEnum::SalesCreate,
            PermissionEnum::InventoryView,
            PermissionEnum::EmiView,
            PermissionEnum::ServiceView,
        ]
    ];

    public function provisionForNewShop(Shop $shop, User $owner): void
    {
        $this->provision($shop);
        $owner->assignRole('Owner');
    }

    public function provision(Shop $shop): void
    {
        $registrar = app(PermissionRegistrar::class);

        $previousTeamId = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId($shop->id);
     try {
        foreach (self::DEFAULT_ROLES as $roleName => $permissions) {

            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
                'shop_id' => $shop->id,
            ], [
                'is_system' => $roleName === 'Owner',
            ]);

            $permissionNames = $permissions === '*'
                ? array_map(fn(PermissionEnum $p) => $p->value, PermissionEnum::cases())
                : array_map(fn(PermissionEnum $p) => $p->value, $permissions);

            $role->syncPermissions($permissionNames);
        }
    } finally {
        $registrar->forgetCachedPermissions();
        $registrar->setPermissionsTeamId($previousTeamId);
    }
    }
}