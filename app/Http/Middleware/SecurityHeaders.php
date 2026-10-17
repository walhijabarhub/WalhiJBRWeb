<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Generate or retrieve per-request cryptographic nonce for Vite and inline scripts
        $nonce = Vite::cspNonce() ?? Vite::useCspNonce();

        $response = $next($request);

        // Security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->remove('X-Powered-By');
        if (function_exists('header_remove')) {
            @header_remove('X-Powered-By');
        }

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        $loginPath = config('auth.login_path', 'portal-jabar');

        // Prevent search engines from indexing admin, auth, and internal profile pages
        if ($request->is('admin*') || $request->is('dashboard*') || $request->is('login') || $request->is($loginPath) || $request->is($loginPath.'/*') || $request->is('register') || $request->is('profile*') || $request->is('password/*') || $request->is('forgot-password*') || $request->is('reset-password*') || $request->is('verify-email*') || $request->is('confirm-password*')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet');
        }

        // Content Security Policy with dynamic cryptographic nonce
        // No 'unsafe-inline' / 'unsafe-eval' for scripts: inline scripts must carry the nonce,
        // event handlers are bound from resources/js/csp-actions.js, and Alpine uses its CSP build.
        // Third-party scripts are pinned to exact paths (and loaded with SRI) rather than whole CDNs.
        $cspDirectives = [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' https://app.midtrans.com/snap/ https://app.sandbox.midtrans.com/snap/ https://cdn.jsdelivr.net/npm/quill@2.0.2/ https://cdn.jsdelivr.net/npm/chart.js@4.4.7/ https://unpkg.com/lucide@0.460.0/",
            "script-src-attr 'none'",
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://fonts.googleapis.com https://cdn.jsdelivr.net/npm/quill@2.0.2/",
            "font-src 'self' https://fonts.bunny.net https://fonts.gstatic.com data:",
            "img-src 'self' data: blob: https:",
            "connect-src 'self' https://app.midtrans.com https://app.sandbox.midtrans.com https://api.midtrans.com https://api.sandbox.midtrans.com",
            "frame-src 'self' https://app.midtrans.com https://app.sandbox.midtrans.com",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "object-src 'none'",
            "base-uri 'self'",
        ];

        if ($request->isSecure()) {
            $cspDirectives[] = 'upgrade-insecure-requests';
        }

        $response->headers->set('Content-Security-Policy', implode('; ', $cspDirectives));

        return $response;
    }
}
