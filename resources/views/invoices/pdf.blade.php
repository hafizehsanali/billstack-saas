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
                    <td>{{ $item->variant?->display_name ?? $item->product->name }}</td>
                    <td>
                        @if($item->regular_price && $item->regular_price > $item->price)
                            <span style="text-decoration: line-through; color: #6b7280;">
                                Rs {{ number_format($item->regular_price, 2) }}
                            </span><br>
                        @endif
                        Rs {{ number_format($item->price, 2) }}
                        @if($item->item_savings > 0)
                            <br><small style="color: #166534;">
                                You saved Rs {{ number_format($item->item_savings, 2) }}
                            </small>
                        @endif
                    </td>
                    <td>{{ $item->quantity }} {{ $item->variant?->unit?->symbol }}</td>
                    <td>{{ number_format($item->total, 2) }}</td>
                </tr>
            @endforeach

        </tbody>

    </table>

    @php
        $promotionalSavings = (float) $invoice->items->sum('item_savings');
    @endphp
    @if($promotionalSavings > 0)
        <p class="text-right" style="color: #166534;">
            <strong>Promotional Savings: Rs {{ number_format($promotionalSavings, 2) }}</strong>
        </p>
    @endif

    <h3 class="text-right">
        Total: Rs {{ number_format($invoice->total, 2) }}
    </h3>

</body>

</html>
