<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Autonomous farm agent: weekly herd AI health report, Mondays 06:00
Schedule::command('ai:herd-report')->weeklyOn(1, '06:00');
