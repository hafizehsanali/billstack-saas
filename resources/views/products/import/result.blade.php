@extends('layouts.app')

@section('content')
<div class="page-heading">
    <div>
        <h3 class="mb-1">Import Complete</h3>
        <div class="text-muted">Your product file has been processed.</div>
    </div>
    <a href="{{ route('products.index') }}" class="btn btn-primary">
        <i data-lucide="package-search"></i>
        View Products
    </a>
</div>

<div class="row row-cards mb-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Products Imported</div>
                <div class="h1 mb-0 text-success">{{ number_format($result['imported']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Rows Rejected</div>
                <div class="h1 mb-0 {{ $result['failed'] ? 'text-danger' : 'text-success' }}">
                    {{ number_format($result['failed']) }}
                </div>
            </div>
        </div>
    </div>
</div>

@if($result['errors'])
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title">Rows Requiring Attention</h4>
            <a href="{{ route('products.import.errors', $errorToken) }}" class="btn btn-outline-danger">
                <i data-lucide="download"></i>
                Download Error Report
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead><tr><th>Line</th><th>SKU</th><th>Product</th><th>Error</th></tr></thead>
                <tbody>
                    @foreach($result['errors'] as $error)
                        <tr>
                            <td>{{ $error['line'] }}</td>
                            <td>{{ $error['sku'] ?: '-' }}</td>
                            <td>{{ $error['product_name'] ?: '-' }}</td>
                            <td class="text-danger">{{ $error['error'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="alert alert-success">
        All rows were imported successfully.
    </div>
@endif

<div class="d-flex gap-2 mt-3">
    <a href="{{ route('products.import') }}" class="btn btn-outline-primary">
        <i data-lucide="upload"></i>
        Import Another File
    </a>
</div>
@endsection
