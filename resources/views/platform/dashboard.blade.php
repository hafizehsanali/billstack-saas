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
        <a href="{{ route('platform.billing.index') }}" class="btn btn-outline-secondary">
            Billing
        </a>
        <a href="{{ route('platform.offers.index') }}" class="btn btn-outline-secondary">
            Offers
        </a>
        <a href="{{ route('platform.features.index') }}" class="btn btn-outline-secondary">
            Features
        </a>
        <a href="{{ route('platform.settings.edit') }}" class="btn btn-outline-secondary">
            Settings
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
                <div class="text-secondary small fw-semibold text-uppercase">Platform Amount Due</div>
                <div class="h2 mb-0 text-danger">Rs {{ number_format($platformDueCents / 100, 2) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <div>
            <h2 class="card-title mb-1">Needs Attention</h2>
            <div class="text-muted small">Usage limits, upcoming subscription expiries, and overdue billing.</div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Business</th>
                    <th>Details</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($usageAlerts as $alert)
                    <tr>
                        <td>
                            <span class="badge {{ $alert['at_limit'] ? 'bg-danger text-white' : 'bg-warning text-dark' }}">
                                Plan usage
                            </span>
                        </td>
                        <td class="fw-semibold">{{ $alert['tenant']->name }}</td>
                        <td>
                            {{ $alert['metric'] }}: {{ $alert['used'] }} of {{ $alert['limit'] }}
                            <span class="text-muted">({{ $alert['percentage'] }}%)</span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('platform.tenants.edit', $alert['tenant']) }}"
                               class="btn btn-sm btn-outline-secondary">
                                Review plan
                            </a>
                        </td>
                    </tr>
                @endforeach

                @foreach($expiringSubscriptions as $alert)
                    <tr>
                        <td><span class="badge bg-warning text-dark">Expiring</span></td>
                        <td class="fw-semibold">{{ $alert['subscription']->tenant?->name ?? '-' }}</td>
                        <td>
                            {{ $alert['label'] }} ends {{ $alert['expires_at']->format('M d, Y') }}
                            <span class="text-muted">
                                ({{ $alert['subscription']->plan?->name ?? 'No plan' }})
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('platform.tenants.edit', $alert['subscription']->tenant) }}"
                               class="btn btn-sm btn-outline-secondary">
                                Subscription
                            </a>
                        </td>
                    </tr>
                @endforeach

                @foreach($overdueInvoices as $invoice)
                    <tr>
                        <td><span class="badge bg-danger text-white">Overdue</span></td>
                        <td class="fw-semibold">{{ $invoice->tenant?->name ?? '-' }}</td>
                        <td>
                            {{ $invoice->invoice_no }} has
                            <span class="text-danger fw-semibold">
                                Rs {{ number_format($invoice->balance_cents / 100, 2) }} due
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('platform.billing.show', $invoice) }}"
                               class="btn btn-sm btn-outline-secondary">
                                View invoice
                            </a>
                        </td>
                    </tr>
                @endforeach

                @if($usageAlerts->isEmpty() && $expiringSubscriptions->isEmpty() && $overdueInvoices->isEmpty())
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">
                            No platform issues need attention.
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
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
                    Platform admins: {{ $platformAdminCount }} · Paid features: {{ $paidFeatureCount }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
