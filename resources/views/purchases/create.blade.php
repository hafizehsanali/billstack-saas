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
                               value="{{ old('purchase_no', 'PUR-'.date('YmdHis')) }}"
                               required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">
                            Purchase Date
                        </label>

                        <input type="date"
                               name="purchase_date"
                               class="form-control"
                               value="{{ old('purchase_date', date('Y-m-d')) }}"
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

                                <option value="{{ $supplier->id }}"
                                        @selected((string) old('supplier_id') === (string) $supplier->id)>
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
                               value="{{ old('subtotal') }}"
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
                               value="{{ old('extra_expense', 0) }}">
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
                               value="{{ old('discount', 0) }}">
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
                               value="{{ old('total') }}"
                               readonly>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Paid to Supplier
                        </label>

                        <input type="number"
                               step="0.01"
                               name="paid_amount"
                               id="paid_amount"
                               class="form-control @error('paid_amount') is-invalid @enderror"
                               value="{{ old('paid_amount', 0) }}">
                        @error('paid_amount')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Still Payable to Supplier
                        </label>

                        <input type="number"
                               step="0.01"
                               name="remaining_amount"
                               id="remaining_amount"
                               class="form-control bg-warning"
                               value="{{ old('remaining_amount') }}"
                               readonly>
                    </div>

                    <div class="col-md-3 mb-3 purchase-payment-field d-none">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select">
                            <option value="cash" @selected(old('payment_method', 'cash') === 'cash')>Cash</option>
                            <option value="bank" @selected(old('payment_method') === 'bank')>Bank</option>
                            <option value="card" @selected(old('payment_method') === 'card')>Card</option>
                            <option value="jazzcash" @selected(old('payment_method') === 'jazzcash')>JazzCash</option>
                            <option value="easypaisa" @selected(old('payment_method') === 'easypaisa')>EasyPaisa</option>
                            <option value="cheque" @selected(old('payment_method') === 'cheque')>Cheque</option>
                        </select>
                    </div>

                    <div class="col-md-3 mb-3 purchase-payment-field d-none">
                        <label class="form-label">Payment Date</label>
                        <input type="datetime-local"
                               name="payment_date"
                               class="form-control"
                               value="{{ old('payment_date', now()->format('Y-m-d\TH:i')) }}">
                    </div>

                    <div class="col-md-3 mb-3 purchase-payment-field d-none">
                        <label class="form-label">Reference No</label>
                        <input type="text"
                               name="reference_no"
                               class="form-control"
                               value="{{ old('reference_no') }}"
                               placeholder="Cheque / Txn / Ref">
                    </div>

                    <div class="col-md-3 mb-3 purchase-payment-field d-none">
                        <label class="form-label">Payment Notes</label>
                        <input type="text"
                               name="payment_notes"
                               class="form-control"
                               value="{{ old('payment_notes') }}"
                               placeholder="Optional payment note">
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">
                            Notes
                        </label>

                        <textarea name="notes"
                                  rows="3"
                                  class="form-control">{{ old('notes') }}</textarea>
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

    const oldProducts = Object.values(@json(old('products', [])));
    let rowIndex = 0;

    function addRow(item = null)
    {
        const selectedProductId = item?.product_id ? String(item.product_id) : '';
        const quantity = Number(item?.quantity ?? 1) || 1;
        const purchasePrice = Number(item?.purchase_price ?? 0) || 0;

        let html = `
            <tr>

                <td>
                    <select name="products[${rowIndex}][product_id]"
                            class="form-select product-select"
                            onchange="setProductPrice(this)"
                            required>

                        <option value="">
                            Select Product
                        </option>

                        @foreach($products as $product)

                            <option value="{{ $product->id }}"
                                    data-price="{{ $product->purchase_price }}">
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
                           value="${quantity}"
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
                           value="${purchasePrice}"
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

        const row = document.querySelector('#purchaseBody tr:last-child');
        const productSelect = row.querySelector('.product-select');

        if (selectedProductId) {
            productSelect.value = selectedProductId;
            row.querySelector('.price').value = parseFloat(purchasePrice || 0).toFixed(2);
        }

        rowIndex++;

        calculateTotals();
    }

    function setProductPrice(select)
    {
        let row = select.closest('tr');
        let option = select.options[select.selectedIndex];
        let price = parseFloat(option.dataset.price) || 0;

        row.querySelector('.price').value = price.toFixed(2);

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

        let total = (subtotal + extraExpense) - discount;

        if (total < 0) {
            total = 0;
        }

        let remainingAmount = total - paidAmount;

        if (remainingAmount < 0) {
            remainingAmount = 0;
        }

        document.getElementById('subtotal').value =
            subtotal.toFixed(2);

        document.getElementById('total').value =
            total.toFixed(2);

        document.getElementById('remaining_amount').value =
            remainingAmount.toFixed(2);

        document.querySelectorAll('.purchase-payment-field').forEach(function(field) {
            field.classList.toggle('d-none', paidAmount <= 0);
        });
    }

    document.addEventListener('input', function() {
        calculateTotals();
    });

    if (oldProducts.length > 0) {
        oldProducts.forEach((item) => addRow(item));
    } else {
        addRow();
    }

</script>

@endsection
