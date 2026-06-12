<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailVerificationIsRequired
{
    public function handle(
        Request $request,
        Closure $next,
        ?string $redirectToRoute = null
    ): Response {
        if (! config('auth.require_email_verification')) {
            return $next($request);
        }

        return app(EnsureEmailIsVerified::class)->handle(
            $request,
            $next,
            $redirectToRoute
        );
    }
}
