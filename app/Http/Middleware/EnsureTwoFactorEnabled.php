<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends admin/editor accounts that have not enrolled in two-factor authentication
 * to the enrolment page before they can use the admin area.
 */
class EnsureTwoFactorEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->requiresTwoFactor() && ! $user->hasTwoFactorEnabled()) {
            if ($request->expectsJson()) {
                abort(403, 'Autentikasi dua langkah wajib diaktifkan.');
            }

            return redirect()->route('two-factor.show');
        }

        return $next($request);
    }
}
