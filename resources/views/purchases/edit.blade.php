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

        <input type="hidden"
               name="purchase_no"
               value="{{ old('purchase_no', $purchase->purchase_no) }}">

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
                                        @selected(old('supplier_id', $purchase->supplier_id) == $supplier->id)>

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
                                    @selected(old('status', $purchase->status) === 'paid')>
                                Paid
                            </option>

                            <option value="partial"
                                    @selected(old('status', $purchase->status) === 'partial')>
                                Partial
                            </option>

                            <option value="unpaid"
                                    @selected(old('status', $purchase->status) === 'unpaid')>
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

                            <th width="30%">
                                Product
                            </th>

                            <th width="12%">Unit</th>

                            <th width="13%">
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

                        @php
                            $purchaseRows = collect(old('products', $purchase->items->map(fn ($item) => [
                                'product_id' => $item->product_id,
                                'product_variant_id' => $item->product_variant_id,
                                'unit_id' => $item->unit_id,
                                'unit_factor' => $item->unit_factor,
                                'unit_symbol' => $item->unit?->symbol,
                                'quantity' => $item->quantity,
                                'purchase_price' => $item->purchase_price,
                                'line_total' => $item->line_total,
                            ])->toArray()))->values();
                        @endphp

                        @foreach($purchaseRows as $index => $item)

                            <tr>

                                <td>

                                    <input type="hidden" name="products[{{ $index }}][product_id]" class="product-id" value="{{ $item['product_id'] ?? '' }}">
                                    <select name="products[{{ $index }}][product_variant_id]"
                                            class="form-select product-select"
                                            onchange="setProductPrice(this)"
                                            required>

                                        <option value="">
                                            Select Product
                                        </option>

                                        @foreach($variants as $variant)

                                            <option value="{{ $variant->id }}"
                                                    data-product-id="{{ $variant->product_id }}"
                                                    data-price="{{ $variant->purchase_unit_price ?? $variant->purchase_price }}"
                                                    data-unit-id="{{ $variant->purchase_unit_id ?: $variant->unit_id }}"
                                                    data-unit="{{ ($variant->purchaseUnit ?: $variant->unit)?->symbol ?? 'unit' }}"
                                                    data-factor="{{ max((int) $variant->purchase_unit_factor, 1) }}"
                                                    @selected(
                                                        ($item['product_variant_id'] ?? null) == $variant->id
                                                        || (! ($item['product_variant_id'] ?? null)
                                                            && ($item['product_id'] ?? null) == $variant->product_id
                                                            && $variant->is_default)
                                                    )>

                                                {{ $variant->display_name }}

                                            </option>

                                        @endforeach

                                    </select>

                                </td>

                                <td>
                                    <input type="hidden" name="products[{{ $index }}][unit_id]" class="unit-id" value="{{ $item['unit_id'] ?? '' }}">
                                    <input type="hidden" name="products[{{ $index }}][unit_factor]" class="unit-factor" value="{{ $item['unit_factor'] ?? 1 }}">
                                    <span class="form-control bg-light unit-label">{{ $item['unit_symbol'] ?? '-' }}</span>
                                </td>

                                <td>

                                    <input type="number"
                                           step="0.01"
                                           min="1"
                                           name="products[{{ $index }}][quantity]"
                                           class="form-control quantity"
                                           value="{{ $item['quantity'] ?? 1 }}"
                                           required>

                                </td>

                                <td>

                                    <input type="number"
                                           step="0.01"
                                           min="0"
                                           name="products[{{ $index }}][purchase_price]"
                                           class="form-control price"
                                           value="{{ $item['purchase_price'] ?? 0 }}"
                                           required>

                                </td>

                                <td>

                                    <input type="text"
                                           class="form-control line-total"
                                           value="{{ number_format($item['line_total'] ?? (($item['quantity'] ?? 0) * ($item['purchase_price'] ?? 0)), 2) }}"
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
                               value="{{ old('subtotal', $purchase->subtotal) }}"
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
                               value="{{ old('extra_expense', $purchase->extra_expense) }}">

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
                               value="{{ old('discount', $purchase->discount) }}">

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
                               value="{{ old('total', $purchase->total) }}"
                               readonly>

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Paid to Supplier
                        </label>

                        <input type="number"
                               step="0.01"
                               min="0"
                               name="paid_amount"
                               id="paid_amount"
                               class="form-control @error('paid_amount') is-invalid @enderror"
                               value="{{ old('paid_amount', $purchase->paid_amount) }}">

                        @error('paid_amount')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror

                    </div>

                    {{-- Supplier payable balance --}}
                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Still Payable to Supplier
                        </label>

                        <input type="number"
                               step="0.01"
                               name="remaining_amount"
                               id="remaining_amount"
                               class="form-control bg-light fw-bold text-danger"
                               value="{{ old('remaining_amount', $purchase->remaining_amount) }}"
                               readonly>

                    </div>

                    {{-- Notes --}}
                    <div class="col-md-12 mb-3">

                        <label class="form-label">
                            Notes
                        </label>

                        <textarea name="notes"
                                  rows="3"
                                  class="form-control">{{ old('notes', $purchase->notes) }}</textarea>

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

    let rowIndex = {{ $purchaseRows->count() }};

    document
        .getElementById('add-row')
        .addEventListener('click', function () {

            let row = `
                <tr>

                    <td>

                        <input type="hidden" name="products[${rowIndex}][product_id]" class="product-id">
                        <select name="products[${rowIndex}][product_variant_id]"
                                class="form-select product-select"
                                onchange="setProductPrice(this)"
                                required>

                            <option value="">
                                Select Product
                            </option>

                            @foreach($variants as $variant)

                                <option value="{{ $variant->id }}"
                                        data-product-id="{{ $variant->product_id }}"
                                        data-price="{{ $variant->purchase_unit_price ?? $variant->purchase_price }}"
                                        data-unit-id="{{ $variant->purchase_unit_id ?: $variant->unit_id }}"
                                        data-unit="{{ ($variant->purchaseUnit ?: $variant->unit)?->symbol ?? 'unit' }}"
                                        data-factor="{{ max((int) $variant->purchase_unit_factor, 1) }}">

                                    {{ $variant->display_name }}

                                </option>

                            @endforeach

                        </select>

                    </td>

                    <td>
                        <input type="hidden" name="products[${rowIndex}][unit_id]" class="unit-id">
                        <input type="hidden" name="products[${rowIndex}][unit_factor]" class="unit-factor" value="1">
                        <span class="form-control bg-light unit-label">-</span>
                    </td>

                    <td>

                        <input type="number"
                               step="0.01"
                               min="1"
                               name="products[${rowIndex}][quantity]"
                               class="form-control quantity"
                               value="1"
                               required>

                    </td>

                    <td>

                        <input type="number"
                               step="0.01"
                               min="0"
                               name="products[${rowIndex}][purchase_price]"
                               class="form-control price"
                               value="0"
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

            calculateTotals();
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

        let total = subtotal + extraExpense - discount;

        if (total < 0) {
            total = 0;
        }

        let paidAmount = parseFloat(
            document.getElementById('paid_amount').value || 0
        );

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
    }

    function setProductPrice(select)
    {
        let row = select.closest('tr');
        let option = select.options[select.selectedIndex];
        let price = parseFloat(option.dataset.price) || 0;

        row.querySelector('.product-id').value = option.dataset.productId || '';
        row.querySelector('.unit-id').value = option.dataset.unitId || '';
        row.querySelector('.unit-factor').value = option.dataset.factor || 1;
        row.querySelector('.unit-label').textContent = option.dataset.unit || '-';
        row.querySelector('.price').value = price.toFixed(2);

        calculateTotals();
    }

    calculateTotals();

</script>

@endsection
