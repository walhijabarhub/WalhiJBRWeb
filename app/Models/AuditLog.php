<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only audit trail entry written by App\Services\AuditLogService.
 */
class AuditLog extends Model
{
    use MassPrunable;

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'user_email',
        'action',
        'model',
        'model_id',
        'ip',
        'user_agent',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Entries older than the retention period (config/privacy.php), removed by `model:prune`.
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDays(config('privacy.audit_log_days')));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
