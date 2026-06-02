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

    <h2>{{ $tenant->name }}</h2>

    <p>
        @if($tenant->email)
            Email: {{ $tenant->email }} <br>
        @endif

        @if($tenant->phone)
            Phone: {{ $tenant->phone }} <br>
        @endif

        @if($tenant->address)
            Address: {{ $tenant->address }}
        @endif
    </p>

    <h3>Invoice</h3>

    <p>
        Invoice #: {{ $invoice->invoice_no }} <br>
        Date: {{ \Carbon\Carbon::parse($invoice->sale_date)->format('d M Y') }}
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
                    <td>{{ number_format($item->price, 2) }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->total, 2) }}</td>
                </tr>
            @endforeach

        </tbody>

    </table>

    <h3 class="text-right">
        Total: Rs {{ number_format($invoice->total, 2) }}
    </h3>

</body>

</html>
