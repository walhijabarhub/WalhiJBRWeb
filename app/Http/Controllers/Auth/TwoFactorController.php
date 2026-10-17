<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use App\Services\Auth\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Enrolment and management of TOTP two-factor authentication for the signed-in user.
 * The secret stays in the (encrypted) session until the user confirms a valid code.
 */
class TwoFactorController extends Controller
{
    private const PENDING_SECRET = 'two_factor.pending_secret';

    private const NEW_RECOVERY_CODES = 'two_factor.recovery_codes';

    public function __construct(private readonly TwoFactorService $twoFactor) {}

    public function show(Request $request): View
    {
        $user = $request->user();
        $secret = null;
        $qrCodeSvg = null;

        if (! $user->hasTwoFactorEnabled()) {
            $secret = $request->session()->get(self::PENDING_SECRET);
            if (! $secret) {
                $secret = $this->twoFactor->generateSecret();
                $request->session()->put(self::PENDING_SECRET, $secret);
            }
            $qrCodeSvg = $this->twoFactor->qrCodeSvg($user, $secret);
        }

        return view('auth.two-factor-setup', [
            'user' => $user,
            'secret' => $secret,
            'qrCodeSvg' => $qrCodeSvg,
            'recoveryCodes' => $request->session()->pull(self::NEW_RECOVERY_CODES),
        ]);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'max:16']]);

        $user = $request->user();
        $secret = $request->session()->get(self::PENDING_SECRET);

        if (! $secret || ! $this->twoFactor->verify($secret, (string) $request->input('code'), $user->id)) {
            throw ValidationException::withMessages(['code' => 'Kode verifikasi tidak valid. Periksa jam perangkat Anda lalu coba lagi.']);
        }

        $codes = $this->twoFactor->generateRecoveryCodes();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $codes,
            'two_factor_confirmed_at' => now(),
        ])->save();

        $request->session()->forget(self::PENDING_SECRET);
        $request->session()->flash(self::NEW_RECOVERY_CODES, $codes);

        AuditLogService::log('TWO_FACTOR_ENABLED', 'User', $user->id);

        return redirect()->route('two-factor.show')->with('status', 'Autentikasi dua langkah berhasil diaktifkan.');
    }

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);

        $user = $request->user();
        abort_unless($user->hasTwoFactorEnabled(), 404);

        $codes = $this->twoFactor->generateRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => $codes])->save();
        $request->session()->flash(self::NEW_RECOVERY_CODES, $codes);

        AuditLogService::log('TWO_FACTOR_RECOVERY_CODES_REGENERATED', 'User', $user->id);

        return redirect()->route('two-factor.show')->with('status', 'Kode pemulihan baru telah dibuat.');
    }

    public function disable(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);

        $user = $request->user();

        if ($user->requiresTwoFactor()) {
            throw ValidationException::withMessages(['password' => 'Autentikasi dua langkah wajib untuk akun admin dan editor.']);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        AuditLogService::log('TWO_FACTOR_DISABLED', 'User', $user->id);

        return redirect()->route('two-factor.show')->with('status', 'Autentikasi dua langkah dinonaktifkan.');
    }
}
