@extends('layouts.app')

@section('content')

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            Suppliers
        </h3>

        <a href="{{ route('suppliers.create') }}"
           class="btn btn-primary ms-auto">
            Add Supplier
        </a>
    </div>

    <div class="table-responsive">

        <table class="table table-vcenter card-table">

            <thead>

                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th class="text-end">Total Purchases</th>
                    <th class="text-end">Paid</th>
                    <th class="text-end">Remaining Amount</th>
                    <th class="text-end">Actions</th>
                </tr>

            </thead>

            <tbody>

                @forelse($suppliers as $supplier)

                    <tr>

                        <td>{{ $supplier->name }}</td>

                        <td>{{ $supplier->phone ?? '-' }}</td>

                        <td>{{ $supplier->email ?? '-' }}</td>

                        <td class="text-end">Rs {{ number_format($supplier->totalPurchases(), 2) }}</td>

                        <td class="text-end">Rs {{ number_format($supplier->totalPaid(), 2) }}</td>

                        <td class="text-end">
                            <span class="badge bg-{{ $supplier->remainingAmount() > 0 ? 'danger' : 'success' }}">
                                Rs {{ number_format($supplier->remainingAmount(), 2) }}
                            </span>
                        </td>

                        <td class="text-end">
                            <a href="{{ route('supplier.account', $supplier) }}"
                               class="btn btn-sm btn-dark">
                                Account Detail
                            </a>

                            <a href="{{ route('suppliers.edit', $supplier->id) }}"
                               class="btn btn-sm btn-outline-secondary">
                                Edit
                            </a>

                            @if($supplier->purchases_count === 0 && $supplier->payments_count === 0)
                                <form action="{{ route('suppliers.destroy', $supplier->id) }}"
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

                        <td colspan="7" class="text-center text-muted">
                            No suppliers found
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
