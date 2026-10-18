<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduled offer distribution. Run the scheduler with `php artisan schedule:work`
// (or a cron entry calling `php artisan schedule:run` every minute).
Schedule::command('app:send-scheduled-offers')->everyMinute()->withoutOverlapping();
