@extends('layouts.app')

@section('content')

<div class="row">

    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h3>Today Sales</h3>
                <h2>Rs {{ $stats['today_sales'] }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h3>Monthly Sales</h3>
                <h2>Rs {{ $stats['monthly_sales'] }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h3>Total Products</h3>
                <h2>{{ $stats['total_products'] }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-4 mt-3">
        <div class="card">
            <div class="card-body">
                <h3>Low Stock Items</h3>
                <h2>{{ $stats['low_stock'] }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-4 mt-3">
        <div class="card">
            <div class="card-body">
                <h3>Customers</h3>
                <h2>{{ $stats['total_customers'] }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-4 mt-3">
        <div class="card">
            <div class="card-body">
                <h3>Total Invoices</h3>
                <h2>{{ $stats['total_invoices'] }}</h2>
            </div>
        </div>
    </div>

</div>

@endsection