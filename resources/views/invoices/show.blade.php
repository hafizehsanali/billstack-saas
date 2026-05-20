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
            <div class="d-flex justify-content-between">

                <div>
                    <h2>BillStack</h2>
                    <p>Invoice #{{ $invoice->invoice_number }}</p>
                </div>

                <div class="text-end">
                    <h3>Invoice</h3>
                    <p>Date: {{ $invoice->created_at->format('d M Y') }}</p>
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

            <!-- TOTALS -->
            <div class="text-end">

                <h4>Subtotal: {{ $invoice->subtotal }}</h4>
                <h3>Total: {{ $invoice->total }}</h3>

            </div>

            <hr>

            <!-- ACTIONS -->
           <div class="d-flex justify-content-end gap-2">
                @if($invoice->status === 'completed')
                    <a href="{{ route('invoices.pdf', $invoice) }}"
                    class="btn btn-success btn-primary ms-auto">
                          Download PDF
                    </a>

                @endif
                <button onclick="window.print()" class="btn btn-primary">
                    Print Invoice
                </button>

            </div>
            

        </div>

    </div>

</div>


@endsection