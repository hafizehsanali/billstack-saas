<!DOCTYPE html>
<html>

<head>
    <title>Invoice</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table, th, td {
            border: 1px solid #000;
        }

        th, td {
            padding: 8px;
            text-align: left;
        }

        .text-right {
            text-align: right;
        }
    </style>
</head>

<body>

    <h2>BillStack Invoice</h2>

    <p>
        Invoice #: {{ $invoice->invoice_number }} <br>
        Date: {{ $invoice->created_at->format('d M Y') }}
    </p>

    <h4>Customer</h4>
    <p>
        {{ $invoice->customer->name }} <br>
        {{ $invoice->customer->phone }}
    </p>

    <table>

        <thead>
            <tr>
                <th>Product</th>
                <th>Price</th>
                <th>Qty</th>
                <th>Total</th>
            </tr>
        </thead>

        <tbody>

            @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->product->name }}</td>
                    <td>{{ $item->price }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $item->total }}</td>
                </tr>
            @endforeach

        </tbody>

    </table>

    <h3 class="text-right">
        Total: {{ $invoice->total }}
    </h3>

</body>

</html>