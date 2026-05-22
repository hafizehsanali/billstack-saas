@extends('layouts.app')

@section('content')

@php
    $remainingAmount = $entries->last()['remaining_amount'] ?? 0;
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">

    <div>

        <h2 class="mb-1">
            Supplier Account
        </h2>

        <small class="text-muted">
            {{ $supplier->name }}
        </small>

    </div>

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

</div>

<div class="row mb-3">

    <div class="col-md-4">

        <div class="card border-0 shadow-sm">

            <div class="card-body">

                <small class="text-muted">

                    @if($remainingAmount > 0)

                        Supplier Payable

                    @elseif($remainingAmount < 0)

                        Advance Paid

                    @else

                        Fully Paid

                    @endif

                </small>

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

<div class="card">

    <div class="table-responsive">

        <table class="table table-bordered table-striped mb-0">

            <thead class="table-dark">

                <tr>
                    <th>Date</th>
                    <th>Entry</th>
                    <th>Purchase / Payment</th>
                    <th>Purchase Amount</th>
                    <th>Payment Sent</th>
                    <th>Remaining Payable</th>
                </tr>

            </thead>

            <tbody>

                @forelse($entries as $entry)

                    <tr>

                        <td>
                            {{ $entry['date']->format('d M Y') }}
                        </td>

                        <td>
                            {{ $entry['entry'] }}
                        </td>

                        <td>
                            {{ $entry['reference'] }}
                        </td>

                        <td>
                            {{ $entry['purchase_amount']
                                ? number_format($entry['purchase_amount'], 2)
                                : '-' }}
                        </td>

                        <td>
                            {{ $entry['payment_sent']
                                ? number_format($entry['payment_sent'], 2)
                                : '-' }}
                        </td>

                        <td>
                            Rs {{ number_format($entry['remaining_amount'], 2) }}
                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="6" class="text-center text-muted">
                            No records found.
                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

@endsection