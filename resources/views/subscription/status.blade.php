@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-body text-center py-5">
        <h1 class="mb-2">Subscription Attention Required</h1>
        <p class="text-muted mb-4">
            {{ $tenant?->name ?? 'This business' }} does not currently have an active subscription.
        </p>

        <div class="row justify-content-center mb-4">
            <div class="col-md-6 col-lg-4">
                <div class="border rounded p-3 text-start">
                    <div class="text-secondary small fw-semibold text-uppercase">Current Plan</div>
                    <div class="h4 mb-3">{{ $subscription?->plan?->name ?? 'Not assigned' }}</div>

                    <div class="text-secondary small fw-semibold text-uppercase">Status</div>
                    <div>
                        <span class="badge {{ $subscription?->status === 'active' ? 'bg-success text-white' : 'bg-light text-dark border' }}">
                            {{ $subscription?->status ?? 'pending' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        @if($requiresPayment)
            <a href="{{ route('subscription.checkout') }}" class="btn btn-primary">
                Purchase {{ $subscription->plan->name }}
            </a>
            <div class="text-muted small mt-3">
                Full payment is required to activate paid access.
            </div>
        @else
            <a href="{{ route('plans.index') }}" class="btn btn-primary">
                Choose Another Package
            </a>
            <div class="text-muted small mt-3">
                Free access has ended. Select an available package to continue.
            </div>
        @endif
    </div>
</div>
@endsection
