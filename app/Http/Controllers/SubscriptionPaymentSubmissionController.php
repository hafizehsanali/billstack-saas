<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubscriptionPaymentSubmissionRequest;
use App\Models\PlatformSubscriptionInvoice;
use App\Services\SubscriptionPaymentSubmissionService;
use Illuminate\Http\RedirectResponse;

class SubscriptionPaymentSubmissionController extends Controller
{
    public function store(
        StoreSubscriptionPaymentSubmissionRequest $request,
        PlatformSubscriptionInvoice $invoice,
        SubscriptionPaymentSubmissionService $submissions
    ): RedirectResponse {
        $submissions->submit($invoice, $request->user(), $request->validated());

        return redirect()->route('subscription.outcome')->with(
            'success',
            'Payment reference submitted. The platform team will review the full payment.'
        );
    }
}
