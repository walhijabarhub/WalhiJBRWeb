<?php

namespace App\Services\Donation;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MidtransService
{
    private string $serverKey;

    private bool $isProduction;

    public function __construct()
    {
        $this->serverKey = (string) config('midtrans.server_key', '');
        $this->isProduction = (bool) config('midtrans.is_production', false);
    }

    /**
     * Whether Midtrans is properly configured with a server key.
     */
    public function isConfigured(): bool
    {
        return ! empty($this->serverKey);
    }

    /**
     * Create a Snap transaction and return the snap_token.
     *
     * @throws \RuntimeException on API failure
     */
    public function createTransaction(string $orderId, array $customerDetails, int $amount): string
    {
        try {
            $response = $this->client()->post($this->snapUrl(), [
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => $amount,
                ],
                'customer_details' => [
                    'first_name' => $customerDetails['donor_name'],
                    'email' => $customerDetails['donor_email'],
                    'phone' => $customerDetails['donor_phone'],
                ],
            ]);
        } catch (ConnectionException $e) {
            throw new \RuntimeException('Midtrans API is unreachable: '.$e->getMessage(), previous: $e);
        }

        if (! $response->successful()) {
            Log::warning('Midtrans API error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'order_id' => $orderId,
            ]);
            throw new \RuntimeException('Midtrans API returned non-2xx response.');
        }

        $token = $response->json('token');

        if (! $token) {
            throw new \RuntimeException('Midtrans API did not return a snap token.');
        }

        return $token;
    }

    /**
     * Fetch the authoritative transaction status from the Midtrans Core API.
     *
     * @return array<string, mixed>|null null when Midtrans has no transaction for the order
     *
     * @throws \RuntimeException when the status cannot be determined (network or API error)
     */
    public function getStatus(string $orderId): ?array
    {
        try {
            // Single attempt: callers (webhook, scheduled reconcile) are retried anyway, and the
            // webhook must answer quickly so PHP-FPM workers are not held by a slow API.
            $response = $this->client(attempts: 1)->get($this->coreBaseUrl().'/v2/'.rawurlencode($orderId).'/status');
        } catch (ConnectionException $e) {
            throw new \RuntimeException('Midtrans status API is unreachable: '.$e->getMessage(), previous: $e);
        }

        // The status API answers "not found" either with HTTP 404 or with HTTP 200 and status_code "404".
        if ($response->status() === 404 || (string) $response->json('status_code') === '404') {
            return null;
        }

        if (! $response->successful() || ! $response->json('transaction_status')) {
            Log::warning('Midtrans status API error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'order_id' => $orderId,
            ]);
            throw new \RuntimeException('Midtrans status API returned an unexpected response.');
        }

        return $response->json();
    }

    /**
     * Validate a Midtrans webhook signature.
     */
    public function validateSignature(
        string $orderId,
        string $statusCode,
        string $grossAmount,
        string $incomingSignature
    ): bool {
        if (! $this->isConfigured()) {
            return false;
        }

        $expected = hash('sha512', $orderId.$statusCode.$grossAmount.$this->serverKey);

        return hash_equals($expected, $incomingSignature);
    }

    private function client(int $attempts = 3): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->withBasicAuth($this->serverKey, '')
            ->connectTimeout((int) config('midtrans.connect_timeout', 5))
            ->timeout((int) config('midtrans.timeout', 10))
            // Retries happen on connection errors only. Snap rejects a reused order_id, so a retry
            // after a lost response cannot create a second transaction.
            ->retry($attempts, 500, fn ($exception) => $exception instanceof ConnectionException, throw: false);
    }

    private function snapUrl(): string
    {
        return $this->isProduction
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
    }

    private function coreBaseUrl(): string
    {
        return $this->isProduction
            ? 'https://api.midtrans.com'
            : 'https://api.sandbox.midtrans.com';
    }
}
