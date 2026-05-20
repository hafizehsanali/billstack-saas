@extends('layouts.app')

@section('content')

<div class="card">

    <div class="card-header">

        <h3 class="card-title">
            Invoice {{ $invoice->invoice_number }}
        </h3>
        
        @if($invoice->status === 'completed')

            <a href="{{ route('invoices.pdf', $invoice) }}"
            class="btn btn-primary ms-auto">

                Download PDF

            </a>

        @endif

    </div>

    <div class="card-body">

        <div class="mb-4">

            <strong>Customer:</strong>

            {{ $invoice->customer?->name ?? 'Walk-in Customer' }}

        </div>

        <table class="table table-bordered">

            <thead>
            <tr>
                <th>Product</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
            </thead>

            <tbody>

            @foreach($invoice->items as $item)

                <tr>

                    <td>
                        {{ $item->product?->name }}
                    </td>

                    <td>
                        {{ $item->quantity }}
                    </td>

                    <td>
                        {{ $item->price }}
                    </td>

                    <td>
                        {{ $item->total }}
                    </td>

                </tr>

            @endforeach

            </tbody>

        </table>

        <div class="text-end mt-4">

            <h3>
                Total:
                Rs. {{ $invoice->total }}
            </h3>

        </div>

    </div>

</div>

@endsection