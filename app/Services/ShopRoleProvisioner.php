<?php

namespace App\Services;

use App\Enums\PermissionEnum;
use App\Models\Shop;
use App\Models\User;
use App\Models\Role;
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
        'Cashier' => [
            PermissionEnum::DashboardView,
            PermissionEnum::ProductsView,
            PermissionEnum::ImeiView,
            PermissionEnum::SalesCreate,
            PermissionEnum::SalesView,
            PermissionEnum::CustomersView,
            PermissionEnum::CustomersManage,
            PermissionEnum::CustomersRecordDuePayment,
            PermissionEnum::FinancePartnersRecordPayment,
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
            PermissionEnum::FinancePartnersViewDue,
            PermissionEnum::FinancePartnersWriteOff,
            PermissionEnum::CustomersWriteOffDue,
        ],
    ];

    public function provisionForNewShop(Shop $shop, User $owner): void
    {
        $registrar = app(PermissionRegistrar::class);
        $previousTeamId = $registrar->getPermissionsTeamId();

        $registrar->setPermissionsTeamId($shop->id);

        foreach (self::DEFAULT_ROLES as $roleName => $permissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
                'shop_id' => $shop->id,
            ], [
                'is_system' => $roleName === 'Owner',
            ]);

            $permissionNames = $permissions === '*'
                ? array_map(fn (PermissionEnum $p) => $p->value, PermissionEnum::cases())
                : array_map(fn (PermissionEnum $p) => $p->value, $permissions);

            $role->syncPermissions($permissionNames);
        }

        $owner->assignRole('Owner');

        $registrar->forgetCachedPermissions();
        $registrar->setPermissionsTeamId($previousTeamId);
    }
}