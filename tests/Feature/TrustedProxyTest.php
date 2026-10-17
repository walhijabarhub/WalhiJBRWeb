<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Regression test for audit issue #62: client-supplied forwarding headers must not
 * change the client IP or scheme unless a proxy is explicitly trusted.
 */
class TrustedProxyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->get('/__test/client', fn () => response()->json([
            'ip' => request()->ip(),
            'secure' => request()->isSecure(),
        ]));
    }

    public function test_forwarded_headers_are_ignored_by_default(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '172.18.0.5'])
            ->withHeaders(['X-Forwarded-For' => '1.2.3.4', 'X-Forwarded-Proto' => 'https'])
            ->getJson('/__test/client')
            ->assertExactJson(['ip' => '172.18.0.5', 'secure' => false]);
    }

    public function test_client_ip_comes_from_remote_addr(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->getJson('/__test/client')
            ->assertJsonPath('ip', '203.0.113.9');
    }
}
