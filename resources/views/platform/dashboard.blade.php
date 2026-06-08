@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="mb-1">Platform Admin</h1>
        <p class="text-muted mb-0">Manage SaaS tenants, plans, paid features, and platform settings.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('platform.tenants.index') }}" class="btn btn-outline-secondary">
            Tenants
        </a>
        <a href="{{ route('platform.features.index') }}" class="btn btn-outline-secondary">
            Features
        </a>
        <a href="{{ route('platform.plans.index') }}" class="btn btn-primary">
            Plans
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-secondary small fw-semibold text-uppercase">Tenants</div>
                <div class="h2 mb-0 text-dark">{{ $tenantCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-secondary small fw-semibold text-uppercase">Active Subscriptions</div>
                <div class="h2 mb-0 text-dark">{{ $activeTenantCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-secondary small fw-semibold text-uppercase">Active Plans</div>
                <div class="h2 mb-0 text-dark">{{ $planCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-secondary small fw-semibold text-uppercase">Paid Features</div>
                <div class="h2 mb-0 text-dark">{{ $paidFeatureCount }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title mb-0">Recent Tenants</h2>
                <a href="{{ route('platform.tenants.index') }}" class="btn btn-sm btn-outline-secondary ms-auto">
                    Manage
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Business</th>
                            <th>Plan</th>
                            <th>Users</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tenants as $tenant)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $tenant->name }}</div>
                                    <div class="text-muted small">{{ $tenant->email ?? $tenant->slug }}</div>
                                </td>
                                <td>{{ $tenant->activeSubscription?->plan?->name ?? 'Not assigned' }}</td>
                                <td>{{ $tenant->users->count() }}</td>
                                <td>
                                    <span class="badge {{ $tenant->activeSubscription?->status === 'active' ? 'bg-success text-white' : 'bg-light text-dark border' }}">
                                        {{ $tenant->activeSubscription?->status ?? 'pending' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-muted">No tenants created yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title mb-0">Plans</h2>
                <a href="{{ route('platform.plans.index') }}" class="btn btn-sm btn-outline-secondary ms-auto">
                    Manage
                </a>
            </div>
            <div class="card-body">
                @foreach($plans as $plan)
                    <div class="d-flex align-items-start justify-content-between border-bottom pb-3 mb-3">
                        <div>
                            <div class="fw-semibold">{{ $plan->name }}</div>
                            <div class="text-muted small">{{ $plan->features_count }} features, {{ $plan->user_limit ?? 'unlimited' }} users</div>
                        </div>
                        <span class="badge {{ $plan->monthly_price_cents > 0 ? 'bg-primary text-white' : 'bg-light text-dark border' }}">
                            {{ $plan->monthly_price_cents > 0 ? 'Paid' : 'Free' }}
                        </span>
                    </div>
                @endforeach

                <div class="text-muted small">
                    Platform admins: {{ $platformAdminCount }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
