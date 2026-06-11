<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\UpdateTenantSubscriptionRequest;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Services\TenantUsageLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(TenantUsageLimitService $usageLimits): View
    {
        $tenants = Tenant::with(['currentSubscription.plan', 'activeSubscription.plan'])
            ->withCount(['users'])
            ->latest()
            ->paginate(15);

        $usage = $tenants->getCollection()
            ->mapWithKeys(fn (Tenant $tenant) => [
                $tenant->id => $usageLimits->summary($tenant),
            ]);

        return view('platform.tenants.index', [
            'tenants' => $tenants,
            'usage' => $usage,
        ]);
    }

    public function edit(Tenant $tenant): View
    {
        return view('platform.tenants.edit', [
            'tenant' => $tenant->load(['currentSubscription.plan']),
            'plans' => SubscriptionPlan::where('is_active', true)
                ->orderBy('monthly_price_cents')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(UpdateTenantSubscriptionRequest $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validated();
        $subscription = $tenant->currentSubscription;

        if ($data['status'] === 'active') {
            if ($subscription) {
                $subscription->update([
                    'subscription_plan_id' => $data['subscription_plan_id'],
                    'status' => 'active',
                    'trial_ends_at' => $data['trial_ends_at'] ?? null,
                    'ends_at' => null,
                ]);
            } else {
                $tenant->subscriptions()->create([
                    'subscription_plan_id' => $data['subscription_plan_id'],
                    'status' => 'active',
                    'starts_at' => now(),
                    'trial_ends_at' => $data['trial_ends_at'] ?? null,
                ]);
            }
        } elseif ($subscription) {
            $subscription->update([
                'status' => $data['status'],
                'ends_at' => $data['ends_at'] ?? now(),
            ]);
        }

        return redirect()
            ->route('platform.tenants.index')
            ->with('success', 'Tenant subscription updated successfully.');
    }
}
