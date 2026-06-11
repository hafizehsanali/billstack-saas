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
                            <div class="text-muted">Issued</div>
                            <div>{{ $invoice->issued_on?->format('M d, Y') }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted">Due</div>
                            <div>{{ $invoice->due_on?->format('M d, Y') }}</div>
                        </div>
                    </div>

                    <div class="alert alert-info mb-0">
                        Online payment integration is pending. Complete payment through the configured business
                        payment channel; the platform administrator will record it and activate the package.
                    </div>
                @else
                    <p class="text-muted">
                        Create the full-price subscription invoice to begin the purchase process.
                    </p>
                    <form method="POST" action="{{ route('subscription.checkout.store') }}">
                        @csrf
                        <button class="btn btn-primary">Create Purchase Invoice</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
