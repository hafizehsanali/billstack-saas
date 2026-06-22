<!DOCTYPE html>
<html>

<head>
    <title>Invoice | {{ platform_name() }}</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            color: #1F2937;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table, th, td {
            border: 1px solid #d1d5db;
        }

        th, td {
            padding: 8px;
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .brand-header {
            border-bottom: 3px solid #F97316;
            margin-bottom: 18px;
            padding-bottom: 12px;
        }

        .brand-header h1 {
            margin: 0;
            color: #1F2937;
            font-size: 22px;
        }

        .brand-header p,
        .brand-footer {
            color: #4b5563;
            font-size: 12px;
        }

        th {
            color: #ffffff;
            background: #1F2937;
        }

        .brand-footer {
            border-top: 1px solid #d1d5db;
            margin-top: 24px;
            padding-top: 10px;
            text-align: center;
        }
    </style>
</head>

<body>

    <div class="brand-header">
        <h1>{{ platform_name() }}</h1>
        <p>
            {{ platform_company_name() }} | {{ platform_primary_email() }} | {{ platform_domain() }}
        </p>
    </div>

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
                            <br><small style="color: #22C55E;">
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
        <p class="text-right" style="color: #22C55E;">
            <strong>Promotional Savings: Rs {{ number_format($promotionalSavings, 2) }}</strong>
        </p>
    @endif

    <h3 class="text-right">
        Total: Rs {{ number_format($invoice->total, 2) }}
    </h3>

    <div class="brand-footer">
        &copy; {{ now()->year }} {{ platform_company_name() }}. All rights reserved.
    </div>

</body>

</html>
