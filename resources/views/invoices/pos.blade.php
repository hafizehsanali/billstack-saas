@extends('layouts.app')

@section('content')

@php
    $productData = $products->map(fn ($product) => [
        'id' => $product->id,
        'name' => $product->name,
        'sku' => $product->sku,
        'barcode' => $product->barcode,
        'price' => (float) $product->selling_price,
        'stock' => (int) $product->stock_quantity,
    ])->values();
@endphp

<form action="{{ route('invoices.store') }}" method="POST" id="posForm">
    @csrf

    <input type="hidden"
           name="invoice_no"
           value="{{ old('invoice_no', 'INV-'.now()->format('YmdHis').'-'.random_int(100, 999)) }}">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-1">POS Billing</h3>
            <div class="text-muted">Fast invoice entry</div>
        </div>

        <a href="{{ route('invoices.index') }}" class="btn btn-secondary">
            Invoices
        </a>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-5">
                            <label class="form-label">Customer</label>
                            <select name="customer_id" class="form-select" required>
                                <option value="">Select customer</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}"
                                            @selected((string) old('customer_id') === (string) $customer->id)>
                                        {{ $customer->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Sale Date</label>
                            <input type="date"
                                   name="sale_date"
                                   class="form-control"
                                   value="{{ old('sale_date', now()->format('Y-m-d')) }}"
                                   required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Search / Barcode</label>
                            <div class="input-group">
                                <input type="text"
                                       id="productSearch"
                                       class="form-control"
                                       placeholder="Scan or type product">
                                <button type="button"
                                        class="btn btn-primary"
                                        onclick="addSearchedProduct()">
                                    Add
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="row g-2 mt-2">
                        <div class="col-md-9">
                            <select id="productPicker" class="form-select">
                                <option value="">Select product</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}">
                                        {{ $product->name }} - Stock: {{ $product->stock_quantity }} - Rs {{ number_format($product->selling_price, 2) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <button type="button"
                                    class="btn btn-outline-primary w-100"
                                    onclick="addPickedProduct()">
                                Add Product
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-end">Stock</th>
                                <th width="120">Qty</th>
                                <th width="150">Price</th>
                                <th class="text-end">Total</th>
                                <th width="80"></th>
                            </tr>
                        </thead>

                        <tbody id="posItems">
                            <tr id="emptyRow">
                                <td colspan="6" class="text-center text-muted py-4">
                                    No products added yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Summary</h3>
                </div>

                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Subtotal</label>
                        <input type="number"
                               id="subtotal"
                               class="form-control"
                               value="0.00"
                               readonly>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Tax</label>
                            <input type="number"
                                   step="0.01"
                                   min="0"
                                   name="tax"
                                   id="tax"
                                   class="form-control"
                                   value="{{ old('tax', 0) }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Discount</label>
                            <input type="number"
                                   step="0.01"
                                   min="0"
                                   name="discount"
                                   id="discount"
                                   class="form-control"
                                   value="{{ old('discount', 0) }}">
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label">Extra Expense</label>
                        <input type="number"
                               step="0.01"
                               min="0"
                               name="extra_expense"
                               id="extra_expense"
                               class="form-control"
                               value="{{ old('extra_expense', 0) }}">
                    </div>

                    <div class="hr-text">Payment</div>

                    <div class="mb-3">
                        <label class="form-label">Amount Received from Customer</label>
                        <input type="number"
                               step="0.01"
                               min="0"
                               name="paid_amount"
                               id="paid_amount"
                               class="form-control"
                               value="{{ old('paid_amount', 0) }}">
                    </div>

                    <div id="paymentDetails" class="d-none">
                        <div class="mb-3">
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

                        <div class="mb-3">
                            <label class="form-label">Payment Date</label>
                            <input type="datetime-local"
                                   name="payment_date"
                                   class="form-control"
                                   value="{{ old('payment_date', now()->format('Y-m-d\TH:i')) }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Reference No</label>
                            <input type="text"
                                   name="reference_no"
                                   class="form-control"
                                   value="{{ old('reference_no') }}">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes"
                                  class="form-control"
                                  rows="2">{{ old('notes') }}</textarea>
                    </div>

                    <div class="d-flex justify-content-between fs-3 fw-bold mb-2">
                        <span>Total</span>
                        <span>Rs <span id="totalDisplay">0.00</span></span>
                    </div>

                    <div class="d-flex justify-content-between text-muted mb-3">
                        <span>Customer Owes Us</span>
                        <span>Rs <span id="remainingDisplay">0.00</span></span>
                    </div>

                    <button type="submit" class="btn btn-success w-100">
                        Save Invoice
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    const products = @json($productData);
    const oldProducts = Object.values(@json(old('products', [])));
    const productMap = new Map(products.map((product) => [String(product.id), product]));
    let rowIndex = 0;

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function addPickedProduct() {
        const picker = document.getElementById('productPicker');

        if (!picker.value) {
            return;
        }

        addProduct(productMap.get(picker.value));
        picker.value = '';
    }

    function addSearchedProduct() {
        const input = document.getElementById('productSearch');
        const keyword = input.value.trim().toLowerCase();

        if (!keyword) {
            return;
        }

        const product = products.find((item) =>
            String(item.barcode || '').toLowerCase() === keyword ||
            String(item.sku || '').toLowerCase() === keyword
        ) || products.find((item) =>
            item.name.toLowerCase().includes(keyword) ||
            String(item.sku || '').toLowerCase().includes(keyword)
        );

        if (product) {
            addProduct(product);
            input.value = '';
        }
    }

    function addProduct(product, item = null) {
        if (!product || product.stock <= 0) {
            return;
        }

        const existingRow = document.querySelector(`tr[data-product-id="${product.id}"]`);

        if (existingRow) {
            const quantityInput = existingRow.querySelector('.quantity');
            quantityInput.value = Math.min(
                parseInt(quantityInput.value || 0) + 1,
                product.stock
            );
            calculateTotals();
            return;
        }

        document.getElementById('emptyRow')?.remove();

        const quantity = Number(item?.quantity ?? 1) || 1;
        const price = Number(item?.price ?? product.price) || 0;

        const html = `
            <tr data-product-id="${product.id}">
                <td>
                    <strong>${escapeHtml(product.name)}</strong>
                    <input type="hidden" name="products[${rowIndex}][product_id]" value="${product.id}">
                </td>
                <td class="text-end">${product.stock}</td>
                <td>
                    <input type="number"
                           name="products[${rowIndex}][quantity]"
                           class="form-control quantity"
                           min="1"
                           max="${product.stock}"
                           value="${Math.min(quantity, product.stock)}"
                           oninput="calculateTotals()"
                           required>
                </td>
                <td>
                    <input type="number"
                           step="0.01"
                           name="products[${rowIndex}][price]"
                           class="form-control price"
                           min="0"
                           value="${price.toFixed(2)}"
                           oninput="calculateTotals()"
                           required>
                </td>
                <td class="text-end fw-bold line-total">0.00</td>
                <td class="text-end">
                    <button type="button"
                            class="btn btn-sm btn-outline-danger"
                            onclick="removeRow(this)">
                        Remove
                    </button>
                </td>
            </tr>
        `;

        document.getElementById('posItems').insertAdjacentHTML('beforeend', html);
        rowIndex++;
        calculateTotals();
    }

    function removeRow(button) {
        button.closest('tr').remove();

        if (!document.querySelector('#posItems tr')) {
            document.getElementById('posItems').innerHTML = `
                <tr id="emptyRow">
                    <td colspan="6" class="text-center text-muted py-4">
                        No products added yet.
                    </td>
                </tr>
            `;
        }

        calculateTotals();
    }

    function calculateTotals() {
        let subtotal = 0;

        document.querySelectorAll('#posItems tr[data-product-id]').forEach((row) => {
            const quantityInput = row.querySelector('.quantity');
            const priceInput = row.querySelector('.price');
            const maxStock = parseInt(quantityInput.getAttribute('max')) || 0;
            let quantity = parseInt(quantityInput.value) || 0;
            const price = parseFloat(priceInput.value) || 0;

            if (maxStock > 0 && quantity > maxStock) {
                quantity = maxStock;
                quantityInput.value = maxStock;
            }

            const lineTotal = quantity * price;
            row.querySelector('.line-total').innerText = lineTotal.toFixed(2);
            subtotal += lineTotal;
        });

        const tax = parseFloat(document.getElementById('tax').value) || 0;
        const discount = parseFloat(document.getElementById('discount').value) || 0;
        const extraExpense = parseFloat(document.getElementById('extra_expense').value) || 0;
        const paidAmount = parseFloat(document.getElementById('paid_amount').value) || 0;
        const total = Math.max((subtotal + tax + extraExpense) - discount, 0);
        const remaining = Math.max(total - paidAmount, 0);

        document.getElementById('subtotal').value = subtotal.toFixed(2);
        document.getElementById('totalDisplay').innerText = total.toFixed(2);
        document.getElementById('remainingDisplay').innerText = remaining.toFixed(2);
        document.getElementById('paymentDetails').classList.toggle('d-none', paidAmount <= 0);
    }

    document.addEventListener('input', calculateTotals);
    document.getElementById('productSearch').addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            addSearchedProduct();
        }
    });

    oldProducts.forEach((item) => {
        const product = productMap.get(String(item.product_id));

        if (product) {
            addProduct(product, item);
        }
    });
</script>

@endsection
