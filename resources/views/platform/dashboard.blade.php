@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="mb-1">Platform Admin</h1>
        <p class="text-muted mb-0">Manage SaaS tenants, plans, paid features, and platform settings.</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Tenants</div>
                <div class="h2 mb-0">{{ $tenantCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Active Subscriptions</div>
                <div class="h2 mb-0">{{ $activeTenantCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Active Plans</div>
                <div class="h2 mb-0">{{ $planCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Paid Features</div>
                <div class="h2 mb-0">{{ $paidFeatureCount }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title mb-0">Recent Tenants</h2>
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
                                    <span class="badge bg-{{ $tenant->activeSubscription ? 'success' : 'secondary' }}">
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
            </div>
            <div class="card-body">
                @foreach($plans as $plan)
                    <div class="d-flex align-items-start justify-content-between border-bottom pb-3 mb-3">
                        <div>
                            <div class="fw-semibold">{{ $plan->name }}</div>
                            <div class="text-muted small">{{ $plan->features_count }} features, {{ $plan->user_limit ?? 'unlimited' }} users</div>
                        </div>
                        <span class="badge bg-{{ $plan->monthly_price_cents > 0 ? 'primary' : 'secondary' }}">
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
