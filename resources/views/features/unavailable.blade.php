@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-body text-center py-5">
        <h1 class="mb-2">Feature Not Available</h1>
        <p class="text-muted mb-4">
            {{ $feature?->name ?? $featureKey }} is not included in the current plan for {{ $tenant?->name ?? 'this business' }}.
        </p>

        <div class="row justify-content-center mb-4">
            <div class="col-md-6 col-lg-4">
                <div class="border rounded p-3 text-start">
                    <div class="text-secondary small fw-semibold text-uppercase">Current Plan</div>
                    <div class="h4 mb-3">{{ $tenant?->activeSubscription?->plan?->name ?? 'Not assigned' }}</div>

                    <div class="text-secondary small fw-semibold text-uppercase">Requested Feature</div>
                    <div>{{ $feature?->name ?? $featureKey }}</div>
                </div>
            </div>
        </div>

        <p class="text-muted mb-0">
            Ask the business owner or platform administrator about upgrading the plan.
        </p>
    </div>
</div>
@endsection
