<?php

namespace App\Enums;

enum DonationStatus: string
{
    case Pending = 'pending';
    case Success = 'success';
    case Failed = 'failed';
    case Expired = 'expired';

    /**
     * Human-readable label in Indonesian.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Pembayaran',
            self::Success => 'Berhasil',
            self::Failed => 'Gagal',
            self::Expired => 'Kadaluarsa',
        };
    }

    /**
     * Whether the donation is considered a completed (successful) transaction.
     */
    public function isCompleted(): bool
    {
        return $this === self::Success;
    }

    /**
     * Strict state transition matrix to prevent illegal status transitions.
     */
    public function canTransitionTo(self $target): bool
    {
        if ($this === $target) {
            // Idempotent webhook replay is permitted
            return true;
        }

        return match ($this) {
            self::Pending => in_array($target, [self::Success, self::Failed, self::Expired], true),
            self::Success => false, // Terminal: cannot be downgraded or altered
            self::Failed => false,  // Terminal: cannot transition to any other status
            self::Expired => false, // Terminal: cannot transition to any other status
        };
    }

    /**
     * Determine the status from a Midtrans transaction_status / fraud_status pair.
     *
     * Card payments report "capture" together with a fraud_status: only "accept"
     * means the money is secured; "challenge" is still under review and "deny"
     * was rejected by fraud detection.
     *
     * @see https://docs.midtrans.com/docs/https-notification-webhooks
     */
    public static function fromMidtrans(string $transactionStatus, ?string $fraudStatus = null): self
    {
        if ($fraudStatus === 'deny') {
            return self::Failed;
        }

        return match ($transactionStatus) {
            'capture' => match ($fraudStatus) {
                'accept', null, '' => self::Success,
                default => self::Pending,
            },
            'settlement' => self::Success,
            'deny', 'cancel', 'failure' => self::Failed,
            'expire' => self::Expired,
            default => self::Pending,
        };
    }
}
