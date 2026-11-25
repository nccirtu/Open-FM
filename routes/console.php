<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('maintenance:generate-orders')->dailyAt('05:00');

Schedule::command('contracts:send-reminders')->dailyAt('05:30');

Schedule::command('tickets:check-sla')->hourly();

Schedule::command('waste:generate-pickups')->dailyAt('04:45');
Schedule::command('waste:check-pickups')->hourly();
