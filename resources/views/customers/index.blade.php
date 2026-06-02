@extends('layouts.app')

@section('content')

<div class="card">

    <div class="card-header">

        <h3 class="card-title">
            Customers
        </h3>

        <a href="{{ route('customers.create') }}"
           class="btn btn-primary ms-auto">
            Add Customer
        </a>

    </div>

    <div class="table-responsive">

        <table class="table table-vcenter card-table">

            <thead>
            <tr>
                <th>Name</th>
                <th>Phone</th>
                <th>Email</th>
                <th class="text-end">Total Sales</th>
                <th class="text-end">Paid</th>
                <th class="text-end">Remaining Amount</th>
                <th class="text-end">Actions</th>
            </tr>
            </thead>

            <tbody>

            @foreach($customers as $customer)

                <tr>

                    <td>{{ $customer->name }}</td>

                    <td>{{ $customer->phone ?? '-' }}</td>

                    <td>{{ $customer->email ?? '-' }}</td>

                    <td class="text-end">Rs {{ number_format($customer->totalSales(), 2) }}</td>

                    <td class="text-end">Rs {{ number_format($customer->totalPaid(), 2) }}</td>

                    <td class="text-end">
                        <span class="badge bg-{{ $customer->remainingAmount() > 0 ? 'danger' : 'success' }}">
                            Rs {{ number_format($customer->remainingAmount(), 2) }}
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('customers.statement', $customer) }}"
                           class="btn btn-sm btn-dark">
                            Account Detail
                        </a>

                        <a href="{{ route('customers.edit', $customer) }}"
                           class="btn btn-sm btn-outline-secondary">
                            Edit
                        </a>

                        @if($customer->invoices_count === 0 && $customer->payments_count === 0 && $customer->returns_count === 0)
                            <form action="{{ route('customers.destroy', $customer) }}"
                                  method="POST"
                                  class="d-inline"
                                  onsubmit="return confirm('Delete this customer?')">
                                @csrf
                                @method('DELETE')

                                <button class="btn btn-sm btn-outline-danger">
                                    Delete
                                </button>
                            </form>
                        @endif
                    </td>

                </tr>

            @endforeach

            </tbody>

        </table>

    </div>

</div>

<div class="mt-3">
    {{ $customers->links() }}
</div>

@endsection
