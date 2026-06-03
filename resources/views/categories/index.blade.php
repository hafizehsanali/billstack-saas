@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-1">Categories</h3>
        <div class="text-muted">
            Product groups used for inventory organization and reporting.
        </div>
    </div>

    <a href="{{ route('categories.create') }}"
       class="btn btn-primary">
        Add Category
    </a>
</div>

<div class="row row-cards mb-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Total Categories</div>
                <div class="h2 mb-0">{{ number_format($categories->count()) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th class="text-end">Products</th>
                </tr>
            </thead>

            <tbody>
                @forelse($categories as $category)
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $category->name }}</div>
                        </td>
                        <td class="text-end">
                            {{ number_format($category->products_count ?? 0) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="text-center text-muted py-4">
                            No categories found. Add categories to organize products for reports and stock review.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
