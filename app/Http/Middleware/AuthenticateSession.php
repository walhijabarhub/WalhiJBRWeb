<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Session\Middleware\AuthenticateSession as BaseAuthenticateSession;

class AuthenticateSession extends BaseAuthenticateSession
{
    public function handle($request, Closure $next)
    {
        // Stateless authentication has no persisted login to revoke.
        if (! $request->hasSession() || ! $request->user()
            || ! $request->session()->has($this->guard()->getName())) {
            return $next($request);
        }

        // Do not let Laravel adopt today's password hash for a pre-deployment login.
        // Remember-cookie logins are checked separately by the framework middleware.
        if (! $this->guard()->viaRemember()
            && ! $request->session()->has('password_hash_'.$this->auth->getDefaultDriver())) {
            $this->logout($request);
        }

        return parent::handle($request, $next);
    }
}
