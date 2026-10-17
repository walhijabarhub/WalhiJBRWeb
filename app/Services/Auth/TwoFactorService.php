<?php

namespace App\Services\Auth;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * TOTP (RFC 6238) two-factor authentication for admin and editor accounts.
 */
class TwoFactorService
{
    private const RECOVERY_CODE_COUNT = 8;

    public function __construct(private readonly Google2FA $google2fa) {}

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    /**
     * Inline SVG QR code for the otpauth:// URL. Generated server-side so the secret
     * never leaves the application (no third-party QR service).
     */
    public function qrCodeSvg(User $user, string $secret): string
    {
        $url = $this->google2fa->getQRCodeUrl(config('app.name', 'WALHI Jawa Barat'), $user->email, $secret);

        $writer = new Writer(new ImageRenderer(new RendererStyle(192, 1), new SvgImageBackEnd));

        return $writer->writeString($url);
    }

    /**
     * Verify a 6-digit code against a secret. A code can be used only once per user,
     * so an intercepted code cannot be replayed within its validity window.
     */
    public function verify(string $secret, string $code, ?int $userId = null): bool
    {
        $code = preg_replace('/\s+/', '', $code);
        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        // Passing a non-null old timestamp makes the library return the matched time step.
        $lastTimestamp = $userId !== null ? (int) Cache::get($this->replayKey($userId), 0) : 0;
        $matched = $this->google2fa->verifyKeyNewer($secret, $code, $lastTimestamp, 1);

        if ($matched === false) {
            return false;
        }

        if ($userId !== null) {
            Cache::put($this->replayKey($userId), (int) $matched, now()->addMinutes(5));
        }

        return true;
    }

    /**
     * @return list<string>
     */
    public function generateRecoveryCodes(): array
    {
        return collect(range(1, self::RECOVERY_CODE_COUNT))
            ->map(fn () => Str::lower(Str::random(5).'-'.Str::random(5)))
            ->all();
    }

    /**
     * Consume a recovery code if it matches one of the user's unused codes.
     */
    public function useRecoveryCode(User $user, string $code): bool
    {
        $code = Str::lower(trim($code));
        $codes = $user->two_factor_recovery_codes ?? [];

        foreach ($codes as $index => $stored) {
            if (hash_equals($stored, $code)) {
                unset($codes[$index]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

                return true;
            }
        }

        return false;
    }

    private function replayKey(int $userId): string
    {
        return 'two-factor:last-timestamp:'.$userId;
    }
}
