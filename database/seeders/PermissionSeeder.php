<?php

namespace Database\Seeders;

use App\Enums\PermissionEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Permissions stay GLOBAL (team/shop_id = null) — the capability
        // "create a sale" means the same thing in every shop. Only Roles
        // are duplicated per shop, not Permissions.
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        foreach (PermissionEnum::cases() as $permission) {
            Permission::firstOrCreate([
                'name' => $permission->value,
                'guard_name' => 'web',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}