@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-1">Expiring Stock Report</h3>
        <div class="text-muted">
            Batches that are expired or will expire within the selected days.
        </div>
    </div>

    <a href="{{ route('alerts.index') }}" class="btn btn-secondary">
        Alerts
    </a>
</div>

<form method="GET" action="{{ route('reports.expiring-stock') }}" class="card mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Show batches expiring within</label>
                <div class="input-group">
                    <input type="number" name="days" class="form-control" min="1" value="{{ $days }}">
                    <span class="input-group-text">days</span>
                </div>
            </div>
            <div class="col-md-auto">
                <button class="btn btn-primary">
                    <i data-lucide="filter"></i>
                    Apply
                </button>
            </div>
        </div>
    </div>
</form>

<div class="row row-cards mb-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Batches Needing Attention</div>
                <div class="h2 mb-0 text-warning">{{ number_format($batches->count()) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Already Expired</div>
                <div class="h2 mb-0 text-danger">{{ number_format($expiredCount) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Batch</th>
                    <th class="text-end">Quantity</th>
                    <th>Expiry Date</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($batches as $batch)
                    @php($isExpired = $batch->expiry_date?->isPast())
                    <tr>
                        <td>
                            @if($isExpired)
                                <span class="badge bg-danger">Expired</span>
                            @else
                                <span class="badge bg-warning">Near Expiry</span>
                            @endif
                        </td>
                        <td>{{ $batch->product?->name ?? '-' }}</td>
                        <td>{{ $batch->product?->category?->name ?? '-' }}</td>
                        <td>{{ $batch->batch_number }}</td>
                        <td class="text-end fw-bold">{{ number_format($batch->quantity, 3) }} {{ $batch->variant?->unit?->symbol }}</td>
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
                        <td colspan="7" class="text-center text-muted py-4">
                            No expired or near-expiry batches found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
