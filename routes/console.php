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

// Housekeeping for tables that otherwise grow forever: old audit_logs rows
// (AuditLog::prunable), failed jobs and finished batches older than 30 days, and
// expired password-reset tokens.
Schedule::command('model:prune')
    ->dailyAt('02:30')
    ->onOneServer();

Schedule::command('queue:prune-failed', ['--hours' => 720])
    ->dailyAt('02:40')
    ->onOneServer();

Schedule::command('queue:prune-batches', ['--hours' => 720])
    ->dailyAt('02:45')
    ->onOneServer();

Schedule::command('auth:clear-resets')
    ->dailyAt('02:50')
    ->onOneServer();

// Personal-data retention policy (config/privacy.php).
Schedule::command('walhi:prune-personal-data')
    ->monthlyOn(1, '03:00')
    ->onOneServer();
