<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signed-link check that does not depend on the host the link arrives on.
 *
 * nginx redirects the bare domain to www, and links built in the queue worker use
 * APP_URL, so a link can be signed for one host and opened on another. Accepted:
 *  - a signature over the path only (new links: URL::temporarySignedRoute(..., absolute: false));
 *  - a signature over the URL as requested (Laravel's default `signed` behaviour);
 *  - a signature over the same path on APP_URL (links e-mailed before this change).
 * Expiry and tampering are still checked by Laravel in every case.
 */
class ValidateSignatureAnyHost
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasValidSignature(false) || $request->hasValidSignature() || $this->hasValidSignatureForAppUrl($request)) {
            return $next($request);
        }

        throw new InvalidSignatureException;
    }

    private function hasValidSignatureForAppUrl(Request $request): bool
    {
        $root = rtrim((string) config('app.url'), '/');
        if ($root === '' || $root === $request->root()) {
            return false;
        }

        $query = (string) $request->server->get('QUERY_STRING');
        $asSigned = Request::create($root.'/'.ltrim($request->path(), '/').($query !== '' ? '?'.$query : ''));

        return URL::hasValidSignature($asSigned);
    }
}
