@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-1">Daily Sales Report</h3>
        <div class="text-muted">{{ now()->format('d M Y') }}</div>
    </div>

    <a href="{{ route('dashboard') }}" class="btn btn-secondary">
        Dashboard
    </a>
</div>

<div class="row row-cards mb-3">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Invoices</div>
                <div class="h2 mb-0">{{ number_format($invoices->count()) }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Sales Total</div>
                <div class="h2 mb-0">Rs {{ number_format($totalSales, 2) }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Received</div>
                <div class="h2 mb-0 text-success">Rs {{ number_format($totalReceived, 2) }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Customer Owes Us</div>
                <div class="h2 mb-0 text-danger">Rs {{ number_format($totalReceivable, 2) }}</div>
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
                    <th>Customer</th>
                    <th class="text-end">Invoice Total</th>
                    <th class="text-end">Received</th>
                    <th class="text-end">Customer Owes Us</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        <td>
                            <a href="{{ route('invoices.show', $invoice) }}">
                                {{ $invoice->invoice_no }}
                            </a>
                        </td>
                        <td>{{ $invoice->customer?->name ?? '-' }}</td>
                        <td class="text-end">Rs {{ number_format($invoice->total, 2) }}</td>
                        <td class="text-end text-success">Rs {{ number_format($invoice->paid_amount, 2) }}</td>
                        <td class="text-end text-danger">Rs {{ number_format($invoice->remaining_amount, 2) }}</td>
                        <td>{{ ucfirst($invoice->status) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            No sales recorded today.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
