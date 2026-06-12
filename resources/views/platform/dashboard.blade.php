@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="mb-1">Platform Admin</h1>
        <p class="text-muted mb-0">Manage businesses, subscriptions, billing, and platform controls.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('platform.tenants.index') }}" class="btn btn-outline-secondary">Tenants</a>
        <a href="{{ route('platform.billing.index') }}" class="btn btn-outline-secondary">Billing</a>
        <a href="{{ route('platform.payment-submissions.index') }}" class="btn btn-outline-secondary">
            Payment Reviews
        </a>
        <a href="{{ route('platform.activities.index') }}" class="btn btn-outline-secondary">Activity</a>
        <a href="{{ route('platform.settings.edit') }}" class="btn btn-outline-secondary">Settings</a>
        <a href="{{ route('platform.plans.index') }}" class="btn btn-primary">Plans</a>
    </div>
</div>

<div class="row g-3 mb-4">
    @foreach([
        ['label' => 'Tenants', 'value' => $tenantCount, 'class' => 'text-dark'],
        ['label' => 'Active', 'value' => $activeTenantCount, 'class' => 'text-success'],
        ['label' => 'Inactive', 'value' => $inactiveTenantCount, 'class' => $inactiveTenantCount > 0 ? 'text-warning' : 'text-dark'],
        ['label' => 'Payment Reviews', 'value' => $pendingPaymentCount, 'class' => $pendingPaymentCount > 0 ? 'text-warning' : 'text-dark'],
        ['label' => 'Active Plans', 'value' => $planCount, 'class' => 'text-dark'],
    ] as $stat)
        <div class="col-md-4 col-xl">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-secondary small fw-semibold text-uppercase">{{ $stat['label'] }}</div>
                    <div class="h2 mb-0 {{ $stat['class'] }}">{{ $stat['value'] }}</div>
                </div>
            </div>
        </div>
    @endforeach

    <div class="col-md-4 col-xl">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-secondary small fw-semibold text-uppercase">Amount Due</div>
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
                            <a href="{{ route('platform.tenants.show', $alert['tenant']) }}"
                               class="btn btn-sm btn-outline-secondary">Review</a>
                        </td>
                    </tr>
                @endforeach

                @foreach($expiringSubscriptions as $alert)
                    <tr>
                        <td><span class="badge bg-warning text-dark">Expiring</span></td>
                        <td class="fw-semibold">{{ $alert['subscription']->tenant?->name ?? '-' }}</td>
                        <td>
                            {{ $alert['label'] }} ends {{ $alert['expires_at']->format('M d, Y') }}
                            <span class="text-muted">({{ $alert['subscription']->plan?->name ?? 'No plan' }})</span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('platform.tenants.show', $alert['subscription']->tenant) }}"
                               class="btn btn-sm btn-outline-secondary">Review</a>
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
                               class="btn btn-sm btn-outline-secondary">View Invoice</a>
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
        <div class="card h-100">
            <div class="card-header">
                <h2 class="card-title mb-0">Recent Tenants</h2>
                <a href="{{ route('platform.tenants.index') }}" class="btn btn-sm btn-outline-secondary ms-auto">
                    View All
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
                                    <a href="{{ route('platform.tenants.show', $tenant) }}" class="fw-semibold">
                                        {{ $tenant->name }}
                                    </a>
                                    <div class="text-muted small">{{ $tenant->email ?? $tenant->slug }}</div>
                                </td>
                                <td>{{ $tenant->activeSubscription?->plan?->name ?? 'Not assigned' }}</td>
                                <td>{{ $tenant->users->count() }}</td>
                                <td>
                                    <span class="badge {{ $tenant->activeSubscription ? 'bg-success text-white' : 'bg-warning text-dark' }}">
                                        {{ $tenant->activeSubscription?->status ?? 'inactive' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No tenants created yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header">
                <h2 class="card-title mb-0">Recent Activity</h2>
                <a href="{{ route('platform.activities.index') }}"
                   class="btn btn-sm btn-outline-secondary ms-auto">View All</a>
            </div>
            <div class="card-body">
                @forelse($recentActivities as $activity)
                    <div class="border-bottom pb-3 mb-3">
                        <div class="d-flex justify-content-between gap-2">
                            <div class="fw-semibold">{{ $activity->description }}</div>
                            <span class="text-muted small text-nowrap">
                                {{ $activity->created_at?->diffForHumans() }}
                            </span>
                        </div>
                        <div class="text-muted small mt-1">
                            {{ $activity->actor?->name ?? 'System' }}
                            @if($activity->tenant)
                                | {{ $activity->tenant->name }}
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-muted">No platform activity recorded yet.</div>
                @endforelse

                <div class="text-muted small">
                    Platform admins: {{ $platformAdminCount }} | Paid features: {{ $paidFeatureCount }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
