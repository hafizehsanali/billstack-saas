@extends('layouts.app')

@section('content')

<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Create Invoice</h3>

        <a href="{{ route('invoices.index') }}" class="btn btn-secondary">
            Back
        </a>
    </div>

    <form action="{{ route('invoices.store') }}" method="POST">
        @csrf

        {{-- Customer & Invoice Info --}}
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">Customer & Invoice Info</h5>
            </div>

            <div class="card-body">
                <div class="row">

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Invoice No</label>
                        <input type="text"
                               name="invoice_no"
                               class="form-control"
                               value="{{ old('invoice_no', 'INV-'.date('YmdHis')) }}"
                               readonly>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Sale Date</label>
                        <input type="date"
                               name="sale_date"
                               class="form-control"
                               value="{{ old('sale_date', date('Y-m-d')) }}"
                               required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Customer</label>
                        <select name="customer_id" class="form-select" required>
                            <option value="">Select Customer</option>

                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}"
                                        @selected((string) old('customer_id') === (string) $customer->id)>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                </div>
            </div>
        </div>

        {{-- Invoice Items --}}
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Invoice Items</h5>

                <button type="button"
                        class="btn btn-sm btn-primary"
                        onclick="addRow()">
                    Add Item
                </button>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th width="32%">Product</th>
                                <th width="12%">Stock</th>
                                <th width="12%">Qty</th>
                                <th width="16%">Price</th>
                                <th width="18%">Line Total</th>
                                <th width="10%">Action</th>
                            </tr>
                        </thead>

                        <tbody id="invoiceBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Charges / Discount / Tax --}}
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">Charges, Discount & Tax</h5>
            </div>

            <div class="card-body">
                <div class="row">

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Subtotal</label>
                        <input type="number"
                               step="0.01"
                               name="subtotal"
                               id="subtotal"
                               class="form-control"
                               readonly>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Tax</label>
                        <input type="number"
                               step="0.01"
                               name="tax"
                               id="tax"
                               class="form-control"
                               value="{{ old('tax', 0) }}">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Discount</label>
                        <input type="number"
                               step="0.01"
                               name="discount"
                               id="discount"
                               class="form-control"
                               value="{{ old('discount', 0) }}">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Extra Expense</label>
                        <input type="number"
                               step="0.01"
                               name="extra_expense"
                               id="extra_expense"
                               class="form-control"
                               value="{{ old('extra_expense', 0) }}">
                    </div>

                </div>
            </div>
        </div>
        <div class="alert alert-info d-flex justify-content-between align-items-center">
            <strong>Total Payable Amount:</strong>
            <strong>
                Rs <span id="payment_total_display">0.00</span>
            </strong>
        </div>
        {{-- Payment --}}
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">Payment</h5>
            </div>

            <div class="card-body">
                <div class="row">

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Amount Received from Customer</label>
                        <input type="number"
                               step="0.01"
                               name="paid_amount"
                               id="paid_amount"
                               class="form-control"
                               value="{{ old('paid_amount', 0) }}">
                    </div>

                    <div class="col-md-3 mb-3 payment-field d-none">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method"
                                id="payment_method"
                                class="form-select">
                            <option value="cash">Cash</option>
                            <option value="bank" @selected(old('payment_method') === 'bank')>Bank</option>
                            <option value="card" @selected(old('payment_method') === 'card')>Card</option>
                            <option value="jazzcash" @selected(old('payment_method') === 'jazzcash')>JazzCash</option>
                            <option value="easypaisa" @selected(old('payment_method') === 'easypaisa')>EasyPaisa</option>
                            <option value="cheque" @selected(old('payment_method') === 'cheque')>Cheque</option>
                        </select>
                    </div>

                    <div class="col-md-3 mb-3 payment-field d-none">
                        <label class="form-label">Payment Date</label>
                        <input type="datetime-local"
                               name="payment_date"
                               id="payment_date"
                               class="form-control"
                               value="{{ old('payment_date', now()->format('Y-m-d\TH:i')) }}">
                    </div>

                    <div class="col-md-3 mb-3 payment-field d-none">
                        <label class="form-label">Reference No</label>
                        <input type="text"
                               name="reference_no"
                               class="form-control"
                               value="{{ old('reference_no') }}"
                               placeholder="Cheque / Txn / Ref">
                    </div>

                    <div class="col-md-12 mb-3 payment-field d-none">
                        <label class="form-label">Payment Notes</label>
                        <textarea name="payment_notes"
                                  rows="2"
                                  class="form-control">{{ old('payment_notes') }}</textarea>
                    </div>

                </div>

                <small class="text-muted">
                    Payment details will show only when paid amount is greater than 0.
                </small>
            </div>
        </div>

        {{-- Final Summary --}}
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Final Summary</h5>
            </div>

            <div class="card-body">
                <div class="row">

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Total</label>
                        <input type="number"
                               step="0.01"
                               name="total"
                               id="total"
                               class="form-control"
                               readonly>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Customer Owes Us</label>
                        <input type="number"
                               step="0.01"
                               name="remaining_amount"
                               id="remaining_amount"
                               class="form-control bg-warning"
                               readonly>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Status</label>
                        <input type="text"
                               name="status"
                               id="status"
                               class="form-control"
                               value="unpaid"
                               readonly>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">Invoice Notes</label>
                        <textarea name="notes"
                                  rows="3"
                                  class="form-control">{{ old('notes') }}</textarea>
                    </div>

                </div>

                <button type="submit" class="btn btn-success">
                    Save Invoice
                </button>
            </div>
        </div>

    </form>

