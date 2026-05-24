@extends('layouts.app')

@section('content')

<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Create Purchase</h3>

        <a href="{{ route('purchases.index') }}"
           class="btn btn-secondary">
            Back
        </a>
    </div>

    <form action="{{ route('purchases.store') }}"
          method="POST">

        @csrf

        <div class="card mb-3">
            <div class="card-body">

                <div class="row">

                    <div class="col-md-4 mb-3">
                        <label class="form-label">
                            Purchase No
                        </label>

                        <input type="text"
                               name="purchase_no"
                               class="form-control"
                               value="PUR-{{ date('YmdHis') }}"
                               required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">
                            Purchase Date
                        </label>

                        <input type="date"
                               name="purchase_date"
                               class="form-control"
                               value="{{ date('Y-m-d') }}"
                               required>
                    </div>

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

                </div>

            </div>
        </div>

        {{-- Purchase Items --}}
        <div class="card mb-3">

            <div class="card-header d-flex justify-content-between align-items-center">

                <h5 class="mb-0">
                    Purchase Items
                </h5>

                <button type="button"
                        class="btn btn-sm btn-primary"
                        onclick="addRow()">

                    Add Item
                </button>

            </div>

            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-bordered"
                           id="purchaseTable">

                        <thead>
                            <tr>
                                <th width="35%">Product</th>
                                <th width="15%">Qty</th>
                                <th width="20%">Purchase Price</th>
                                <th width="20%">Line Total</th>
                                <th width="10%">Action</th>
                            </tr>
                        </thead>

                        <tbody id="purchaseBody">

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

        {{-- Totals --}}
        <div class="card">

            <div class="card-body">

                <div class="row">

                    <div class="col-md-3 mb-3">
                        <label class="form-label">
                            Subtotal
                        </label>

                        <input type="number"
                               step="0.01"
                               name="subtotal"
                               id="subtotal"
                               class="form-control"
                               readonly>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">
                            Extra Expense
                        </label>

                        <input type="number"
                               step="0.01"
                               name="extra_expense"
                               id="extra_expense"
                               class="form-control"
                               value="0">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">
                            Discount
                        </label>

                        <input type="number"
                               step="0.01"
                               name="discount"
                               id="discount"
                               class="form-control"
                               value="0">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">
                            Total
                        </label>

                        <input type="number"
                               step="0.01"
                               name="total"
                               id="total"
                               class="form-control"
                               readonly>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Paid Amount
                        </label>

                        <input type="number"
                               step="0.01"
                               name="paid_amount"
                               id="paid_amount"
                               class="form-control"
                               value="0">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Remaining Amount
                        </label>

                        <input type="number"
                               step="0.01"
                               id="remaining_amount"
                               class="form-control bg-warning"
                               readonly>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">
                            Notes
                        </label>

                        <textarea name="notes"
                                  rows="3"
                                  class="form-control"></textarea>
                    </div>

                </div>

                <button type="submit"
                        class="btn btn-success">

                    Save Purchase
                </button>

            </div>

        </div>

    </form>

</div>

<script>

    let rowIndex = 0;

    function addRow()
    {
        let html = `
            <tr>

                <td>
                    <select name="products[${rowIndex}][product_id]"
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
                           name="products[${rowIndex}][quantity]"
                           class="form-control quantity"
                           min="1"
                           value="1"
                           onkeyup="calculateTotals()"
                           onchange="calculateTotals()"
                           required>
                </td>

                <td>
                    <input type="number"
                           step="0.01"
                           name="products[${rowIndex}][purchase_price]"
                           class="form-control price"
                           min="0"
                           value="0"
                           onkeyup="calculateTotals()"
                           onchange="calculateTotals()"
                           required>
                </td>

                <td>
                    <input type="number"
                           class="form-control line_total"
                           readonly>
                </td>

                <td>
                    <button type="button"
                            class="btn btn-danger btn-sm"
                            onclick="removeRow(this)">

                        Remove
                    </button>
                </td>

            </tr>
        `;

        document.getElementById('purchaseBody')
            .insertAdjacentHTML('beforeend', html);

        rowIndex++;

        calculateTotals();
    }

    function removeRow(button)
    {
        button.closest('tr').remove();

        calculateTotals();
    }

    function calculateTotals()
    {
        let subtotal = 0;

        let rows = document.querySelectorAll('#purchaseBody tr');

        rows.forEach(function(row) {

            let qty = parseFloat(
                row.querySelector('.quantity').value
            ) || 0;

            let price = parseFloat(
                row.querySelector('.price').value
            ) || 0;

            let lineTotal = qty * price;

            row.querySelector('.line_total').value =
                lineTotal.toFixed(2);

            subtotal += lineTotal;
        });

        let extraExpense = parseFloat(
            document.getElementById('extra_expense').value
        ) || 0;

        let discount = parseFloat(
            document.getElementById('discount').value
        ) || 0;

        let paidAmount = parseFloat(
            document.getElementById('paid_amount').value
        ) || 0;

        let total =
            (subtotal + extraExpense) - discount;

        let remainingAmount =
            total - paidAmount;

        document.getElementById('subtotal').value =
            subtotal.toFixed(2);

        document.getElementById('total').value =
            total.toFixed(2);

        document.getElementById('remaining_amount').value =
            remainingAmount.toFixed(2);
    }

    document.addEventListener('input', function() {
        calculateTotals();
    });

    // Default first row
    addRow();

</script>

@endsection