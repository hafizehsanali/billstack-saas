@extends('layouts.app')

@section('content')

@php
    $supplierRows = $suppliers->getCollection();
    $totalPurchases = $supplierRows->sum(fn ($supplier) => $supplier->totalPurchases());
    $totalPaid = $supplierRows->sum(fn ($supplier) => $supplier->totalPaid());
    $totalPayable = $supplierRows->sum(fn ($supplier) => $supplier->remainingAmount());
    $suppliersToPay = $supplierRows->filter(fn ($supplier) => $supplier->remainingAmount() > 0)->count();
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-1">Suppliers</h3>
        <div class="text-muted">
            Supplier accounts, purchase history, and money still payable.
        </div>
    </div>

    <a href="{{ route('suppliers.create') }}"
       class="btn btn-primary">
        Add Supplier
    </a>
</div>

<div class="row row-cards mb-3">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Total Suppliers</div>
                <div class="h2 mb-0">{{ number_format($suppliers->total()) }}</div>
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
                <div class="text-muted">Suppliers to Pay</div>
                <div class="h2 mb-0 text-danger">{{ number_format($suppliersToPay) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Supplier</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th class="text-end">Total Purchases</th>
                    <th class="text-end">Amount Paid</th>
                    <th class="text-end">Still Payable to Supplier</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($suppliers as $supplier)
                    @php
                        $payable = $supplier->remainingAmount();
                    @endphp

                    <tr>
                        <td>
                            <div class="fw-bold">{{ $supplier->name }}</div>
                            @if($supplier->address)
                                <div class="text-muted small">{{ $supplier->address }}</div>
                            @endif
                        </td>

                        <td>{{ $supplier->phone ?? '-' }}</td>

                        <td>{{ $supplier->email ?? '-' }}</td>

                        <td class="text-end">Rs {{ number_format($supplier->totalPurchases(), 2) }}</td>

                        <td class="text-end text-success">Rs {{ number_format($supplier->totalPaid(), 2) }}</td>

                        <td class="text-end fw-bold {{ $payable > 0 ? 'text-danger' : 'text-success' }}">
                            Rs {{ number_format($payable, 2) }}
                        </td>

                        <td>
                            @if($payable > 0)
                                <span class="badge bg-danger">Payment Due</span>
                            @else
                                <span class="badge bg-success">Clear</span>
                            @endif
                        </td>

                        <td class="text-end">
                            <a href="{{ route('supplier.account', $supplier) }}"
                               class="btn btn-sm btn-dark">
                                Account Detail
                            </a>

                            <a href="{{ route('suppliers.edit', $supplier) }}"
                               class="btn btn-sm btn-outline-secondary">
                                Edit
                            </a>

                            @if($supplier->purchases_count === 0 && $supplier->payments_count === 0)
                                <form action="{{ route('suppliers.destroy', $supplier) }}"
                                      method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm('Delete this supplier?')">
                                    @csrf
                                    @method('DELETE')

                                    <button class="btn btn-sm btn-outline-danger">
                                        Delete
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            No suppliers found. Add your first supplier to start tracking purchases and payments.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $suppliers->links() }}
</div>

@endsection
