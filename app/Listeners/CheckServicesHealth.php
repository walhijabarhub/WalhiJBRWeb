<?php

namespace App\Listeners;

use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Makes the /up health route fail (HTTP 500) when the database or the cache store
 * (Redis in production) is unreachable or refuses writes, instead of only checking
 * that the framework boots. Used by deploy.sh and external uptime monitors.
 */
class CheckServicesHealth
{
    public function handle(DiagnosingHealth $event): void
    {
        DB::select('select 1');

        $value = (string) microtime(true);
        Cache::put('health:probe', $value, 30);

        if (Cache::get('health:probe') !== $value) {
            throw new RuntimeException('Cache store did not return the health probe value.');
        }
    }
}
