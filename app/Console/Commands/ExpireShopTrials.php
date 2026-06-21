<?php

namespace App\Console\Commands;

use App\Enums\ShopStatus;
use App\Models\Shop;
use Illuminate\Console\Command;

class ExpireShopTrials extends Command
{
    protected $signature = 'shops:expire-trials';
    protected $description = "Move shops whose trial period has ended to the expired status.";

    public function handle(): void
    {
        $count = Shop::where('status', ShopStatus::Trial)
            ->where('trial_ends_at', '<', now())
            ->update(['status' => ShopStatus::Expired]);

        $this->info("Expired {$count} shop(s) whose trial period ended.");
    }
}