@extends('layouts.app')

@section('content')

<div class="card">

    <div class="card-header">

        <h3 class="card-title">
            Stock Report
        </h3>

    </div>

    <div class="table-responsive">

        <table class="table table-vcenter card-table">

            <thead>

            <tr>
                <th>Product</th>
                <th>SKU</th>
                <th>Stock</th>
                <th>Selling Price</th>
            </tr>

            </thead>

            <tbody>

            @foreach($products as $product)

                <tr>

                    <td>{{ $product->name }}</td>

                    <td>{{ $product->sku }}</td>

                    <td>{{ $product->stock_quantity }}</td>

                    <td>{{ $product->selling_price }}</td>

                </tr>

            @endforeach

            </tbody>

        </table>

    </div>

</div>

@endsection