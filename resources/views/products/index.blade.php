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
                    <th>Price</th>
                    <th>Stock</th>
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

                    <td>
                        {{ $product->selling_price }}
                    </td>

                    <td>
                        {{ $product->stock_quantity }}
                    </td>

                </tr>

            @endforeach

            </tbody>

        </table>

    </div>

</div>

@endsection