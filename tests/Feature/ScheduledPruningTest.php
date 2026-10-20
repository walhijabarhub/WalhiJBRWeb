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

        // The pruning itself is recorded in the audit trail.
        $record = AuditLog::where('action', 'AUDIT_LOGS_PRUNED')->sole();
        $this->assertSame(['deleted' => 1, 'retention_days' => 30], $record->details);
    }

    public function test_nothing_is_recorded_when_no_audit_log_expired(): void
    {
        AuditLog::create(['action' => 'RECENT', 'model' => 'Content']);

        $this->artisan('model:prune', ['--model' => [AuditLog::class]])->assertSuccessful();

        $this->assertSame(0, AuditLog::where('action', 'AUDIT_LOGS_PRUNED')->count());
    }
}
