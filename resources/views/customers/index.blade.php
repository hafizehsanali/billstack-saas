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
                <th>Total Sales</th>
                <th>Paid</th>
                <th>Balance</th>
            </tr>
            </thead>

            <tbody>

            @foreach($customers as $customer)

                <tr>

                    <td>{{ $customer->name }}</td>

                    <td>{{ $customer->phone }}</td>

                    <td>{{ $customer->email }}</td>

                    <td>Rs {{ number_format($customer->totalSales(), 2) }}</td>

                    <td>Rs {{ number_format($customer->totalPaid(), 2) }}</td>

                    <td>
                        <span class="badge bg-{{ $customer->balance() > 0 ? 'danger' : 'success' }}">
                            Rs {{ number_format($customer->balance(), 2) }}
                        </span>
                    </td>

                </tr>

            @endforeach

            </tbody>

        </table>

    </div>

</div>

@endsection