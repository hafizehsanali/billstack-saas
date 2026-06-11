@extends('layouts.app')

@section('content')

@php
    $plan = $subscription?->plan;
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-1">Plan & Billing</h3>
        <div class="text-muted">
            Review subscription charges and payments for {{ $tenant->name }}.
        </div>
    </div>
</div>

<div class="row row-cards mb-3">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Current Plan</div>
                <div class="h3 mb-0">{{ $plan?->name ?? 'Not assigned' }}</div>
                <div class="text-muted small">
                    {{ $plan?->user_limit ? $plan->user_limit.' users' : 'Unlimited users' }}
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Total Billed</div>
                <div class="h3 mb-0">Rs {{ number_format($billingSummary->total_billed_cents / 100, 2) }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Total Paid</div>
                <div class="h3 mb-0 text-success">Rs {{ number_format($billingSummary->total_paid_cents / 100, 2) }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Amount Due</div>
                <div class="h3 mb-0 {{ $billingSummary->total_due_cents > 0 ? 'text-danger' : 'text-success' }}">
                    Rs {{ number_format($billingSummary->total_due_cents / 100, 2) }}
                </div>
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
                    <th>Billing Period</th>
                    <th class="text-end">Total</th>
                    <th class="text-end">Paid</th>
                    <th class="text-end">Due</th>
                    <th>Status</th>
                    <th>Payments</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $invoice->invoice_no }}</div>
                            <div class="text-muted small">Issued {{ $invoice->issued_on?->format('M d, Y') ?? '-' }}</div>
                        </td>
                        <td>
                            <div>{{ $invoice->billing_period }}</div>
                            <div class="text-muted small">Due {{ $invoice->due_on?->format('M d, Y') ?? '-' }}</div>
                        </td>
                        <td class="text-end">Rs {{ number_format($invoice->total_cents / 100, 2) }}</td>
                        <td class="text-end text-success">Rs {{ number_format($invoice->paid_cents / 100, 2) }}</td>
                        <td class="text-end {{ $invoice->balance_cents > 0 ? 'text-danger' : 'text-success' }}">
                            Rs {{ number_format($invoice->balance_cents / 100, 2) }}
                        </td>
                        <td>
                            <span class="badge {{ $invoice->status === 'paid' ? 'bg-success text-white' : 'bg-warning text-dark' }}">
                                {{ str($invoice->status)->replace('_', ' ')->title() }}
                            </span>
                        </td>
                        <td>
                            @forelse($invoice->payments as $payment)
                                <div class="small">
                                    Rs {{ number_format($payment->amount_cents / 100, 2) }}
                                    <span class="text-muted">
                                        {{ $payment->paid_on?->format('M d, Y') ?? '-' }}
                                    </span>
                                </div>
                            @empty
                                <span class="text-muted small">No payment recorded</span>
                            @endforelse
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            No subscription invoices found.
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
