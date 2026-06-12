@extends('layouts.app')

@section('content')

@php
    $customerRows = $customers->getCollection();
    $totalSales = $customerRows->sum(fn ($customer) => $customer->totalSales());
    $totalReceived = $customerRows->sum(fn ($customer) => $customer->totalPaid());
    $totalReceivable = $customerRows->sum(fn ($customer) => $customer->remainingAmount());
    $customersWithBalance = $customerRows->filter(fn ($customer) => $customer->remainingAmount() > 0)->count();
@endphp

<div class="page-heading">
    <div>
        <h3 class="mb-1">Customers</h3>
        <div class="text-muted">
            Customer accounts, sales history, and money still receivable.
        </div>
    </div>

    <a href="{{ route('customers.create') }}"
       class="btn btn-primary">
        <i data-lucide="user-plus"></i>
        Add Customer
    </a>
</div>

<div class="row row-cards mb-3">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Total Customers</div>
                <div class="h2 mb-0">{{ number_format($customers->total()) }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Sales on This Page</div>
                <div class="h2 mb-0">Rs {{ number_format($totalSales, 2) }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Received on This Page</div>
                <div class="h2 mb-0 text-success">Rs {{ number_format($totalReceived, 2) }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Customers Owing Money</div>
                <div class="h2 mb-0 text-danger">{{ number_format($customersWithBalance) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th class="text-end">Total Sales</th>
                    <th class="text-end">Amount Received</th>
                    <th class="text-end">Customer Owes Us</th>
                    <th>Status</th>
                    <th class="text-end" style="min-width: 220px;">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($customers as $customer)
                    @php
                        $receivable = $customer->remainingAmount();
                    @endphp

                    <tr>
                        <td>
                            <div class="fw-bold">{{ $customer->name }}</div>
                            @if($customer->address)
                                <div class="text-muted small">{{ $customer->address }}</div>
                            @endif
                        </td>

                        <td>{{ $customer->phone ?? '-' }}</td>

                        <td>{{ $customer->email ?? '-' }}</td>

                        <td class="text-end">Rs {{ number_format($customer->totalSales(), 2) }}</td>

                        <td class="text-end text-success">Rs {{ number_format($customer->totalPaid(), 2) }}</td>

                        <td class="text-end fw-bold {{ $receivable > 0 ? 'text-danger' : 'text-success' }}">
                            Rs {{ number_format($receivable, 2) }}
                        </td>

                        <td>
                            @if($receivable > 0)
                                <span class="badge bg-danger">Payment Due</span>
                            @else
                                <span class="badge bg-success">Clear</span>
                            @endif
                        </td>

                        <td class="text-end">
                            <div class="d-inline-flex gap-1 flex-nowrap">
                                <a href="{{ route('customers.statement', $customer) }}"
                                   class="btn btn-sm btn-dark text-nowrap">
                                    Account
                                </a>

                                <a href="{{ route('customers.edit', $customer) }}"
                                   class="btn btn-sm btn-outline-secondary text-nowrap">
                                    Edit
                                </a>

                                @if($customer->invoices_count === 0 && $customer->payments_count === 0 && $customer->returns_count === 0)
                                    <form action="{{ route('customers.destroy', $customer) }}"
                                          method="POST"
                                          class="m-0"
                                          onsubmit="return confirm('Delete this customer?')">
                                        @csrf
                                        @method('DELETE')

                                        <button class="btn btn-sm btn-outline-danger text-nowrap">
                                            Delete
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            No customers found. Add your first customer to start tracking sales and payments.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $customers->links() }}
</div>

@endsection
