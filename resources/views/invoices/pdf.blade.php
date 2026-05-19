<!DOCTYPE html>
<html>
<head>

    <title>
        Invoice PDF
    </title>

    <style>

        body {
            font-family: sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table, th, td {
            border: 1px solid #000;
        }

        th, td {
            padding: 8px;
        }

    </style>

</head>

<body>

    <h2>
        BillStack Invoice
    </h2>

    <p>
        Invoice:
        {{ $invoice->invoice_number }}
    </p>

    <p>
        Customer:
        {{ $invoice->customer?->name ?? 'Walk-in Customer' }}
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

        @foreach($invoice->items as $item)

            <tr>

                <td>
                    {{ $item->product?->name }}
                </td>

                <td>
                    {{ $item->quantity }}
                </td>

                <td>
                    {{ $item->price }}
                </td>

                <td>
                    {{ $item->total }}
                </td>

            </tr>

        @endforeach

        </tbody>

    </table>

    <h3 style="margin-top:20px; text-align:right;">

        Total:
        Rs. {{ $invoice->total }}

    </h3>

</body>
</html>