@extends('layouts.app')

@section('content')

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Create Product</h3>

        <a href="{{ route('products.index') }}"
           class="btn btn-secondary ms-auto">
            Back
        </a>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route('products.store') }}">
            @csrf

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Category <span class="text-danger">*</span></label>
                    <select name="category_id"
                            class="form-select @error('category_id') is-invalid @enderror"
                            required>
                        <option value="">Select Category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Product Name <span class="text-danger">*</span></label>
                    <input type="text"
                           name="name"
                           value="{{ old('name') }}"
                           class="form-control @error('name') is-invalid @enderror"
                           maxlength="255"
                           required>
                    @error('name')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">SKU <span class="text-danger">*</span></label>
                    <input type="text"
                           name="sku"
                           value="{{ old('sku') }}"
                           class="form-control @error('sku') is-invalid @enderror"
                           maxlength="100"
                           required>
                    @error('sku')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Barcode</label>
                    <input type="text"
                           name="barcode"
                           value="{{ old('barcode') }}"
                           class="form-control @error('barcode') is-invalid @enderror"
                           maxlength="100">
                    @error('barcode')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Purchase Price <span class="text-danger">*</span></label>
                    <input type="number"
                           step="0.01"
                           min="0"
                           name="purchase_price"
                           value="{{ old('purchase_price') }}"
                           class="form-control @error('purchase_price') is-invalid @enderror"
                           required>
                    @error('purchase_price')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Selling Price <span class="text-danger">*</span></label>
                    <input type="number"
                           step="0.01"
                           min="0"
                           name="selling_price"
                           value="{{ old('selling_price') }}"
                           class="form-control @error('selling_price') is-invalid @enderror"
                           required>
                    @error('selling_price')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Opening Stock Quantity <span class="text-danger">*</span></label>
                    <input type="number"
                           name="stock_quantity"
                           min="0"
                           value="{{ old('stock_quantity', 0) }}"
                           class="form-control @error('stock_quantity') is-invalid @enderror"
                           required>
                    @error('stock_quantity')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Low Stock Alert <span class="text-danger">*</span></label>
                    <input type="number"
                           name="low_stock_alert"
                           min="0"
                           value="{{ old('low_stock_alert', 5) }}"
                           class="form-control @error('low_stock_alert') is-invalid @enderror"
                           required>
                    @error('low_stock_alert')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
            </div>

            <button class="btn btn-primary">
                Save Product
            </button>
        </form>
    </div>
</div>

@endsection
