@extends('layouts.app')

@section('content')

<div class="card">

    <div class="card-header">

        <h3 class="card-title">
            Monthly Sales Report
        </h3>

    </div>

    <div class="card-body">

        <h2>
            Monthly Sales:
            Rs. {{ number_format($totalSales, 2) }}
        </h2>

    </div>

    <div class="table-responsive">

        <table class="table table-vcenter card-table">

            <thead>

            <tr>
                <th>Invoice</th>
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