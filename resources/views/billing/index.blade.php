@extends('layouts.app')

@section('content')

@php
    $plan = $subscription?->plan;
    $activePlan = $activeSubscription?->plan;
    $isTrial = $subscription?->status === 'active'
        && $subscription?->trial_ends_at?->isFuture()
        && $plan?->monthly_price_cents > 0;
    $requiresPayment = $subscription?->status !== 'active'
        && $plan?->monthly_price_cents > 0;
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <div>
        <h3 class="mb-1">Plan & Billing</h3>
        <div class="text-muted">
            Review your subscription, change packages, and manage payments for {{ $tenant->name }}.
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="#available-packages" class="btn btn-outline-secondary">
            <i data-lucide="arrow-up-down"></i>
            Change Package
        </a>
        @if($isTrial || $requiresPayment)
            <a href="{{ route('subscription.checkout') }}" class="btn btn-primary">
                <i data-lucide="{{ $isTrial ? 'credit-card' : 'arrow-right' }}"></i>
                {{ $isTrial ? 'Pay Early' : 'Continue Payment' }}
            </a>
        @endif
    </div>
</div>

<div class="row row-cards mb-3">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Current Plan</div>
                <div class="h3 mb-0">{{ $activePlan?->name ?? $plan?->name ?? 'Not assigned' }}</div>
                <div class="text-muted small">
                    {{ $activePlan?->user_limit ? $activePlan->user_limit.' users' : 'Unlimited users' }}
                </div>
                @if($isTrial)
                    <span class="badge bg-warning text-dark mt-2">
                        Trial ends {{ $subscription->trial_ends_at->format('M d, Y') }}
                    </span>
                @elseif($activeSubscription)
                    <span class="badge bg-success text-white mt-2">Active</span>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Total Billed</div>
                <div class="h3 mb-0">Rs {{ number_format($billingSummary->total_billed_cents / 100, 2) }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Total Paid</div>
                <div class="h3 mb-0 text-success">Rs {{ number_format($billingSummary->total_paid_cents / 100, 2) }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Amount Due</div>
                <div class="h3 mb-0 {{ $billingSummary->total_due_cents > 0 ? 'text-danger' : 'text-success' }}">
                    Rs {{ number_format($billingSummary->total_due_cents / 100, 2) }}
                </div>
            </div>
        </div>
    </div>
</div>

@if($subscription && $activeSubscription && $subscription->id !== $activeSubscription->id)
    <div class="alert alert-info d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <div class="fw-semibold">{{ $plan->name }} is selected and awaiting full payment.</div>
            <div class="small">
                {{ $activePlan->name }} remains active until the new package payment is approved.
            </div>
        </div>
        <a href="{{ route('subscription.checkout') }}" class="btn btn-primary btn-sm">Continue Payment</a>
    </div>
@endif

<div class="card mb-3">
    <div class="card-header">
        <div>
            <h3 class="card-title mb-1">Plan Usage</h3>
            <div class="text-muted small">Invoice usage resets at the beginning of each calendar month.</div>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-4">
            @foreach([
                'products' => 'Products',
                'monthly_invoices' => 'Invoices This Month',
            ] as $usageKey => $label)
                @php
                    $item = $usage[$usageKey];
                    $barClass = match ($item['status']) {
                        'limit' => 'bg-danger',
                        'warning' => 'bg-warning',
                        default => 'bg-primary',
                    };
                @endphp
                <div class="col-md-6">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="fw-semibold">{{ $label }}</div>
                        <div class="{{ $item['status'] === 'limit' ? 'text-danger fw-semibold' : 'text-muted' }}">
                            {{ $item['used'] }} of {{ $item['limit'] ?? 'Unlimited' }}
                        </div>
                    </div>
                    @if($item['limit'] !== null)
                        <div class="progress" style="height: 8px;" role="progressbar"
                             aria-label="{{ $label }} usage"
                             aria-valuenow="{{ $item['percentage'] }}"
                             aria-valuemin="0"
                             aria-valuemax="100">
                            <div class="progress-bar {{ $barClass }}"
                                 style="width: {{ $item['percentage'] }}%"></div>
                        </div>
                    @else
                        <div class="text-success small">No usage limit on this plan.</div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>

<section id="available-packages" class="mb-3">
    <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-3">
        <div>
            <h3 class="mb-1">Available Packages</h3>
            <div class="text-muted">
                Upgrade or downgrade at any time. Paid package changes activate after full payment approval.
            </div>
        </div>
        <a href="{{ route('plans.index') }}" class="btn btn-outline-secondary btn-sm">
            Full Package Comparison
        </a>
    </div>

    <div class="row g-3">
        @foreach($plans as $availablePlan)
            @php
                $isActivePlan = $activeSubscription?->subscription_plan_id === $availablePlan->id;
                $isPendingPlan = $subscription?->status !== 'active'
                    && $subscription?->subscription_plan_id === $availablePlan->id;
                $availableOffers = $offers->filter(fn ($offer) => $offer->appliesTo($availablePlan));
                $activePrice = $activePlan?->monthly_price_cents ?? 0;
                $changeLabel = match(true) {
                    $availablePlan->monthly_price_cents === 0 => 'Downgrade to Free',
                    $availablePlan->monthly_price_cents > $activePrice => 'Upgrade Package',
                    $availablePlan->monthly_price_cents < $activePrice => 'Downgrade Package',
                    default => 'Switch Package',
                };
            @endphp

            <div class="col-md-6 col-xl-4">
                <article class="card h-100 subscription-plan-option {{ $isActivePlan ? 'is-current' : '' }}">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-start justify-content-between gap-2">
                            <div>
                                <h4 class="mb-1">{{ $availablePlan->name }}</h4>
                                <div class="text-muted small">
                                    {{ $availablePlan->user_limit ? 'Up to '.$availablePlan->user_limit.' users' : 'Unlimited users' }}
                                </div>
                            </div>
                            @if($isActivePlan)
                                <span class="badge bg-success text-white">Current</span>
                            @elseif($isPendingPlan)
                                <span class="badge bg-warning text-dark">Selected</span>
                            @endif
                        </div>

                        <div class="subscription-plan-price">
                            @if($availablePlan->monthly_price_cents > 0)
                                <strong>Rs {{ number_format($availablePlan->monthly_price_cents / 100, 0) }}</strong>
                                <span>/month</span>
                                @if($availablePlan->annual_price_cents > 0)
                                    <small>Rs {{ number_format($availablePlan->annual_price_cents / 100, 0) }} annually</small>
                                @endif
                            @else
                                <strong>Free</strong>
                                <small>
                                    {{ $availablePlan->free_access_days
                                        ? $availablePlan->free_access_days.' days access'
                                        : 'No time limit' }}
                                </small>
                            @endif
                        </div>

                        <p class="text-muted small">{{ $availablePlan->description }}</p>

                        <div class="small mb-3">
                            <div><i data-lucide="package-check"></i> {{ $availablePlan->product_limit ?? 'Unlimited' }} products</div>
                            <div class="mt-1"><i data-lucide="receipt-text"></i> {{ $availablePlan->monthly_invoice_limit ?? 'Unlimited' }} invoices monthly</div>
                        </div>

                        @if($availableOffers->isNotEmpty())
                            <div class="subscription-plan-offer">
                                <i data-lucide="badge-percent"></i>
                                <span>
                                    Offers:
                                    {{ $availableOffers->pluck('code')->join(', ') }}
                                </span>
                            </div>
                        @endif

                        <div class="mt-auto pt-3">
                            @if($isActivePlan && $isTrial)
                                <a href="{{ route('subscription.checkout') }}" class="btn btn-primary w-100">
                                    Pay Early
                                </a>
                            @elseif($isActivePlan)
                                <button class="btn btn-outline-secondary w-100" disabled>Current Package</button>
                            @elseif($isPendingPlan)
                                <a href="{{ route('subscription.checkout') }}" class="btn btn-primary w-100">
                                    Continue Payment
                                </a>
                            @else
                                <form method="POST"
                                      action="{{ route('subscription.plans.select', $availablePlan) }}"
                                      @if($availablePlan->monthly_price_cents === 0)
                                          onsubmit="return confirm('Switch to the free package now? Current paid access will end immediately.')"
                                      @endif>
                                    @csrf
                                    <input type="hidden" name="billing_cycle" value="monthly">
                                    <button class="btn {{ $availablePlan->monthly_price_cents > 0 ? 'btn-primary' : 'btn-outline-primary' }} w-100">
                                        {{ $changeLabel }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </article>
            </div>
        @endforeach
    </div>
</section>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Billing Period</th>
                    <th class="text-end">Total</th>
                    <th class="text-end">Paid</th>
                    <th class="text-end">Due</th>
                    <th>Status</th>
                    <th>Payments</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $invoice->invoice_no }}</div>
                            <div class="text-muted small">Issued {{ $invoice->issued_on?->format('M d, Y') ?? '-' }}</div>
                        </td>
                        <td>
                            <div>{{ $invoice->billing_period }}</div>
                            <div class="text-muted small">Due {{ $invoice->due_on?->format('M d, Y') ?? '-' }}</div>
                        </td>
                        <td class="text-end">Rs {{ number_format($invoice->total_cents / 100, 2) }}</td>
                        <td class="text-end text-success">Rs {{ number_format($invoice->paid_cents / 100, 2) }}</td>
                        <td class="text-end {{ $invoice->balance_cents > 0 ? 'text-danger' : 'text-success' }}">
                            Rs {{ number_format($invoice->balance_cents / 100, 2) }}
                        </td>
                        <td>
                            <span class="badge {{ $invoice->status === 'paid' ? 'bg-success text-white' : 'bg-warning text-dark' }}">
                                {{ str($invoice->status)->replace('_', ' ')->title() }}
                            </span>
                        </td>
                        <td>
                            @forelse($invoice->payments as $payment)
                                <div class="small">
                                    Rs {{ number_format($payment->amount_cents / 100, 2) }}
                                    <span class="text-muted">
                                        {{ $payment->paid_on?->format('M d, Y') ?? '-' }}
                                    </span>
                                </div>
                            @empty
                                <span class="text-muted small">No payment recorded</span>
                            @endforelse
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            No subscription invoices found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $invoices->links() }}
</div>

@endsection
