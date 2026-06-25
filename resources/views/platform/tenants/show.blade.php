@extends('layouts.app')

@section('content')
@php
    $subscription = $tenant->currentSubscription;
@endphp

<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
    <div>
        <h1 class="mb-1">{{ $tenant->name }}</h1>
        <div class="text-muted">{{ $tenant->email ?? $tenant->slug }}</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('platform.tenants.index') }}" class="btn btn-outline-secondary">Tenants</a>
        <a href="{{ route('platform.tenants.edit', $tenant) }}" class="btn btn-primary">
            Manage Subscription
        </a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted">Current Plan</div>
                <div class="h3 mb-1">{{ $subscription?->plan?->name ?? 'Not assigned' }}</div>
                <span class="badge {{ $subscription?->status === 'active' ? 'bg-success text-white' : 'bg-warning text-dark' }}">
                    {{ str($subscription?->status ?? 'pending')->title() }}
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted">Business Users</div>
                <div class="h3 mb-0">{{ $tenant->users->count() }}</div>
                <div class="text-muted small">
                    {{ $subscription?->plan?->user_limit ?? 'Unlimited' }} allowed
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted">Total Paid</div>
                <div class="h3 mb-0 text-success">
                    Rs {{ number_format($billingSummary->total_paid_cents / 100, 2) }}
                </div>
                <div class="text-muted small">
                    Rs {{ number_format($billingSummary->total_billed_cents / 100, 2) }} billed
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted">Subscription Amount Due</div>
                <div class="h3 mb-0 {{ $billingSummary->total_due_cents > 0 ? 'text-danger' : 'text-success' }}">
                    Rs {{ number_format($billingSummary->total_due_cents / 100, 2) }}
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">
                <h2 class="card-title mb-0">Plan Usage</h2>
            </div>
            <div class="card-body">
                @foreach([
                    'products' => 'Products',
                    'monthly_invoices' => 'Invoices This Month',
                ] as $key => $label)
                    @php
                        $item = $usage[$key];
                        $barClass = match($item['status']) {
                            'limit' => 'bg-danger',
                            'warning' => 'bg-warning',
                            default => 'bg-primary',
                        };
                    @endphp
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="fw-semibold">{{ $label }}</span>
                            <span>{{ $item['used'] }} of {{ $item['limit'] ?? 'Unlimited' }}</span>
                        </div>
                        @if($item['limit'] !== null)
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar {{ $barClass }}"
                                     style="width: {{ $item['percentage'] }}%"></div>
                            </div>
                        @else
                            <div class="text-success small">Unlimited on the current plan.</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header">
                <h2 class="card-title mb-0">Subscription Timeline</h2>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5">Started</dt>
                    <dd class="col-7">{{ $subscription?->starts_at?->format('M d, Y') ?? '-' }}</dd>
                    <dt class="col-5">Trial Ends</dt>
                    <dd class="col-7">{{ $subscription?->trial_ends_at?->format('M d, Y') ?? '-' }}</dd>
                    <dt class="col-5">Access Ends</dt>
                    <dd class="col-7">{{ $subscription?->ends_at?->format('M d, Y') ?? 'No fixed end date' }}</dd>
                    <dt class="col-5">Workspace Created</dt>
                    <dd class="col-7">{{ $tenant->created_at?->format('M d, Y') }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">
        <div>
            <h2 class="card-title mb-0">Business Setup</h2>
            <div class="text-muted small">Business preset and enabled operational modules for this tenant.</div>
        </div>
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
            <div>
                <div class="text-muted small">Business Type</div>
                <div class="h4 mb-1">{{ $tenant->businessPreset?->name ?? 'Not selected' }}</div>
                <div class="text-muted">{{ $tenant->businessPreset?->description ?? 'Choose a business type to load recommended modules.' }}</div>
            </div>
            <a href="{{ route('platform.tenants.edit', $tenant) }}" class="btn btn-outline-primary">
                Manage Modules
            </a>
        </div>

        <div class="row g-2">
            @forelse($enabledModules as $module)
                <div class="col-md-4">
                    <div class="border rounded p-2 h-100">
                        <div class="fw-semibold">{{ $module->name }}</div>
                        <div class="text-muted small">{{ $module->description }}</div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="text-muted">No modules are enabled yet.</div>
                </div>
            @endforelse
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">
        <h2 class="card-title mb-0">Recent Subscription Invoices</h2>
        <a href="{{ route('platform.billing.index') }}" class="btn btn-sm btn-outline-secondary ms-auto">
            All Billing
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Period</th>
                    <th class="text-end">Total</th>
                    <th class="text-end">Due</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        <td>{{ $invoice->invoice_no }}</td>
                        <td>{{ $invoice->billing_period }}</td>
                        <td class="text-end">Rs {{ number_format($invoice->total_cents / 100, 2) }}</td>
                        <td class="text-end {{ $invoice->balance_cents > 0 ? 'text-danger' : 'text-success' }}">
                            Rs {{ number_format($invoice->balance_cents / 100, 2) }}
                        </td>
                        <td>
                            <span class="badge {{ $invoice->status === 'paid' ? 'bg-success text-white' : 'bg-light text-dark border' }}">
                                {{ str($invoice->status)->title() }}
                            </span>
                            @if($invoice->paymentSubmission?->status === 'pending')
                                <span class="badge bg-warning text-dark">Review pending</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('platform.billing.show', $invoice) }}"
                               class="btn btn-sm btn-outline-secondary">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            No subscription invoices for this business.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">
        <h2 class="card-title mb-0">Payment Review History</h2>
        <a href="{{ route('platform.payment-submissions.index') }}"
           class="btn btn-sm btn-outline-secondary ms-auto">
            Review Queue
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Reference</th>
                    <th>Submitted</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($paymentSubmissions as $submission)
                    <tr>
                        <td>{{ $submission->invoice?->invoice_no ?? '-' }}</td>
                        <td>{{ $submission->reference_no }}</td>
                        <td>{{ $submission->created_at?->format('M d, Y') }}</td>
                        <td>
                            <span class="badge {{ match($submission->status) {
                                'approved' => 'bg-success text-white',
                                'rejected' => 'bg-danger text-white',
                                default => 'bg-warning text-dark',
                            } }}">
                                {{ str($submission->status)->title() }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">
                            No payment submissions for this business.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title mb-0">Business Users</h2>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Joined</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tenant->users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->getRoleNames()->map(fn ($role) => str($role)->title())->join(', ') ?: '-' }}</td>
                        <td>{{ $user->created_at?->format('M d, Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
