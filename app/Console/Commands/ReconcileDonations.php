<?php

namespace App\Console\Commands;

use App\Enums\DonationStatus;
use App\Models\Donation;
use App\Services\Donation\DonationService;
use App\Services\Donation\GatewayAmountMismatchException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Brings pending donations in line with Midtrans when a notification was missed,
 * and expires pending donations that never reached the gateway.
 */
class ReconcileDonations extends Command
{
    protected $signature = 'walhi:donations-reconcile';

    protected $description = 'Sinkronkan donasi berstatus pending dengan Midtrans dan kedaluwarsakan yang terlalu lama';

    public function handle(DonationService $donations): int
    {
        $reconcileBefore = now()->subMinutes((int) config('midtrans.reconcile_after_minutes', 15));
        $expireBefore = now()->subHours((int) config('midtrans.expire_after_hours', 24));
        $gateway = $donations->gatewayConfigured();

        $stats = ['updated' => 0, 'expired' => 0, 'unchanged' => 0, 'mismatch' => 0, 'errors' => 0];

        Donation::where('status', DonationStatus::Pending->value)
            ->where('created_at', '<=', $reconcileBefore)
            ->orderBy('id')
            ->chunkById(100, function ($pending) use ($donations, $gateway, $expireBefore, &$stats) {
                foreach ($pending as $donation) {
                    try {
                        $result = $gateway ? $donations->syncFromGateway($donation) : null;

                        if ($result === true) {
                            $stats['updated']++;
                        } elseif ($result === null && $donation->created_at <= $expireBefore && $donations->expire($donation)) {
                            $stats['expired']++;
                        } else {
                            $stats['unchanged']++;
                        }
                    } catch (GatewayAmountMismatchException $e) {
                        // Logged by the service; left pending for manual review without failing the run.
                        $stats['mismatch']++;
                    } catch (\Throwable $e) {
                        $stats['errors']++;
                        Log::warning('Donation reconciliation failed', [
                            'order_id' => $donation->order_id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        $this->info(sprintf(
            'Rekonsiliasi donasi: %d diperbarui, %d kedaluwarsa, %d tetap, %d nominal tidak cocok (perlu cek manual), %d gagal.',
            $stats['updated'], $stats['expired'], $stats['unchanged'], $stats['mismatch'], $stats['errors']
        ));

        return $stats['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
