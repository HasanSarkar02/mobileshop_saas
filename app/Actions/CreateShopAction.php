<?php

namespace App\Actions;

use App\Enums\ShopStatus;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Shop;
use App\Models\User;
use App\Services\ShopRoleProvisioner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateShopAction
{
    public function __construct(
        private readonly ShopRoleProvisioner $roleProvisioner,
    ) {}

    /**
     * @param  array{name: string, owner_name: string, email: string, phone?: string,
     *               address?: string, business_type?: string, trial_days?: int,
     *               vat_enabled?: bool, vat_registration_number?: string, default_vat_rate?: float}  $data
     * @return array{shop: Shop, branch: Branch, owner: User, plain_password: string}
     */
    public function execute(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $plainPassword = Str::password(12);

            $shop = Shop::create([
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name']),
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'business_type' => $data['business_type'] ?? 'mobile_shop',
                'status' => ShopStatus::Trial,
                'trial_ends_at' => now()->addDays($data['trial_days'] ?? 14),
                'is_active' => true,
                'vat_enabled' => $data['vat_enabled'] ?? false,
                'vat_registration_number' => $data['vat_registration_number'] ?? null,
                'default_vat_rate' => $data['default_vat_rate'] ?? 0,
            ]);

            $branch = Branch::create([
                'shop_id' => $shop->id,
                'name' => 'Main Branch',
                'code' => 'MAIN',
                'address' => $data['address'] ?? null,
                'phone' => $data['phone'] ?? null,
                'is_main' => true,
                'is_active' => true,
            ]);

            $owner = User::create([
                'shop_id' => $shop->id,
                'branch_id' => null, // Owner is never branch-restricted
                'name' => $data['owner_name'],
                'email' => $data['email'],
                'password' => $plainPassword,
                'user_type' => UserType::Owner,
                'phone' => $data['phone'] ?? null,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $this->roleProvisioner->provisionForNewShop($shop, $owner);

            return [
                'shop' => $shop,
                'branch' => $branch,
                'owner' => $owner,
                'plain_password' => $plainPassword,
            ];
        });
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (Shop::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}