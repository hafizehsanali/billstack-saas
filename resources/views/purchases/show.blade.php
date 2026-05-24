@extends('layouts.app')

@section('content')

<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-3">

        <h3>
            Purchase Details
        </h3>

        <div>

            <a href="{{ route('purchases.print', $purchase) }}"
               target="_blank"
               class="btn btn-dark">

                Print Invoice
            </a>

            <a href="{{ route('purchases.index') }}"
               class="btn btn-secondary">

                Back
            </a>

        </div>

    </div>

    <div class="card mb-3">

        <div class="card-body">

            <div class="row">

                <div class="col-md-4 mb-3">
                    <strong>Purchase No:</strong><br>
                    {{ $purchase->purchase_no }}
                </div>

                <div class="col-md-4 mb-3">
                    <strong>Purchase Date:</strong><br>
                    {{ $purchase->purchase_date->format('d M Y') }}
                </div>

                <div class="col-md-4 mb-3">
                    <strong>Supplier:</strong><br>
                    {{ $purchase->supplier->name }}
                </div>

                <div class="col-md-4 mb-3">
                    <strong>Status:</strong><br>

                    @if($purchase->status == 'paid')

                        <span class="badge bg-success">
                            Paid
                        </span>

                    @elseif($purchase->status == 'partial')

                        <span class="badge bg-warning">
                            Partial
                        </span>

                    @else

                        <span class="badge bg-danger">
                            Unpaid
                        </span>

                    @endif
                </div>

                <div class="col-md-4 mb-3">
                    <strong>Created By:</strong><br>
                    {{ $purchase->creator->name ?? 'N/A' }}
                </div>

            </div>

        </div>

    </div>

    {{-- Items --}}
    <div class="card mb-3">

        <div class="card-header">
            Purchase Items
        </div>

        <div class="card-body table-responsive">

            <table class="table table-bordered">

                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Purchase Price</th>
                        <th>Total</th>
                    </tr>
                </thead>

                <tbody>

                    @foreach($purchase->items as $item)

                        <tr>

                            <td>
                                {{ $item->product->name }}
                            </td>

                            <td>
                                {{ $item->quantity }}
                            </td>

                            <td>
                                Rs {{ number_format($item->purchase_price, 2) }}
                            </td>

                            <td>
                                Rs {{ number_format($item->line_total, 2) }}
                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </div>

    {{-- Totals --}}
    <div class="card">

        <div class="card-body">

            <div class="row text-end">

                <div class="col-md-12 mb-2">
                    <strong>
                        Subtotal:
                    </strong>

                    Rs {{ number_format($purchase->subtotal, 2) }}
                </div>

                <div class="col-md-12 mb-2">
                    <strong>
                        Extra Expense:
                    </strong>

                    Rs {{ number_format($purchase->extra_expense, 2) }}
                </div>

                <div class="col-md-12 mb-2">
                    <strong>
                        Discount:
                    </strong>

                    Rs {{ number_format($purchase->discount, 2) }}
                </div>

                <div class="col-md-12 mb-2">
                    <strong>
                        Paid Amount:
                    </strong>

                    Rs {{ number_format($purchase->paid_amount, 2) }}
                </div>

                <div class="col-md-12 mb-2">
                    <h5>
                        Remaining Amount:
                        <span class="text-danger">

                            Rs {{ number_format($purchase->remaining_amount, 2) }}

                        </span>
                    </h5>
                </div>

            </div>

        </div>

    </div>

</div>

@endsection