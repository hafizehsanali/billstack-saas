<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\UpdateTenantSubscriptionRequest;
use App\Models\PlatformSubscriptionInvoice;
use App\Models\SubscriptionPaymentSubmission;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Services\TenantUsageLimitService;
use App\Services\PlatformActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(Request $request, TenantUsageLimitService $usageLimits): View
    {
        $tenants = Tenant::query()
            ->with(['currentSubscription.plan', 'activeSubscription.plan'])
            ->withCount(['users'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhereHas('users', fn ($users) => $users->where('email', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('plan'), fn ($query) => $query->whereHas(
                'currentSubscription.plan',
                fn ($plans) => $plans->where('subscription_plans.id', $request->integer('plan'))
            ))
            ->when($request->input('status') === 'active', fn ($query) => $query->whereHas('activeSubscription'))
            ->when($request->input('status') === 'inactive', fn ($query) => $query->whereDoesntHave('activeSubscription'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $usage = $tenants->getCollection()
            ->mapWithKeys(fn (Tenant $tenant) => [
                $tenant->id => $usageLimits->summary($tenant),
            ]);

        return view('platform.tenants.index', [
            'tenants' => $tenants,
            'usage' => $usage,
            'plans' => SubscriptionPlan::where('is_active', true)->orderBy('name')->get(),
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

    public function show(Tenant $tenant, TenantUsageLimitService $usageLimits): View
    {
        $tenant->load([
            'currentSubscription.plan',
            'activeSubscription.plan',
            'users',
        ]);

        $billingSummary = PlatformSubscriptionInvoice::where('tenant_id', $tenant->id)
            ->selectRaw('COALESCE(SUM(total_cents), 0) as total_billed_cents')
            ->selectRaw('COALESCE(SUM(paid_cents), 0) as total_paid_cents')
            ->selectRaw('COALESCE(SUM(balance_cents), 0) as total_due_cents')
            ->first();

        return view('platform.tenants.show', [
            'tenant' => $tenant,
            'usage' => $usageLimits->summary($tenant),
            'billingSummary' => $billingSummary,
            'invoices' => PlatformSubscriptionInvoice::with('paymentSubmission')
                ->where('tenant_id', $tenant->id)
                ->latest('issued_on')
                ->take(8)
                ->get(),
            'paymentSubmissions' => SubscriptionPaymentSubmission::with('invoice')
                ->where('tenant_id', $tenant->id)
                ->latest()
                ->take(8)
                ->get(),
        ]);
    }

    public function update(
        UpdateTenantSubscriptionRequest $request,
        Tenant $tenant,
        PlatformActivityService $activity
    ): RedirectResponse
    {
        $data = $request->validated();
        $subscription = $tenant->currentSubscription;
        $previousPlan = $subscription?->plan?->name ?? 'Not assigned';
        $previousStatus = $subscription?->status ?? 'pending';

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

        $updatedSubscription = $tenant->fresh()->currentSubscription;
        $activity->record(
            'tenant.subscription_updated',
            "Changed subscription from {$previousPlan} ({$previousStatus}) to "
                .($updatedSubscription?->plan?->name ?? 'Not assigned')
                .' ('.($updatedSubscription?->status ?? 'pending').').',
            $updatedSubscription,
            $tenant
        );

        return redirect()
            ->route('platform.tenants.index')
            ->with('success', 'Tenant subscription updated successfully.');
    }
}
