@extends('layouts.app')

@section('content')

<div class="container">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">

        <div>
            <h3 class="mb-0">
                Supplier Payment Details
            </h3>

            <small class="text-muted">
                Payment Reference:
                {{ $supplierPayment->reference_no ?? 'N/A' }}
            </small>
        </div>

        <div>
            <a href="{{ route('supplier.account', $supplierPayment->supplier_id) }}"
               class="btn btn-secondary">
                Back to Ledger
            </a>
        </div>

    </div>

    {{-- Summary --}}
    <div class="row">

        <div class="col-md-4">
            <div class="card shadow-sm border-0">

                <div class="card-body">

                    <small class="text-muted d-block">
                        Payment Amount
                    </small>

                    <h3 class="text-success mb-0">
                        Rs {{ number_format($supplierPayment->amount, 2) }}
                    </h3>

                </div>

            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-0">

                <div class="card-body">

                    <small class="text-muted d-block">
                        Payment Date
                    </small>

                    <h5 class="mb-0">
                        {{ optional($supplierPayment->payment_date)->format('d M Y') }}
                    </h5>

                </div>

            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-0">

                <div class="card-body">

                    <small class="text-muted d-block">
                        Payment Method
                    </small>

                    <h5 class="mb-0 text-capitalize">
                        {{ $supplierPayment->payment_method }}
                    </h5>

                </div>

            </div>
        </div>

    </div>

    {{-- Details --}}
    <div class="card mt-4 shadow-sm border-0">

        <div class="card-header fw-bold">
            Payment Information
        </div>

        <div class="card-body">

            <table class="table table-bordered">

                <tr>
                    <th width="250">
                        Supplier
                    </th>

                    <td>
                        {{ $supplierPayment->supplier->name ?? '-' }}
                    </td>
                </tr>

                <tr>
                    <th>
                        Purchase Invoice
                    </th>

                    <td>

                        @if($supplierPayment->purchase)

                            <a href="{{ route('purchases.show', $supplierPayment->purchase_id) }}">

                                {{ $supplierPayment->purchase->purchase_no }}

                            </a>

                        @else

                            -

                        @endif

                    </td>
                </tr>

                <tr>
                    <th>
                        Reference No
                    </th>

                    <td>
                        {{ $supplierPayment->reference_no ?? '-' }}
                    </td>
                </tr>

                <tr>
                    <th>
                        Notes
                    </th>

                    <td>
                        {{ $supplierPayment->notes ?? '-' }}
                    </td>
                </tr>

                <tr>
                    <th>
                        Created At
                    </th>

                    <td>
                        {{ $supplierPayment->created_at->format('d M Y h:i A') }}
                    </td>
                </tr>

            </table>

        </div>

    </div>

</div>

@endsection