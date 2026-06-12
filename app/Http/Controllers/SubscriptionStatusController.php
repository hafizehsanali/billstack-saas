<?php

namespace App\Http\Controllers;

use App\Models\PlatformSubscriptionInvoice;
use Illuminate\View\View;

class SubscriptionStatusController extends Controller
{
    public function show(): View
    {
        $tenant = auth()->user()->tenant?->load('currentSubscription.plan');

        return view('subscription.status', [
            'tenant' => $tenant,
            'subscription' => $tenant?->currentSubscription,
            'requiresPayment' => $tenant?->currentSubscription?->plan?->monthly_price_cents > 0,
        ]);
    }

    public function outcome(): View
    {
        $tenant = auth()->user()->tenant?->load('currentSubscription.plan');
        $invoice = PlatformSubscriptionInvoice::with(['paymentSubmission', 'subscription.plan'])
            ->where('tenant_id', $tenant?->id)
            ->latest()
            ->first();

        $state = match (true) {
            $invoice?->status === 'paid' => 'approved',
            $invoice?->paymentSubmission?->status === 'pending' => 'pending',
            $invoice?->paymentSubmission?->status === 'rejected' => 'rejected',
            $invoice?->status === 'cancelled' => 'cancelled',
            in_array($invoice?->status, ['unpaid', 'overdue'], true) => 'payment_required',
            default => 'expired',
        };

        return view('subscription.outcome', [
            'tenant' => $tenant,
            'subscription' => $tenant?->currentSubscription,
            'invoice' => $invoice,
            'submission' => $invoice?->paymentSubmission,
            'state' => $state,
        ]);
    }
}
