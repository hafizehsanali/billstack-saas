<?php

namespace App\Http\Middleware;

use App\Services\TenantSubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantSubscriptionIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isPlatformAdmin()) {
            return $next($request);
        }

        if ($request->routeIs('subscription.*', 'profile.*', 'logout')) {
            return $next($request);
        }

        $tenant = $user->tenant;

        if (! $tenant) {
            return redirect()->route('subscription.status');
        }

        if (! $tenant->currentSubscription) {
            app(TenantSubscriptionService::class)->assignDefaultPlan($tenant);
            $tenant->refresh();
        }

        $subscription = $tenant->currentSubscription;

        if (
            $subscription?->status === 'active'
            && $subscription->trial_ends_at
            && $subscription->trial_ends_at->isPast()
        ) {
            $subscription->update([
                'status' => 'paused',
                'ends_at' => now(),
            ]);
            $tenant->unsetRelation('activeSubscription');
        }

        if ($tenant->activeSubscription) {
            return $next($request);
        }

        return redirect()->route('subscription.status');
    }
}
