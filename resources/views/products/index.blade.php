@extends('layouts.app')

@section('content')

@php
    $selectedCategory = (int) ($filters['category_id'] ?? 0);
    $selectedBrand = (int) ($filters['brand_id'] ?? 0);
    $categoryLabel = function ($category) use (&$categoryLabel) {
        return $category->parent
            ? $categoryLabel($category->parent).' / '.$category->name
            : $category->name;
    };
    $totalProducts = $products->count();
    $outOfStockCount = $products->where('stock_quantity', '<=', 0)->count();
    $lowStockCount = $products
        ->filter(fn ($product) => $product->stock_quantity > 0 && $product->stock_quantity <= $product->low_stock_alert)
        ->count();
    $stockValue = $products->sum(fn ($product) => $product->stock_quantity * $product->purchase_price);
@endphp

<div class="page-heading products-page-heading">
    <div>
        <h3 class="mb-1">Products</h3>
        <div class="text-muted">
            Filter products by category, brand, stock condition, and product type.
        </div>
    </div>

    <div class="product-header-actions">
        <div class="product-header-actions-primary">
            <a href="{{ route('products.create') }}"
               class="btn btn-primary">
                <i data-lucide="package-plus"></i>
                Add Product
            </a>
            <a href="{{ route('products.import') }}" class="btn btn-outline-primary">
                <i data-lucide="file-up"></i>
                Import Products
            </a>
        </div>

        <div class="product-header-actions-secondary">
            <a href="{{ route('brands.index') }}" class="btn btn-outline-secondary">
                <i data-lucide="badge-check"></i>
                Brands
            </a>
            <a href="{{ route('product-attributes.index') }}" class="btn btn-outline-secondary">
                <i data-lucide="list-filter"></i>
                Attributes
            </a>
        </div>

        @feature('pro.barcode')
            <span class="btn btn-outline-success disabled">
                <i data-lucide="scan-line"></i>
                Scanner Enabled
            </span>
        @else
            <a href="{{ route('features.unavailable', ['feature' => 'pro.barcode']) }}"
               class="btn btn-outline-secondary">
                <i data-lucide="scan-line"></i>
                Barcode Scanner
            </a>
        @endfeature
    </div>
</div>

