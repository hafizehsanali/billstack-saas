@extends('layouts.app')

@section('content')

<div class="card">

    <div class="card-header">
        <h3 class="card-title">
            Edit Product
        </h3>

        <a href="{{ route('products.index') }}" class="btn btn-secondary ms-auto">
            Back
        </a>
    </div>

    <div class="card-body">

        <form method="POST"
              action="{{ route('products.update', $product) }}">

            @csrf
            @method('PUT')

            <div class="row">

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Category
                    </label>

                    <select name="category_id"
                            class="form-select">

                        <option value="">
                            Select Category
                        </option>

                        @foreach($categories as $category)

                            <option value="{{ $category->id }}"
                                    @selected(old('category_id', $product->category_id) == $category->id)>
                                {{ $category->name }}
                            </option>

                        @endforeach

                    </select>

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Product Name
                    </label>

                    <input type="text"
                           name="name"
                           class="form-control"
                           value="{{ old('name', $product->name) }}">

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        SKU
                    </label>

                    <input type="text"
                           name="sku"
                           class="form-control"
                           value="{{ old('sku', $product->sku) }}">

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Barcode
                    </label>

                    <input type="text"
                           name="barcode"
                           class="form-control"
                           value="{{ old('barcode', $product->barcode) }}">

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Purchase Price
                    </label>

                    <input type="number"
                           step="0.01"
                           name="purchase_price"
                           class="form-control"
                           value="{{ old('purchase_price', $product->purchase_price) }}">

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Selling Price
                    </label>

                    <input type="number"
                           step="0.01"
                           name="selling_price"
                           class="form-control"
                           value="{{ old('selling_price', $product->selling_price) }}">

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Stock Quantity
                    </label>

                    <input type="number"
                           name="stock_quantity"
                           class="form-control"
                           value="{{ old('stock_quantity', $product->stock_quantity) }}">

                    <small class="text-muted">
                        Stock changes are recorded in the product stock ledger.
                    </small>

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Low Stock Alert
                    </label>

                    <input type="number"
                           name="low_stock_alert"
                           class="form-control"
                           value="{{ old('low_stock_alert', $product->low_stock_alert) }}">

                </div>

            </div>

            <button class="btn btn-primary">
                Update Product
            </button>

        </form>

    </div>

</div>

@endsection
