<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $user = $request->authenticate();

        $request->session()->regenerate();

        // Password is correct; accounts with 2FA still need the second step before login.
        if ($user->hasTwoFactorEnabled()) {
            $request->session()->put(TwoFactorChallengeController::SESSION_KEY, [
                'id' => $user->id,
                'remember' => $request->boolean('remember'),
                'at' => now()->getTimestamp(),
            ]);

            return redirect()->route('two-factor.challenge');
        }

        Auth::login($user, $request->boolean('remember'));

        return redirect()->intended(route('admin.dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
