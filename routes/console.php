<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sync pending donations with Midtrans (missed notifications) and expire stale ones.
Schedule::command('walhi:donations-reconcile')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->onOneServer();

// Write buffered page/download views to the database.
Schedule::command('walhi:flush-views')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer();

// Personal-data retention policy (config/privacy.php).
Schedule::command('walhi:prune-personal-data')
    ->monthlyOn(1, '03:00')
    ->onOneServer();
