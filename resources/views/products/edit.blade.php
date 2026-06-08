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
                            class="form-select @error('category_id') is-invalid @enderror"
                            required>

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

                    @error('category_id')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Product Name
                    </label>

                    <input type="text"
                           name="name"
                           class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $product->name) }}"
                           maxlength="255"
                           required>

                    @error('name')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        SKU
                    </label>

                    <input type="text"
                           name="sku"
                           class="form-control @error('sku') is-invalid @enderror"
                           value="{{ old('sku', $product->sku) }}"
                           maxlength="100"
                           required>

                    @error('sku')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Barcode
                    </label>

                    <input type="text"
                           name="barcode"
                           class="form-control @error('barcode') is-invalid @enderror"
                           value="{{ old('barcode', $product->barcode) }}"
                           maxlength="100">

                    @feature('pro.barcode')
                        <small class="text-muted">Barcode scanner workflows are enabled for this plan.</small>
                    @else
                        <small class="text-muted">Manual barcode entry is available. Scanner workflows require a paid feature.</small>
                    @endfeature

                    @error('barcode')
                        <small class="text-danger d-block">{{ $message }}</small>
                    @enderror

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Purchase Price
                    </label>

                    <input type="number"
                           step="0.01"
                           min="0"
                           name="purchase_price"
                           class="form-control @error('purchase_price') is-invalid @enderror"
                           value="{{ old('purchase_price', $product->purchase_price) }}"
                           required>

                    @error('purchase_price')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Selling Price
                    </label>

                    <input type="number"
                           step="0.01"
                           min="0"
                           name="selling_price"
                           class="form-control @error('selling_price') is-invalid @enderror"
                           value="{{ old('selling_price', $product->selling_price) }}"
                           required>

                    @error('selling_price')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Current Stock Quantity
                    </label>

                    <input type="number"
                           name="stock_quantity"
                           min="0"
                           class="form-control @error('stock_quantity') is-invalid @enderror"
                           value="{{ old('stock_quantity', $product->stock_quantity) }}"
                           required>

                    <small class="text-muted">
                        Stock changes are recorded in the product stock ledger.
                    </small>

                    @error('stock_quantity')
                        <small class="text-danger d-block">{{ $message }}</small>
                    @enderror

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Low Stock Alert
                    </label>

                    <input type="number"
                           name="low_stock_alert"
                           min="0"
                           class="form-control @error('low_stock_alert') is-invalid @enderror"
                           value="{{ old('low_stock_alert', $product->low_stock_alert) }}"
                           required>

                    @error('low_stock_alert')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror

                </div>

            </div>

            <button class="btn btn-primary">
                Update Product
            </button>

        </form>

    </div>

</div>

@endsection
