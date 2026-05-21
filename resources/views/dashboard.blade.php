@extends('layouts.app')

@section('content')

<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Dashboard</h2>
            <p class="text-muted mb-0">Business overview & analytics</p>
        </div>
    </div>

    <div class="row">

        {{-- Sales & Finance --}}
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Today Sales</p>
                    <h3 class="fw-bold text-success">
                        Rs {{ number_format($stats['today_sales'], 2) }}
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Monthly Sales</p>
                    <h3 class="fw-bold text-primary">
                        Rs {{ number_format($stats['monthly_sales'], 2) }}
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Total Expenses</p>
                    <h3 class="fw-bold text-danger">
                        Rs {{ number_format($totalExpenses, 2) }}
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Net Profit</p>
                    <h3 class="fw-bold text-success">
                        Rs {{ number_format($totalProfit, 2) }}
                    </h3>
                </div>
            </div>
        </div>

        {{-- Inventory --}}
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Total Products</p>
                    <h3 class="fw-bold">{{ $stats['total_products'] }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Low Stock Items</p>
                    <h3 class="fw-bold text-warning">
                        {{ $stats['low_stock'] }}
                    </h3>
                </div>
            </div>
        </div>

        {{-- CRM --}}
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Customers</p>
                    <h3 class="fw-bold">{{ $stats['total_customers'] }}</h3>
                </div>
            </div>
        </div>

        {{-- Invoices --}}
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Total Invoices</p>
                    <h3 class="fw-bold">{{ $stats['total_invoices'] }}</h3>
                </div>
            </div>
        </div>

    </div>

    {{-- Invoice Analytics --}}
    <div class="card shadow-sm border-0 mt-4">

        <div class="card-header bg-white">
            <h4 class="mb-0">Invoice Status Analytics</h4>
        </div>

        <div class="card-body">

            <div class="row text-center">

                <div class="col-md-3 mb-3">
                    <h5 class="text-success">Paid</h5>
                    <h2>{{ $stats['paid_invoices'] }}</h2>
                </div>

                <div class="col-md-3 mb-3">
                    <h5 class="text-warning">Partial</h5>
                    <h2>{{ $stats['partial_invoices'] }}</h2>
                </div>

                <div class="col-md-3 mb-3">
                    <h5 class="text-danger">Unpaid</h5>
                    <h2>{{ $stats['unpaid_invoices'] }}</h2>
                </div>

                <div class="col-md-3 mb-3">
                    <h5 class="text-secondary">Cancelled</h5>
                    <h2>{{ $stats['cancelled_invoices'] }}</h2>
                </div>

            </div>

        </div>

    </div>

</div>

@endsection