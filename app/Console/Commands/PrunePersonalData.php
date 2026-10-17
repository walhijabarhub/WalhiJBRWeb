<?php

namespace App\Console\Commands;

use App\Models\Comment;
use App\Models\Donation;
use App\Models\Subscriber;
use App\Services\AuditLogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Applies the personal-data retention policy in config/privacy.php.
 * Amounts, dates and comment texts are kept; contact details are removed.
 */
class PrunePersonalData extends Command
{
    private const ANONYMISED_DOMAIN = 'anonymized.invalid';

    protected $signature = 'walhi:prune-personal-data {--dry-run : Hanya tampilkan jumlah data yang akan diproses}';

    protected $description = 'Anonimkan data pribadi donatur/komentator lama dan hapus pelanggan nawala yang tidak dikonfirmasi';

    public function handle(): int
    {
        $retentionCutoff = now()->subMonths((int) config('privacy.retention_months', 24));
        $unconfirmedCutoff = now()->subDays((int) config('privacy.unconfirmed_subscriber_days', 30));
        $deletedCutoff = now()->subDays((int) config('privacy.deleted_subscriber_days', 30));
        $notAnonymised = '%@'.self::ANONYMISED_DOMAIN;

        $donations = Donation::withTrashed()
            ->where('created_at', '<', $retentionCutoff)
            ->where('donor_email', 'not like', $notAnonymised);
        $comments = Comment::where('created_at', '<', $retentionCutoff)
            ->where('author_email', 'not like', $notAnonymised);
        $unconfirmed = Subscriber::withTrashed()
            ->whereNull('confirmed_at')
            ->where('created_at', '<', $unconfirmedCutoff);
        $deleted = Subscriber::onlyTrashed()->where('deleted_at', '<', $deletedCutoff);

        $counts = [
            'donations' => (clone $donations)->count(),
            'comments' => (clone $comments)->count(),
            'unconfirmed_subscribers' => (clone $unconfirmed)->count(),
            'deleted_subscribers' => (clone $deleted)->count(),
        ];

        if (! $this->option('dry-run')) {
            DB::transaction(function () use ($donations, $comments, $unconfirmed, $deleted) {
                $email = DB::raw("CONCAT('anonymized+', id, '@".self::ANONYMISED_DOMAIN."')");
                if (DB::getDriverName() === 'sqlite') {
                    $email = DB::raw("'anonymized+' || id || '@".self::ANONYMISED_DOMAIN."'");
                }

                $donations->toBase()->update(['donor_name' => 'Donatur Anonim', 'donor_email' => $email, 'donor_phone' => '-']);
                $comments->toBase()->update(['author_email' => $email]);
                $unconfirmed->forceDelete();
                $deleted->forceDelete();
            });

            AuditLogService::log('PERSONAL_DATA_PRUNED', 'System', null, $counts);
        }

        $this->table(['Data', 'Jumlah'], collect($counts)->map(fn ($count, $key) => [$key, $count])->values()->all());

        return self::SUCCESS;
    }
}
