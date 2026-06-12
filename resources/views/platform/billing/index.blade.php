@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <div>
        <h1 class="mb-1">Platform Billing</h1>
        <div class="text-muted">Track subscription invoices, payments, and balances across tenants.</div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('platform.dashboard') }}" class="btn btn-outline-secondary">
            Platform
        </a>
        <a href="{{ route('platform.billing.create') }}" class="btn btn-primary">
            Create Invoice
        </a>
    </div>
</div>

<form method="GET" class="card card-body mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-lg-5">
            <label for="billing-search" class="form-label">Search billing</label>
            <input id="billing-search"
                   type="search"
                   name="search"
                   value="{{ request('search') }}"
                   class="form-control"
                   placeholder="Invoice number or business name">
        </div>
        <div class="col-sm-5 col-lg-3">
            <label for="billing-status" class="form-label">Invoice status</label>
            <select id="billing-status" name="status" class="form-select">
                <option value="">All statuses</option>
                @foreach(['unpaid', 'paid', 'overdue', 'cancelled'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>
                        {{ str($status)->title() }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-4 col-lg-2">
            <label for="billing-cycle" class="form-label">Cycle</label>
            <select id="billing-cycle" name="billing_cycle" class="form-select">
                <option value="">All cycles</option>
                <option value="monthly" @selected(request('billing_cycle') === 'monthly')>Monthly</option>
                <option value="annual" @selected(request('billing_cycle') === 'annual')>Annual</option>
            </select>
        </div>
        <div class="col-sm-3 col-lg-2 d-flex gap-2">
            <button class="btn btn-primary flex-fill"><i data-lucide="search"></i> Filter</button>
            <a href="{{ route('platform.billing.index') }}" class="btn btn-icon btn-outline-secondary" title="Clear filters">
                <i data-lucide="x"></i>
            </a>
        </div>
    </div>
</form>

<div class="row row-cards mb-3">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Total Billed</div>
                <div class="h2 mb-0">Rs {{ number_format($totalBilledCents / 100, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Total Paid</div>
                <div class="h2 mb-0 text-success">Rs {{ number_format($totalPaidCents / 100, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Amount Due</div>
                <div class="h2 mb-0 text-danger">Rs {{ number_format($totalDueCents / 100, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Overdue Invoices</div>
                <div class="h2 mb-0">{{ number_format($overdueCount) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Tenant</th>
                    <th>Plan</th>
                    <th class="text-end">Total</th>
                    <th class="text-end">Paid</th>
                    <th class="text-end">Due</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $invoice->invoice_no }}</div>
                            <div class="text-muted small">
                                {{ $invoice->billing_period }} | Due {{ $invoice->due_on?->format('M d, Y') ?? '-' }}
                            </div>
                        </td>
                        <td>{{ $invoice->tenant?->name ?? '-' }}</td>
                        <td>{{ $invoice->subscription?->plan?->name ?? '-' }}</td>
                        <td class="text-end">Rs {{ number_format($invoice->total_cents / 100, 2) }}</td>
                        <td class="text-end text-success">Rs {{ number_format($invoice->paid_cents / 100, 2) }}</td>
                        <td class="text-end fw-bold {{ $invoice->balance_cents > 0 ? 'text-danger' : 'text-success' }}">
                            Rs {{ number_format($invoice->balance_cents / 100, 2) }}
                        </td>
                        <td>
                            <span class="badge {{ match($invoice->status) {
                                'paid' => 'bg-success text-white',
                                'overdue' => 'bg-danger text-white',
                                'cancelled' => 'bg-secondary text-white',
                                default => 'bg-light text-dark border',
                            } }}">
                                {{ str($invoice->status)->replace('_', ' ')->title() }}
                            </span>
                            @if($invoice->paymentSubmission?->status === 'pending')
                                <div class="mt-1">
                                    <span class="badge bg-warning text-dark">Payment review pending</span>
                                </div>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('platform.billing.show', $invoice) }}"
                               class="btn btn-sm btn-outline-secondary">
                                View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            No platform billing invoices found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $invoices->links() }}
</div>
@endsection
