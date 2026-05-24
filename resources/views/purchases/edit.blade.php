@extends('layouts.app')

@section('content')

<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-3">

        <h3>
            Edit Purchase
        </h3>

        <a href="{{ route('purchases.index') }}"
           class="btn btn-secondary">

            Back
        </a>

    </div>

    @if ($errors->any())

        <div class="alert alert-danger">

            <ul class="mb-0">

                @foreach ($errors->all() as $error)

                    <li>{{ $error }}</li>

                @endforeach

            </ul>

        </div>

    @endif

    <form action="{{ route('purchases.update', $purchase) }}"
          method="POST">

        @csrf

        @method('PUT')

        <div class="card mb-3">

            <div class="card-body">

                <div class="row">

                    {{-- Supplier --}}
                    <div class="col-md-4 mb-3">

                        <label class="form-label">
                            Supplier
                        </label>

                        <select name="supplier_id"
                                class="form-control"
                                required>

                            <option value="">
                                Select Supplier
                            </option>

                            @foreach($suppliers as $supplier)

                                <option value="{{ $supplier->id }}"
                                    {{ $purchase->supplier_id == $supplier->id ? 'selected' : '' }}>

                                    {{ $supplier->name }}

                                </option>

                            @endforeach

                        </select>

                    </div>

                    {{-- Purchase Date --}}
                    <div class="col-md-4 mb-3">

                        <label class="form-label">
                            Purchase Date
                        </label>

                        <input type="date"
                               name="purchase_date"
                               class="form-control"
                               value="{{ old('purchase_date', $purchase->purchase_date->format('Y-m-d')) }}"
                               required>

                    </div>

                    {{-- Status --}}
                    <div class="col-md-4 mb-3">

                        <label class="form-label">
                            Status
                        </label>

                        <select name="status"
                                class="form-control"
                                required>

                            <option value="paid"
                                {{ $purchase->status == 'paid' ? 'selected' : '' }}>
                                Paid
                            </option>

                            <option value="partial"
                                {{ $purchase->status == 'partial' ? 'selected' : '' }}>
                                Partial
                            </option>

                            <option value="unpaid"
                                {{ $purchase->status == 'unpaid' ? 'selected' : '' }}>
                                Unpaid
                            </option>

                        </select>

                    </div>

                </div>

            </div>

        </div>

        {{-- Purchase Items --}}
        <div class="card mb-3">

            <div class="card-header d-flex justify-content-between align-items-center">

                <span>
                    Purchase Items
                </span>

                <button type="button"
                        class="btn btn-sm btn-primary"
                        id="add-row">

                    Add Item
                </button>

            </div>

            <div class="card-body table-responsive">

                <table class="table table-bordered"
                       id="purchase-table">

                    <thead>

                        <tr>

                            <th width="35%">
                                Product
                            </th>

                            <th width="15%">
                                Quantity
                            </th>

                            <th width="20%">
                                Purchase Price
                            </th>

                            <th width="20%">
                                Total
                            </th>

                            <th width="10%">
                                Action
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        @foreach($purchase->items as $index => $item)

                            <tr>

                                <td>

                                    <select name="items[{{ $index }}][product_id]"
                                            class="form-control"
                                            required>

                                        <option value="">
                                            Select Product
                                        </option>

                                        @foreach($products as $product)

                                            <option value="{{ $product->id }}"
                                                {{ $item->product_id == $product->id ? 'selected' : '' }}>

                                                {{ $product->name }}

                                            </option>

                                        @endforeach

                                    </select>

                                </td>

                                <td>

                                    <input type="number"
                                           step="0.01"
                                           min="1"
                                           name="items[{{ $index }}][quantity]"
                                           class="form-control quantity"
                                           value="{{ $item->quantity }}"
                                           required>

                                </td>

                                <td>

                                    <input type="number"
                                           step="0.01"
                                           min="0"
                                           name="items[{{ $index }}][purchase_price]"
                                           class="form-control price"
                                           value="{{ $item->purchase_price }}"
                                           required>

                                </td>

                                <td>

                                    <input type="text"
                                           class="form-control line-total"
                                           value="{{ number_format($item->line_total, 2) }}"
                                           readonly>

                                </td>

                                <td>

                                    <button type="button"
                                            class="btn btn-danger btn-sm remove-row">

                                        X

                                    </button>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        </div>

        {{-- Totals --}}
        <div class="card mb-3">

            <div class="card-body">

                <div class="row">

                    {{-- Subtotal --}}
                    <div class="col-md-3 mb-3">

                        <label class="form-label">
                            Subtotal
                        </label>

                        <input type="number"
                               step="0.01"
                               name="subtotal"
                               id="subtotal"
                               class="form-control"
                               value="{{ $purchase->subtotal }}"
                               readonly>

                    </div>

                    {{-- Extra Expense --}}
                    <div class="col-md-3 mb-3">

                        <label class="form-label">
                            Extra Expense
                        </label>

                        <input type="number"
                               step="0.01"
                               min="0"
                               name="extra_expense"
                               id="extra_expense"
                               class="form-control"
                               value="{{ $purchase->extra_expense }}">

                    </div>

                    {{-- Discount --}}
                    <div class="col-md-3 mb-3">

                        <label class="form-label">
                            Discount
                        </label>

                        <input type="number"
                               step="0.01"
                               min="0"
                               name="discount"
                               id="discount"
                               class="form-control"
                               value="{{ $purchase->discount }}">

                    </div>

                    {{-- Total --}}
                    <div class="col-md-3 mb-3">

                        <label class="form-label">
                            Total
                        </label>

                        <input type="number"
                               step="0.01"
                               name="total"
                               id="total"
                               class="form-control"
                               value="{{ $purchase->total }}"
                               readonly>

                    </div>

                    {{-- Paid Amount --}}
                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Paid Amount
                        </label>

                        <input type="number"
                               step="0.01"
                               min="0"
                               name="paid_amount"
                               id="paid_amount"
                               class="form-control"
                               value="{{ $purchase->paid_amount }}">

                    </div>

                    {{-- Remaining Amount --}}
                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Remaining Amount
                        </label>

                        <input type="number"
                               step="0.01"
                               name="remaining_amount"
                               id="remaining_amount"
                               class="form-control bg-light fw-bold text-danger"
                               value="{{ $purchase->remaining_amount }}"
                               readonly>

                    </div>

                    {{-- Notes --}}
                    <div class="col-md-12 mb-3">

                        <label class="form-label">
                            Notes
                        </label>

                        <textarea name="notes"
                                  rows="3"
                                  class="form-control">{{ $purchase->notes }}</textarea>

                    </div>

                </div>

            </div>

        </div>

        <button type="submit"
                class="btn btn-success">

            Update Purchase

        </button>

    </form>

</div>

@endsection

@section('scripts')

<script>

    let rowIndex = {{ count($purchase->items) }};

    document
        .getElementById('add-row')
        .addEventListener('click', function () {

            let row = `
                <tr>

                    <td>

                        <select name="items[${rowIndex}][product_id]"
                                class="form-control"
                                required>

                            <option value="">
                                Select Product
                            </option>

                            @foreach($products as $product)

                                <option value="{{ $product->id }}">

                                    {{ $product->name }}

                                </option>

                            @endforeach

                        </select>

                    </td>

                    <td>

                        <input type="number"
                               step="0.01"
                               min="1"
                               name="items[${rowIndex}][quantity]"
                               class="form-control quantity"
                               required>

                    </td>

                    <td>

                        <input type="number"
                               step="0.01"
                               min="0"
                               name="items[${rowIndex}][purchase_price]"
                               class="form-control price"
                               required>

                    </td>

                    <td>

                        <input type="text"
                               class="form-control line-total"
                               readonly>

                    </td>

                    <td>

                        <button type="button"
                                class="btn btn-danger btn-sm remove-row">

                            X

                        </button>

                    </td>

                </tr>
            `;

            document
                .querySelector('#purchase-table tbody')
                .insertAdjacentHTML('beforeend', row);

            rowIndex++;
        });

    document.addEventListener('input', function (e) {

        if (
            e.target.classList.contains('quantity')
            || e.target.classList.contains('price')
        ) {

            calculateTotals();
        }
    });

    document.addEventListener('click', function (e) {

        if (e.target.classList.contains('remove-row')) {

            e.target.closest('tr').remove();

            calculateTotals();
        }
    });

    document
        .getElementById('discount')
        .addEventListener('input', calculateTotals);

    document
        .getElementById('extra_expense')
        .addEventListener('input', calculateTotals);

    document
        .getElementById('paid_amount')
        .addEventListener('input', calculateTotals);

    function calculateTotals()
    {
        let subtotal = 0;

        document.querySelectorAll('#purchase-table tbody tr')
            .forEach(function (row) {

                let qty = parseFloat(
                    row.querySelector('.quantity')?.value || 0
                );

                let price = parseFloat(
                    row.querySelector('.price')?.value || 0
                );

                let lineTotal = qty * price;

                row.querySelector('.line-total').value =
                    lineTotal.toFixed(2);

                subtotal += lineTotal;
            });

        let discount = parseFloat(
            document.getElementById('discount').value || 0
        );

        let extraExpense = parseFloat(
            document.getElementById('extra_expense').value || 0
        );

        let total =
            subtotal
            + extraExpense
            - discount;

        let paidAmount = parseFloat(
            document.getElementById('paid_amount').value || 0
        );

        let remainingAmount =
            total - paidAmount;

        document.getElementById('subtotal').value =
            subtotal.toFixed(2);

        document.getElementById('total').value =
            total.toFixed(2);

        document.getElementById('remaining_amount').value =
            remainingAmount.toFixed(2);
    }

    calculateTotals();

</script>

@endsection