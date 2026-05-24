@extends('layouts.app')

@section('content')

<div class="container">

    <div class="mb-4">

        <h2 class="mb-0">Create Purchase</h2>

        <small class="text-muted">
            Add supplier purchase and update inventory
        </small>

    </div>

    <form action="{{ route('purchases.store') }}"
          method="POST">

        @csrf

        <div class="card border-0 shadow-sm mb-4">

            <div class="card-body">

                <div class="row">

                    <div class="col-md-4 mb-3">

                        <label class="form-label">
                            Supplier
                        </label>

                        <select name="supplier_id"
                                class="form-select"
                                required>

                            <option value="">
                                Select Supplier
                            </option>

                            @foreach($suppliers as $supplier)

                                <option value="{{ $supplier->id }}">

                                    {{ $supplier->name }}

                                </option>

                            @endforeach

                        </select>

                    </div>

                    <div class="col-md-4 mb-3">

                        <label class="form-label">
                            Purchase Date
                        </label>

                        <input type="date"
                               name="purchase_date"
                               class="form-control"
                               value="{{ now()->format('Y-m-d') }}"
                               required>

                    </div>

                </div>

            </div>

        </div>

        {{-- Product Table --}}

        <div class="card border-0 shadow-sm mb-4">

            <div class="card-header bg-white">

                <div class="d-flex justify-content-between align-items-center">

                    <h5 class="mb-0">
                        Products
                    </h5>

                    <button type="button"
                            class="btn btn-sm btn-primary"
                            id="addRow">

                        Add Product

                    </button>

                </div>

            </div>

            <div class="card-body p-0">

                <div class="table-responsive">

                    <table class="table align-middle mb-0"
                           id="productTable">

                        <thead class="table-light">

                        <tr>
                            <th>Product</th>
                            <th width="150">Quantity</th>
                            <th width="180">Purchase Price</th>
                            <th width="180">Total</th>
                            <th width="80"></th>
                        </tr>

                        </thead>

                        <tbody>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

        {{-- Totals --}}

        <div class="card border-0 shadow-sm">

            <div class="card-body">

                <div class="row">

                    <div class="col-md-3 mb-3">

                        <label class="form-label">
                            Discount
                        </label>

                        <input type="number"
                               step="0.01"
                               min="0"
                               name="discount"
                               value="0"
                               class="form-control total-input">

                    </div>

                    <div class="col-md-3 mb-3">

                        <label class="form-label">
                            Extra Expense
                        </label>

                        <input type="number"
                               step="0.01"
                               min="0"
                               name="extra_expense"
                               value="0"
                               class="form-control total-input">

                    </div>

                    <div class="col-md-3 mb-3">

                        <label class="form-label">
                            Paid Amount
                        </label>

                        <input type="number"
                               step="0.01"
                               min="0"
                               name="paid_amount"
                               value="0"
                               class="form-control">

                    </div>

                    <div class="col-md-3 mb-3">

                        <label class="form-label">
                            Grand Total
                        </label>

                        <input type="text"
                               id="grandTotal"
                               class="form-control fw-bold"
                               readonly>

                    </div>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Note
                    </label>

                    <textarea name="note"
                              rows="3"
                              class="form-control"></textarea>

                </div>

                <button class="btn btn-success">

                    Save Purchase

                </button>

            </div>

        </div>

    </form>

</div>

{{-- Template Row --}}

<template id="rowTemplate">

<tr>

    <td>

        <select name="products[INDEX][product_id]"
                class="form-select"
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
               min="1"
               name="products[INDEX][quantity]"
               class="form-control quantity"
               value="1"
               required>

    </td>

    <td>

        <input type="number"
               min="0"
               step="0.01"
               name="products[INDEX][purchase_price]"
               class="form-control price"
               value="0"
               required>

    </td>

    <td>

        <input type="text"
               class="form-control itemTotal"
               readonly>

    </td>

    <td>

        <button type="button"
                class="btn btn-sm btn-danger removeRow">

            X

        </button>

    </td>

</tr>

</template>

<script>

let rowIndex = 0;

function calculateTotals()
{
    let subtotal = 0;

    document.querySelectorAll('#productTable tbody tr')
        .forEach(row => {

            let qty = parseFloat(
                row.querySelector('.quantity').value
            ) || 0;

            let price = parseFloat(
                row.querySelector('.price').value
            ) || 0;

            let total = qty * price;

            row.querySelector('.itemTotal').value =
                total.toFixed(2);

            subtotal += total;
        });

    let discount = parseFloat(
        document.querySelector('[name="discount"]').value
    ) || 0;

    let extraExpense = parseFloat(
        document.querySelector('[name="extra_expense"]').value
    ) || 0;

    let grandTotal = (
        subtotal - discount + extraExpense
    );

    document.getElementById('grandTotal').value =
        grandTotal.toFixed(2);
}

document.getElementById('addRow')
    .addEventListener('click', function () {

        let template = document
            .getElementById('rowTemplate')
            .innerHTML;

        template = template.replaceAll(
            'INDEX',
            rowIndex++
        );

        document.querySelector('#productTable tbody')
            .insertAdjacentHTML('beforeend', template);

        calculateTotals();
    });

document.addEventListener('input', function (e) {

    if (
        e.target.classList.contains('quantity') ||
        e.target.classList.contains('price') ||
        e.target.classList.contains('total-input')
    ) {
        calculateTotals();
    }
});

document.addEventListener('click', function (e) {

    if (e.target.classList.contains('removeRow')) {

        e.target.closest('tr').remove();

        calculateTotals();
    }
});

document.getElementById('addRow').click();

</script>

@endsection