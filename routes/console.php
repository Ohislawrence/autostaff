<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
*/
\Illuminate\Support\Facades\Schedule::command('tools:sync')->dailyAt('03:00');
\Illuminate\Support\Facades\Schedule::command('store:sync-all-products')->dailyAt('02:00')->withoutOverlapping();
\Illuminate\Support\Facades\Schedule::command('followups:send')->everyMinute()->withoutOverlapping();
\Illuminate\Support\Facades\Schedule::command('reports:generate daily')->dailyAt('01:00');
\Illuminate\Support\Facades\Schedule::command('reports:generate weekly')->weeklyOn(1, '01:30');
\Illuminate\Support\Facades\Schedule::command('reports:generate monthly')->monthlyOn(1, '02:00');
\Illuminate\Support\Facades\Schedule::command('prospecting:daily-run')->dailyAt('08:00')->withoutOverlapping();
\Illuminate\Support\Facades\Schedule::command('growth:evaluate')->dailyAt('00:30')->withoutOverlapping();
\Illuminate\Support\Facades\Schedule::command('prospecting:run-tenants')->hourly()->withoutOverlapping();
\Illuminate\Support\Facades\Schedule::command('prospecting:followups')->dailyAt('09:00')->withoutOverlapping();
