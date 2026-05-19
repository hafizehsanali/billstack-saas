@extends('layouts.app')

@section('content')

<div class="card">

    <div class="card-header">

        <h3 class="card-title">
            Invoices
        </h3>

        <a href="{{ route('invoices.create') }}"
           class="btn btn-primary ms-auto">
            Create Invoice
        </a>

    </div>

    <div class="table-responsive">

        <table class="table table-vcenter card-table">

            <thead>
            <tr>
                <th>Invoice #</th>
                <th>Customer</th>
                <th>Total</th>
                <th>Date</th>
            </tr>
            </thead>

            <tbody>

            @foreach($invoices as $invoice)

                <tr>

                    <td>
                        {{ $invoice->invoice_number }}
                    </td>

                    <td>
                        {{ $invoice->customer?->name }}
                    </td>

                    <td>
                        {{ $invoice->total }}
                    </td>

                    <td>
                        {{ $invoice->created_at->format('Y-m-d') }}
                    </td>

                </tr>

            @endforeach

            </tbody>

        </table>

    </div>

</div>

@endsection