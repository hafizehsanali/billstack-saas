@extends('layouts.app')

@section('content')
@php
    $isFiltered = request()->start_date || request()->end_date;
@endphp

<div class="page-heading">
    <div>
        <h1>Business Overview</h1>
        <div class="text-muted">
            Sales, profit, inventory health, and account activity in one view.
        </div>
    </div>
    <div class="page-actions">
        <a href="{{ route('invoices.pos') }}" class="btn btn-primary">
            <i data-lucide="scan-barcode"></i>
            Open POS
        </a>
        <a href="{{ route('invoices.create') }}" class="btn btn-outline-secondary">
            <i data-lucide="file-plus-2"></i>
            New Invoice
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('dashboard') }}">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>
                <div class="col-md-4">
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary">
                            <i data-lucide="sliders-horizontal"></i>
                            Apply Period
                        </button>
                        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                            <i data-lucide="rotate-ccw"></i>
                            Reset
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    @foreach([
        ['label' => 'Sales Today', 'value' => 'Rs '.number_format($stats['today_sales'], 2), 'icon' => 'banknote', 'tone' => ''],
        ['label' => 'Sales in Selected Period', 'value' => 'Rs '.number_format($stats['monthly_sales'], 2), 'icon' => 'calendar-range', 'tone' => 'blue'],
        ['label' => 'Total Sales', 'value' => 'Rs '.number_format($stats['total_sales'], 2), 'icon' => 'trending-up', 'tone' => ''],
        ['label' => 'Net Profit', 'value' => 'Rs '.number_format($stats['net_profit'], 2), 'icon' => 'chart-no-axes-combined', 'tone' => $stats['net_profit'] < 0 ? 'danger' : ''],
    ] as $metric)
        <div class="col-sm-6 col-xl-3">
            <div class="card metric-card h-100">
                <div class="card-body d-flex align-items-start justify-content-between gap-3">
                    <div>
                        <div class="metric-label">{{ $metric['label'] }}</div>
                        <div class="metric-value">{{ $metric['value'] }}</div>
                        @if ($metric['label'] === 'Net Profit')
                            <div class="small text-muted mt-2">
                                Cost of Goods Sold: Rs {{ number_format($stats['total_cogs'], 2) }}
                            </div>
                        @endif
                    </div>
                    <span class="metric-icon {{ $metric['tone'] }}">
                        <i data-lucide="{{ $metric['icon'] }}"></i>
                    </span>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-3">
    @foreach([
        ['label' => 'Gross Profit', 'value' => 'Rs '.number_format($stats['gross_profit'], 2), 'icon' => 'circle-dollar-sign', 'tone' => ''],
        ['label' => 'Expenses', 'value' => 'Rs '.number_format($stats['total_expenses'], 2), 'icon' => 'wallet-cards', 'tone' => 'warning'],
        ['label' => $isFiltered ? 'Products Added' : 'Total Products', 'value' => number_format($stats['total_products']), 'icon' => 'boxes', 'tone' => 'blue'],
        ['label' => 'Low Stock Items', 'value' => number_format($stats['low_stock']), 'icon' => 'triangle-alert', 'tone' => $stats['low_stock'] > 0 ? 'danger' : ''],
        ['label' => $isFiltered ? 'New Customers' : 'Total Customers', 'value' => number_format($stats['total_customers']), 'icon' => 'users', 'tone' => ''],
        ['label' => $isFiltered ? 'Invoices Created' : 'Total Invoices', 'value' => number_format($stats['total_invoices']), 'icon' => 'receipt-text', 'tone' => 'blue'],
    ] as $metric)
        <div class="col-sm-6 col-lg-4 col-xl-2">
            <div class="card metric-card h-100">
                <div class="card-body">
                    <span class="metric-icon {{ $metric['tone'] }} mb-3">
                        <i data-lucide="{{ $metric['icon'] }}"></i>
                    </span>
                    <div class="metric-label">{{ $metric['label'] }}</div>
                    <div class="metric-value">{{ $metric['value'] }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- Charts --}}
<div class="row mt-4">

    {{-- Sales Chart --}}
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Sales & Profit Analytics</h3>
            </div>

            <div class="card-body">
                <div id="salesChart"></div>
            </div>
        </div>
    </div>

    {{-- Invoice Summary --}}
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Invoice Summary</h3>
            </div>

            <div class="card-body">

                <div class="mb-3">
                    <strong>Paid:</strong>
                    {{ $stats['paid_invoices'] }}
                </div>

                <div class="mb-3">
                    <strong>Partially Paid:</strong>
                    {{ $stats['partial_invoices'] }}
                </div>

                <div class="mb-3">
                    <strong>Unpaid:</strong>
                    {{ $stats['unpaid_invoices'] }}
                </div>

                <div>
                    <strong>Cancelled:</strong>
                    {{ $stats['cancelled_invoices'] }}
                </div>

            </div>
        </div>
    </div>

</div>
{{-- Advanced Widgets --}}
<div class="row mt-4">

    {{-- Invoice Status Chart --}}
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Invoice Status</h3>
            </div>

            <div class="card-body">
                <div id="invoiceChart"></div>
            </div>
        </div>
    </div>

    {{-- Top Products --}}
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Top Selling Products</h3>
            </div>

            <div class="table-responsive">
                <table class="table table-vcenter">

                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Qty</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($topProducts as $item)

                            <tr>
                                <td>{{ $item->product?->name }}</td>
                                <td>{{ $item->total_qty }}</td>
                            </tr>

                        @empty

                            <tr>
                                <td colspan="2" class="text-center">
                    No product sales in this period
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>
            </div>
        </div>
    </div>

    {{-- Low Stock --}}
    <div class="col-md-4">
        <div class="card border-danger">
            <div class="card-header">
                <h3 class="card-title text-danger">
                    Low Stock Alerts
                </h3>

                <a href="{{ route('alerts.index') }}"
                   class="btn btn-sm btn-outline-danger ms-auto">
                    View All
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-vcenter">

                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Stock</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($lowStockProducts as $product)

                            <tr>
                                <td>{{ $product->name }}</td>

                                <td>
                                    <span class="badge bg-danger">
                                        {{ $product->stock_quantity }}
                                    </span>
                                </td>
                            </tr>

                        @empty

                            <tr>
                                <td colspan="2" class="text-center">
                                    No low-stock alerts right now
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>
            </div>
        </div>
    </div>

</div>

{{-- Recent Invoices --}}
<div class="row mt-4">

    <div class="col-12">

        <div class="card">

            <div class="card-header">
                <h3 class="card-title">
                    {{ request()->start_date || request()->end_date
                        ? 'Filtered Recent Invoices'
                        : 'Recent Invoices'
                    }}
                </h3>
            </div>

            <div class="table-responsive">

                <table class="table table-vcenter">

                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th class="text-end">Invoice Total</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($recentInvoices as $invoice)

                            <tr>

                                <td>
                                    <a href="{{ route('invoices.show', $invoice) }}">
                                        {{ $invoice->invoice_no }}
                                    </a>
                                </td>

                                <td>
                                    {{ $invoice->customer?->name }}
                                </td>

                                <td>

                                    <span class="badge
                                        @if($invoice->status === 'paid') bg-success
                                        @elseif($invoice->status === 'partial') bg-warning
                                        @elseif($invoice->status === 'cancelled') bg-dark
                                        @else bg-danger
                                        @endif">

                                        {{ ucfirst($invoice->status) }}

                                    </span>

                                </td>

                                <td class="text-end">
                                    Rs {{ number_format($invoice->total, 2) }}
                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="4" class="text-center">
                                    No recent invoices found
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>
{{-- ApexCharts --}}
<script>

document.addEventListener('DOMContentLoaded', function () {

    let options = {

        chart: {
            type: 'line',
            height: 350,
            toolbar: {
                show: true
            }
        },

        series: [

            {
                name: 'Sales',
                data: @json($chartData['sales'])
            },

            {
                name: 'Profit',
                data: @json($chartData['profits'])
            }

        ],

        xaxis: {
            categories: @json($chartData['months'])
        },

        stroke: {
            curve: 'smooth'
        },

        dataLabels: {
            enabled: false
        }

    };

    let chart = new ApexCharts(
        document.querySelector("#salesChart"),
        options
    );

    chart.render();

    // Invoice status donut chart
    let invoiceOptions = {

        chart: {
            type: 'donut',
            height: 320
        },

        series: [
            {{ $invoiceChart['paid'] }},
            {{ $invoiceChart['partial'] }},
            {{ $invoiceChart['unpaid'] }},
            {{ $invoiceChart['cancelled'] }}
        ],

        labels: [
            'Paid',
            'Partial',
            'Unpaid',
            'Cancelled'
        ]

    };

    let invoiceChart = new ApexCharts(
        document.querySelector("#invoiceChart"),
        invoiceOptions
    );

    invoiceChart.render();

});

</script>
@endsection
