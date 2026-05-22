@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">

    <div>
        <h2 class="mb-1">Customer Account</h2>
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

{{-- Remaining amount summary --}}
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

                        Customer Remaining Amount

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

<div class="card">

    <div class="table-responsive">

        <table class="table table-bordered table-striped mb-0">

            <thead class="table-dark">

                <tr>
                    <th>Date</th>
                    <th>Entry</th>
                    <th>Invoice / Payment</th>
                    <th>Sale Amount</th>
                    <th>Payment Received</th>
                    <th>Remaining Amount</th>
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
                            No statement records found.
                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

@endsection