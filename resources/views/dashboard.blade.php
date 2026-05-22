@extends('layouts.app')

@section('content')
{{-- Dashboard Filters --}}
<div class="card mb-4">

    <div class="card-body">

        <form method="GET" action="{{ route('dashboard') }}">

            <div class="row align-items-end">

                <div class="col-md-4">
                    <label class="form-label">Start Date</label>

                    <input type="date"
                        name="start_date"
                        class="form-control"
                        value="{{ request('start_date') }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">End Date</label>

                    <input type="date"
                        name="end_date"
                        class="form-control"
                        value="{{ request('end_date') }}">
                </div>

                <div class="col-md-4">

                    <button class="btn btn-primary">
                        Filter Analytics
                    </button>

                    <a href="{{ route('dashboard') }}"
                        class="btn btn-secondary">

                        Reset

                    </a>

                </div>

            </div>

        </form>

    </div>

</div>
@php
    $isFiltered = request()->start_date || request()->end_date;
@endphp
<div class="row g-3">

    {{-- Sales --}}
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <small>Today Sales</small>
                <h2>Rs {{ number_format($stats['today_sales'], 2) }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-blue text-white">
            <div class="card-body">
                <small>Monthly Sales</small>
                <h2>Rs {{ number_format($stats['monthly_sales'], 2) }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <small>Total Revenue</small>
                <h2>Rs {{ number_format($stats['total_sales'], 2) }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-green text-white">
            <div class="card-body">
                <small>Net Profit</small>
                <h2>Rs {{ number_format($stats['net_profit'], 2) }}</h2>
            </div>
        </div>
    </div>

    {{-- Profit --}}
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <small>COGS</small>
                <h2>Rs {{ number_format($stats['total_cogs'], 2) }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <small>Gross Profit</small>
                <h2>Rs {{ number_format($stats['gross_profit'], 2) }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <small>Expenses</small>
                <h2>Rs {{ number_format($stats['total_expenses'], 2) }}</h2>
            </div>
        </div>
    </div>

    {{-- Inventory --}}
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <small>{{ $isFiltered ? 'Products Added' : 'Total Products' }}</small>
                <h2>{{ $stats['total_products'] }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-danger">
            <div class="card-body">
                <small>Low Stock</small>
                <h2>{{ $stats['low_stock'] }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <small> {{ $isFiltered ? 'New Customers' : 'Total Customers' }}</small>
                <h2>{{ $stats['total_customers'] }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <small>{{ $isFiltered ? 'Invoices Created' : 'Total Invoices' }}</small>
                <h2>{{ $stats['total_invoices'] }}</h2>
            </div>
        </div>
    </div>

    {{-- Invoice Status --}}
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <small>Paid</small>
                <h2>{{ $stats['paid_invoices'] }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <small>Partial</small>
                <h2>{{ $stats['partial_invoices'] }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <small>Unpaid</small>
                <h2>{{ $stats['unpaid_invoices'] }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-dark text-white">
            <div class="card-body">
                <small>Cancelled</small>
                <h2>{{ $stats['cancelled_invoices'] }}</h2>
            </div>
        </div>
    </div>

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
                    <strong>Partial:</strong>
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
                                    No data found
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
                                    No low stock items
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
                            <th>Total</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($recentInvoices as $invoice)

                            <tr>

                                <td>
                                    {{ $invoice->invoice_number }}
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

                                <td>
                                    Rs {{ number_format($invoice->total, 2) }}
                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="4" class="text-center">
                                    No invoices found
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