<?php

namespace App\Http\Controllers;

use App\Http\Requests\Donation\InitiateDonationRequest;
use App\Models\Content;
use App\Models\Donation;
use App\Services\Donation\DonationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\IpUtils;

class DonationController extends Controller
{
    public function __construct(private readonly DonationService $donationService) {}

    /**
     * Initiate a donation checkout. Returns a Snap token (real or mock).
     */
    public function pay(InitiateDonationRequest $request)
    {
        $validated = $request->validated();

        // Validate campaign exists, is a donation campaign, and is published
        if (! empty($validated['campaign_id'])) {
            $campaign = Content::find($validated['campaign_id']);
            if (! $campaign || $campaign->category !== 'donasi' || $campaign->status !== 'published') {
                return response()->json([
                    'success' => false,
                    'message' => 'Kampanye donasi tidak valid atau sudah tidak aktif.',
                ], 422);
            }
        }

        try {
            $result = $this->donationService->initiate($validated);

            return response()->json([
                'success' => true,
                'snap_token' => $result['snap_token'],
                'order_id' => $result['order_id'],
                'is_mock' => $result['is_mock'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Layanan gateway pembayaran sedang tidak dapat diakses. Silakan coba beberapa saat lagi.',
            ], 503);
        }
    }

    /**
     * Handle Midtrans payment notification webhook.
     * Implementation is idempotent: a successful donation cannot be downgraded.
     */
    public function webhook(Request $request)
    {
        // Optional source-IP allowlist (see config/midtrans.php); the signature check below is mandatory.
        if (config('midtrans.ip_whitelist_enabled')) {
            $clientIp = $request->ip();
            $allowedRanges = config('midtrans.ip_whitelist', []);

            if (! IpUtils::checkIp($clientIp, $allowedRanges)) {
                Log::warning('Midtrans webhook rejected due to unlisted IP address', [
                    'ip' => $clientIp,
                    'user_agent' => $request->userAgent(),
                ]);

                return response('Forbidden IP', 403);
            }
        }

        $payload = $request->all();

        $orderId = $payload['order_id'] ?? null;
        $statusCode = $payload['status_code'] ?? null;
        $grossAmount = $payload['gross_amount'] ?? null;
        $signatureKey = $payload['signature_key'] ?? null;

        if (! $orderId || ! $statusCode || ! $grossAmount || ! $signatureKey) {
            return response('Bad Request', 400);
        }

        // Validate Midtrans signature using service
        if (! $this->donationService->validateSignature($orderId, $statusCode, $grossAmount, $signatureKey)) {
            return response('Invalid Signature', 403);
        }

        $donation = Donation::where('order_id', $orderId)->first();
        if (! $donation) {
            return response('Donation Not Found', 404);
        }

        // Defense-in-depth: Verify gross amount matches stored donation record
        if ((int) floatval($grossAmount) !== (int) $donation->amount) {
            Log::warning('Donation webhook amount mismatch detected', [
                'order_id' => $orderId,
                'database_amount' => $donation->amount,
                'payload_amount' => $grossAmount,
            ]);

            return response('Amount Mismatch', 400);
        }

        // The notification body (transaction_status, fraud_status) is not covered by the
        // signature, so confirm the final status with the Midtrans status API.
        if (config('midtrans.verify_webhook_status', true)) {
            try {
                if ($this->donationService->syncFromGateway($donation) === null) {
                    Log::warning('Midtrans webhook for an order unknown to the status API', ['order_id' => $orderId]);

                    return response('Transaction Not Found', 404);
                }
            } catch (\RuntimeException $e) {
                Log::error('Midtrans webhook status confirmation failed', [
                    'order_id' => $orderId,
                    'error' => $e->getMessage(),
                ]);

                // Non-2xx makes Midtrans retry the notification later.
                return response('Status Unavailable', 503);
            }

            return response('OK', 200);
        }

        $transactionStatus = (string) ($payload['transaction_status'] ?? '');
        $paymentType = $payload['payment_type'] ?? null;
        $fraudStatus = $payload['fraud_status'] ?? null;

        $this->donationService->processWebhook($donation, $transactionStatus, $paymentType, $fraudStatus);

        return response('OK', 200);
    }

    /**
     * Simulate payment status update — LOCAL / TESTING ONLY.
     * This method is only reachable when the route is registered (see routes/web.php).
     */
    public function mockPaymentStatus(Request $request)
    {
        // Double-check environment as defense-in-depth
        abort_unless(app()->environment('local', 'testing'), 403, 'Mock payment is only available in local or testing environments.');

        $validated = $request->validate([
            'order_id' => ['required', 'exists:donations,order_id'],
            'status' => ['required', 'in:success,failed,pending,expired'],
        ]);

        $donation = Donation::where('order_id', $validated['order_id'])->firstOrFail();
        $donation->status = $validated['status'];
        $donation->payment_type = 'simulation';
        $donation->save();

        return response()->json([
            'success' => true,
            'status' => $donation->status,
        ]);
    }
}
