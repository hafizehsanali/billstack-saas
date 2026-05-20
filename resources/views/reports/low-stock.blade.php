@extends('layouts.app')

@section('content')

<div class="card">

    <div class="card-header">

        <h3 class="card-title">
            Low Stock Report
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

            @forelse($products as $product)

                <tr>

                    <td>{{ $product->name }}</td>

                    <td>{{ $product->stock_quantity }}</td>

                    <td>{{ $product->low_stock_alert }}</td>

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