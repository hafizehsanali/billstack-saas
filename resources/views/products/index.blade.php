@extends('layouts.app')

@section('content')

<div class="card">

    <div class="card-header">

        <h3 class="card-title">
            Products
        </h3>

        <a href="{{ route('products.create') }}"
           class="btn btn-primary ms-auto">
            Add Product
        </a>

    </div>

    <div class="table-responsive">

        <table class="table table-vcenter card-table">

            <thead>
                <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Purchase Price</th>
                    <th>Selling Price</th>
                    <th>Stock</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>

            <tbody>

            @foreach($products as $product)

                <tr>

                    <td>
                        {{ $product->name }}
                    </td>

                    <td>
                        {{ $product->category?->name }}
                    </td>

                    
                    <td>{{ $product->purchase_price }}</td>
                    <td>{{ $product->selling_price }}</td>

                    <td>
                        {{ $product->stock_quantity }}
                    </td>

                    <td class="text-end">
                        <a href="{{ route('products.edit', $product) }}"
                           class="btn btn-sm btn-outline-secondary">
                            Edit
                        </a>

                        <a href="{{ route('products.stock-ledger', $product) }}"
                           class="btn btn-sm btn-outline-primary">
                            Stock Ledger
                        </a>
                    </td>

                </tr>

            @endforeach

            </tbody>

        </table>

    </div>

</div>

@endsection
