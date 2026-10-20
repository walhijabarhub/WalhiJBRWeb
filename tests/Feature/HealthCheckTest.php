<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_up_is_ok_when_database_and_cache_work(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_up_fails_when_database_is_unreachable(): void
    {
        DB::shouldReceive('select')->once()->andThrow(new \RuntimeException('database down'));

        $this->get('/up')->assertStatus(500);
    }

    public function test_up_fails_when_cache_store_is_unreachable(): void
    {
        Cache::shouldReceive('put')->once()->andThrow(new \RuntimeException('redis down'));

        $this->get('/up')->assertStatus(500);
    }
}
