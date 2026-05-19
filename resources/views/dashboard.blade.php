@extends('layouts.app')

@section('content')

<div class="row row-deck row-cards">

    <!-- Today Sales -->

    <div class="col-sm-6 col-lg-3">

        <div class="card">

            <div class="card-body">

                <div class="subheader">
                    Today Sales
                </div>

                <div class="h1 mb-3">
                    Rs. {{ number_format($todaySales, 2) }}
                </div>

            </div>

        </div>

    </div>

    <!-- Total Products -->

    <div class="col-sm-6 col-lg-3">

        <div class="card">

            <div class="card-body">

                <div class="subheader">
                    Products
                </div>

                <div class="h1 mb-3">
                    {{ $totalProducts }}
                </div>

            </div>

        </div>

    </div>

    <!-- Total Customers -->

    <div class="col-sm-6 col-lg-3">

        <div class="card">

            <div class="card-body">

                <div class="subheader">
                    Customers
                </div>

                <div class="h1 mb-3">
                    {{ $totalCustomers }}
                </div>

            </div>

        </div>

    </div>

    <!-- Total Invoices -->

    <div class="col-sm-6 col-lg-3">

        <div class="card">

            <div class="card-body">

                <div class="subheader">
                    Invoices
                </div>

                <div class="h1 mb-3">
                    {{ $totalInvoices }}
                </div>

            </div>

        </div>

    </div>

</div>

<!-- Low Stock Alerts -->

<div class="card mt-4">

    <div class="card-header">

        <h3 class="card-title">
            Low Stock Alerts
        </h3>

    </div>

    <div class="table-responsive">

        <table class="table table-vcenter card-table">

            <thead>

            <tr>
                <th>Product</th>
                <th>Current Stock</th>
                <th>Alert Level</th>
            </tr>

            </thead>

            <tbody>

            @forelse($lowStockProducts as $product)

                <tr>

                    <td>
                        {{ $product->name }}
                    </td>

                    <td>
                        {{ $product->stock_quantity }}
                    </td>

                    <td>
                        {{ $product->low_stock_alert }}
                    </td>

                </tr>

            @empty

                <tr>

                    <td colspan="3">
                        No low stock products.
                    </td>

                </tr>

            @endforelse

            </tbody>

        </table>

    </div>

</div>

@endsection