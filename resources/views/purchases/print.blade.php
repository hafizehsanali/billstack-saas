<!DOCTYPE html>
<html>
<head>

    <title>
        Purchase Invoice
    </title>

    <style>

        body {
            font-family: Arial;
            padding: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table,
        th,
        td {
            border: 1px solid #000;
        }

        th,
        td {
            padding: 10px;
        }

        .text-end {
            text-align: right;
        }

    </style>

</head>
<body>

    <h2>
        Purchase Invoice
    </h2>

    <p>
        <strong>Purchase No:</strong>
        {{ $purchase->purchase_no }}
    </p>

    <p>
        <strong>Supplier:</strong>
        {{ $purchase->supplier->name }}
    </p>

    <p>
        <strong>Date:</strong>
        {{ $purchase->purchase_date->format('d M Y') }}
    </p>

    <table>

        <thead>
            <tr>
                <th>Product</th>
                <th>Qty</th>
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

    <h3 class="text-end">
        Total:
        Rs {{ number_format($purchase->total, 2) }}
    </h3>

<script>
    window.print();
</script>

</body>
</html>