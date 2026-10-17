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
