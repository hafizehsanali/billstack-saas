@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <div>
        <h1 class="mb-1">Plans</h1>
        <div class="text-muted">Control pricing, access periods, user limits, visibility, and enabled features.</div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('platform.dashboard') }}" class="btn btn-outline-secondary">
            Platform
        </a>
        <a href="{{ route('platform.plans.create') }}" class="btn btn-primary">
            Add Plan
        </a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Plan</th>
                    <th class="text-end">Monthly</th>
                    <th class="text-end">Annual</th>
                    <th class="text-end">Users</th>
                    <th class="text-end">Trial</th>
                    <th class="text-end">Free Access</th>
                    <th>Usage Limits</th>
                    <th>Features</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($plans as $plan)
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $plan->name }}</div>
                            <div class="text-muted small">{{ $plan->slug }}</div>
                        </td>
                        <td class="text-end">Rs {{ number_format($plan->monthly_price_cents / 100, 2) }}</td>
                        <td class="text-end">Rs {{ number_format($plan->annual_price_cents / 100, 2) }}</td>
                        <td class="text-end">{{ $plan->user_limit ?? 'Unlimited' }}</td>
                        <td class="text-end">
                            {{ $plan->monthly_price_cents > 0 && $plan->trial_days > 0 ? $plan->trial_days.' days' : 'None' }}
                        </td>
                        <td class="text-end">
                            @if($plan->monthly_price_cents === 0)
                                {{ $plan->free_access_days ? $plan->free_access_days.' days' : 'Permanent' }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <div>{{ $plan->product_limit ?? 'Unlimited' }} products</div>
                            <div class="text-muted small">
                                {{ $plan->monthly_invoice_limit ?? 'Unlimited' }} invoices/month
                            </div>
                        </td>
                        <td>{{ $plan->features_count }} features</td>
                        <td>
                            <span class="badge {{ $plan->is_active ? 'bg-success text-white' : 'bg-light text-dark border' }}">
                                {{ $plan->is_active ? 'Active' : 'Inactive' }}
                            </span>
                            <span class="badge {{ $plan->is_public ? 'bg-primary text-white' : 'bg-light text-dark border' }}">
                                {{ $plan->is_public ? 'Public' : 'Private' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('platform.plans.edit', $plan) }}"
                               class="btn btn-sm btn-outline-secondary">
                                Edit
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            No plans found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $plans->links() }}
</div>
@endsection
