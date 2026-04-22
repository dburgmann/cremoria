<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Game tick scheduler.
 * Assumes the server's cron calls: * * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
 */
Schedule::command('game:tick-economy')->everyMinute()->withoutOverlapping();
Schedule::command('game:tick-movement')->everyMinute()->withoutOverlapping();
Schedule::command('game:tick-score')->everyFiveMinutes()->withoutOverlapping();
