@extends('layouts.app')

@section('content')

@php
    $invoiceRows = $invoices->getCollection();
    $totalSales = $invoiceRows->where('status', '!=', 'cancelled')->sum('total');
    $totalReceived = $invoiceRows->where('status', '!=', 'cancelled')->sum('paid_amount');
    $totalReceivable = $invoiceRows->where('status', '!=', 'cancelled')->sum('remaining_amount');
    $openInvoices = $invoiceRows
        ->filter(fn ($invoice) => in_array($invoice->status, ['unpaid', 'partial'], true))
        ->count();
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-1">Invoices</h3>
        <div class="text-muted">
            Customer bills, payment status, and money still receivable.
        </div>
    </div>

    <a href="{{ route('invoices.create') }}"
       class="btn btn-primary">
        Create Invoice
    </a>
</div>

<div class="row row-cards mb-3">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Invoices</div>
                <div class="h2 mb-0">{{ number_format($invoices->total()) }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Sales on This Page</div>
                <div class="h2 mb-0">Rs {{ number_format($totalSales, 2) }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Received on This Page</div>
                <div class="h2 mb-0 text-success">Rs {{ number_format($totalReceived, 2) }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Open Invoices</div>
                <div class="h2 mb-0 text-danger">{{ number_format($openInvoices) }}</div>
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
                    <th>Date</th>
                    <th class="text-end">Invoice Total</th>
                    <th class="text-end">Amount Received</th>
                    <th class="text-end">Customer Owes Us</th>
                    <th>Status</th>
                    <th class="text-end" style="min-width: 180px;">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($invoices as $invoice)
                    <tr class="{{ $invoice->status === 'cancelled' ? 'table-secondary' : '' }}">
                        <td>
                            <div class="fw-bold">{{ $invoice->invoice_no }}</div>
                        </td>

                        <td>{{ $invoice->customer?->name ?? 'Walk-in Customer' }}</td>

                        <td>{{ \Carbon\Carbon::parse($invoice->sale_date)->format('d M Y') }}</td>

                        <td class="text-end">Rs {{ number_format($invoice->total, 2) }}</td>

                        <td class="text-end text-success">Rs {{ number_format($invoice->paid_amount, 2) }}</td>

                        <td class="text-end fw-bold {{ $invoice->remaining_amount > 0 ? 'text-danger' : 'text-success' }}">
                            Rs {{ number_format($invoice->remaining_amount, 2) }}
                        </td>

                        <td>
                            @if($invoice->status == 'paid')
                                <span class="badge bg-success">Paid</span>
                            @elseif($invoice->status == 'partial')
                                <span class="badge bg-info">Partial</span>
                            @elseif($invoice->status == 'cancelled')
                                <span class="badge bg-danger">Cancelled</span>
                            @elseif($invoice->status == 'returned')
                                <span class="badge bg-secondary">Returned</span>
                            @else
                                <span class="badge bg-warning">Unpaid</span>
                            @endif
                        </td>

                        <td class="text-end">
                            <div class="d-inline-flex gap-1 flex-nowrap">
                                <a href="{{ route('invoices.show', $invoice) }}"
                                   class="btn btn-sm btn-primary text-nowrap">
                                    View
                                </a>

                                @if(! in_array($invoice->status, ['cancelled', 'paid', 'partial', 'returned'], true))
                                    <form method="POST"
                                          action="{{ route('invoices.cancel', $invoice) }}"
                                          class="m-0"
                                          onsubmit="return confirm('Cancel invoice?')">
                                        @csrf
                                        @method('PATCH')

                                        <button class="btn btn-sm btn-warning text-nowrap">
                                            Cancel
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            No invoices found. Create your first invoice to start billing customers.
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
