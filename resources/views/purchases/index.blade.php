@extends('layouts.app')

@section('content')

@php
    $purchaseRows = $purchases->getCollection();
    $totalPurchases = $purchaseRows->where('status', '!=', 'cancelled')->sum('total');
    $totalPaid = $purchaseRows->where('status', '!=', 'cancelled')->sum('paid_amount');
    $totalPayable = $purchaseRows->where('status', '!=', 'cancelled')->sum('remaining_amount');
    $openPurchases = $purchaseRows
        ->filter(fn ($purchase) => in_array($purchase->status, ['unpaid', 'partial'], true))
        ->count();
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-1">Purchases</h3>
        <div class="text-muted">
            Supplier bills, payment status, and money still payable.
        </div>
    </div>

    <a href="{{ route('purchases.create') }}"
       class="btn btn-primary">
        Create Purchase
    </a>
</div>

<div class="row row-cards mb-3">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Purchases</div>
                <div class="h2 mb-0">{{ number_format($purchases->total()) }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Purchases on This Page</div>
                <div class="h2 mb-0">Rs {{ number_format($totalPurchases, 2) }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Paid on This Page</div>
                <div class="h2 mb-0 text-success">Rs {{ number_format($totalPaid, 2) }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Open Purchases</div>
                <div class="h2 mb-0 text-danger">{{ number_format($openPurchases) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Purchase</th>
                    <th>Supplier</th>
                    <th>Date</th>
                    <th class="text-end">Purchase Total</th>
                    <th class="text-end">Amount Paid</th>
                    <th class="text-end">Still Payable to Supplier</th>
                    <th>Status</th>
                    <th class="text-end" style="min-width: 180px;">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($purchases as $purchase)
                    <tr class="{{ $purchase->status === 'cancelled' ? 'table-secondary' : '' }}">
                        <td>
                            <div class="fw-bold">{{ $purchase->purchase_no }}</div>
                        </td>

                        <td>{{ $purchase->supplier?->name ?? 'Deleted supplier' }}</td>

                        <td>{{ $purchase->purchase_date?->format('d M Y') }}</td>

                        <td class="text-end">Rs {{ number_format($purchase->total, 2) }}</td>

                        <td class="text-end text-success">Rs {{ number_format($purchase->paid_amount, 2) }}</td>

                        <td class="text-end fw-bold {{ $purchase->remaining_amount > 0 ? 'text-danger' : 'text-success' }}">
                            Rs {{ number_format($purchase->remaining_amount, 2) }}
                        </td>

                        <td>
                            @if($purchase->status == 'paid')
                                <span class="badge bg-success">Paid</span>
                            @elseif($purchase->status == 'partial')
                                <span class="badge bg-info">Partial</span>
                            @elseif($purchase->status == 'returned')
                                <span class="badge bg-secondary">Returned</span>
                            @elseif($purchase->status == 'cancelled')
                                <span class="badge bg-danger">Cancelled</span>
                            @else
                                <span class="badge bg-warning">Unpaid</span>
                            @endif
                        </td>

                        <td class="text-end">
                            <div class="d-inline-flex gap-1 flex-nowrap">
                                <a href="{{ route('purchases.show', $purchase) }}"
                                   class="btn btn-sm btn-primary text-nowrap">
                                    View
                                </a>

                                @if(! in_array($purchase->status, ['cancelled', 'paid', 'partial', 'returned'], true))
                                    <form action="{{ route('purchases.cancel', $purchase) }}"
                                          method="POST"
                                          class="m-0"
                                          onsubmit="return confirm('Cancel this purchase?')">
                                        @csrf

                                        <button type="submit"
                                                class="btn btn-sm btn-warning text-nowrap">
                                            Cancel
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            No purchases found. Create your first purchase to start tracking supplier stock and payments.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $purchases->links() }}
</div>

@endsection
