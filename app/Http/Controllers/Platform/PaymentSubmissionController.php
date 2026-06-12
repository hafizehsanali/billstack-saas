<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\RejectSubscriptionPaymentSubmissionRequest;
use App\Models\SubscriptionPaymentSubmission;
use App\Services\PlatformBillingService;
use App\Services\SubscriptionPaymentSubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Services\PlatformActivityService;

class PaymentSubmissionController extends Controller
{
    public function index(Request $request): View
    {
        $submissions = SubscriptionPaymentSubmission::query()
            ->with([
                'tenant',
                'invoice.subscription.plan',
                'submitter',
            ])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function ($query) use ($search): void {
                    $query->where('reference_no', 'like', "%{$search}%")
                        ->orWhereHas('tenant', fn ($tenants) => $tenants->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('invoice', fn ($invoices) => $invoices->where('invoice_no', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where(
                'status',
                $request->string('status')->toString()
            ))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('platform.payment-submissions.index', [
            'submissions' => $submissions,
            'pendingCount' => SubscriptionPaymentSubmission::where('status', 'pending')->count(),
        ]);
    }

    public function approve(
        SubscriptionPaymentSubmission $submission,
        SubscriptionPaymentSubmissionService $submissions,
        PlatformBillingService $billing,
        PlatformActivityService $activity
    ): RedirectResponse {
        $submissions->approve($submission, auth()->user(), $billing);
        $submission->loadMissing(['tenant', 'invoice']);
        $activity->record(
            'payment_submission.approved',
            "Approved full payment for {$submission->invoice?->invoice_no}.",
            $submission,
            $submission->tenant
        );

        return back()->with('success', 'Full subscription payment approved and the plan activated.');
    }

    public function reject(
        RejectSubscriptionPaymentSubmissionRequest $request,
        SubscriptionPaymentSubmission $submission,
        SubscriptionPaymentSubmissionService $submissions,
        PlatformActivityService $activity
    ): RedirectResponse {
        $submissions->reject(
            $submission,
            $request->user(),
            $request->validated('rejection_reason')
        );
        $submission->loadMissing(['tenant', 'invoice']);
        $activity->record(
            'payment_submission.rejected',
            "Rejected payment reference for {$submission->invoice?->invoice_no}.",
            $submission,
            $submission->tenant
        );

        return back()->with('success', 'Payment submission rejected.');
    }
}
