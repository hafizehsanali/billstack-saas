<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformSubscriptionInvoice;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function index(): View
    {
        $invoices = PlatformSubscriptionInvoice::with(['tenant', 'subscription.plan'])
            ->latest('issued_on')
            ->latest()
            ->paginate(15);

        return view('platform.billing.index', [
            'invoices' => $invoices,
            'totalBilledCents' => PlatformSubscriptionInvoice::sum('total_cents'),
            'totalPaidCents' => PlatformSubscriptionInvoice::sum('paid_cents'),
            'totalDueCents' => PlatformSubscriptionInvoice::sum('balance_cents'),
            'overdueCount' => PlatformSubscriptionInvoice::where('status', '!=', 'paid')
                ->whereDate('due_on', '<', today())
                ->count(),
        ]);
    }

    public function show(PlatformSubscriptionInvoice $invoice): View
    {
        return view('platform.billing.show', [
            'invoice' => $invoice->load(['tenant', 'subscription.plan', 'payments']),
        ]);
    }
}
