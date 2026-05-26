@extends('layouts.app')

@section('content')

<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-3">

        <h3>Purchase Details</h3>

        <div class="d-flex gap-2">

            @if($purchase->remaining_amount > 0)

                <a href="{{ route('supplier-payments.create',[$purchase->supplier_id,$purchase->id]) }}"
                   class="btn btn-success">
                    Add Payment
                </a>

            @endif

            <a href="{{ route('purchases.index') }}"
               class="btn btn-secondary">
                Back
            </a>

        </div>

    </div>

    <div class="card mb-3">
        <div class="card-body">

            <div class="row">

                <div class="col-md-6">
                    <strong>Invoice No:</strong>
                    {{ $purchase->purchase_no }}
                </div>

                <div class="col-md-6">
                    <strong>Date:</strong>
                    {{ $purchase->purchase_date }}
                </div>

                <div class="col-md-6 mt-2">
                    <strong>Supplier:</strong>
                    {{ $purchase->supplier->name }}
                </div>

                <div class="col-md-6 mt-2">
                    <strong>Status:</strong>

                    @if($purchase->status=='paid')
                        <span class="badge bg-success">Paid</span>
                    @elseif($purchase->status=='partial')
                        <span class="badge bg-warning">Partial</span>
                    @else
                        <span class="badge bg-danger">Unpaid</span>
                    @endif

                </div>

            </div>

        </div>
    </div>

    <div class="card mb-3">

        <div class="card-header">
            Purchase Items
        </div>

        <div class="table-responsive">

            <table class="table table-bordered mb-0">

                <thead>
                    <tr>
                        <th>Product</th>
                        <th width="120">Qty</th>
                        <th width="150">Price</th>
                        <th width="150">Total</th>
                    </tr>
                </thead>

                <tbody>

                    @foreach($purchase->items as $item)

                        <tr>

                            <td>{{ $item->product->name }}</td>

                            <td>{{ $item->quantity }}</td>

                            <td>{{ number_format($item->purchase_price,2) }}</td>

                            <td>{{ number_format($item->line_total,2) }}</td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </div>

    <div class="row">

        <div class="col-md-6">

            <div class="card">

                <div class="card-header">
                    Payment History
                </div>

                <div class="table-responsive">

                    <table class="table table-bordered mb-0">

                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Method</th>
                                <th>Amount</th>
                            </tr>
                        </thead>

                        <tbody>

                            @forelse($purchase->payments as $payment)

                                <tr>

                                    <td>{{ $payment->payment_date }}</td>

                                    <td>{{ $payment->payment_method }}</td>

                                    <td>
                                        {{ number_format($payment->amount,2) }}
                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td colspan="3" class="text-center">
                                        No payments found
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

        <div class="col-md-6">

            <div class="card">

                <div class="card-header">
                    Purchase Summary
                </div>

                <div class="card-body">

                    <div class="d-flex justify-content-between mb-2">
                        <strong>Total:</strong>
                        <span>{{ number_format($purchase->total,2) }}</span>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <strong>Paid:</strong>
                        <span>{{ number_format($purchase->paid_amount,2) }}</span>
                    </div>

                    <div class="d-flex justify-content-between">
                        <strong>Remaining:</strong>
                        <strong class="text-danger">
                            {{ number_format($purchase->remaining_amount,2) }}
                        </strong>
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection