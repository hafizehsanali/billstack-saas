@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-1">Low Stock Report</h3>
        <div class="text-muted">
            Products at or below their alert level.
        </div>
    </div>

    <a href="{{ route('products.index') }}" class="btn btn-secondary">
        Products
    </a>
</div>

<div class="row row-cards mb-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Needs Attention</div>
                <div class="h2 mb-0 text-warning">{{ number_format($products->count()) }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Out of Stock</div>
                <div class="h2 mb-0 text-danger">{{ number_format($outOfStockCount) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="text-end">Current Stock</th>
                    <th class="text-end">Alert Level</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($products as $product)
                    <tr>
                        <td>{{ $product->name }}</td>
                        <td class="text-end fw-bold">{{ number_format($product->stock_quantity) }}</td>
                        <td class="text-end">{{ number_format($product->low_stock_alert) }}</td>
                        <td>
                            @if($product->stock_quantity <= 0)
                                <span class="badge bg-danger">Out of Stock</span>
                            @else
                                <span class="badge bg-warning">Low Stock</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('products.stock-ledger', $product) }}"
                               class="btn btn-sm btn-outline-primary">
                                Stock Ledger
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            All products are above their low-stock alert level.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
