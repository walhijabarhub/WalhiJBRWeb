<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduledPruningTest extends TestCase
{
    use RefreshDatabase;

    public function test_housekeeping_commands_are_scheduled(): void
    {
        $commands = collect(app(Schedule::class)->events())->map(fn ($event) => $event->command)->implode("\n");

        $this->assertStringContainsString('model:prune', $commands);
        $this->assertStringContainsString('queue:prune-failed --hours=720', $commands);
        $this->assertStringContainsString('queue:prune-batches', $commands);
        $this->assertStringContainsString('auth:clear-resets', $commands);
    }

    public function test_model_prune_removes_only_expired_audit_logs(): void
    {
        config(['privacy.audit_log_days' => 30]);

        $old = AuditLog::create(['action' => 'OLD', 'model' => 'Content']);
        $old->forceFill(['created_at' => now()->subDays(31)])->save();
        $recent = AuditLog::create(['action' => 'RECENT', 'model' => 'Content']);
        $recent->forceFill(['created_at' => now()->subDays(29)])->save();

        $this->artisan('model:prune', ['--model' => [AuditLog::class]])->assertSuccessful();

        $this->assertDatabaseMissing('audit_logs', ['id' => $old->id]);
        $this->assertDatabaseHas('audit_logs', ['id' => $recent->id]);
    }
}
