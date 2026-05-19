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
                <th>Balance</th>
            </tr>
            </thead>

            <tbody>

            @foreach($customers as $customer)

                <tr>

                    <td>{{ $customer->name }}</td>

                    <td>{{ $customer->phone }}</td>

                    <td>{{ $customer->email }}</td>

                    <td>{{ $customer->opening_balance }}</td>

                </tr>

            @endforeach

            </tbody>

        </table>

    </div>

</div>

@endsection