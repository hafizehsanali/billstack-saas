@extends('layouts.app')

@section('content')
<style>
    @media print {
    .navbar, .btn, .no-print {
        display: none !important;
    }

    .card {
        border: none !important;
        box-shadow: none !important;
    }

    body {
        font-size: 12px;
    }
}
</style>
<div class="container">
{{-- fillters --}}
<form method="GET" class="row g-2 mb-3 ">

    <div class="col-md-3">
        <input type="date" name="from" value="{{ request('from') }}" class="form-control">
    </div>

    <div class="col-md-3">
        <input type="date" name="to" value="{{ request('to') }}" class="form-control">
    </div>

    <div class="col-md-2">
        <button class="btn btn-primary w-100">Filter</button>
    </div>

    <div class="col-md-2">
        <a href="{{ url()->current() }}" class="btn btn-secondary w-100">
            Reset
        </a>
    </div>

</form>

<h3>{{ $supplier->name }} Ledger</h3>
{{-- Summary --}}
<div class="row g-3 mb-4">

    {{-- Total Purchases --}}
    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small">Total Purchases</div>
                <div class="fs-4 fw-bold text-danger">
                    {{ number_format($total_purchases,2) }}
                </div>
            </div>
        </div>
    </div>

    {{-- Total Payments --}}
    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small">Total Payments</div>
                <div class="fs-4 fw-bold text-success">
                    {{ number_format($total_payments,2) }}
                </div>
            </div>
        </div>
    </div>

    {{-- Outstanding / Advance --}}
    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small">
                    {{ $remaining_amount >= 0 ? 'Outstanding Payable' : 'Advance Paid' }}
                </div>

                <div class="fs-4 fw-bold {{ $remaining_amount >= 0 ? 'text-warning' : 'text-primary' }}">
                    {{ number_format(abs($remaining_amount),2) }}
                </div>
            </div>
        </div>
    </div>

    {{-- Status --}}
    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small">Account Status</div>

                <div class="fs-5 fw-bold">
                    @if($remaining_amount > 0)
                        <span class="text-danger">Payable</span>
                    @elseif($remaining_amount < 0)
                        <span class="text-primary">Advance</span>
                    @else
                        <span class="text-success">Settled</span>
                    @endif
                </div>

            </div>
        </div>
    </div>

</div>

{{-- Ledger Table --}}
<button onclick="window.print()" class="btn btn-dark btn-sm">
    Print Statement
</button>
<div class="card mt-3">
    <div class="card-header fw-bold">
        Ledger Statement
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-striped mb-0">

            <thead class="table-dark">
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Reference</th>
                    <th class="text-end">Debit</th>
                    <th class="text-end">Credit</th>
                    <th class="text-end">Balance</th>
                </tr>
            </thead>

            <tbody>
                @foreach($ledger as $row)
                  <tr>
                        <td>{{ $row['date'] }}</td>

                        <td>
                            @if($row['type'] === 'Purchase')
                                <a href="{{ route('purchases.show', $row['reference_id'] ?? 0) }}">
                                    {{ $row['description'] }}
                                </a>
                            @elseif($row['type'] === 'Payment')
                                <a href="{{ route('supplier-payments.show', $row['reference_id'] ?? 0) }}">
                                    {{ $row['description'] }}
                                </a>
                            @else
                                {{ $row['description'] }}
                            @endif
                        </td>

                        <td>{{ $row['reference'] }}</td>
                        <td class="text-end text-danger">{{ number_format($row['debit'],2) }}</td>
                        <td class="text-end text-success">{{ number_format($row['credit'],2) }}</td>
                        <td class="text-end fw-bold">{{ number_format($row['balance'],2) }}</td>
                    </tr>
                @endforeach
            </tbody>

        </table>
    </div>
</div>

<div class="mt-3 card">
    <div class="card-body d-flex justify-content-between">

        <div>
            <strong>Opening Balance:</strong>
            {{ number_format($opening_balance,2) }}
        </div>

        <div>
            <strong>Closing Balance:</strong>
            <span class="fw-bold text-primary">
                {{ number_format($closing_balance,2) }}
            </span>
        </div>

    </div>
</div>

</div>
@endsection