@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h2 class="mb-1">
                Customer Ledger
            </h2>

            <p class="text-muted mb-0">
                {{ $customer->name }}
            </p>
        </div>

        <a href="{{ url()->previous() }}"
           class="btn btn-secondary">
            Back
        </a>

    </div>

    <div class="row mb-4">

        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">

                    <small class="text-muted">
                        Total Sales
                    </small>

                    <h3 class="mt-2">
                        Rs {{ number_format($totalSales, 2) }}
                    </h3>

                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">

                    <small class="text-muted">
                        Total Returns
                    </small>

                    <h3 class="mt-2 text-warning">
                        Rs {{ number_format($totalReturns, 2) }}
                    </h3>

                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">

                    <small class="text-muted">
                        Total Received
                    </small>

                    <h3 class="mt-2 text-success">
                        Rs {{ number_format($totalReceived, 2) }}
                    </h3>

                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">

                    <small class="text-muted">
                        Outstanding Receivable
                    </small>

                    <h3 class="mt-2 text-danger">
                        Rs {{ number_format($receivable, 2) }}
                    </h3>

                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">

                    <small class="text-muted">
                        Advance Received
                    </small>

                    <h3 class="mt-2 text-primary">
                        Rs {{ number_format($advance, 2) }}
                    </h3>

                </div>
            </div>
        </div>

    </div>

    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white">
            <h5 class="mb-0">
                Ledger Transactions
            </h5>
        </div>

        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead class="table-light">

                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Reference</th>
                        <th class="text-end">Debit</th>
                        <th class="text-end">Credit</th>
                        <th class="text-end">Running</th>
                    </tr>

                </thead>

                <tbody>

                    @forelse($ledger as $entry)

                        <tr>

                            <td>
                                {{ \Carbon\Carbon::parse($entry['date'])->format('d M Y') }}
                            </td>

                            <td>

                                @if($entry['type'] === 'invoice')

                                    <span class="badge bg-danger">
                                        Invoice
                                    </span>

                                @elseif($entry['type'] === 'return')

                                    <span class="badge bg-warning">
                                        Return
                                    </span>

                                @else

                                    <span class="badge bg-success">
                                        Payment
                                    </span>

                                @endif

                            </td>

                            <td>

                                @if($entry['type'] === 'invoice')

                                    <a href="{{ route('invoices.show', $entry['model']->id) }}">
                                        {{ $entry['reference'] }}
                                    </a>

                                @elseif($entry['type'] === 'return')

                                    <a href="{{ route('invoices.show', $entry['model']->invoice_id) }}">
                                        {{ $entry['reference'] }}
                                    </a>

                                @else

                                    {{ $entry['reference'] }}

                                @endif

                            </td>

                            <td class="text-end text-danger">

                                @if($entry['debit'] > 0)

                                    Rs {{ number_format($entry['debit'], 2) }}

                                @endif

                            </td>

                            <td class="text-end text-success">

                                @if($entry['credit'] > 0)

                                    Rs {{ number_format($entry['credit'], 2) }}

                                @endif

                            </td>

                            <td class="text-end fw-bold">

                                Rs {{ number_format($entry['running_balance'], 2) }}

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="6" class="text-center py-4 text-muted">
                                No ledger transactions found
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection
