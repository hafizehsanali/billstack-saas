@extends('layouts.app')

@section('content')

<div class="card">

    <div class="card-header">
        <h3 class="card-title">
            Create Product
        </h3>
    </div>

    <div class="card-body">

        <form method="POST"
              action="{{ route('products.store') }}">

            @csrf

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

                            <option value="{{ $category->id }}">
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
                           class="form-control">

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        SKU
                    </label>

                    <input type="text"
                           name="sku"
                           class="form-control">

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Barcode
                    </label>

                    <input type="text"
                           name="barcode"
                           class="form-control">

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Purchase Price
                    </label>

                    <input type="number"
                           step="0.01"
                           name="purchase_price"
                           class="form-control">

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Selling Price
                    </label>

                    <input type="number"
                           step="0.01"
                           name="selling_price"
                           class="form-control">

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Stock Quantity
                    </label>

                    <input type="number"
                           name="stock_quantity"
                           class="form-control">

                </div>

                <div class="col-md-6 mb-3">

                    <label class="form-label">
                        Low Stock Alert
                    </label>

                    <input type="number"
                           name="low_stock_alert"
                           value="5"
                           class="form-control">

                </div>

            </div>

            <button class="btn btn-primary">
                Save Product
            </button>

        </form>

    </div>

</div>

@endsection