<form method="GET" action="{{ route('products.index') }}" class="card product-filter-card mb-3">
    <div class="card-body">
        <div class="product-filter-grid">
            <div class="product-filter-search">
                <label class="form-label" for="product-search">Search</label>
                <div class="input-icon">
                    <span class="input-icon-addon">
                        <i data-lucide="search"></i>
                    </span>
                    <input id="product-search"
                           type="search"
                           name="search"
                           value="{{ $filters['search'] ?? '' }}"
                           class="form-control"
                           placeholder="Name, SKU, barcode, brand, category">
                </div>
            </div>

            <div>
                <label class="form-label" for="product-category-filter">Category</label>
                <select id="product-category-filter" name="category_id" class="form-select">
                    <option value="">All categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected($selectedCategory === $category->id)>
                            {{ $categoryLabel($category) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label" for="product-brand-filter">Brand</label>
                <select id="product-brand-filter" name="brand_id" class="form-select">
                    <option value="">All brands</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->id }}" @selected($selectedBrand === $brand->id)>
                            {{ $brand->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label" for="product-stock-filter">Stock</label>
                <select id="product-stock-filter" name="stock_status" class="form-select">
                    <option value="">All stock</option>
                    <option value="in_stock" @selected(($filters['stock_status'] ?? '') === 'in_stock')>In stock</option>
                    <option value="low_stock" @selected(($filters['stock_status'] ?? '') === 'low_stock')>Low stock</option>
                    <option value="out_of_stock" @selected(($filters['stock_status'] ?? '') === 'out_of_stock')>Out of stock</option>
                </select>
            </div>

            <div>
                <label class="form-label" for="product-type-filter">Product Type</label>
                <select id="product-type-filter" name="product_type" class="form-select">
                    <option value="">All types</option>
                    <option value="simple" @selected(($filters['product_type'] ?? '') === 'simple')>Simple products</option>
                    <option value="variants" @selected(($filters['product_type'] ?? '') === 'variants')>Products with variants</option>
                </select>
            </div>

            <div>
                <label class="form-label" for="product-active-filter">Active Status</label>
                <select id="product-active-filter" name="active_status" class="form-select">
                    <option value="">All statuses</option>
                    <option value="active" @selected(($filters['active_status'] ?? '') === 'active')>Active only</option>
                    <option value="inactive" @selected(($filters['active_status'] ?? '') === 'inactive')>Inactive only</option>
                </select>
            </div>

            <div class="product-filter-actions">
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="filter"></i>
                    Apply Filters
                </button>
                <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">
                    <i data-lucide="rotate-ccw"></i>
                    Reset
                </a>
            </div>
        </div>
    </div>
</form>

<div class="row row-cards mb-3">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Total Products</div>
                <div class="h2 mb-0">{{ number_format($totalProducts) }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Low Stock Items</div>
                <div class="h2 mb-0 text-warning">{{ number_format($lowStockCount) }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Out of Stock</div>
                <div class="h2 mb-0 text-danger">{{ number_format($outOfStockCount) }}</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Stock Value at Cost</div>
                <div class="h2 mb-0">Rs {{ number_format($stockValue, 2) }}</div>
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
                    <th>Category</th>
                    <th>Brand / Variants</th>
                    <th>SKU</th>
                    <th class="text-end">Purchase Price</th>
                    <th class="text-end">Selling Price</th>
                    <th class="text-end">Stock</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($products as $product)
                    @php
                        $isOutOfStock = $product->stock_quantity <= 0;
                        $isLowStock = ! $isOutOfStock && $product->stock_quantity <= $product->low_stock_alert;
                    @endphp

                    <tr>
                        <td>
                            <div class="fw-bold">{{ $product->name }}</div>
                            @if($product->barcode)
                                <div class="text-muted small">Barcode: {{ $product->barcode }}</div>
                            @endif
                        </td>

                        <td>{{ $product->category?->name ?? '-' }}</td>

                        <td>
                            <div>{{ $product->brand?->name ?? 'No brand' }}</div>
                            @if($product->has_variants)
                                <button class="btn btn-sm btn-link px-0"
                                        type="button"
                                        data-variant-toggle="productVariants{{ $product->id }}"
                                        aria-controls="productVariants{{ $product->id }}"
                                        aria-expanded="false">
                                    {{ $product->variants->count() }} {{ Str::plural('variant', $product->variants->count()) }}
                                    <i data-lucide="chevron-down"></i>
                                </button>
                            @else
                                <span class="text-muted small">Simple product</span>
                            @endif
                        </td>

                        <td>{{ $product->sku ?: '-' }}</td>

                        <td class="text-end">
                            Rs {{ number_format($product->purchase_price, 2) }}
                        </td>

                        <td class="text-end">
                            Rs {{ number_format($product->selling_price, 2) }}
                        </td>

                        <td class="text-end fw-bold">
                            {{ number_format($product->stock_quantity) }}
                            @if($product->variants->count() === 1)
                                {{ $product->variants->first()?->unit?->symbol }}
                            @endif
                        </td>

                        <td>
                            @if($isOutOfStock)
                                <span class="badge bg-danger">Out of Stock</span>
                            @elseif($isLowStock)
                                <span class="badge bg-warning">Low Stock</span>
                            @else
                                <span class="badge bg-success">In Stock</span>
                            @endif
                        </td>

                        <td class="text-end">
                            <div class="product-table-actions">
                                <a href="{{ route('products.stock-ledger', $product) }}"
                                   class="btn btn-sm btn-outline-primary text-nowrap">
                                    <i data-lucide="history"></i>
                                    Stock Ledger
                                </a>

                                <a href="{{ route('products.edit', $product) }}"
                                   class="btn btn-sm btn-outline-secondary text-nowrap">
                                    <i data-lucide="pencil"></i>
                                    Edit
                                </a>

                                @if($product->canBeDeleted())
                                    <form action="{{ route('products.destroy', $product) }}"
                                          method="POST"
                                          class="m-0"
                                          onsubmit="return confirm('Delete this product?')">
                                        @csrf
                                        @method('DELETE')

                                        <button class="btn btn-sm btn-outline-danger text-nowrap">
                                            <i data-lucide="trash-2"></i>
                                            Delete
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @if($product->has_variants)
                    <tr id="productVariants{{ $product->id }}" hidden>
                        <td colspan="9" class="bg-light">
                            <div class="table-responsive">
                                <table class="table table-sm table-vcenter mb-0">
                                    <thead>
                                        <tr>
                                            <th>Variant</th>
                                            <th>Options</th>
                                            <th>SKU</th>
                                            <th>Barcode</th>
                                            <th class="text-end">Cost</th>
                                            <th class="text-end">Price</th>
                                            <th class="text-end">Stock</th>
                                            <th>Status</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($product->variants as $variant)
                                            <tr>
                                                <td class="fw-bold">{{ $variant->name ?: 'Default' }}</td>
                                                <td>
                                                    @forelse($variant->attributeValues as $value)
                                                        <span class="badge bg-secondary-lt">{{ $value->attribute?->name }}: {{ $value->value }}</span>
                                                    @empty
                                                        <span class="text-muted">-</span>
                                                    @endforelse
                                                </td>
                                                <td>{{ $variant->sku }}</td>
                                                <td>{{ $variant->barcode ?: '-' }}</td>
                                                <td class="text-end">Rs {{ number_format($variant->purchase_price, 2) }}</td>
                                                <td class="text-end">Rs {{ number_format($variant->selling_price, 2) }}</td>
                                                <td class="text-end fw-bold">{{ number_format($variant->stock_quantity) }} {{ $variant->unit?->symbol }}</td>
                                                <td><span class="badge {{ $variant->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $variant->is_active ? 'Active' : 'Inactive' }}</span></td>
                                                <td class="text-end">
                                                    <a href="{{ route('products.edit', $product).'#variant-'.$variant->id }}"
                                                       class="btn btn-sm btn-outline-secondary">
                                                        <i data-lucide="pencil"></i> Edit
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            No products found. Add your first product to start tracking inventory.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
    document.querySelectorAll('[data-variant-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const row = document.getElementById(button.dataset.variantToggle);
            const isOpening = row.hidden;

            row.hidden = !isOpening;
            button.setAttribute('aria-expanded', String(isOpening));
        });
    });
</script>

@endsection
