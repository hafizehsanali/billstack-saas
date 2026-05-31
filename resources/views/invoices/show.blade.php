@extends('layouts.app')

@section('content')


<div class="container mt-4">
    <style>
@media print {
    aside,
    .navbar,
    .sidebar,
    nav {
        display: none !important;
    }

    body {
        margin: 0;
        padding: 0;
        background: white;
    }

    .page-wrapper {
        margin: 0 !important;
        padding: 0 !important;
    }
}
</style>
    <div class="card">

        <div class="card-body">

            <!-- HEADER -->
            <div class="d-flex justify-content-between align-items-start">

                <div>

                    <h2 class="mb-1">
                        Invoice #{{ $invoice->invoice_no }}
                    </h2>

                    <p class="text-muted mb-2">

                        Sale Date:
                        {{ \Carbon\Carbon::parse($invoice->sale_date)?->format('d M Y') }}

                    </p>

                    <h5>

                        <span class="badge
                            @if($invoice->status == 'paid') bg-success
                            @elseif($invoice->status == 'partial') bg-info
                            @elseif($invoice->status == 'cancelled') bg-danger
                            @else bg-warning
                            @endif">

                            {{ ucfirst($invoice->status) }}

                        </span>

                    </h5>

                </div>

                <div class="d-flex gap-2">

                    <a href="{{ route('customer.account', $invoice->customer_id) }}"
                    class="btn btn-dark">
                        Customer Ledger
                    </a>

                    <button onclick="window.print()"
                            class="btn btn-primary">
                        Print
                    </button>

                    <a href="{{ route('invoices.pdf', $invoice) }}"
                    class="btn btn-outline-primary">
                        PDF
                    </a>

                    <a href="{{ url()->previous() }}"
                    class="btn btn-secondary">
                        Back
                    </a>

                </div>

            </div>

            <hr>

            <!-- CUSTOMER INFO -->
            <div class="row">

                <div class="col-md-6">
                    <h5>Bill To:</h5>
                    <p>
                        {{ $invoice->customer->name }} <br>
                        {{ $invoice->customer->phone }}
                    </p>
                </div>

            </div>

            <hr>

            <!-- ITEMS -->
            <table class="table table-bordered">

                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Qty</th>
                        <th>Total</th>
                    </tr>
                </thead>

                <tbody>

                    @foreach($invoice->items as $item)

                        <tr>
                            <td>{{ $item->product->name }}</td>
                            <td>{{ $item->price }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ $item->total }}</td>
                        </tr>

                    @endforeach

                </tbody>

            </table>

             @php

                $paid = $invoice->payments->sum('amount');

                $remaining = $invoice->total - $paid;

            @endphp
            <!-- ACCOUNTING SUMMARY -->
            <div class="row text-center mb-3">

                <div class="col-md-3">

                    <div class="border rounded p-3">

                        <small class="text-muted">
                            Subtotal
                        </small>

                        <h5 class="mt-2">
                            Rs {{ number_format($invoice->subtotal, 2) }}
                        </h5>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="border rounded p-3">

                        <small class="text-muted">
                            Invoice Total
                        </small>

                        <h5 class="mt-2">
                            Rs {{ number_format($invoice->total, 2) }}
                        </h5>

                    </div>

                </div>
                @if($invoice->status !== 'cancelled')
                <div class="col-md-3">

                    <div class="border rounded p-3">

                        <small class="text-muted">
                            Paid Amount
                        </small>

                        <h5 class="mt-2 text-success">
                            Rs {{ number_format($paid, 2) }}
                        </h5>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="border rounded p-3">

                        <small class="text-muted">
                            Remaining Amount
                        </small>

                        <h5 class="mt-2 text-danger">
                            Rs {{ number_format($remaining, 2) }}
                        </h5>

                    </div>

                </div>
                @endif
            </div>

            <hr>
            @if($invoice->status === 'cancelled')

                <div class="alert alert-danger">
                    This invoice has been cancelled. No further payments can be recorded.
                </div>

            @endif
            @if(in_array($invoice->status, ['unpaid', 'partial']))
            <h4>Record Payment</h4>
            <div class="alert alert-info">

                <strong>Paid:</strong>
                {{ $paid }}

                <br>

                <strong>Remaining:</strong>
                {{ $remaining }}

            </div>

            <form action="{{ route('payments.store', $invoice->id) }}" method="POST">

                @csrf

                <div class="row g-2 align-items-end">

                    {{-- Amount --}}
                    <div class="col-md-3">
                        <label class="form-label mb-1">Amount</label>
                        <input type="number"
                            step="0.01"
                            name="amount"
                            class="form-control"
                            placeholder="0.00"
                            required>
                    </div>

                    {{-- Payment Method --}}
                    <div class="col-md-3">
                        <label class="form-label mb-1">Method</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="cash">Cash</option>
                            <option value="bank">Bank</option>
                            <option value="card">Card</option>
                            <option value="jazzcash">JazzCash</option>
                            <option value="easypaisa">EasyPaisa</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>

                    {{-- Payment Date --}}
                    <div class="col-md-3">
                        <label class="form-label mb-1">Payment Date</label>
                        <input type="datetime-local"
                            name="payment_date"
                            class="form-control"
                            value="{{ now()->format('Y-m-d\TH:i') }}"
                            required>
                    </div>

                    {{-- Reference No --}}
                    <div class="col-md-3">
                        <label class="form-label mb-1">Reference No</label>
                        <input type="text"
                            name="reference_no"
                            class="form-control"
                            placeholder="Cheque / Txn / Ref">
                    </div>

                    {{-- Note --}}
                    <div class="col-md-6 mt-2">
                        <label class="form-label mb-1">Note</label>
                        <input type="text"
                            name="notes"
                            class="form-control"
                            placeholder="Optional note">
                    </div>

                    {{-- Submit --}}
                    <div class="col-md-6 mt-2">
                        <button type="submit" class="btn btn-success w-100">
                            Save Payment
                        </button>
                    </div>

                </div>

            </form>
            @endif
            <hr>

             <h4 class="mt-4">
                Payment History
            </h4>

            <table class="table table-bordered align-middle">

                <thead class="table-light">

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

                            <td>
                                {{  \Carbon\Carbon::parse($payment->payment_date)?->format('d M Y') }}
                            </td>

                            <td>
                                {{ ucfirst($payment->payment_method) }}
                            </td>

                            <td>
                                {{ $payment->reference_no ?? '-' }}
                            </td>

                            <td class="text-end text-success fw-bold">
                                Rs {{ number_format($payment->amount, 2) }}
                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="4" class="text-center text-muted py-4">
                                No payments recorded
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

            <!-- ACTIONS -->
           
            <div class="d-flex gap-2">
                @if($invoice->status != 'cancelled' && $invoice->status != 'paid' && $invoice->status != 'partial')

                    <form method="POST" action="{{ route('invoices.cancel', $invoice->id) }}">
                        @csrf
                        @method('PATCH')

                        <button class="btn btn-danger">
                            Cancel Invoice
                        </button>

                    </form>

                @endif
                 <a href="{{ route('invoices.pdf', $invoice) }}"  class="btn btn-primary">
                          Download PDF
                 </a>

            </div>

        </div>

    </div>

</div>


@endsection
