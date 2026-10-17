<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Second login step for accounts with two-factor authentication enabled.
 * The password step stores the pending user id in the session without logging in.
 */
class TwoFactorChallengeController extends Controller
{
    public const SESSION_KEY = 'login.two_factor';

    private const MAX_ATTEMPTS = 5;

    public function __construct(private readonly TwoFactorService $twoFactor) {}

    public function create(Request $request): View|RedirectResponse
    {
        if (! $this->pendingUser($request)) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login');
        }

        $request->validate([
            'code' => ['nullable', 'string', 'max:16'],
            'recovery_code' => ['nullable', 'string', 'max:32'],
        ]);

        $throttleKey = 'two-factor-challenge:'.$user->id;
        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'code' => trans('auth.throttle', [
                    'seconds' => RateLimiter::availableIn($throttleKey),
                    'minutes' => ceil(RateLimiter::availableIn($throttleKey) / 60),
                ]),
            ]);
        }

        $valid = $request->filled('recovery_code')
            ? $this->twoFactor->useRecoveryCode($user, (string) $request->input('recovery_code'))
            : $this->twoFactor->verify($user->two_factor_secret, (string) $request->input('code'), $user->id);

        if (! $valid) {
            RateLimiter::hit($throttleKey, 300);

            throw ValidationException::withMessages([
                'code' => 'Kode verifikasi tidak valid.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        $remember = (bool) $request->session()->get(self::SESSION_KEY.'.remember', false);
        $request->session()->forget(self::SESSION_KEY);

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard', absolute: false));
    }

    private function pendingUser(Request $request): ?User
    {
        $pending = $request->session()->get(self::SESSION_KEY);

        // The pending login expires after 5 minutes.
        if (! is_array($pending) || ($pending['at'] ?? 0) < now()->subMinutes(5)->getTimestamp()) {
            return null;
        }

        $user = User::find($pending['id'] ?? null);

        return $user && $user->hasTwoFactorEnabled() ? $user : null;
    }
}
