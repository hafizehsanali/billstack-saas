@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Alerts Center</h2>
        <small class="text-muted">Operational alerts that need attention.</small>
    </div>

    <a href="{{ route('dashboard') }}" class="btn btn-secondary">
        Dashboard
    </a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card border-danger">
            <div class="card-body">
                <small class="text-muted">Total Active Alerts</small>
                <h2 class="mb-0 text-danger">{{ $summary['total'] }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-danger">
            <div class="card-body">
                <small class="text-muted">Out of Stock</small>
                <h2 class="mb-0 text-danger">{{ $summary['out_of_stock'] }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-warning">
            <div class="card-body">
                <small class="text-muted">Low Stock</small>
                <h2 class="mb-0 text-warning">{{ $summary['low_stock'] }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-primary">
            <div class="card-body">
                <small class="text-muted">Payment Due</small>
                <h2 class="mb-0 text-primary">
                    {{ $summary['customer_payment_due'] + $summary['supplier_payment_due'] }}
                </h2>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-warning">
            <div class="card-body">
                <small class="text-muted">Expiry Alerts</small>
                <h2 class="mb-0 text-warning">{{ $summary['expiry'] }}</h2>
                @if($summary['expired'] > 0)
                    <small class="text-danger">{{ $summary['expired'] }} expired</small>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
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

<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title mb-0">
            Expiry Alerts
        </h3>
    </div>

    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Product</th>
                    <th>Batch</th>
                    <th class="text-end">Quantity</th>
                    <th>Expiry Date</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($expiryBatches as $batch)
                    @php
                        $isExpired = $batch->expiry_date?->isPast();
                    @endphp
                    <tr>
                        <td>
                            @if($isExpired)
                                <span class="badge bg-danger">Expired</span>
                            @else
                                <span class="badge bg-warning">Near Expiry</span>
                            @endif
                        </td>
                        <td>{{ $batch->product?->name ?? '-' }}</td>
                        <td>{{ $batch->batch_number }}</td>
                        <td class="text-end fw-bold">
                            {{ number_format($batch->quantity, 3) }} {{ $batch->variant?->unit?->symbol }}
                        </td>
                        <td>{{ $batch->expiry_date?->format('d M Y') ?? '-' }}</td>
                        <td class="text-end">
                            @if($batch->product)
                                <a href="{{ route('products.stock-ledger', $batch->product) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    Stock Ledger
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            No expired or near-expiry batches.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title mb-0">
            Customer Payment Due
        </h3>
    </div>

    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th class="text-center">Open Invoices</th>
                    <th>Oldest Due</th>
                    <th>Latest Due</th>
                    <th class="text-end">Customer Owes Us</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($customerPaymentDues as $alert)
                    <tr>
                        <td>{{ $alert['customer']?->name ?? '-' }}</td>
                        <td class="text-center">
                            <span class="badge bg-dark text-white fs-5 px-3 py-2">
                                {{ $alert['open_invoices'] }}
                            </span>
                        </td>
                        <td>{{ \Carbon\Carbon::parse($alert['oldest_date'])->format('d M Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($alert['latest_date'])->format('d M Y') }}</td>
                        <td class="text-end fw-bold text-danger">
                            Rs {{ number_format($alert['remaining_amount'], 2) }}
                        </td>
                        <td class="text-end text-nowrap">
                            @if($alert['customer'])
                                <a href="{{ route('customers.statement', $alert['customer']) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    Statement
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            No customer payment dues.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title mb-0">
            Supplier Payment Due
        </h3>
    </div>

    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Supplier</th>
                    <th class="text-center">Open Purchases</th>
                    <th>Oldest Due</th>
                    <th>Latest Due</th>
                    <th class="text-end">Still Payable to Supplier</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($supplierPaymentDues as $alert)
                    <tr>
                        <td>{{ $alert['supplier']?->name ?? '-' }}</td>
                        <td class="text-center">
                            <span class="badge bg-dark text-white fs-5 px-3 py-2">
                                {{ $alert['open_purchases'] }}
                            </span>
                        </td>
                        <td>{{ \Carbon\Carbon::parse($alert['oldest_date'])->format('d M Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($alert['latest_date'])->format('d M Y') }}</td>
                        <td class="text-end fw-bold text-danger">
                            Rs {{ number_format($alert['remaining_amount'], 2) }}
                        </td>
                        <td class="text-end text-nowrap">
                            @if($alert['supplier'])
                                <a href="{{ route('supplier.account', $alert['supplier']) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    Supplier Account
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            No supplier payment dues.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
