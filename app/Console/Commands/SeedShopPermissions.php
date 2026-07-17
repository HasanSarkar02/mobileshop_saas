<?php

namespace App\Console\Commands;

use App\Models\Shop;
use App\Services\ShopRoleProvisioner;
use Illuminate\Console\Command;

class SeedShopPermissions extends Command
{
    protected $signature   = 'shops:seed-permissions';
    protected $description = 'Re-seed all permission cases to every shop.';

    public function handle(ShopRoleProvisioner $provisioner): void
    {
        $shops = Shop::withoutGlobalScopes()->get();
        foreach ($shops as $shop) {
            $provisioner->provision($shop);
            $this->line("✓ {$shop->name}");
        }

        $this->info('Done.');
    }
}