<?php

namespace App\Http\Middleware;

use App\Services\TenantFeatureService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantFeatureIsEnabled
{
    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        if (app(TenantFeatureService::class)->userHasFeature($request->user(), $featureKey)) {
            return $next($request);
        }

        return redirect()->route('features.unavailable', ['feature' => $featureKey]);
    }
}
