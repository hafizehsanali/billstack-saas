@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <div>
        <h1 class="mb-1">Purchase Subscription</h1>
        <div class="text-muted">Activate {{ $subscription->plan->name }} for {{ $tenant->name }}.</div>
    </div>
    <a href="{{ route('plans.index') }}" class="btn btn-outline-secondary">Compare Packages</a>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Package Summary</h2>
            </div>
            <div class="card-body">
                <div class="h2 mb-1">{{ $subscription->plan->name }}</div>
                <div class="text-muted mb-4">{{ $subscription->plan->description }}</div>

                <dl class="row mb-0">
                    <dt class="col-6">Monthly Price</dt>
                    <dd class="col-6 text-end fw-bold">
                        Rs {{ number_format($subscription->plan->monthly_price_cents / 100, 2) }}
                    </dd>

                    <dt class="col-6">Users</dt>
                    <dd class="col-6 text-end">{{ $subscription->plan->user_limit ?? 'Unlimited' }}</dd>

                    <dt class="col-6">Annual Price</dt>
                    <dd class="col-6 text-end fw-bold">
                        Rs {{ number_format($subscription->plan->annual_price_cents / 100, 2) }}
                    </dd>

                    <dt class="col-6">Payment</dt>
                    <dd class="col-6 text-end">Full payment</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Payment Status</h2>
            </div>
            <div class="card-body">
                @if($invoice)
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <div class="text-muted">Invoice</div>
                            <div class="fw-bold">{{ $invoice->invoice_no }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted">Amount Due</div>
                            <div class="h3 mb-0 text-danger">
                                Rs {{ number_format($invoice->balance_cents / 100, 2) }}
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted">Billing Cycle</div>
                            <div>{{ str($invoice->billing_cycle)->title() }}</div>
                        </div>
                        @if($invoice->offer_code)
                            <div class="col-sm-6">
                                <div class="text-muted">Promotion</div>
                                <div>
                                    <span class="badge bg-warning text-dark">{{ $invoice->offer_code }}</span>
                                    <span class="text-success ms-1">
                                        - Rs {{ number_format($invoice->discount_cents / 100, 2) }}
                                    </span>
                                </div>
                            </div>
                        @endif
                        <div class="col-sm-6">
                            <div class="text-muted">Issued</div>
                            <div>{{ $invoice->issued_on?->format('M d, Y') }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted">Due</div>
                            <div>{{ $invoice->due_on?->format('M d, Y') }}</div>
                        </div>
                    </div>

                    <div class="alert alert-info mb-0">
                        <div class="fw-semibold mb-1">Full payment instructions</div>
                        <div>
                            {{ $platformSettings->payment_instructions
                                ?: 'Contact platform support to complete the full subscription payment.' }}
                        </div>
                        @if($platformSettings->support_email || $platformSettings->support_phone)
                            <div class="mt-2 small">
                                Support:
                                {{ collect([
                                    $platformSettings->support_email,
                                    $platformSettings->support_phone,
                                ])->filter()->join(' | ') }}
                            </div>
                        @endif
                    </div>
                @else
                    <p class="text-muted">
                        Create the subscription invoice to begin the purchase process.
                        An eligible promotion code will be applied before the amount is finalized.
                    </p>
                    <form method="POST" action="{{ route('subscription.checkout.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Billing Cycle <span class="text-danger">*</span></label>
                            <div class="btn-group w-100" role="group" aria-label="Billing cycle">
                                <input type="radio"
                                       class="btn-check"
                                       name="billing_cycle"
                                       id="billing_monthly"
                                       value="monthly"
                                       @checked(old('billing_cycle', 'monthly') === 'monthly')>
                                <label class="btn btn-outline-primary" for="billing_monthly">
                                    Monthly - Rs {{ number_format($subscription->plan->monthly_price_cents / 100, 0) }}
                                </label>

                                <input type="radio"
                                       class="btn-check"
                                       name="billing_cycle"
                                       id="billing_annual"
                                       value="annual"
                                       @checked(old('billing_cycle') === 'annual')>
                                <label class="btn btn-outline-primary" for="billing_annual">
                                    Annual - Rs {{ number_format($subscription->plan->annual_price_cents / 100, 0) }}
                                </label>
                            </div>
                            @error('billing_cycle')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="promo_code" class="form-label">Promotion Code</label>
                            <div class="input-group">
                                <input id="promo_code"
                                       type="text"
                                       name="promo_code"
                                       value="{{ old('promo_code') }}"
                                       class="form-control text-uppercase @error('promo_code') is-invalid @enderror"
                                       placeholder="Optional">
                                @error('promo_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            @if($offers->isNotEmpty())
                                <div class="mt-2 d-flex flex-wrap gap-2">
                                    @foreach($offers as $offer)
                                        <span class="badge bg-light text-dark border">
                                            {{ $offer->code }}:
                                            {{ $offer->discount_type === 'percent'
                                                ? $offer->discount_value.'% off'
                                                : 'Rs '.number_format($offer->discount_value / 100, 0).' off' }}
                                            ({{ $offer->billing_cycle === 'both' ? 'monthly/annual' : $offer->billing_cycle }})
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <button class="btn btn-primary">Create Purchase Invoice</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
