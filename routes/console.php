<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Console\Commands\RunScheduledReminders;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use App\Console\Commands\ExpireShopTrials;
use App\Console\Commands\EscalatePendingNotifications;
use Illuminate\Support\Facades\Schedule;

Schedule::command(ExpireShopTrials::class)->daily();
Schedule::command(EscalatePendingNotifications::class)->everyFiveMinutes();
schedule::command('billing:send-due-reminders')->dailyAt('09:00');

Schedule::command(RunScheduledReminders::class)->dailyAt('09:00');