@extends('layouts.app')

@section('content')
<div class="page-heading">
    <div>
        <h3 class="mb-1">Import Products</h3>
        <div class="text-muted">Bring an existing product list into Zephrant ERP using a CSV file.</div>
    </div>
    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">
        <i data-lucide="arrow-left"></i>
        Products
    </a>
</div>

<div class="product-import-layout">
    <section class="card">
        <div class="card-header">
            <h4 class="card-title">Upload Product File</h4>
        </div>
        <div class="card-body">
            <form method="POST"
                  action="{{ route('products.import.preview') }}"
                  enctype="multipart/form-data">
                @csrf
                <label class="product-import-dropzone">
                    <input type="file"
                           name="csv_file"
                           accept=".csv,text/csv"
                           required>
                    <i data-lucide="file-up"></i>
                    <strong>Select a CSV file</strong>
                    <span>Maximum 5 MB and 1,000 product rows per import.</span>
                </label>
                @error('csv_file')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror

                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button class="btn btn-primary">
                        <i data-lucide="table-2"></i>
                        Review File
                    </button>
                    <a href="{{ route('products.import.template') }}" class="btn btn-outline-primary">
                        <i data-lucide="download"></i>
                        Download CSV Template
                    </a>
                </div>
            </form>
        </div>
    </section>

    <aside class="card">
        <div class="card-header">
            <h4 class="card-title">Before You Import</h4>
        </div>
        <div class="card-body product-import-checklist">
            <div><i data-lucide="check"></i><span>Every product needs a unique SKU.</span></div>
            <div><i data-lucide="check"></i><span>Use Parent Category, Category, and Subcategory to preserve your catalog structure.</span></div>
            <div><i data-lucide="check"></i><span>Mixed stock is supported, for example: 3 Bags + 10 KG.</span></div>
            <div><i data-lucide="check"></i><span>Use the same Product Group SKU on multiple rows to create product variants.</span></div>
            <div><i data-lucide="check"></i><span>Missing categories, brands, and units can be created automatically.</span></div>
            <div><i data-lucide="check"></i><span>Invalid rows are skipped and included in an error report.</span></div>
        </div>
    </aside>
</div>
@endsection
