<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreBillingInvoiceRequest;
use App\Http\Requests\Platform\StoreBillingPaymentRequest;
use App\Models\PlatformSubscriptionInvoice;
use App\Models\Tenant;
use App\Services\PlatformBillingService;
use Illuminate\Http\RedirectResponse;
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

    public function create(): View
    {
        return view('platform.billing.create', [
            'tenants' => Tenant::with('currentSubscription.plan')
                ->whereHas('currentSubscription')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(
        StoreBillingInvoiceRequest $request,
        PlatformBillingService $billing
    ): RedirectResponse {
        $invoice = $billing->createInvoice(
            Tenant::findOrFail($request->integer('tenant_id')),
            $request->validated()
        );

        return redirect()
            ->route('platform.billing.show', $invoice)
            ->with('success', 'Subscription invoice created successfully.');
    }

    public function show(PlatformSubscriptionInvoice $invoice): View
    {
        return view('platform.billing.show', [
            'invoice' => $invoice->load(['tenant', 'subscription.plan', 'payments', 'offer']),
        ]);
    }

    public function storePayment(
        StoreBillingPaymentRequest $request,
        PlatformSubscriptionInvoice $invoice,
        PlatformBillingService $billing
    ): RedirectResponse {
        $billing->recordPayment($invoice, $request->validated());

        return redirect()
            ->route('platform.billing.show', $invoice)
            ->with('success', 'Subscription payment recorded successfully.');
    }
}
