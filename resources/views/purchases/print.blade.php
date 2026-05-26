<!DOCTYPE html>
<html>
<head>

    <title>
        Purchase Invoice
    </title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

</head>

<body onload="window.print()">

<div class="container mt-4">

    <div class="text-center mb-4">

        <h2>
            Purchase Invoice
        </h2>

    </div>

    <div class="row mb-4">

        <div class="col-6">

            <strong>
                Purchase No:
            </strong>

            {{ $purchase->purchase_no }}

            <br>

            <strong>
                Date:
            </strong>

            {{ $purchase->purchase_date }}

        </div>

        <div class="col-6 text-end">

            <strong>
                Supplier:
            </strong>

            {{ $purchase->supplier->name }}

        </div>

    </div>

    <table class="table table-bordered">

        <thead>

            <tr>

                <th>Product</th>

                <th>Quantity</th>

                <th>Price</th>

                <th>Total</th>

            </tr>

        </thead>

        <tbody>

            @foreach($purchase->items as $item)

                <tr>

                    <td>

                        {{ $item->product->name }}

                    </td>

                    <td>

                        {{ $item->quantity }}

                    </td>

                    <td>

                        Rs {{ number_format($item->purchase_price, 2) }}

                    </td>

                    <td>

                        Rs {{ number_format($item->line_total, 2) }}

                    </td>

                </tr>

            @endforeach

        </tbody>

    </table>

    <div class="row mt-4">

        <div class="col-6"></div>

        <div class="col-6">

            <table class="table table-bordered">

                <tr>

                    <th>
                        Subtotal
                    </th>

                    <td>

                        Rs {{ number_format($purchase->subtotal, 2) }}

                    </td>

                </tr>

                <tr>

                    <th>
                        Extra Expense
                    </th>

                    <td>

                        Rs {{ number_format($purchase->extra_expense, 2) }}

                    </td>

                </tr>

                <tr>

                    <th>
                        Discount
                    </th>

                    <td>

                        Rs {{ number_format($purchase->discount, 2) }}

                    </td>

                </tr>

                <tr>

                    <th>
                        Grand Total
                    </th>

                    <td>

                        Rs {{ number_format($purchase->total, 2) }}

                    </td>

                </tr>

            </table>

        </div>

    </div>

</div>

</body>
</html>