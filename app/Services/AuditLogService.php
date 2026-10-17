<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuditLogService
{
    /**
     * Record an audit trail entry.
     *
     * Entries are stored in the audit_logs table (queryable, not affected by LOG_LEVEL)
     * and mirrored to the dedicated "audit" log channel. A storage failure is logged
     * but never breaks the action being audited.
     */
    public static function log(string $action, string $modelName, mixed $modelId = null, array $details = []): void
    {
        $user = auth()->user();
        $request = request();

        $entry = [
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'action' => $action,
            'model' => $modelName,
            'model_id' => $modelId !== null ? (string) $modelId : null,
            'ip' => $request?->ip(),
            'user_agent' => Str::limit((string) $request?->userAgent(), 500, ''),
            'details' => $details ?: null,
        ];

        try {
            AuditLog::create($entry);
        } catch (\Throwable $e) {
            Log::error('Audit log could not be stored in the database', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }

        Log::channel('audit')->info("Audit Trail: Action [{$action}] on [{$modelName}]", $entry);
    }
}
