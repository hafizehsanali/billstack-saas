@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-1">Stock Ledger</h3>
        <div class="text-muted">
            {{ $product->name }}
            @if($product->sku)
                · SKU: {{ $product->sku }}
            @endif
        </div>
    </div>

    <a href="{{ route('products.index') }}" class="btn btn-secondary">
        Back
    </a>
</div>

<div class="row row-cards mb-3">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Current Stock</div>
                <div class="h2 mb-0">{{ number_format($product->stock_quantity) }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Category</div>
                <div class="h3 mb-0">{{ $product->category?->name ?? '-' }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Purchase Price</div>
                <div class="h3 mb-0">Rs {{ number_format($product->purchase_price, 2) }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Selling Price</div>
                <div class="h3 mb-0">Rs {{ number_format($product->selling_price, 2) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Movement History</h3>
    </div>

    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Reference</th>
                    <th>Movement</th>
                    <th>Direction</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">Unit Cost</th>
                    <th class="text-end">Unit Price</th>
                    <th class="text-end">Stock After</th>
                    <th>Notes</th>
                </tr>
            </thead>

            <tbody>
                @forelse($movements as $movement)
                    <tr>
                        <td>
                            {{ optional($movement->movement_date)->format('d M Y') ?? '-' }}
                        </td>

                        <td>
                            {{ $movement->reference_no ?? '-' }}
                        </td>

                        <td>
                            {{ str($movement->type)->replace('_', ' ')->title() }}
                        </td>

                        <td>
                            <span class="badge {{ $movement->direction === 'in' ? 'bg-green' : 'bg-red' }}">
                                {{ strtoupper($movement->direction) }}
                            </span>
                        </td>

                        <td class="text-end">
                            {{ number_format($movement->quantity, 3) }}
                        </td>

                        <td class="text-end">
                            {{ $movement->unit_cost !== null ? 'Rs ' . number_format($movement->unit_cost, 2) : '-' }}
                        </td>

                        <td class="text-end">
                            {{ $movement->unit_price !== null ? 'Rs ' . number_format($movement->unit_price, 2) : '-' }}
                        </td>

                        <td class="text-end">
                            {{ number_format($movement->stock_after) }}
                        </td>

                        <td>
                            {{ $movement->notes ?? '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            No stock movements recorded for this product yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($movements->hasPages())
        <div class="card-footer">
            {{ $movements->links() }}
        </div>
    @endif
</div>

@endsection
