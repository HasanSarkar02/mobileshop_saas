<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\RunScheduledReminders;
use App\Console\Commands\ExpireShopTrials;
use App\Console\Commands\EscalatePendingNotifications;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

//Scheduled Tasks
Schedule::command(ExpireShopTrials::class)->daily();
Schedule::command(EscalatePendingNotifications::class)->everyFiveMinutes();
Schedule::command('billing:send-due-reminders')->dailyAt('09:00');
Schedule::command(RunScheduledReminders::class)->dailyAt('09:00');

// Queue Worker
Schedule::command('queue:work --stop-when-empty')->everyMinute()->withoutOverlapping();