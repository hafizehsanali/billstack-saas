<?php

namespace App\Http\Controllers;

use App\Models\PlatformOffer;
use App\Models\PlatformSubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Services\TenantUsageLimitService;
use Illuminate\Contracts\View\View;

class TenantBillingController extends Controller
{
    public function index(TenantUsageLimitService $usageLimits): View
    {
        $tenant = auth()->user()->tenant()->with([
            'currentSubscription.plan',
            'activeSubscription.plan',
        ])->firstOrFail();

        $invoices = PlatformSubscriptionInvoice::with('payments')
            ->where('tenant_id', $tenant->id)
            ->latest('issued_on')
            ->latest()
            ->paginate(10);

        $billingSummary = PlatformSubscriptionInvoice::where('tenant_id', $tenant->id)
            ->where('status', '!=', 'cancelled')
            ->selectRaw('COALESCE(SUM(total_cents), 0) as total_billed_cents')
            ->selectRaw('COALESCE(SUM(paid_cents), 0) as total_paid_cents')
            ->selectRaw('COALESCE(SUM(balance_cents), 0) as total_due_cents')
            ->first();

        return view('billing.index', [
            'tenant' => $tenant,
            'subscription' => $tenant->currentSubscription,
            'activeSubscription' => $tenant->activeSubscription,
            'invoices' => $invoices,
            'billingSummary' => $billingSummary,
            'usage' => $usageLimits->summary($tenant),
            'plans' => SubscriptionPlan::with('features')
                ->where('is_public', true)
                ->where('is_active', true)
                ->orderBy('monthly_price_cents')
                ->orderBy('name')
                ->get(),
            'offers' => PlatformOffer::with('plans')
                ->where('is_active', true)
                ->get()
                ->filter->isCurrentlyAvailable(),
        ]);
    }
}
