<?php

namespace App\Http\Middleware;

use App\Services\TenantModuleService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantModuleIsEnabled
{
    public function handle(Request $request, Closure $next, string $moduleKey): Response
    {
        $user = $request->user();

        if ($user?->isPlatformAdmin() || app(TenantModuleService::class)->hasModule($user?->tenant, $moduleKey)) {
            return $next($request);
        }

        abort(403, 'This module is not enabled for this business.');
    }
}