</div>

<script>
    const oldProducts = Object.values(@json(old('products', [])));
    let rowIndex = 0;

    function addRow(item = null) {
        const selectedProductId = item?.product_id ? String(item.product_id) : '';
        const quantity = Number(item?.quantity ?? 1) || 1;
        const price = Number(item?.price ?? 0) || 0;

        let html = `
            <tr>
                <td>
                    <select name="products[${rowIndex}][product_id]"
                            class="form-select product-select"
                            onchange="setProductData(this)"
                            required>
                        <option value="">Select Product</option>

                        @foreach($products as $product)
                            <option value="{{ $product->id }}"
                                    data-price="{{ $product->selling_price }}"
                                    data-stock="{{ $product->stock_quantity }}">
                                {{ $product->name }} (Stock: {{ $product->stock_quantity }})
                            </option>
                        @endforeach
                    </select>
                </td>

                <td>
                    <input type="number"
                           class="form-control stock"
                           readonly>
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
                           name="products[${rowIndex}][price]"
                           class="form-control price"
                           min="0"
                           value="${price}"
                           onkeyup="calculateTotals()"
                           onchange="calculateTotals()"
                           required>
                </td>

                <td>
                    <input type="number"
                           step="0.01"
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

        document.getElementById('invoiceBody')
            .insertAdjacentHTML('beforeend', html);

        const row = document.querySelector('#invoiceBody tr:last-child');
        const productSelect = row.querySelector('.product-select');

        if (selectedProductId) {
            productSelect.value = selectedProductId;
            setProductData(productSelect);
            row.querySelector('.quantity').value = quantity;
            row.querySelector('.price').value = parseFloat(price || 0).toFixed(2);
        }

        rowIndex++;
        calculateTotals();
    }

    function setProductData(select) {
        let row = select.closest('tr');
        let option = select.options[select.selectedIndex];

        let price = parseFloat(option.dataset.price) || 0;
        let stock = parseFloat(option.dataset.stock) || 0;

        row.querySelector('.price').value = price.toFixed(2);
        row.querySelector('.stock').value = stock;

        calculateTotals();
    }

    function removeRow(button) {
        button.closest('tr').remove();
        calculateTotals();
    }

    function calculateTotals() {
        let subtotal = 0;

        document.querySelectorAll('#invoiceBody tr').forEach(function(row) {
            let qtyInput = row.querySelector('.quantity');
            let priceInput = row.querySelector('.price');
            let stockInput = row.querySelector('.stock');

            let qty = parseFloat(qtyInput.value) || 0;
            let price = parseFloat(priceInput.value) || 0;
            let stock = parseFloat(stockInput.value) || 0;

            if (stock > 0 && qty > stock) {
                qty = stock;
                qtyInput.value = stock;
            }

            let lineTotal = qty * price;

            row.querySelector('.line_total').value =
                lineTotal.toFixed(2);

            subtotal += lineTotal;
        });

        let tax = parseFloat(document.getElementById('tax').value) || 0;
        let discount = parseFloat(document.getElementById('discount').value) || 0;
        let extraExpense = parseFloat(document.getElementById('extra_expense').value) || 0;
        let paidAmount = parseFloat(document.getElementById('paid_amount').value) || 0;

        let total = (subtotal + tax + extraExpense) - discount;

        if (total < 0) {
            total = 0;
        }

        let remainingAmount = total - paidAmount;

        if (remainingAmount < 0) {
            remainingAmount = 0;
        }

        let status = 'unpaid';

        if (paidAmount >= total && total > 0) {
            status = 'paid';
        } else if (paidAmount > 0) {
            status = 'partial';
        }

        document.getElementById('subtotal').value = subtotal.toFixed(2);
        document.getElementById('total').value = total.toFixed(2);
        document.getElementById('payment_total_display').innerText = total.toFixed(2);
        document.getElementById('remaining_amount').value = remainingAmount.toFixed(2);
        document.getElementById('status').value = status;

        togglePaymentFields(paidAmount);
    }

    function togglePaymentFields(paidAmount) {
        document.querySelectorAll('.payment-field').forEach(function(field) {
            if (paidAmount > 0) {
                field.classList.remove('d-none');
            } else {
                field.classList.add('d-none');
            }
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
