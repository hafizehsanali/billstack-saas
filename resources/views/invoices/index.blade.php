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
                <th>Status</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
            </thead>

            <tbody>

            @foreach($invoices as $invoice)

                <tr class="{{ $invoice->status === 'cancelled' ? 'table-secondary' : '' }}">

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
                       <span class="badge
                            @if($invoice->status == 'paid') bg-success
                            @elseif($invoice->status == 'cancelled') bg-danger
                            @else bg-warning
                            @endif ">
                            {{ ucfirst($invoice->status) }}
                        </span>
                    </td>
                    <td>
                        {{ $invoice->created_at->format('Y-m-d') }}
                    </td>
                    <td>
                        <a href="{{ route('invoices.show', $invoice) }}"
                        class="btn btn-sm btn-primary">
                            View
                        </a>
                         @if($invoice->status != 'cancelled')
                            <form method="POST" action="{{ route('invoices.cancel', $invoice) }}" style="display:inline;">
                               @csrf
                                <button class="btn btn-sm btn-warning" onclick="return confirm('Cancel invoice?')">
                                    Cancel
                                </button>
                            </form>
                        @endif
                    </td>

                </tr>

            @endforeach

            </tbody>

        </table>

    </div>

</div>

@endsection