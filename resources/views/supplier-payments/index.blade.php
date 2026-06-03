@extends('layouts.app')

@section('content')

@php
    $totalPayments = $payments->sum('amount');
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-1">Supplier Payment History</h3>
        <div class="text-muted">
            {{ $supplier->name }}
            @if($supplier->phone)
                / {{ $supplier->phone }}
            @endif
        </div>
    </div>

    <a href="{{ route('supplier-payments.create', $supplier) }}"
       class="btn btn-primary">
        Record Supplier Payment
    </a>
</div>

<div class="row row-cards mb-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Payments Recorded</div>
                <div class="h2 mb-0">{{ number_format($payments->count()) }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Total Paid to Supplier</div>
                <div class="h2 mb-0 text-success">Rs {{ number_format($totalPayments, 2) }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Supplier Account</div>
                <a href="{{ route('supplier.account', $supplier) }}"
                   class="btn btn-sm btn-dark mt-2">
                    Open Ledger
                </a>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th class="text-end">Amount Paid</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th>Notes</th>
                    <th class="text-end" style="min-width: 100px;">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($payment->payment_date ?? $payment->created_at)->format('d M Y') }}</td>

                        <td class="text-end text-success fw-bold">
                            Rs {{ number_format($payment->amount, 2) }}
                        </td>

                        <td>{{ $payment->payment_method ? ucfirst($payment->payment_method) : '-' }}</td>

                        <td>{{ $payment->reference_no ?? '-' }}</td>

                        <td>{{ $payment->notes ?? '-' }}</td>

                        <td class="text-end">
                            <form action="{{ route('supplier-payments.destroy', $payment) }}"
                                  method="POST"
                                  class="m-0"
                                  onsubmit="return confirm('Delete payment?')">
                                @csrf
                                @method('DELETE')

                                <button class="btn btn-sm btn-outline-danger text-nowrap">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            No supplier payments recorded yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
