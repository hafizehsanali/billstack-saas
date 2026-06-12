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
                            <span class="badge {{ $invoice->status === 'paid' ? 'bg-success text-white' : 'bg-light text-dark border' }}">
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
