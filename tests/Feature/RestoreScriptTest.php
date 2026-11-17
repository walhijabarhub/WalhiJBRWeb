<?php

namespace Tests\Feature;

use Symfony\Component\Process\Process;
use Tests\TestCase;

class RestoreScriptTest extends TestCase
{
    public function test_production_restore_fails_closed_with_mock_docker(): void
    {
        $process = new Process(['bash', base_path('tests/restore-script-test.sh')], base_path());
        $process->setTimeout(60);

        $this->assertSame(0, $process->run(), $process->getOutput().$process->getErrorOutput());
    }
}
