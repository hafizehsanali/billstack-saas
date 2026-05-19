@extends('layouts.app')

@section('content')

<div class="card">

    <div class="card-header">

        <h3 class="card-title">
            Create Invoice
        </h3>

    </div>

    <div class="card-body">

        <form method="POST"
              action="{{ route('invoices.store') }}">

            @csrf

            <!-- Customer -->

            <div class="mb-4">

                <label class="form-label">
                    Customer
                </label>

                <select name="customer_id"
                        class="form-select">

                    <option value="">
                        Walk-in Customer
                    </option>

                    @foreach($customers as $customer)

                        <option value="{{ $customer->id }}">
                            {{ $customer->name }}
                        </option>

                    @endforeach

                </select>

            </div>

            <!-- Products -->

            <div class="table-responsive">

                <table class="table table-bordered">

                    <thead>

                    <tr>
                        <th>Product</th>
                        <th width="150">Quantity</th>
                    </tr>

                    </thead>

                    <tbody>

                    @foreach($products as $product)

                        <tr>

                            <td>

                                <div class="form-check">

                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="products[]"
                                           value="{{ $product->id }}">

                                    <label class="form-check-label">

                                        {{ $product->name }}

                                        —
                                        Rs. {{ $product->selling_price }}

                                        —
                                        Stock:
                                        {{ $product->stock_quantity }}

                                    </label>

                                </div>

                            </td>

                            <td>

                                <input type="number"
                                       name="quantities[]"
                                       value="1"
                                       min="1"
                                       class="form-control">

                            </td>

                        </tr>

                    @endforeach

                    </tbody>

                </table>

            </div>

            <button class="btn btn-primary mt-3">
                Generate Invoice
            </button>

        </form>

    </div>

</div>

@endsection