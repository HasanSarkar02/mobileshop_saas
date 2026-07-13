<?php

namespace App\Console\Commands;

use App\Models\Shop;
use App\Services\ChartOfAccountsProvisioner;
use Illuminate\Console\Command;

class ProvisionPayrollGlAccounts extends Command
{
    protected $signature   = 'payroll:provision-gl-accounts';
    protected $description = 'Add payroll-specific GL accounts to all existing shops.';

    public function handle(ChartOfAccountsProvisioner $provisioner): void
    {
        $shops = Shop::withoutGlobalScopes()->get();

        foreach ($shops as $shop) {
            $provisioner->provisionPayrollAccounts($shop);
            $this->line("✓ {$shop->name}");
        }

        $this->info("Payroll GL accounts provisioned for {$shops->count()} shop(s).");
    }
}