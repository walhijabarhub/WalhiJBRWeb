<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Verifies a Cloudflare Turnstile token with the siteverify API.
 *
 * Turnstile is optional: it is only enforced when both TURNSTILE_SITE_KEY and
 * TURNSTILE_SECRET_KEY are configured (see rules()).
 */
class Turnstile implements ValidationRule
{
    public const FIELD = 'cf-turnstile-response';

    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public static function enabled(): bool
    {
        return filled(config('services.turnstile.site_key')) && filled(config('services.turnstile.secret_key'));
    }

    /**
     * Validation rules to merge into a public form request (empty when disabled).
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return self::enabled()
            ? [self::FIELD => ['required', 'string', 'max:2048', new self]]
            : [];
    }

    public static function message(): string
    {
        return 'Verifikasi keamanan gagal. Muat ulang halaman lalu coba lagi.';
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post(self::VERIFY_URL, [
                    'secret' => config('services.turnstile.secret_key'),
                    'response' => (string) $value,
                    'remoteip' => request()->ip(),
                ]);

            if ($response->successful() && $response->json('success') === true) {
                return;
            }

            Log::info('Turnstile verification rejected', ['errors' => $response->json('error-codes')]);
        } catch (\Throwable $e) {
            Log::warning('Turnstile verification unavailable', ['error' => $e->getMessage()]);
        }

        $fail(self::message());
    }
}
