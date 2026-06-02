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

    {{-- Total Returns --}}
    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small">Total Returns</div>
                <div class="fs-4 fw-bold text-warning">
                    {{ number_format($total_returns,2) }}
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

<div class="card mb-3">
    <div class="card-header fw-bold">
        Pay Supplier
    </div>

    <div class="card-body">
        <form action="{{ route('supplier-payments.store') }}" method="POST">
            @csrf

            <input type="hidden" name="supplier_id" value="{{ $supplier->id }}">
            <input type="hidden" name="source" value="account">

            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Amount</label>
                    <input type="number"
                           name="amount"
                           step="0.01"
                           min="1"
                           max="{{ max($outstanding_payable, 0) }}"
                           class="form-control"
                           value="{{ old('amount') }}"
                           required>
                    <small class="text-muted">
                        Payable: Rs {{ number_format($outstanding_payable, 2) }}
                    </small>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Method</label>
                    <select name="payment_method" class="form-select">
                        <option value="">Select Method</option>
                        <option value="cash" @selected(old('payment_method') === 'cash')>Cash</option>
                        <option value="bank" @selected(old('payment_method') === 'bank')>Bank Transfer</option>
                        <option value="jazzcash" @selected(old('payment_method') === 'jazzcash')>JazzCash</option>
                        <option value="easypaisa" @selected(old('payment_method') === 'easypaisa')>EasyPaisa</option>
                        <option value="cheque" @selected(old('payment_method') === 'cheque')>Cheque</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Payment Date</label>
                    <input type="date"
                           name="payment_date"
                           value="{{ old('payment_date', now()->format('Y-m-d')) }}"
                           class="form-control"
                           required>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Reference No</label>
                    <input type="text"
                           name="reference_no"
                           value="{{ old('reference_no') }}"
                           class="form-control">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Notes</label>
                    <input type="text"
                           name="notes"
                           value="{{ old('notes') }}"
                           class="form-control">
                </div>

                <div class="col-md-1">
                    <button type="submit"
                            class="btn btn-success w-100"
                            @disabled($outstanding_payable <= 0)>
                        Save
                    </button>
                </div>
            </div>
        </form>
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
                            @elseif($row['type'] === 'Supplier Return')
                                <a href="{{ route('purchases.show', $row['reference_id'] ?? 0) }}">
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
