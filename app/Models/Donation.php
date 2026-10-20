<?php

namespace App\Models;

use App\Enums\DonationStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Donation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_id',
        'donor_name',
        'donor_email',
        'donor_phone',
        'amount',
        'status',
        'snap_token',
        'campaign_id',
        'payment_type',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

    // ──────────────────────────────────────────────────────────────────────────
    // Scopes & reports
    // ──────────────────────────────────────────────────────────────────────────

    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status', DonationStatus::Success->value);
    }

    /**
     * Successful donation totals for the last 12 months (real data only), used by
     * the admin dashboard and the donation page.
     *
     * @return array{0: array<int, string>, 1: array<int, int>} [labels, totals]
     */
    public static function monthlySuccessChart(): array
    {
        $startDate = Carbon::now()->startOfMonth()->subMonths(11);

        $periodSelect = (new static)->getConnection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', created_at) AS period_key, SUM(amount) AS total"
            : "DATE_FORMAT(created_at, '%Y-%m') AS period_key, SUM(amount) AS total";

        $monthlyTotals = static::successful()
            ->where('created_at', '>=', $startDate)
            ->selectRaw($periodSelect)
            ->groupBy('period_key')
            ->pluck('total', 'period_key');

        $labels = [];
        $data = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->startOfMonth()->subMonths($i);
            $labels[] = $month->translatedFormat("M 'y");
            $data[] = (int) ($monthlyTotals[$month->format('Y-m')] ?? 0);
        }

        return [$labels, $data];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────────────────────────────────

    public function campaign()
    {
        return $this->belongsTo(Content::class, 'campaign_id');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Accessors
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Resolve the DonationStatus enum for the current record.
     */
    public function getStatusEnumAttribute(): DonationStatus
    {
        return DonationStatus::from($this->status);
    }

    /**
     * Human-readable status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->statusEnum->label();
    }

    /**
     * Whether the donation is completed/successful.
     */
    public function getIsSuccessAttribute(): bool
    {
        return $this->status === DonationStatus::Success->value;
    }
}
