<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Feature tests render Blade views that use @vite; they must not depend on a
        // compiled public/build/manifest.json (CI test jobs do not build assets).
        $this->withoutVite();
    }
}
