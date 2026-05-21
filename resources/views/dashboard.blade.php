@extends('layouts.app')

@section('content')

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
                <small>Total Products</small>
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
                <small>Customers</small>
                <h2>{{ $stats['total_customers'] }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <small>Total Invoices</small>
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

});

</script>
@endsection