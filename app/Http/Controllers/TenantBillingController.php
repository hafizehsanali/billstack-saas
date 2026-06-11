<?php

namespace App\Http\Controllers;

use App\Models\PlatformSubscriptionInvoice;
use Illuminate\Contracts\View\View;

class TenantBillingController extends Controller
{
    public function index(): View
    {
        $tenant = auth()->user()->tenant()->with('activeSubscription.plan')->firstOrFail();

        $invoices = PlatformSubscriptionInvoice::with('payments')
            ->where('tenant_id', $tenant->id)
            ->latest('issued_on')
            ->latest()
            ->paginate(10);

        $billingSummary = PlatformSubscriptionInvoice::where('tenant_id', $tenant->id)
            ->selectRaw('COALESCE(SUM(total_cents), 0) as total_billed_cents')
            ->selectRaw('COALESCE(SUM(paid_cents), 0) as total_paid_cents')
            ->selectRaw('COALESCE(SUM(balance_cents), 0) as total_due_cents')
            ->first();

        return view('billing.index', [
            'tenant' => $tenant,
            'subscription' => $tenant->activeSubscription,
            'invoices' => $invoices,
            'billingSummary' => $billingSummary,
        ]);
    }
}
