<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Organisation domains that may be omitted when signing in with a username.
     */
    private const USERNAME_DOMAINS = ['walhijabar.co.id', 'walhijabar.or.id', 'walhi-jabar.org'];

    /**
     * Attempts allowed per account + IP, and per account across all IPs, before lockout.
     */
    private const MAX_ATTEMPTS_PER_IP = 5;

    private const MAX_ATTEMPTS_PER_ACCOUNT = 20;

    private ?User $resolvedUser = null;

    private bool $resolved = false;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Validate the credentials without logging the user in, so the caller can require
     * a second factor first.
     *
     * Only exact matches are accepted: a full email address, or a username that equals
     * the local part of an address on one of the organisation domains.
     *
     * @throws ValidationException
     */
    public function authenticate(): User
    {
        $this->ensureIsNotRateLimited();

        $user = $this->resolveUser();

        if (! $user || ! Auth::getProvider()->validateCredentials($user, ['password' => (string) $this->input('password')])) {
            RateLimiter::hit($this->throttleKey(), 60);
            RateLimiter::hit($this->accountThrottleKey(), 900);
            event(new Failed('web', $user, ['email' => $this->normalizedLogin()]));

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        return $user;
    }

    /**
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $key = match (true) {
            RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS_PER_IP) => $this->throttleKey(),
            RateLimiter::tooManyAttempts($this->accountThrottleKey(), self::MAX_ATTEMPTS_PER_ACCOUNT) => $this->accountThrottleKey(),
            default => null,
        };

        if ($key === null) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($key);

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Throttle key per account (or per normalised login when no account matches) and IP.
     */
    public function throttleKey(): string
    {
        return $this->accountThrottleKey().'|'.$this->ip();
    }

    private function accountThrottleKey(): string
    {
        $user = $this->resolveUser();

        return $user ? 'login:user:'.$user->id : 'login:name:'.sha1($this->normalizedLogin());
    }

    private function resolveUser(): ?User
    {
        if ($this->resolved) {
            return $this->resolvedUser;
        }

        $this->resolved = true;
        $login = $this->normalizedLogin();

        if ($login === '') {
            return null;
        }

        if (str_contains($login, '@')) {
            return $this->resolvedUser = filter_var($login, FILTER_VALIDATE_EMAIL)
                ? User::where('email', $login)->first()
                : null;
        }

        if (! preg_match('/^[a-z0-9._-]+$/', $login)) {
            return null;
        }

        $candidates = array_map(fn (string $domain) => $login.'@'.$domain, self::USERNAME_DOMAINS);

        return $this->resolvedUser = User::whereIn('email', $candidates)->orderBy('id')->first();
    }

    private function normalizedLogin(): string
    {
        return Str::lower(trim((string) $this->input('email')));
    }
}
