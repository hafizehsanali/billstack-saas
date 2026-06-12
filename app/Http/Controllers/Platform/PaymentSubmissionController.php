<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\RejectSubscriptionPaymentSubmissionRequest;
use App\Models\SubscriptionPaymentSubmission;
use App\Services\PlatformBillingService;
use App\Services\SubscriptionPaymentSubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PaymentSubmissionController extends Controller
{
    public function index(): View
    {
        return view('platform.payment-submissions.index', [
            'submissions' => SubscriptionPaymentSubmission::with([
                'tenant',
                'invoice.subscription.plan',
                'submitter',
            ])->latest()->paginate(20),
            'pendingCount' => SubscriptionPaymentSubmission::where('status', 'pending')->count(),
        ]);
    }

    public function approve(
        SubscriptionPaymentSubmission $submission,
        SubscriptionPaymentSubmissionService $submissions,
        PlatformBillingService $billing
    ): RedirectResponse {
        $submissions->approve($submission, auth()->user(), $billing);

        return back()->with('success', 'Full subscription payment approved and the plan activated.');
    }

    public function reject(
        RejectSubscriptionPaymentSubmissionRequest $request,
        SubscriptionPaymentSubmission $submission,
        SubscriptionPaymentSubmissionService $submissions
    ): RedirectResponse {
        $submissions->reject(
            $submission,
            $request->user(),
            $request->validated('rejection_reason')
        );

        return back()->with('success', 'Payment submission rejected.');
    }
}
