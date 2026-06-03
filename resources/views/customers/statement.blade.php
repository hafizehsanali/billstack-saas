@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">

    <div>
        <h2 class="mb-1">Customer Statement</h2>
        <small class="text-muted">{{ $customer->name }}</small>
    </div>

    <div class="d-flex gap-2">

        <form method="GET" class="d-flex gap-2">

            <input type="date" name="start_date"
                   value="{{ request('start_date') }}"
                   class="form-control">

            <input type="date" name="end_date"
                   value="{{ request('end_date') }}"
                   class="form-control">

            <button class="btn btn-primary">
                Filter
            </button>

        </form>

        <button onclick="window.print()" class="btn btn-secondary">
            Print
        </button>

    </div>

</div> {{-- filters row ends here --}}

{{-- Customer balance summary --}}
@php
    $remainingAmount = $entries->last()['remaining_amount'] ?? 0;
@endphp

<div class="row mb-3">

    <div class="col-md-4">

        <div class="card border-0 shadow-sm">

            <div class="card-body">

                {{-- Balance label --}}
                <small class="text-muted">

                    @if($remainingAmount > 0)

                        Customer Owes Us

                    @elseif($remainingAmount < 0)

                        Advance Amount

                    @else

                        Fully Paid

                    @endif

                </small>

                {{-- Balance amount --}}
                <h1 class="
                    mb-0 fw-bold
                    {{ $remainingAmount > 0 ? 'text-danger' : 'text-success' }}
                ">

                    Rs {{ number_format(abs($remainingAmount), 2) }}

                </h1>

            </div>

        </div>

    </div>

</div>

<div class="card mb-3">

    <div class="card-header">
        <h3 class="card-title mb-0">
            Receive Payment
        </h3>
    </div>

    <div class="card-body">

        <form action="{{ route('customer-payments.store', $customer) }}" method="POST">
            @csrf

            <div class="row g-3 align-items-end">

                <div class="col-md-2">
                    <label class="form-label">Amount Received from Customer</label>
                    <input type="number"
                           name="amount"
                           step="0.01"
                           min="1"
                           max="{{ max($outstandingBalance, 0) }}"
                           class="form-control"
                           value="{{ old('amount') }}"
                           required>
                    <small class="text-muted">
                        Customer owes us: Rs {{ number_format($outstandingBalance, 2) }}
                    </small>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Method</label>
                    <select name="payment_method" class="form-select" required>
                        <option value="cash" @selected(old('payment_method', 'cash') === 'cash')>Cash</option>
                        <option value="bank" @selected(old('payment_method') === 'bank')>Bank</option>
                        <option value="card" @selected(old('payment_method') === 'card')>Card</option>
                        <option value="jazzcash" @selected(old('payment_method') === 'jazzcash')>JazzCash</option>
                        <option value="easypaisa" @selected(old('payment_method') === 'easypaisa')>EasyPaisa</option>
                        <option value="cheque" @selected(old('payment_method') === 'cheque')>Cheque</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Date</label>
                    <input type="date"
                           name="payment_date"
                           class="form-control"
                           value="{{ old('payment_date', now()->format('Y-m-d')) }}">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Reference No</label>
                    <input type="text"
                           name="reference_no"
                           class="form-control"
                           value="{{ old('reference_no') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Notes</label>
                    <input type="text"
                           name="notes"
                           class="form-control"
                           value="{{ old('notes') }}">
                </div>

                <div class="col-md-1">
                    <button type="submit"
                            class="btn btn-success w-100"
                            @disabled($outstandingBalance <= 0)>
                        Save Payment
                    </button>
                </div>

            </div>

        </form>

    </div>

</div>

<div class="card">

    <div class="table-responsive">

        <table class="table table-bordered table-striped mb-0">

            <thead class="table-dark">

                <tr>
                    <th>Date</th>
                    <th>Entry</th>
                    <th>Invoice / Payment</th>
                    <th>Sale Amount</th>
                    <th>Payment / Return Credit</th>
                    <th>Customer Balance</th>
                </tr>

            </thead>

            <tbody>

                @forelse($entries as $entry)

                    <tr>

                        <td>
                            {{ $entry['date']->format('d M Y') }}
                        </td>

                        <td>
                            {{ $entry['type'] }}
                        </td>

                        <td>
                            {{ $entry['reference'] }}
                        </td>

                        <td>
                            {{ $entry['debit']
                                ? number_format($entry['debit'], 2)
                                : '-' }}
                        </td>

                        <td>
                            {{ $entry['credit']
                                ? number_format($entry['credit'], 2)
                                : '-' }}
                        </td>

                        <td>
                            Rs {{ number_format($entry['remaining_amount'], 2) }}
                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="6" class="text-center text-muted">
                            No customer statement records found.
                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

@endsection
