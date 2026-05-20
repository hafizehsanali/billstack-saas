@extends('layouts.app')

@section('content')

<div x-data="invoiceForm()" class="card">
    <div class="card-header">
        <h3 class="card-title">Create Invoice</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('invoices.store') }}">
            @csrf
            <!-- Customer -->
            <div class="mb-3">
                <label class="form-label"> Customer </label>
                <select name="customer_id" class="form-select" required>
                    <option value="">Select Customer</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">
                            {{ $customer->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <!-- Invoice Rows -->
            <template x-for="(row, index) in rows" :key="index" >
                <div class="row mb-3">
                    <!-- Product -->
                    <div class="col-md-4">
                        <label class="form-label">Product</label>

                        <select
                            class="form-select"
                            :name="'products[]'"
                            x-model="row.product_id"
                            @change="updatePrice(index)"
                            required
                        >

                            <option value="">
                                Select Product
                            </option>

                            @foreach($products as $product)

                                <option
                                    value="{{ $product->id }}"
                                    data-price="{{ $product->selling_price }}"
                                >

                                    {{ $product->name }}
                                    (Stock:
                                    {{ $product->stock_quantity }})

                                </option>

                            @endforeach

                        </select>

                    </div>

                    <!-- Quantity -->

                    <div class="col-md-2">

                        <label class="form-label">
                            Quantity
                        </label>

                        <input
                            type="number"
                            min="1"
                            class="form-control"
                            :name="'quantities[' + row.product_id + ']'"
                            x-model="row.quantity"
                            @input="calculateRow(index)"
                            required
                        >

                    </div>

                    <!-- Price -->

                    <div class="col-md-2">

                        <label class="form-label">
                            Price
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            x-model="row.price"
                            readonly
                        >

                    </div>

                    <!-- Total -->

                    <div class="col-md-2">

                        <label class="form-label">
                            Total
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            x-model="row.total"
                            readonly
                        >

                    </div>

                    <!-- Remove -->

                    <div class="col-md-2 d-flex align-items-end">

                        <button
                            type="button"
                            class="btn btn-danger"
                            @click="removeRow(index)"
                        >

                            Remove

                        </button>

                    </div>

                </div>

            </template>

            <!-- Add Row -->

            <button
                type="button"
                class="btn btn-secondary mb-4"
                @click="addRow()"
            >

                Add Product

            </button>

            <!-- Grand Total -->

            <div class="mb-3">

                <h2>

                    Grand Total:
                    Rs.
                    <span x-text="grandTotal"></span>

                </h2>

            </div>

            <!-- Submit -->

            <button
                class="btn btn-primary"
            >

                Create Invoice

            </button>

        </form>

    </div>

</div>

<script>

function invoiceForm()
{
    return {

        rows: [
            {
                product_id: '',
                quantity: 1,
                price: 0,
                total: 0
            }
        ],

        grandTotal: 0,

        addRow()
        {
            this.rows.push({
                product_id: '',
                quantity: 1,
                price: 0,
                total: 0
            })
        },

        removeRow(index)
        {
            this.rows.splice(index, 1)

            this.calculateGrandTotal()
        },

        updatePrice(index)
        {
            let row = this.rows[index]

            let select = document.querySelectorAll(
                'select[name=\"products[]\"]'
            )[index]

            let option = select.options[
                select.selectedIndex
            ]

            row.price = option.dataset.price || 0

            this.calculateRow(index)
        },

        calculateRow(index)
        {
            let row = this.rows[index]

            row.total = row.price * row.quantity

            this.calculateGrandTotal()
        },

        calculateGrandTotal()
        {
            this.grandTotal = this.rows.reduce(
                (sum, row) => sum + Number(row.total),
                0
            )
        }

    }
}

</script>

@endsection