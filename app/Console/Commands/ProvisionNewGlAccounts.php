<?php

namespace App\Console\Commands;

use App\Models\Shop;
use App\Services\ChartOfAccountsProvisioner;
use Illuminate\Console\Command;

class ProvisionNewGlAccounts extends Command
{
    protected $signature = 'treasury:provision-gl-accounts';
    protected $description = 'Add missing GL accounts (equity, loans, fees) to all existing shops.';

    public function handle(ChartOfAccountsProvisioner $provisioner): void
    {
        $shops = Shop::withoutGlobalScopes()->get();

        foreach ($shops as $shop) {
            $provisioner->provisionTreasuryAccounts($shop);
            $this->line("✓ {$shop->name}");
        }

        $this->info("Treasury GL accounts provisioned for {$shops->count()} shop(s).");
    }
}