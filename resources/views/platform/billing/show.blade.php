@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <div>
        <h1 class="mb-1">{{ $invoice->invoice_no }}</h1>
        <div class="text-muted">{{ $invoice->tenant?->name }} · {{ $invoice->billing_period }}</div>
    </div>

    <a href="{{ route('platform.billing.index') }}" class="btn btn-secondary">
        Back
    </a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Total</div>
                <div class="h2 mb-0">Rs {{ number_format($invoice->total_cents / 100, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Paid</div>
                <div class="h2 mb-0 text-success">Rs {{ number_format($invoice->paid_cents / 100, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Balance</div>
                <div class="h2 mb-0 {{ $invoice->balance_cents > 0 ? 'text-danger' : 'text-success' }}">
                    Rs {{ number_format($invoice->balance_cents / 100, 2) }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Status</div>
                <div>
                    <span class="badge {{ $invoice->status === 'paid' ? 'bg-success text-white' : 'bg-light text-dark border' }}">
                        {{ str($invoice->status)->replace('_', ' ')->title() }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Invoice Details</h3>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5">Tenant</dt>
                    <dd class="col-7">{{ $invoice->tenant?->name ?? '-' }}</dd>

                    <dt class="col-5">Plan</dt>
                    <dd class="col-7">{{ $invoice->subscription?->plan?->name ?? '-' }}</dd>

                    <dt class="col-5">Issued</dt>
                    <dd class="col-7">{{ $invoice->issued_on?->format('M d, Y') }}</dd>

                    <dt class="col-5">Due</dt>
                    <dd class="col-7">{{ $invoice->due_on?->format('M d, Y') ?? '-' }}</dd>

                    <dt class="col-5">Subtotal</dt>
                    <dd class="col-7">Rs {{ number_format($invoice->subtotal_cents / 100, 2) }}</dd>

                    <dt class="col-5">Discount</dt>
                    <dd class="col-7">Rs {{ number_format($invoice->discount_cents / 100, 2) }}</dd>

                    <dt class="col-5">Tax</dt>
                    <dd class="col-7">Rs {{ number_format($invoice->tax_cents / 100, 2) }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Payments</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoice->payments as $payment)
                            <tr>
                                <td>{{ $payment->paid_on?->format('M d, Y') }}</td>
                                <td>{{ str($payment->payment_method)->replace('_', ' ')->title() }}</td>
                                <td>{{ $payment->reference_no ?? '-' }}</td>
                                <td class="text-end text-success">Rs {{ number_format($payment->amount_cents / 100, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    No payments recorded for this invoice.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
