@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Alerts Center</h2>
        <small class="text-muted">Operational alerts that need attention.</small>
    </div>

    <a href="{{ route('products.index') }}" class="btn btn-secondary">
        Products
    </a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card border-danger">
            <div class="card-body">
                <small class="text-muted">Total Active Alerts</small>
                <h2 class="mb-0 text-danger">{{ $summary['total'] }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-danger">
            <div class="card-body">
                <small class="text-muted">Out of Stock</small>
                <h2 class="mb-0 text-danger">{{ $summary['out_of_stock'] }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-warning">
            <div class="card-body">
                <small class="text-muted">Low Stock</small>
                <h2 class="mb-0 text-warning">{{ $summary['low_stock'] }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title mb-0">
            Low Stock Alerts
        </h3>
    </div>

    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>SKU</th>
                    <th class="text-end">Current Stock</th>
                    <th class="text-end">Alert Level</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($lowStockProducts as $product)
                    <tr>
                        <td>
                            @if($product->stock_quantity <= 0)
                                <span class="badge bg-danger">Out of Stock</span>
                            @else
                                <span class="badge bg-warning">Low Stock</span>
                            @endif
                        </td>

                        <td>{{ $product->name }}</td>
                        <td>{{ $product->category?->name ?? '-' }}</td>
                        <td>{{ $product->sku ?? '-' }}</td>

                        <td class="text-end">
                            {{ $product->stock_quantity }}
                        </td>

                        <td class="text-end">
                            {{ $product->low_stock_alert }}
                        </td>

                        <td class="text-end">
                            <a href="{{ route('products.edit', $product) }}"
                               class="btn btn-sm btn-outline-secondary">
                                Edit
                            </a>

                            <a href="{{ route('products.stock-ledger', $product) }}"
                               class="btn btn-sm btn-outline-primary">
                                Stock Ledger
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            No active alerts.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
