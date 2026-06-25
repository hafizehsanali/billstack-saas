@php
    $editing = isset($product);
    $variantRows = old('variants');

    if ($variantRows === null) {
        $variantRows = $editing
            ? $product->variants->map(fn ($variant) => [
                'id' => $variant->id,
                'name' => $variant->name,
                'sku' => $variant->sku,
                'barcode' => $variant->barcode,
                'unit_id' => $variant->unit_id,
                'purchase_unit_id' => $variant->purchase_unit_id,
                'purchase_unit_factor' => $variant->purchase_unit_factor,
                'conversion_to_base_unit' => $variant->conversion_to_base_unit,
                'purchase_price' => $variant->purchase_unit_price ?? $variant->purchase_price,
                'selling_price' => $variant->selling_price,
                'compare_at_price' => $variant->compare_at_price,
                'stock_quantity' => $variant->stock_quantity,
                'low_stock_alert' => $variant->low_stock_alert,
                'track_stock' => $variant->track_stock,
                'is_active' => $variant->is_active,
                'attribute_value_ids' => $variant->attributeValues->pluck('id')->all(),
            ])->values()->all()
            : [[
                'name' => 'Default',
                'sku' => old('sku', ''),
                'barcode' => old('barcode', ''),
                'unit_id' => $units->first()?->id,
                'purchase_unit_id' => $units->first()?->id,
                'purchase_unit_factor' => 1,
                'conversion_to_base_unit' => 1,
                'purchase_price' => old('purchase_price', 0),
                'selling_price' => old('selling_price', 0),
                'compare_at_price' => null,
                'stock_quantity' => old('stock_quantity', 0),
                'low_stock_alert' => old('low_stock_alert', 5),
                'track_stock' => true,
                'is_active' => true,
                'attribute_value_ids' => [],
            ]];
    }

    $startsWithVariants = count($variantRows) > 1;
    $selectedCategoryId = old('category_id', $product->category_id ?? null);
    $selectedCategory = $categories->firstWhere('id', $selectedCategoryId);
    $selectedBrandId = old('brand_id', $product->brand_id ?? null);
    $selectedBrand = $brands->firstWhere('id', $selectedBrandId);
    $selectedSaleMode = old('product_sale_mode', $product->product_sale_mode ?? \App\Models\Product::SALE_MODE_PACKED);
    $productModeOptions = $productModeOptions ?? [
        \App\Models\Product::SALE_MODE_LOOSE => 'Loose item',
        \App\Models\Product::SALE_MODE_PACKED => 'Packed item',
        \App\Models\Product::SALE_MODE_SERVICE => 'Service',
    ];
    $enabledModuleKeys = $enabledModuleKeys ?? [];
    $canUseBatchExpiry = in_array(\App\Models\BusinessModule::BATCH_EXPIRY, $enabledModuleKeys, true)
        || ($editing && (($product->track_batch ?? false) || ($product->track_expiry ?? false)));
    $canUseSerialTracking = in_array(\App\Models\BusinessModule::SERIAL_WARRANTY, $enabledModuleKeys, true)
        || ($editing && ($product->track_serial ?? false));
    $selectedBaseStockUnitId = old('base_stock_unit_id', $product->base_stock_unit_id ?? null);
    $selectedDefaultPurchaseUnitId = old('default_purchase_unit_id', $product->default_purchase_unit_id ?? null);
    $catalogAttributes = $attributes->map(function ($attribute) {
        return [
            'id' => $attribute->id,
            'name' => $attribute->name,
            'values' => $attribute->values->map(function ($value) {
                return [
                    'id' => $value->id,
                    'value' => $value->value,
                ];
            })->values()->all(),
            'can_delete' => $attribute->values->every(
                fn ($value) => $value->variants()->doesntExist()
            ),
        ];
    })->values()->all();
    $catalogCategories = $categories->map(function ($category) {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'parent_id' => $category->parent_id,
        ];
    })->values()->all();
    $catalogBrands = $brands->map(function ($brand) {
        return [
            'id' => $brand->id,
            'name' => $brand->name,
            'description' => $brand->description,
            'is_active' => $brand->is_active,
        ];
    })->values()->all();
@endphp

<form method="POST" action="{{ $action }}" id="product-form" class="product-editor" enctype="multipart/form-data">
    @csrf
    @if($method !== 'POST') @method($method) @endif

    <input type="hidden" name="sku" id="legacy-sku">
    <input type="hidden" name="barcode" id="legacy-barcode">
    <input type="hidden" name="purchase_price" id="legacy-purchase-price">
    <input type="hidden" name="selling_price" id="legacy-selling-price">
    <input type="hidden" name="stock_quantity" id="legacy-stock">
    <input type="hidden" name="low_stock_alert" id="legacy-alert">
    <input type="hidden"
           name="is_online_enabled"
           value="{{ old('is_online_enabled', $product->is_online_enabled ?? false) ? 1 : 0 }}">

    <div class="product-editor-heading">
        <div>
            <a href="{{ route('products.index') }}" class="product-editor-back">
                <i data-lucide="arrow-left"></i>
                Products
            </a>
            <h2>{{ $title }}</h2>
            <p>Set up product identity, pricing, inventory, and sellable options.</p>
        </div>
        <button type="button" class="btn btn-icon btn-ghost-secondary" onclick="window.location='{{ route('products.index') }}'" title="Close">
            <i data-lucide="x"></i>
        </button>
    </div>

    <div class="product-form-layout">
        <div class="product-form-main">
    <div class="product-editor-surface">
        <section class="product-editor-section product-editor-intro">
            <div class="product-static-section-heading">
                <span class="product-section-icon"><i data-lucide="package"></i></span>
                <span>
                    <strong>Basic Information</strong>
                    <small>Product identity, classification, description, and images.</small>
                </span>
            </div>

            <div class="product-static-section-body" id="product-details-panel">
            <div class="product-form-grid product-form-grid-intro">
                <div class="product-field product-field-wide">
                    <label class="form-label">Product Name <span class="text-danger">*</span></label>
                    <input name="name"
                           data-product-name
                           value="{{ old('name', $product->name ?? '') }}"
                           class="form-control @error('name') is-invalid @enderror"
                           placeholder="Enter product name"
                           required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="product-field">
                    <label class="form-label">Category <span class="text-danger">*</span></label>
                    <div class="catalog-select @error('category_id') is-invalid @enderror" data-category-select>
                        <input type="hidden" name="category_id" value="{{ $selectedCategoryId }}" data-category-value>
                        <button type="button"
                                class="catalog-select-trigger"
                                data-category-trigger
                                aria-haspopup="listbox"
                                aria-expanded="false">
                            <span data-category-label>{{ $selectedCategory?->name ?? 'Select a category' }}</span>
                            <i data-lucide="chevron-down"></i>
                        </button>
                        <div class="catalog-select-menu" data-category-menu hidden>
                            <div class="catalog-select-search">
                                <i data-lucide="search"></i>
                                <input type="search"
                                       placeholder="Search categories"
                                       autocomplete="off"
                                       aria-label="Search categories"
                                       data-category-search>
                            </div>
                            <div class="catalog-select-options" role="listbox">
                                @forelse($categories as $category)
                                    <button type="button"
                                            class="catalog-select-option @if((string) $selectedCategoryId === (string) $category->id) is-selected @endif"
                                            role="option"
                                            aria-selected="{{ (string) $selectedCategoryId === (string) $category->id ? 'true' : 'false' }}"
                                            data-category-option
                                            data-category-id="{{ $category->id }}"
                                            data-category-parent-id="{{ $category->parent_id }}"
                                            data-category-name="{{ $category->name }}">
                                        <span>{{ $category->name }}</span>
                                        <i data-lucide="check"></i>
                                    </button>
                                @empty
                                    <div class="catalog-select-empty">No categories available.</div>
                                @endforelse
                                <div class="catalog-select-empty" data-category-no-results hidden>No matching categories.</div>
                            </div>
                            <button type="button" class="catalog-select-manage" data-manage-categories>
                                <i data-lucide="settings-2"></i>
                                Manage Categories
                            </button>
                        </div>
                    </div>
                    @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="product-field">
                    <label class="form-label">Brand</label>
                    <div class="catalog-select" data-brand-select>
                        <input type="hidden" name="brand_id" value="{{ $selectedBrandId }}" data-brand-value>
                        <button type="button"
                                class="catalog-select-trigger"
                                data-brand-trigger
                                aria-haspopup="listbox"
                                aria-expanded="false">
                            <span data-brand-label>{{ $selectedBrand?->name ?? 'No brand' }}</span>
                            <i data-lucide="chevron-down"></i>
                        </button>
                        <div class="catalog-select-menu" data-brand-menu hidden>
                            <div class="catalog-select-search">
                                <i data-lucide="search"></i>
                                <input type="search"
                                       placeholder="Search brands"
                                       autocomplete="off"
                                       aria-label="Search brands"
                                       data-brand-search>
                            </div>
                            <div class="catalog-select-options" role="listbox" data-brand-options>
                                <button type="button"
                                        class="catalog-select-option @if(!$selectedBrandId) is-selected @endif"
                                        data-brand-option
                                        data-brand-id=""
                                        data-brand-name="No brand"
                                        role="option">
                                    <span>No brand</span>
                                    <i data-lucide="check"></i>
                                </button>
                                <div class="catalog-select-empty" data-brand-no-results hidden>No matching brands.</div>
                            </div>
                            <button type="button" class="catalog-select-manage" data-manage-brands>
                                <i data-lucide="settings-2"></i>
                                Manage Brands
                            </button>
                        </div>
                    </div>
                </div>

                <div class="product-field product-field-wide">
                    <label class="form-label">Description</label>
                    <textarea name="description"
                              rows="4"
                              class="form-control"
                              placeholder="Product details visible to your team and future sales channels">{{ old('description', $product->description ?? '') }}</textarea>
                </div>

                <div class="product-field">
                    <label class="form-label">Product Sale Mode <span class="text-danger">*</span></label>
                    <select name="product_sale_mode" class="form-select" data-product-sale-mode required>
                        @foreach($productModeOptions as $mode => $label)
                            <option value="{{ $mode }}" @selected($selectedSaleMode === $mode)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    <small class="form-hint" data-sale-mode-help>
                        Choose how this product is sold and tracked across purchases, invoices, and stock.
                    </small>
                </div>

                <div class="product-field">
                    <label class="form-label">Base Stock Unit</label>
                    <select name="base_stock_unit_id" class="form-select">
                        <option value="">Use variant sale unit</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" @selected((string) $selectedBaseStockUnitId === (string) $unit->id)>
                                {{ $unit->name }} ({{ $unit->symbol }})
                            </option>
                        @endforeach
                    </select>
                    <small class="form-hint">Useful for loose and hybrid products, for example KG for almonds.</small>
                </div>

                <div class="product-field">
                    <label class="form-label">Default Supplier Unit</label>
                    <select name="default_purchase_unit_id" class="form-select">
                        <option value="">Use variant supplier unit</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" @selected((string) $selectedDefaultPurchaseUnitId === (string) $unit->id)>
                                {{ $unit->name }} ({{ $unit->symbol }})
                            </option>
                        @endforeach
                    </select>
                    <small class="form-hint">Example: Bag, Carton, Box, Tray.</small>
                </div>

                <div class="product-field">
                    <label class="form-label">Default Units Per Supplier Unit</label>
                    <input type="number"
                           name="default_purchase_unit_factor"
                           class="form-control"
                           min="0.001"
                           step="0.001"
                           value="{{ old('default_purchase_unit_factor', $product->default_purchase_unit_factor ?? '') }}"
                           placeholder="Example: 50">
                </div>

                <div class="product-field product-field-wide product-mode-options">
                    <label class="product-feature-toggle">
                        <input type="checkbox" name="allow_loose_sale" value="1" @checked(old('allow_loose_sale', $product->allow_loose_sale ?? false))>
                        <span>
                            <strong>Allow loose sale</strong>
                            <small>Use this when customers can buy partial quantity, for example 0.250 KG.</small>
                        </span>
                    </label>
                    @if($canUseBatchExpiry)
                        <label class="product-feature-toggle">
                            <input type="checkbox" name="track_batch" value="1" @checked(old('track_batch', $product->track_batch ?? false))>
                            <span>
                                <strong>Track batch number</strong>
                                <small>For products where each purchase lot should be traceable.</small>
                            </span>
                        </label>
                        <label class="product-feature-toggle">
                            <input type="checkbox" name="track_expiry" value="1" @checked(old('track_expiry', $product->track_expiry ?? false))>
                            <span>
                                <strong>Track expiry date</strong>
                                <small>For medicine, dairy, cosmetics, baby formula, and food items.</small>
                            </span>
                        </label>
                    @endif
                    @if($canUseSerialTracking)
                        <label class="product-feature-toggle">
                            <input type="checkbox" name="track_serial" value="1" @checked(old('track_serial', $product->track_serial ?? false))>
                            <span>
                                <strong>Track serial numbers</strong>
                                <small>For mobiles, electronics, warranty, and unique-unit products.</small>
                            </span>
                        </label>
                    @endif
                </div>

            </div>
            </div>
        </section>

        <section class="product-editor-section product-image-section">
            <div class="product-static-section-heading">
                <span class="product-section-icon"><i data-lucide="image"></i></span>
                <span>
                    <strong>Product Image</strong>
                    <small>Add a clear image for product lists, invoices, and future online sales.</small>
                </span>
            </div>
            <div class="product-static-section-body" id="product-image-panel">
                <div class="product-image-panel">
                    <label class="product-image-dropzone" data-image-dropzone>
                        <input type="file" name="images[]" accept=".jpg,.jpeg,.png,.webp" data-product-image>
                        <span class="product-image-preview" data-image-preview>
                            @if($editing && $product->images->isNotEmpty())
                                <img src="{{ Storage::disk('public')->url($product->images->first()->path) }}"
                                     alt="{{ $product->images->first()->alt_text ?: $product->name }}">
                            @else
                                <i data-lucide="upload-cloud"></i>
                            @endif
                        </span>
                        <strong data-image-action>{{ $editing && $product->images->isNotEmpty() ? 'Replace image' : 'Drop an image here or click to upload' }}</strong>
                        <span>JPG, PNG or WebP. Maximum 5 MB.</span>
                    </label>
                    @error('images.*')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
            </div>
        </section>

        <section class="product-editor-section product-accordion-section is-open" data-product-accordion>
            <button type="button"
                    class="product-accordion-trigger"
                    data-product-accordion-trigger
                    aria-expanded="true"
                    aria-controls="item-details-panel">
                <span class="product-section-icon"><i data-lucide="boxes"></i></span>
                <span>
                    <strong>Inventory & Pricing</strong>
                    <small>Choose how the product is identified, purchased, sold, priced, and counted.</small>
                </span>
                <i data-lucide="chevron-down" class="product-accordion-chevron"></i>
            </button>

            <div class="product-accordion-panel" id="item-details-panel" data-product-accordion-panel>
            <div id="variant-workspace">
                <div class="product-mode-row product-mode-row-inline">
                    <label class="product-feature-toggle">
                        <input type="checkbox" data-has-variants @checked($startsWithVariants)>
                        <span>
                            <strong>This product has variants</strong>
                            <small>Use this for options such as size, color, flavor, or packing.</small>
                        </span>
                    </label>
                </div>

                <div class="product-form-grid product-identifier-row">
                    <div class="product-field">
                        <label class="form-label">SKU <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control"
                               value="{{ $variantRows[0]['sku'] ?? '' }}"
                               placeholder="Example: RICE-BAS-01"
                               data-single-sku
                               required>
                    </div>

                    <div class="product-field">
                        <label class="form-label">Barcode</label>
                        <input type="text"
                               class="form-control"
                               value="{{ $variantRows[0]['barcode'] ?? '' }}"
                               placeholder="Scan or enter barcode"
                               data-single-barcode>
                    </div>
                </div>

                <div id="variation-manager" class="variation-manager" hidden>
                    <div class="variation-manager-heading">
                        <div>
                            <h4>Variations</h4>
                            <p>Select attributes and options to generate product combinations.</p>
                        </div>
                    </div>
                    <div class="variation-attribute-header" aria-hidden="true">
                        <span>Attribute</span>
                        <span>Options</span>
                        <span>Action</span>
                    </div>
                    <div id="variation-attribute-rows" class="variation-attribute-rows"></div>
                    <div class="variation-attribute-actions">
                        <button type="button"
                                class="btn btn-sm btn-outline-primary variation-add-attribute"
                                id="add-attribute-row">
                            <i data-lucide="plus"></i>
                            <span data-add-attribute-label>Add Attribute</span>
                        </button>
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary"
                                data-manage-attributes>
                            <i data-lucide="settings-2"></i>
                            Manage Attributes
                        </button>
                    </div>
                    <div class="variation-manager-note" id="variation-manager-note">
                        Select at least one attribute and option to generate variants.
                    </div>
                </div>

                    <div class="variant-table-scroll">
                    <div id="variant-table-header" class="variant-table-header" hidden>
                        <span>Variant Name</span>
                        <span>SKU</span>
                        <span>Barcode</span>
                        <span>Action</span>
                    </div>
                    <div id="variant-body" class="variant-editor-list"></div>
                </div>
            </div>
            </div>
        </section>

        <section class="product-editor-section product-accordion-section" data-product-accordion>
            <button type="button"
                    class="product-accordion-trigger"
                    data-product-accordion-trigger
                    aria-expanded="false"
                    aria-controls="wholesale-panel">
                <span class="product-section-icon"><i data-lucide="badge-percent"></i></span>
                <span>
                    <strong>Wholesale / Bulk Pricing</strong>
                    <small>Offer a lower price when customers buy a larger quantity.</small>
                </span>
                <i data-lucide="chevron-down" class="product-accordion-chevron"></i>
            </button>
            <div class="product-accordion-panel" id="wholesale-panel" data-product-accordion-panel hidden>
                <div class="bulk-price-table">
                    <div class="bulk-price-header">
                        <span data-tier-from-label>From Unit</span>
                        <span data-tier-price-label>Price Per Unit</span>
                        <span>Action</span>
                    </div>
                    <div data-price-tiers></div>
                </div>
                <button type="button" class="btn btn-outline-primary mt-3" data-add-price-tier>
                    <i data-lucide="plus"></i>
                    Add Price Tier
                </button>
                <p class="form-hint mt-2">Bulk prices are prepared in this form but will be saved after the wholesale module is connected.</p>
            </div>
        </section>

        <section class="product-editor-section product-accordion-section" data-product-accordion>
            <button type="button"
                    class="product-accordion-trigger"
                    data-product-accordion-trigger
                    aria-expanded="false"
                    aria-controls="availability-panel">
                <span class="product-section-icon"><i data-lucide="sliders-horizontal"></i></span>
                <span>
                    <strong>Product Availability</strong>
                    <small>Control whether this product can be used in business transactions.</small>
                </span>
                <i data-lucide="chevron-down" class="product-accordion-chevron"></i>
            </button>
            <div class="product-accordion-panel" id="availability-panel" data-product-accordion-panel hidden>
                <div class="product-availability-options">
                    <label class="form-check form-switch">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" @checked(old('is_active', $product->is_active ?? true))>
                        <span>
                            <strong>Active in inventory</strong>
                            <small>Allow this product in purchasing, POS, invoices, and stock reports.</small>
                        </span>
                    </label>
                </div>
            </div>
        </section>
    </div>

        </div>

        <aside class="product-summary-column">
            <div class="product-summary-card">
                <div class="product-summary-heading">
                    <span class="product-section-icon"><i data-lucide="clipboard-list"></i></span>
                    <div>
                        <h3>Product Summary</h3>
                        <p>Live overview of the item being configured.</p>
                    </div>
                </div>
                <dl class="product-summary-list">
                    <div><dt>Product</dt><dd data-summary-product>Not entered</dd></div>
                    <div><dt>Customer Unit</dt><dd data-summary-customer-unit>Not selected</dd></div>
                    <div><dt>Supplier Unit</dt><dd data-summary-supplier-unit>Not selected</dd></div>
                    <div><dt>Conversion</dt><dd data-summary-conversion>Not required</dd></div>
                    <div><dt>Purchase Price</dt><dd data-summary-purchase>PKR 0.00</dd></div>
                    <div><dt>Selling Price</dt><dd data-summary-selling>PKR 0.00</dd></div>
                </dl>
                <div class="product-stock-preview">
                    <span>Supplier quantity preview</span>
                    <strong data-summary-stock-preview>2 units = 2 units in stock</strong>
                </div>
                <div class="product-summary-status">
                    <i data-lucide="info"></i>
                    <span data-summary-status>Complete the required fields before saving.</span>
                </div>
            </div>
        </aside>

        <div class="product-editor-actions">
            <button class="btn btn-primary">
                <i data-lucide="save"></i>
                {{ $editing ? 'Update Product' : 'Save Product' }}
            </button>
            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </div>
</form>

<template id="variation-attribute-template">
    <div class="variation-attribute-row">
        <div class="product-field">
            <select class="form-select variation-attribute-select">
                <option value="">Select attribute</option>
            </select>
        </div>
        <div class="product-field">
            <div class="variation-option-checklist" role="group" aria-label="Attribute options">
                <span class="variation-option-placeholder">Select an attribute first.</span>
            </div>
        </div>
        <button type="button"
                class="btn btn-icon btn-outline-danger remove-attribute-row"
                title="Remove attribute"
                aria-label="Remove attribute">
            <i data-lucide="trash-2"></i>
        </button>
    </div>
</template>

<template id="variant-template">
    <article class="variant-editor">
                <input type="hidden" data-field="id">
                <input type="hidden" data-field="track_stock" value="1">

        <div class="variant-editor-header">
            <button type="button"
                    class="variant-collapse-trigger"
                    data-variant-collapse
                    aria-expanded="true">
                <span>
                    <small class="variant-number">Variant</small>
                    <strong class="variant-summary">Default item</strong>
                </span>
                <i data-lucide="chevron-down" class="variant-collapse-chevron"></i>
            </button>
        </div>

        <div class="variant-editor-fields" data-variant-fields>
            <div class="product-field variant-name-field">
                <label class="form-label">Variant Name</label>
                <input data-field="name" class="form-control" placeholder="Black / Large">
            </div>
            <div class="product-field variant-barcode-field">
                <label class="form-label">Barcode</label>
                <input data-field="barcode" class="form-control" placeholder="Scan or enter">
            </div>
            <div class="product-field variant-sku-field">
                <label class="form-label">SKU <span class="text-danger">*</span></label>
                <input data-field="sku" class="form-control" placeholder="Unique stock code" required>
            </div>
            <div class="variant-field-section variant-unit-section">
                <h5 class="variant-field-section-title">
                    <i data-lucide="scale"></i>
                    Inventory & Units
                </h5>
                <div class="variant-field-section-grid">
            <div class="product-field customer-unit-field">
                <label class="form-label product-unit-label">
                    <span>Customer Unit <span class="text-danger">*</span></span>
                    <span class="product-unit-purpose">We sell in this unit</span>
                    <span class="product-label-help"
                          title="Choose the unit customers normally buy, such as Piece, KG, or Liter."
                          aria-label="Customer Unit help">
                        <i data-lucide="info"></i>
                    </span>
                </label>
                <select class="form-select" data-field="unit_id" data-unit-select data-customer-unit required>
                    <option value="">Select unit</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}"
                                data-unit-name="{{ $unit->name }}"
                                data-unit-symbol="{{ $unit->symbol }}">
                            {{ $unit->name }} ({{ $unit->symbol }})
                        </option>
                    @endforeach
                    <option disabled>----------------</option>
                    <option value="__add_unit__">Manage Units</option>
                </select>
                <small class="form-hint">The unit customers normally buy, such as Piece, KG, or Liter.</small>
            </div>
            <div class="product-field supplier-unit-field">
                <label class="form-label product-unit-label">
                    <span>Supplier Unit <span class="text-danger">*</span></span>
                    <span class="product-unit-purpose">We buy in this unit</span>
                    <span class="product-label-help"
                          title="Choose the package or unit received from the supplier, such as Box, Carton, Bag, or Tray."
                          aria-label="Supplier Unit help">
                        <i data-lucide="info"></i>
                    </span>
                </label>
                <select class="form-select" data-field="purchase_unit_id" data-unit-select data-supplier-unit required>
                    <option value="">Select unit</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}"
                                data-unit-name="{{ $unit->name }}"
                                data-unit-symbol="{{ $unit->symbol }}">
                            {{ $unit->name }} ({{ $unit->symbol }})
                        </option>
                    @endforeach
                    <option disabled>----------------</option>
                    <option value="__add_unit__">Manage Units</option>
                </select>
                <small class="form-hint">The package or unit received from your supplier.</small>
            </div>
            <div class="product-unit-conversion" data-conversion-panel>
                <label class="form-label" data-conversion-title>1 Supplier Unit Equals</label>
                <div class="input-group">
                    <input type="number" data-field="purchase_unit_factor" class="form-control" min="0.001" step="0.001" value="1">
                    <span class="input-group-text" data-conversion-customer-unit>Customer Unit</span>
                </div>
                <small class="form-hint" data-conversion-help>Enter how many customer units are inside one supplier unit.</small>
            </div>
            <div class="product-field">
                <label class="form-label">Variant Conversion to Base Unit</label>
                <input type="number"
                       data-field="conversion_to_base_unit"
                       class="form-control"
                       min="0.000001"
                       step="0.000001"
                       value="1"
                       placeholder="Example: 0.25">
                <small class="form-hint">For hybrid products, example: 250g pack = 0.25 KG.</small>
            </div>
            <div class="product-unit-same-message" data-same-unit-message hidden>
                <i data-lucide="circle-check"></i>
                <span><strong>Customer and supplier unit are the same.</strong> No conversion is required.</span>
            </div>
                </div>
            </div>
            <div class="variant-field-section variant-purchase-section">
                <h5 class="variant-field-section-title">
                    <i data-lucide="shopping-bag"></i>
                    Purchase Information
                </h5>
                <div class="variant-field-section-grid">
                    <div class="product-field">
                        <label class="form-label"><span data-purchase-price-label>Purchase Price</span> <span class="text-danger">*</span></label>
                        <input type="number" data-field="purchase_price" class="form-control" min="0" step="0.01" required>
                    </div>
                    <div class="product-field">
                        <label class="form-label">Tax Class</label>
                        <select class="form-select" data-tax-class>
                            <option value="">No tax</option>
                            <option>Standard Tax</option>
                            <option>Reduced Tax</option>
                            <option>Tax Exempt</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="variant-field-section variant-sales-section">
                <h5 class="variant-field-section-title">
                    <i data-lucide="badge-dollar-sign"></i>
                    Sales Information
                </h5>
                <div class="variant-field-section-grid">
                    <div class="product-field">
                        <label class="form-label"><span data-selling-price-label>Selling Price</span> <span class="text-danger">*</span></label>
                        <input type="number" data-field="selling_price" class="form-control" min="0" step="0.01" required>
                    </div>
                    <div class="product-field">
                        <label class="form-label">Regular Price</label>
                        <input type="number" data-field="compare_at_price" class="form-control" min="0" step="0.01" placeholder="Optional">
                        <small class="form-hint">When higher than the selling price, the invoice shows the customer's savings.</small>
                    </div>
                    <div class="product-field">
                        <label class="form-label">Status</label>
                        <select class="form-select" data-field="is_active">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="variant-field-section variant-inventory-section">
                <h5 class="variant-field-section-title">
                    <i data-lucide="warehouse"></i>
                    Inventory
                </h5>
                <div class="variant-field-section-grid">
                    <div class="product-field">
                        <label class="form-label"><span data-opening-stock-label>Opening Stock</span> <span class="text-danger">*</span></label>
                        <input type="number"
                               class="form-control"
                               min="0"
                               step="0.001"
                               data-supplier-stock
                               required>
                        <input type="hidden" data-field="stock_quantity">
                        <small class="form-hint" data-opening-stock-help>Enter stock in the supplier unit.</small>
                        <div class="stock-conversion-preview" data-stock-conversion-preview>
                            Select customer and supplier units to preview converted stock.
                        </div>
                    </div>
                    <div class="product-field">
                        <label class="form-label">Low Stock Alert <span class="text-danger">*</span></label>
                        <input type="number" data-field="low_stock_alert" class="form-control" min="0" required>
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-outline-danger remove-variant" title="Remove variant">
                <i data-lucide="trash-2"></i>
                <span>Remove</span>
            </button>
            <div class="product-field attribute-field visually-hidden">
                <select data-field="attribute_value_ids" class="form-select" multiple>
                    @foreach($attributes as $attribute)
                        <optgroup label="{{ $attribute->name }}">
                            @foreach($attribute->values as $value)
                                <option value="{{ $value->id }}">{{ $value->value }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
        </div>
    </article>
</template>

<div class="modal fade" id="category-manager-modal" tabindex="-1" aria-labelledby="category-manager-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl category-manager-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="category-manager-title">Manage Categories</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body category-manager">
                <form id="category-manager-form" class="category-manager-form" hidden>
                    <input type="hidden" data-editing-category-id>
                    <div class="category-manager-form-fields">
                        <div class="category-manager-form-label">
                            <strong data-category-form-title>Add New Category</strong>
                            <small>Use a parent to create a subcategory.</small>
                        </div>
                        <div>
                            <label for="category-manager-name" class="form-label">
                                Category Name <span class="text-danger">*</span>
                            </label>
                            <input id="category-manager-name"
                                   name="name"
                                   class="form-control"
                                   placeholder="Example: Power Tools"
                                   required>
                        </div>
                        <div>
                            <label for="category-manager-parent" class="form-label">Parent Category</label>
                            <select id="category-manager-parent" name="parent_id" class="form-select">
                                <option value="">No parent (top-level category)</option>
                            </select>
                        </div>
                    </div>
                    <div id="category-manager-form-error" class="text-danger small mt-3" hidden></div>
                    <div class="category-manager-form-actions">
                        <button type="submit" class="btn btn-primary" data-category-submit>Save</button>
                        <button type="button" class="btn btn-outline-secondary" data-reset-category-form>Cancel</button>
                    </div>
                </form>

                <section class="category-manager-list">
                    <div class="category-manager-list-heading">
                        <strong>Categories</strong>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-new-category>
                            <i data-lucide="plus"></i>
                            New Category
                        </button>
                    </div>
                    <div id="category-manager-message" class="alert py-2 px-3 small mt-3" role="alert" hidden></div>
                    <div class="category-manager-items" data-category-manager-items></div>
                </section>
            </div>
            <div class="modal-footer category-manager-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="brand-manager-modal" tabindex="-1" aria-labelledby="brand-manager-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl category-manager-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="brand-manager-title">Manage Brands</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body category-manager">
                <form id="brand-manager-form" class="category-manager-form" hidden>
                    <input type="hidden" data-editing-brand-id>
                    <div class="brand-manager-form-fields">
                        <div class="category-manager-form-label">
                            <strong data-brand-form-title>Add New Brand</strong>
                            <small>Add a manufacturer or store brand.</small>
                        </div>
                        <div>
                            <label for="brand-manager-name" class="form-label">
                                Brand Name <span class="text-danger">*</span>
                            </label>
                            <input id="brand-manager-name"
                                   name="name"
                                   class="form-control"
                                   placeholder="Example: Northstar"
                                   required>
                        </div>
                        <div>
                            <label for="brand-manager-description" class="form-label">Description</label>
                            <input id="brand-manager-description"
                                   name="description"
                                   class="form-control"
                                   placeholder="Optional brand details">
                        </div>
                        <label class="form-check form-switch brand-manager-status">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" checked>
                            <span class="form-check-label">Active</span>
                        </label>
                    </div>
                    <div id="brand-manager-form-error" class="text-danger small mt-3" hidden></div>
                    <div class="category-manager-form-actions">
                        <button type="submit" class="btn btn-primary" data-brand-submit>Save</button>
                        <button type="button" class="btn btn-outline-secondary" data-reset-brand-form>Cancel</button>
                    </div>
                </form>

                <section class="category-manager-list">
                    <div class="category-manager-list-heading">
                        <strong>Brands</strong>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-new-brand>
                            <i data-lucide="plus"></i>
                            New Brand
                        </button>
                    </div>
                    <div id="brand-manager-message" class="alert py-2 px-3 small mt-3" role="alert" hidden></div>
                    <div class="brand-manager-items" data-brand-manager-items></div>
                </section>
            </div>
            <div class="modal-footer category-manager-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="attribute-manager-modal" tabindex="-1" aria-labelledby="attribute-manager-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl category-manager-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="attribute-manager-title">Manage Attributes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body category-manager">
                <form id="attribute-manager-form" class="category-manager-form" hidden>
                    <input type="hidden" data-editing-attribute-id>
                    <div class="brand-manager-form-fields">
                        <div class="category-manager-form-label">
                            <strong data-attribute-form-title>Add New Attribute</strong>
                            <small>Add reusable choices such as colors, sizes, or flavors.</small>
                        </div>
                        <div>
                            <label for="attribute-manager-name" class="form-label">
                                Attribute Name <span class="text-danger">*</span>
                            </label>
                            <input id="attribute-manager-name"
                                   name="name"
                                   class="form-control"
                                   placeholder="Example: Color"
                                   required>
                        </div>
                        <div>
                            <label for="attribute-manager-values" class="form-label">
                                Options <span class="text-danger">*</span>
                            </label>
                            <input id="attribute-manager-values"
                                   name="values"
                                   class="form-control"
                                   placeholder="Black, Blue, White"
                                   required>
                            <small class="form-hint">Separate options with commas.</small>
                        </div>
                        <label class="form-check form-switch brand-manager-status">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" checked>
                            <span class="form-check-label">Active</span>
                        </label>
                    </div>
                    <div id="attribute-manager-form-error" class="text-danger small mt-3" hidden></div>
                    <div class="category-manager-form-actions">
                        <button type="submit" class="btn btn-primary" data-attribute-submit>Save</button>
                        <button type="button" class="btn btn-outline-secondary" data-reset-attribute-form>Cancel</button>
                    </div>
                </form>

                <section class="category-manager-list">
                    <div class="category-manager-list-heading">
                        <strong>Available Attributes</strong>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-new-attribute>
                            <i data-lucide="plus"></i>
                            New Attribute
                        </button>
                    </div>
                    <div id="attribute-manager-message" class="alert py-2 px-3 small mt-3" role="alert" hidden></div>
                    <div class="brand-manager-items" data-attribute-manager-items></div>
                </section>
            </div>
            <div class="modal-footer category-manager-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="add-unit-modal" tabindex="-1" aria-labelledby="add-unit-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="add-unit-title">Manage Inventory Units</h5>
                    <small class="text-muted">Add a unit or select an existing unit to edit it.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body unit-manager">
                <div class="unit-manager-list">
                    <div class="unit-manager-list-heading">
                        <strong>Available Units</strong>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-new-unit>
                            <i data-lucide="plus"></i>
                            New Unit
                        </button>
                    </div>
                    <div id="unit-manager-message" class="alert py-2 px-3 small" role="alert" hidden></div>
                    <div class="unit-manager-items" data-unit-manager-items>
                        @foreach($units as $unit)
                            <div class="unit-manager-item"
                                 data-unit-row
                                 data-unit-id="{{ $unit->id }}"
                                 data-unit-name="{{ $unit->name }}"
                                 data-unit-symbol="{{ $unit->symbol }}"
                                 data-unit-description="{{ $unit->description }}">
                                <button type="button" class="unit-manager-edit" data-edit-unit>
                                    <span>
                                        <strong>{{ $unit->name }}</strong>
                                        <small>{{ $unit->description }}</small>
                                    </span>
                                    <span class="badge bg-light text-dark border">{{ $unit->symbol }}</span>
                                    <i data-lucide="pencil"></i>
                                </button>
                                <button type="button"
                                        class="btn btn-icon btn-outline-danger unit-manager-delete"
                                        data-delete-unit
                                        title="Delete unit">
                                    <i data-lucide="trash-2"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
                <form id="add-unit-form" class="unit-manager-form">
                    <input type="hidden" id="editing-unit-id">
                    <div class="unit-manager-form-heading">
                        <strong data-unit-form-title>Add New Unit</strong>
                        <small>Descriptions help your team understand when to use this unit.</small>
                    </div>
                    <div class="mb-3">
                        <label for="new-unit-name" class="form-label">Unit Name</label>
                        <input id="new-unit-name" name="name" class="form-control" placeholder="Example: Carton" required>
                    </div>
                    <div class="mb-3">
                        <label for="new-unit-symbol" class="form-label">Short Symbol</label>
                        <input id="new-unit-symbol" name="symbol" class="form-control" placeholder="Example: ctn" required>
                    </div>
                    <div>
                        <label for="new-unit-description" class="form-label">Description</label>
                        <textarea id="new-unit-description"
                                  name="description"
                                  class="form-control"
                                  rows="3"
                                  maxlength="255"
                                  placeholder="Example: A supplier carton containing smaller boxes."
                                  required></textarea>
                        <small class="form-hint">This help text will appear in the unit dropdown.</small>
                    </div>
                    <div id="add-unit-error" class="text-danger small mt-3" hidden></div>
                    <div class="unit-manager-form-actions">
                        <button type="button" class="btn btn-outline-secondary" data-reset-unit-form>Clear</button>
                        <button type="submit" class="btn btn-primary" data-unit-submit>Add Unit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const variantRows = @json(array_values($variantRows));
    const variantBody = document.getElementById('variant-body');
    const variantTemplate = document.getElementById('variant-template');
    const variationAttributeTemplate = document.getElementById('variation-attribute-template');
    const variationAttributeRows = document.getElementById('variation-attribute-rows');
    const variationManager = document.getElementById('variation-manager');
    const variantTableHeader = document.getElementById('variant-table-header');
    const addAttributeButton = document.getElementById('add-attribute-row');
    let catalogAttributes = @json($catalogAttributes);
    let catalogCategories = @json($catalogCategories);
    let catalogBrands = @json($catalogBrands);
    const hasVariantsInput = document.querySelector('[data-has-variants]');
    const accordionSections = document.querySelectorAll('[data-product-accordion]');
    const addUnitForm = document.getElementById('add-unit-form');
    const unitManagerItems = document.querySelector('[data-unit-manager-items]');
    const editingUnitId = document.getElementById('editing-unit-id');
    const categorySelect = document.querySelector('[data-category-select]');
    const categoryManagerForm = document.getElementById('category-manager-form');
    const categoryManagerItems = document.querySelector('[data-category-manager-items]');
    const categoryParentSelect = document.getElementById('category-manager-parent');
    const editingCategoryId = document.querySelector('[data-editing-category-id]');
    const brandSelect = document.querySelector('[data-brand-select]');
    const brandManagerForm = document.getElementById('brand-manager-form');
    const brandManagerItems = document.querySelector('[data-brand-manager-items]');
    const editingBrandId = document.querySelector('[data-editing-brand-id]');
    const attributeManagerForm = document.getElementById('attribute-manager-form');
    const attributeManagerItems = document.querySelector('[data-attribute-manager-items]');
    const editingAttributeId = document.querySelector('[data-editing-attribute-id]');
    const productNameInput = document.querySelector('[data-product-name]');
    const singleSkuInput = document.querySelector('[data-single-sku]');
    const singleBarcodeInput = document.querySelector('[data-single-barcode]');
    const summaryStatus = document.querySelector('.product-summary-status');
    let pendingUnitSelect = null;
    let productMode = @json($startsWithVariants ? 'variants' : 'single');
    let variantSeedData = null;
    const removedVariantCombinations = new Set();

    function panelData(panel) {
        const data = {};

        panel.querySelectorAll('[data-field]').forEach((input) => {
            data[input.dataset.field] = input.multiple
                ? Array.from(input.selectedOptions).map((option) => Number(option.value))
                : input.value;
        });

        return data;
    }

    function combinationKey(valueIds) {
        return (valueIds || []).map(Number).sort((a, b) => a - b).join('-');
    }

    function generatedVariantName(optionLabels) {
        const productName = productNameInput.value.trim();
        const options = optionLabels.filter(Boolean).join(' / ');

        return [productName, options].filter(Boolean).join(' - ');
    }

    function generatedVariantSku(optionLabels) {
        return [productNameInput.value, ...optionLabels]
            .join('-')
            .toUpperCase()
            .replace(/[^A-Z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .slice(0, 255);
    }

    function money(value) {
        return `PKR ${Number(value || 0).toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        })}`;
    }

    function updateProductSummary() {
        const firstVariant = variantBody.firstElementChild;
        const fieldValue = (field) => firstVariant?.querySelector(`[data-field="${field}"]`)?.value ?? '';
        const customerSelect = firstVariant?.querySelector('[data-field="unit_id"]');
        const supplierSelect = firstVariant?.querySelector('[data-field="purchase_unit_id"]');
        const customerUnit = customerSelect?.selectedOptions[0];
        const supplierUnit = supplierSelect?.selectedOptions[0];
        const customerName = customerUnit?.dataset.unitName || customerUnit?.textContent?.trim() || '';
        const supplierName = supplierUnit?.dataset.unitName || supplierUnit?.textContent?.trim() || '';
        const factor = Number(fieldValue('purchase_unit_factor') || 1);
        const productName = productNameInput.value.trim();
        const requiredValues = [
            productName,
            categoryValue?.value,
            fieldValue('sku'),
            fieldValue('unit_id'),
            fieldValue('purchase_price'),
            fieldValue('selling_price'),
        ];
        const ready = requiredValues.every((value) => String(value ?? '').trim() !== '');

        document.querySelector('[data-summary-product]').textContent = productName || 'Not entered';
        document.querySelector('[data-summary-customer-unit]').textContent = customerName || 'Not selected';
        document.querySelector('[data-summary-supplier-unit]').textContent = supplierName || 'Not selected';
        document.querySelector('[data-summary-conversion]').textContent =
            customerName && supplierName && customerSelect.value !== supplierSelect.value
                ? `1 ${supplierName} = ${factor.toLocaleString()} ${customerName}`
                : 'Not required';
        document.querySelector('[data-summary-purchase]').textContent =
            `${money(fieldValue('purchase_price'))}${supplierName ? ` / ${supplierName}` : ''}`;
        document.querySelector('[data-summary-selling]').textContent =
            `${money(fieldValue('selling_price'))}${customerName ? ` / ${customerName}` : ''}`;
        document.querySelector('[data-summary-stock-preview]').textContent =
            customerName && supplierName
                ? `2 ${supplierName}${supplierName.endsWith('s') ? '' : 's'} = ${(2 * factor).toLocaleString()} ${customerName} Stock`
                : 'Choose customer and supplier units';
        document.querySelector('[data-summary-status]').textContent = ready
            ? 'The required product information is ready to save.'
            : 'Complete the required fields before saving.';
        summaryStatus.classList.toggle('is-ready', ready);
    }

    const categoryTrigger = categorySelect?.querySelector('[data-category-trigger]');
    const categoryMenu = categorySelect?.querySelector('[data-category-menu]');
    const categorySearch = categorySelect?.querySelector('[data-category-search]');
    const categoryValue = categorySelect?.querySelector('[data-category-value]');
    const categoryLabel = categorySelect?.querySelector('[data-category-label]');
    const categoryNoResults = categorySelect?.querySelector('[data-category-no-results]');

    function orderedCategoryRows() {
        const rows = [];
        const visited = new Set();
        const children = new Map();

        catalogCategories.forEach((category) => {
            const parentKey = category.parent_id ? String(category.parent_id) : '';
            if (!children.has(parentKey)) children.set(parentKey, []);
            children.get(parentKey).push(category);
        });
        children.forEach((items) => items.sort((a, b) => a.name.localeCompare(b.name)));

        const visit = (parentId, depth) => {
            (children.get(parentId) || []).forEach((category) => {
                if (visited.has(String(category.id))) return;
                visited.add(String(category.id));
                rows.push({ ...category, depth });
                visit(String(category.id), depth + 1);
            });
        };

        visit('', 0);
        catalogCategories.forEach((category) => {
            if (!visited.has(String(category.id))) {
                rows.push({ ...category, depth: 0 });
            }
        });

        return rows;
    }

    function descendantCategoryIds(categoryId) {
        const ids = new Set();
        let pending = [String(categoryId)];

        while (pending.length) {
            const children = catalogCategories.filter((category) =>
                pending.includes(String(category.parent_id))
            );
            pending = children.map((category) => String(category.id));
            pending.forEach((id) => ids.add(id));
        }

        return ids;
    }

    function selectCategory(category) {
        categoryValue.value = category ? String(category.id) : '';
        categoryLabel.textContent = category?.name || 'Select a category';
        categorySelect.classList.remove('is-invalid');
        renderCategorySelectOptions();
        updateProductSummary();
    }

    function renderCategorySelectOptions() {
        const optionsContainer = categorySelect.querySelector('.catalog-select-options');
        optionsContainer.querySelectorAll('[data-category-option]').forEach((option) => option.remove());

        orderedCategoryRows().forEach((category) => {
            const option = document.createElement('button');
            option.type = 'button';
            option.className = 'catalog-select-option';
            option.dataset.categoryOption = '';
            option.dataset.categoryId = category.id;
            option.dataset.categoryName = category.name;
            option.dataset.categoryParentId = category.parent_id || '';
            option.style.setProperty('--category-depth', category.depth);
            option.setAttribute('role', 'option');
            const selected = String(categoryValue.value) === String(category.id);
            option.classList.toggle('is-selected', selected);
            option.setAttribute('aria-selected', selected ? 'true' : 'false');

            const name = document.createElement('span');
            name.textContent = category.name;
            const check = document.createElement('i');
            check.dataset.lucide = 'check';
            option.append(name, check);
            optionsContainer.insertBefore(option, categoryNoResults);
        });

        if (categoryNoResults) categoryNoResults.hidden = catalogCategories.length > 0;
        if (window.lucide) window.lucide.createIcons();
    }

    function renderCategoryManager() {
        categoryManagerItems.replaceChildren();

        orderedCategoryRows().forEach((category) => {
            const row = document.createElement('div');
            row.className = 'category-manager-item';
            row.dataset.categoryRow = '';
            row.dataset.categoryId = category.id;
            row.style.setProperty('--category-depth', category.depth);

            const edit = document.createElement('button');
            edit.type = 'button';
            edit.className = 'category-manager-edit';
            edit.dataset.editCategory = '';
            const branch = document.createElement('i');
            branch.dataset.lucide = category.depth ? 'corner-down-right' : 'folder';
            const name = document.createElement('strong');
            name.textContent = category.name;
            edit.append(branch, name);

            const actions = document.createElement('div');
            actions.className = 'category-manager-row-actions';
            const editAction = document.createElement('button');
            editAction.type = 'button';
            editAction.className = 'btn btn-sm btn-outline-secondary';
            editAction.dataset.editCategory = '';
            editAction.title = 'Edit category';
            const pencil = document.createElement('i');
            pencil.dataset.lucide = 'pencil';
            editAction.append(pencil, document.createTextNode('Edit'));

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'btn btn-sm btn-outline-danger category-manager-delete';
            remove.dataset.deleteCategory = '';
            remove.title = 'Delete category';
            const trash = document.createElement('i');
            trash.dataset.lucide = 'trash-2';
            remove.append(trash, document.createTextNode('Delete'));
            actions.append(editAction, remove);
            row.append(edit, actions);
            categoryManagerItems.append(row);
        });

        if (!catalogCategories.length) {
            const empty = document.createElement('div');
            empty.className = 'text-muted small py-3 text-center';
            empty.textContent = 'No categories have been added yet.';
            categoryManagerItems.append(empty);
        }

        if (window.lucide) window.lucide.createIcons();
    }

    function populateCategoryParentOptions(selectedParentId = '') {
        const blocked = editingCategoryId.value
            ? descendantCategoryIds(editingCategoryId.value)
            : new Set();
        if (editingCategoryId.value) blocked.add(String(editingCategoryId.value));

        categoryParentSelect.replaceChildren(new Option('No parent (top-level category)', ''));
        orderedCategoryRows().forEach((category) => {
            if (blocked.has(String(category.id))) return;
            const prefix = category.depth ? `${'-- '.repeat(category.depth)}` : '';
            categoryParentSelect.add(new Option(`${prefix}${category.name}`, category.id));
        });
        categoryParentSelect.value = selectedParentId ? String(selectedParentId) : '';
    }

    function resetCategoryForm() {
        categoryManagerForm.reset();
        editingCategoryId.value = '';
        document.querySelector('[data-category-form-title]').textContent = 'Add New Category';
        document.querySelector('[data-category-submit]').textContent = 'Save';
        document.getElementById('category-manager-form-error').hidden = true;
        populateCategoryParentOptions();
    }

    function showCategoryForm() {
        categoryManagerForm.hidden = false;
        requestAnimationFrame(() => document.getElementById('category-manager-name').focus());
    }

    function hideCategoryForm() {
        resetCategoryForm();
        categoryManagerForm.hidden = true;
    }

    function showCategoryManagerMessage(message, type = 'danger') {
        const notice = document.getElementById('category-manager-message');
        notice.className = `alert alert-${type} py-2 px-3 small mt-3`;
        notice.textContent = message;
        notice.hidden = false;
    }

    function closeCategoryMenu() {
        categoryMenu.hidden = true;
        categoryTrigger.setAttribute('aria-expanded', 'false');
    }

    if (categorySelect) {
        renderCategorySelectOptions();

        categoryTrigger.addEventListener('click', () => {
            categoryMenu.hidden = !categoryMenu.hidden;
            categoryTrigger.setAttribute('aria-expanded', String(!categoryMenu.hidden));
            if (!categoryMenu.hidden) {
                categorySearch.value = '';
                categorySelect.querySelectorAll('[data-category-option]').forEach((option) => option.hidden = false);
                categoryNoResults.hidden = catalogCategories.length > 0;
                requestAnimationFrame(() => categorySearch.focus());
            }
        });

        categorySearch.addEventListener('input', () => {
            const query = categorySearch.value.trim().toLowerCase();
            let matches = 0;
            categorySelect.querySelectorAll('[data-category-option]').forEach((option) => {
                const visible = option.dataset.categoryName.toLowerCase().includes(query);
                option.hidden = !visible;
                if (visible) matches++;
            });
            categoryNoResults.hidden = matches > 0;
        });

        categorySelect.addEventListener('click', (event) => {
            const option = event.target.closest('[data-category-option]');
            if (option) {
                selectCategory(catalogCategories.find((category) => String(category.id) === option.dataset.categoryId));
                closeCategoryMenu();
                categoryTrigger.focus();
                return;
            }

            if (event.target.closest('[data-manage-categories]')) {
                closeCategoryMenu();
                hideCategoryForm();
                renderCategoryManager();
                bootstrap.Modal.getOrCreateInstance(document.getElementById('category-manager-modal')).show();
            }
        });

        document.addEventListener('click', (event) => {
            if (!categorySelect.contains(event.target)) closeCategoryMenu();
        });

        categorySelect.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeCategoryMenu();
                categoryTrigger.focus();
            }
        });
    }

    document.querySelector('[data-new-category]').addEventListener('click', () => {
        resetCategoryForm();
        showCategoryForm();
    });
    document.querySelector('[data-reset-category-form]').addEventListener('click', hideCategoryForm);

    categoryManagerItems.addEventListener('click', async (event) => {
        const row = event.target.closest('[data-category-row]');
        if (!row) return;

        const category = catalogCategories.find((item) => String(item.id) === row.dataset.categoryId);
        if (!category) return;

        if (event.target.closest('[data-edit-category]')) {
            showCategoryForm();
            editingCategoryId.value = category.id;
            document.getElementById('category-manager-name').value = category.name;
            document.querySelector('[data-category-form-title]').textContent = `Edit ${category.name}`;
            document.querySelector('[data-category-submit]').textContent = 'Update Category';
            document.getElementById('category-manager-form-error').hidden = true;
            populateCategoryParentOptions(category.parent_id);
            document.getElementById('category-manager-name').focus();
            return;
        }

        const deleteButton = event.target.closest('[data-delete-category]');
        if (!deleteButton || !window.confirm(`Delete ${category.name}?`)) return;

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!csrfToken) {
            showCategoryManagerMessage('Your session token is unavailable. Refresh the page and try again.');
            return;
        }

        deleteButton.disabled = true;
        const endpoint = @json(route('categories.destroy', ['category' => '__CATEGORY__'], false))
            .replace('__CATEGORY__', category.id);

        try {
            const response = await fetch(endpoint, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });
            const result = await response.json();
            if (!response.ok) {
                showCategoryManagerMessage(result.message || 'Unable to delete the category.');
                return;
            }

            catalogCategories = catalogCategories.filter((item) => String(item.id) !== String(category.id));
            if (String(categoryValue.value) === String(category.id)) selectCategory(null);
            if (String(editingCategoryId.value) === String(category.id)) hideCategoryForm();
            renderCategorySelectOptions();
            renderCategoryManager();
            populateCategoryParentOptions();
            showCategoryManagerMessage(result.message || 'Category deleted successfully.', 'success');
        } catch (error) {
            showCategoryManagerMessage('The category could not be deleted. Refresh the page and try again.');
        } finally {
            deleteButton.disabled = false;
        }
    });

    categoryManagerForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const error = document.getElementById('category-manager-form-error');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const categoryId = editingCategoryId.value;
        const endpoint = categoryId
            ? @json(route('categories.update', ['category' => '__CATEGORY__'], false)).replace('__CATEGORY__', categoryId)
            : @json(route('categories.store', [], false));
        error.hidden = true;

        if (!csrfToken) {
            error.textContent = 'Your session token is unavailable. Refresh the page and try again.';
            error.hidden = false;
            return;
        }

        try {
            const response = await fetch(endpoint, {
                method: categoryId ? 'PUT' : 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(Object.fromEntries(new FormData(categoryManagerForm))),
            });
            const result = await response.json();

            if (!response.ok) {
                error.textContent = result.message
                    || Object.values(result.errors || {}).flat()[0]
                    || 'Unable to save the category.';
                error.hidden = false;
                return;
            }

            const saved = {
                id: result.id,
                name: result.name,
                parent_id: result.parent_id || null,
            };
            const existingIndex = catalogCategories.findIndex((item) => String(item.id) === String(saved.id));
            if (existingIndex >= 0) {
                catalogCategories[existingIndex] = saved;
            } else {
                catalogCategories.push(saved);
                selectCategory(saved);
            }

            if (String(categoryValue.value) === String(saved.id)) {
                categoryLabel.textContent = saved.name;
            }
            renderCategorySelectOptions();
            renderCategoryManager();
            hideCategoryForm();
            showCategoryManagerMessage(
                categoryId ? 'Category updated successfully.' : 'Category added successfully.',
                'success'
            );
        } catch (error) {
            error.textContent = 'The category could not be saved. Refresh the page and try again.';
            error.hidden = false;
        }
    });

    const brandTrigger = brandSelect.querySelector('[data-brand-trigger]');
    const brandMenu = brandSelect.querySelector('[data-brand-menu]');
    const brandSearch = brandSelect.querySelector('[data-brand-search]');
    const brandValue = brandSelect.querySelector('[data-brand-value]');
    const brandLabel = brandSelect.querySelector('[data-brand-label]');
    const brandOptions = brandSelect.querySelector('[data-brand-options]');
    const brandNoResults = brandSelect.querySelector('[data-brand-no-results]');

    function selectableBrands() {
        return catalogBrands
            .filter((brand) => brand.is_active || String(brand.id) === String(brandValue.value))
            .sort((a, b) => a.name.localeCompare(b.name));
    }

    function renderBrandOptions() {
        brandOptions.querySelectorAll('[data-brand-option]').forEach((option) => option.remove());

        const brands = [
            { id: '', name: 'No brand', is_active: true },
            ...selectableBrands(),
        ];
        brands.forEach((brand) => {
            const option = document.createElement('button');
            option.type = 'button';
            option.className = 'catalog-select-option';
            option.dataset.brandOption = '';
            option.dataset.brandId = brand.id;
            option.dataset.brandName = brand.name;
            option.setAttribute('role', 'option');
            const selected = String(brandValue.value) === String(brand.id);
            option.classList.toggle('is-selected', selected);
            option.setAttribute('aria-selected', selected ? 'true' : 'false');

            const name = document.createElement('span');
            name.textContent = brand.name;
            const check = document.createElement('i');
            check.dataset.lucide = 'check';
            option.append(name, check);
            brandOptions.insertBefore(option, brandNoResults);
        });

        brandNoResults.hidden = true;
        window.lucide?.createIcons();
    }

    function selectBrand(brand) {
        brandValue.value = brand?.id || '';
        brandLabel.textContent = brand?.name || 'No brand';
        renderBrandOptions();
        updateProductSummary();
    }

    function closeBrandMenu() {
        brandMenu.hidden = true;
        brandTrigger.setAttribute('aria-expanded', 'false');
    }

    function resetBrandForm() {
        brandManagerForm.reset();
        editingBrandId.value = '';
        brandManagerForm.querySelector('[name="is_active"]').checked = true;
        document.querySelector('[data-brand-form-title]').textContent = 'Add New Brand';
        document.querySelector('[data-brand-submit]').textContent = 'Save';
        document.getElementById('brand-manager-form-error').hidden = true;
    }

    function showBrandForm() {
        brandManagerForm.hidden = false;
        requestAnimationFrame(() => document.getElementById('brand-manager-name').focus());
    }

    function hideBrandForm() {
        resetBrandForm();
        brandManagerForm.hidden = true;
    }

    function showBrandManagerMessage(message, type = 'danger') {
        const notice = document.getElementById('brand-manager-message');
        notice.className = `alert alert-${type} py-2 px-3 small mt-3`;
        notice.textContent = message;
        notice.hidden = false;
    }

    function renderBrandManager() {
        brandManagerItems.replaceChildren();

        [...catalogBrands].sort((a, b) => a.name.localeCompare(b.name)).forEach((brand) => {
            const row = document.createElement('div');
            row.className = 'brand-manager-item';
            row.dataset.brandRow = '';
            row.dataset.brandId = brand.id;

            const details = document.createElement('div');
            details.className = 'brand-manager-details';
            const icon = document.createElement('i');
            icon.dataset.lucide = 'badge-check';
            const copy = document.createElement('div');
            const name = document.createElement('strong');
            name.textContent = brand.name;
            const description = document.createElement('small');
            description.textContent = brand.description || 'No description';
            copy.append(name, description);
            details.append(icon, copy);

            const status = document.createElement('span');
            status.className = `badge ${brand.is_active ? 'bg-success' : 'bg-secondary'}`;
            status.textContent = brand.is_active ? 'Active' : 'Inactive';

            const actions = document.createElement('div');
            actions.className = 'category-manager-row-actions';
            const edit = document.createElement('button');
            edit.type = 'button';
            edit.className = 'btn btn-sm btn-outline-secondary';
            edit.dataset.editBrand = '';
            const pencil = document.createElement('i');
            pencil.dataset.lucide = 'pencil';
            edit.append(pencil, document.createTextNode('Edit'));

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'btn btn-sm btn-outline-danger';
            remove.dataset.deleteBrand = '';
            const trash = document.createElement('i');
            trash.dataset.lucide = 'trash-2';
            remove.append(trash, document.createTextNode('Delete'));
            actions.append(edit, remove);
            row.append(details, status, actions);
            brandManagerItems.append(row);
        });

        if (!catalogBrands.length) {
            const empty = document.createElement('div');
            empty.className = 'category-tree-empty';
            empty.textContent = 'No brands have been added yet.';
            brandManagerItems.append(empty);
        }

        window.lucide?.createIcons();
    }

    renderBrandOptions();
    brandTrigger.addEventListener('click', () => {
        brandMenu.hidden = !brandMenu.hidden;
        brandTrigger.setAttribute('aria-expanded', String(!brandMenu.hidden));
        if (!brandMenu.hidden) {
            brandSearch.value = '';
            brandSelect.querySelectorAll('[data-brand-option]').forEach((option) => option.hidden = false);
            brandNoResults.hidden = true;
            requestAnimationFrame(() => brandSearch.focus());
        }
    });

    brandSearch.addEventListener('input', () => {
        const query = brandSearch.value.trim().toLowerCase();
        let matches = 0;
        brandSelect.querySelectorAll('[data-brand-option]').forEach((option) => {
            const visible = option.dataset.brandName.toLowerCase().includes(query);
            option.hidden = !visible;
            if (visible) matches++;
        });
        brandNoResults.hidden = matches > 0;
    });

    brandSelect.addEventListener('click', (event) => {
        const option = event.target.closest('[data-brand-option]');
        if (option) {
            selectBrand(catalogBrands.find((brand) => String(brand.id) === option.dataset.brandId));
            closeBrandMenu();
            brandTrigger.focus();
            return;
        }

        if (event.target.closest('[data-manage-brands]')) {
            closeBrandMenu();
            hideBrandForm();
            renderBrandManager();
            bootstrap.Modal.getOrCreateInstance(document.getElementById('brand-manager-modal')).show();
        }
    });

    document.addEventListener('click', (event) => {
        if (!brandSelect.contains(event.target)) closeBrandMenu();
    });

    document.querySelector('[data-new-brand]').addEventListener('click', () => {
        resetBrandForm();
        showBrandForm();
    });
    document.querySelector('[data-reset-brand-form]').addEventListener('click', hideBrandForm);

    brandManagerItems.addEventListener('click', async (event) => {
        const row = event.target.closest('[data-brand-row]');
        if (!row) return;
        const brand = catalogBrands.find((item) => String(item.id) === row.dataset.brandId);
        if (!brand) return;

        if (event.target.closest('[data-edit-brand]')) {
            showBrandForm();
            editingBrandId.value = brand.id;
            document.getElementById('brand-manager-name').value = brand.name;
            document.getElementById('brand-manager-description').value = brand.description || '';
            brandManagerForm.querySelector('[name="is_active"]').checked = Boolean(brand.is_active);
            document.querySelector('[data-brand-form-title]').textContent = `Edit ${brand.name}`;
            document.querySelector('[data-brand-submit]').textContent = 'Update Brand';
            return;
        }

        const deleteButton = event.target.closest('[data-delete-brand]');
        if (!deleteButton || !window.confirm(`Delete ${brand.name}?`)) return;

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const endpoint = @json(route('brands.destroy', ['brand' => '__BRAND__'], false))
            .replace('__BRAND__', brand.id);
        deleteButton.disabled = true;

        try {
            const response = await fetch(endpoint, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });
            const result = await response.json();
            if (!response.ok) {
                showBrandManagerMessage(result.message || 'Unable to delete the brand.');
                return;
            }

            catalogBrands = catalogBrands.filter((item) => String(item.id) !== String(brand.id));
            if (String(brandValue.value) === String(brand.id)) selectBrand(null);
            if (String(editingBrandId.value) === String(brand.id)) hideBrandForm();
            renderBrandOptions();
            renderBrandManager();
            showBrandManagerMessage(result.message || 'Brand deleted successfully.', 'success');
        } catch (error) {
            showBrandManagerMessage('The brand could not be deleted. Refresh the page and try again.');
        } finally {
            deleteButton.disabled = false;
        }
    });

    brandManagerForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const error = document.getElementById('brand-manager-form-error');
        const brandId = editingBrandId.value;
        const endpoint = brandId
            ? @json(route('brands.update', ['brand' => '__BRAND__'], false)).replace('__BRAND__', brandId)
            : @json(route('brands.store', [], false));
        const payload = Object.fromEntries(new FormData(brandManagerForm));
        payload.is_active = brandManagerForm.querySelector('[name="is_active"]').checked ? 1 : 0;
        error.hidden = true;

        try {
            const response = await fetch(endpoint, {
                method: brandId ? 'PUT' : 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(payload),
            });
            const result = await response.json();
            if (!response.ok) {
                error.textContent = result.message
                    || Object.values(result.errors || {}).flat()[0]
                    || 'Unable to save the brand.';
                error.hidden = false;
                return;
            }

            const existingIndex = catalogBrands.findIndex((brand) => String(brand.id) === String(result.id));
            if (existingIndex >= 0) {
                catalogBrands[existingIndex] = result;
            } else {
                catalogBrands.push(result);
                if (result.is_active) selectBrand(result);
            }
            if (String(brandValue.value) === String(result.id)) brandLabel.textContent = result.name;
            renderBrandOptions();
            renderBrandManager();
            hideBrandForm();
            showBrandManagerMessage(brandId ? 'Brand updated successfully.' : 'Brand added successfully.', 'success');
        } catch (error) {
            error.textContent = 'The brand could not be saved. Refresh the page and try again.';
            error.hidden = false;
        }
    });

    function resetAttributeForm() {
        attributeManagerForm.reset();
        editingAttributeId.value = '';
        attributeManagerForm.querySelector('[name="is_active"]').checked = true;
        document.querySelector('[data-attribute-form-title]').textContent = 'Add New Attribute';
        document.querySelector('[data-attribute-submit]').textContent = 'Save';
        document.getElementById('attribute-manager-form-error').hidden = true;
    }

    function showAttributeForm() {
        attributeManagerForm.hidden = false;
        requestAnimationFrame(() => document.getElementById('attribute-manager-name').focus());
    }

    function hideAttributeForm() {
        resetAttributeForm();
        attributeManagerForm.hidden = true;
    }

    function showAttributeManagerMessage(message, type = 'danger') {
        const notice = document.getElementById('attribute-manager-message');
        notice.className = `alert alert-${type} py-2 px-3 small mt-3`;
        notice.textContent = message;
        notice.hidden = false;
    }

    function renderAttributeManager() {
        attributeManagerItems.replaceChildren();

        [...catalogAttributes].sort((a, b) => a.name.localeCompare(b.name)).forEach((attribute) => {
            const row = document.createElement('div');
            row.className = 'brand-manager-item';
            row.dataset.attributeRow = '';
            row.dataset.attributeId = attribute.id;

            const details = document.createElement('div');
            details.className = 'brand-manager-details';
            const icon = document.createElement('i');
            icon.dataset.lucide = 'list-filter';
            const copy = document.createElement('div');
            const name = document.createElement('strong');
            name.textContent = attribute.name;
            const values = document.createElement('small');
            values.textContent = attribute.values.map((value) => value.value).join(', ');
            copy.append(name, values);
            details.append(icon, copy);

            const status = document.createElement('span');
            status.className = `badge attribute-status-badge ${
                attribute.is_active ? 'is-active' : 'is-inactive'
            }`;
            status.textContent = attribute.is_active ? 'Active' : 'Inactive';

            const actions = document.createElement('div');
            actions.className = 'category-manager-row-actions';
            const edit = document.createElement('button');
            edit.type = 'button';
            edit.className = 'btn btn-sm btn-outline-secondary';
            edit.dataset.editAttribute = '';
            edit.title = 'Edit attribute';
            edit.innerHTML = '<i data-lucide="pencil"></i> Edit';
            actions.append(edit);

            if (attribute.can_delete) {
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'btn btn-sm btn-outline-danger';
                remove.dataset.deleteAttribute = '';
                remove.title = 'Delete attribute';
                remove.innerHTML = '<i data-lucide="trash-2"></i>';
                actions.append(remove);
            }

            row.append(details, status, actions);
            attributeManagerItems.append(row);
        });

        if (!catalogAttributes.length) {
            const empty = document.createElement('div');
            empty.className = 'category-tree-empty';
            empty.textContent = 'No attributes have been added yet.';
            attributeManagerItems.append(empty);
        }

        window.lucide?.createIcons();
    }

    function syncVariantAttributeOptions() {
        const selects = [
            ...variantTemplate.content.querySelectorAll('[data-field="attribute_value_ids"]'),
            ...variantBody.querySelectorAll('[data-field="attribute_value_ids"]'),
        ];

        selects.forEach((select) => {
            const selectedIds = Array.from(select.selectedOptions).map((option) => String(option.value));
            select.replaceChildren();
            catalogAttributes.forEach((attribute) => {
                const group = document.createElement('optgroup');
                group.label = attribute.name;
                attribute.values.forEach((value) => {
                    const option = new Option(value.value, value.id);
                    option.selected = selectedIds.includes(String(value.id));
                    group.append(option);
                });
                select.append(group);
            });
        });
    }

    function populateAttributeSelect(select, selectedId = '') {
        select.replaceChildren(new Option('Select attribute', ''));
        catalogAttributes
            .filter((attribute) => attribute.is_active || String(attribute.id) === String(selectedId))
            .sort((a, b) => a.name.localeCompare(b.name))
            .forEach((attribute) => select.add(new Option(attribute.name, attribute.id)));
        select.value = selectedId ? String(selectedId) : '';
    }

    function refreshAttributeCatalog() {
        variationAttributeRows.querySelectorAll('.variation-attribute-row').forEach((row) => {
            const select = row.querySelector('.variation-attribute-select');
            const selectedId = select.value;
            const selectedValueIds = Array.from(
                row.querySelectorAll('.variation-option-checklist input:checked')
            ).map((option) => Number(option.value));
            populateAttributeSelect(select, selectedId);
            populateOptionChecklist(row, selectedId, selectedValueIds);
        });
        syncVariantAttributeOptions();
        refreshAttributeChoices();
        generateVariantCombinations();
    }

    document.querySelector('[data-manage-attributes]').addEventListener('click', () => {
        hideAttributeForm();
        renderAttributeManager();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('attribute-manager-modal')).show();
    });
    document.querySelector('[data-new-attribute]').addEventListener('click', () => {
        resetAttributeForm();
        showAttributeForm();
    });
    document.querySelector('[data-reset-attribute-form]').addEventListener('click', hideAttributeForm);

    attributeManagerItems.addEventListener('click', async (event) => {
        const row = event.target.closest('[data-attribute-row]');
        if (!row) return;
        const attribute = catalogAttributes.find((item) => String(item.id) === row.dataset.attributeId);
        if (!attribute) return;

        if (event.target.closest('[data-edit-attribute]')) {
            showAttributeForm();
            editingAttributeId.value = attribute.id;
            document.getElementById('attribute-manager-name').value = attribute.name;
            document.getElementById('attribute-manager-values').value =
                attribute.values.map((value) => value.value).join(', ');
            attributeManagerForm.querySelector('[name="is_active"]').checked = Boolean(attribute.is_active);
            document.querySelector('[data-attribute-form-title]').textContent = `Edit ${attribute.name}`;
            document.querySelector('[data-attribute-submit]').textContent = 'Update Attribute';
            return;
        }

        const deleteButton = event.target.closest('[data-delete-attribute]');
        if (!deleteButton || !window.confirm(`Delete ${attribute.name}?`)) return;
        deleteButton.disabled = true;
        const endpoint = @json(route('product-attributes.destroy', ['productAttribute' => '__ATTRIBUTE__'], false))
            .replace('__ATTRIBUTE__', attribute.id);

        try {
            const response = await fetch(endpoint, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });
            const result = await response.json();
            if (!response.ok) {
                showAttributeManagerMessage(result.message || 'Unable to delete the attribute.');
                return;
            }

            catalogAttributes = catalogAttributes.filter((item) => String(item.id) !== String(attribute.id));
            variationAttributeRows.querySelectorAll('.variation-attribute-row').forEach((attributeRow) => {
                if (String(attributeRow.querySelector('.variation-attribute-select').value) === String(attribute.id)) {
                    attributeRow.remove();
                }
            });
            refreshAttributeCatalog();
            renderAttributeManager();
            showAttributeManagerMessage(result.message || 'Attribute deleted successfully.', 'success');
        } catch (error) {
            showAttributeManagerMessage('The attribute could not be deleted. Refresh the page and try again.');
        } finally {
            deleteButton.disabled = false;
        }
    });

    attributeManagerForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const error = document.getElementById('attribute-manager-form-error');
        const attributeId = editingAttributeId.value;
        const endpoint = attributeId
            ? @json(route('product-attributes.update', ['productAttribute' => '__ATTRIBUTE__'], false))
                .replace('__ATTRIBUTE__', attributeId)
            : @json(route('product-attributes.store', [], false));
        const payload = Object.fromEntries(new FormData(attributeManagerForm));
        payload.is_active = attributeManagerForm.querySelector('[name="is_active"]').checked ? 1 : 0;
        error.hidden = true;

        try {
            const response = await fetch(endpoint, {
                method: attributeId ? 'PUT' : 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(payload),
            });
            const result = await response.json();
            if (!response.ok) {
                error.textContent = result.message
                    || Object.values(result.errors || {}).flat()[0]
                    || 'Unable to save the attribute.';
                error.hidden = false;
                return;
            }

            const existingIndex = catalogAttributes.findIndex((item) => String(item.id) === String(result.id));
            if (existingIndex >= 0) catalogAttributes[existingIndex] = result;
            else catalogAttributes.push(result);
            refreshAttributeCatalog();
            renderAttributeManager();
            hideAttributeForm();
            showAttributeManagerMessage(
                attributeId ? 'Attribute updated successfully.' : 'Attribute added successfully.',
                'success'
            );
        } catch (error) {
            error.textContent = 'The attribute could not be saved. Refresh the page and try again.';
            error.hidden = false;
        }
    });

    function normalizedUnit(option) {
        return `${option?.dataset.unitName || ''} ${option?.dataset.unitSymbol || ''}`.trim().toLowerCase();
    }

    function unitDisplayName(select) {
        const option = select?.selectedOptions[0];
        return option?.dataset.unitName || option?.textContent?.trim() || '';
    }

    function allowedSupplierUnits(customerOption) {
        const customer = normalizedUnit(customerOption);
        if (customer.includes('piece') || customer === 'pc') {
            return ['piece', 'pc', 'box', 'carton', 'tray', 'dozen'];
        }
        if (customer.includes('kilogram') || customer.includes(' kg')) {
            return ['kilogram', ' kg', 'gram', 'bag'];
        }
        if (customer.includes('liter') || customer.includes('litre') || customer.includes(' l')) {
            return ['liter', 'litre', ' ml', 'bottle', 'can', 'carton'];
        }
        return [];
    }

    function updateUnitFlow(panel, customerChanged = false) {
        const customerSelect = panel.querySelector('[data-customer-unit]');
        const supplierSelect = panel.querySelector('[data-supplier-unit]');
        const factorInput = panel.querySelector('[data-field="purchase_unit_factor"]');
        const allowed = allowedSupplierUnits(customerSelect.selectedOptions[0]);

        Array.from(supplierSelect.options).forEach((option) => {
            if (!option.value || option.value === '__add_unit__' || option.disabled) return;
            const unit = normalizedUnit(option);
            option.hidden = allowed.length > 0 && !allowed.some((name) => unit.includes(name));
        });

        if (customerChanged) {
            const selectedSupplier = supplierSelect.selectedOptions[0];
            if (!selectedSupplier || selectedSupplier.hidden) supplierSelect.value = customerSelect.value;
        }
        if (!supplierSelect.value && customerSelect.value) supplierSelect.value = customerSelect.value;

        const customerName = unitDisplayName(customerSelect);
        const supplierName = unitDisplayName(supplierSelect);
        const sameUnit = Boolean(customerSelect.value && customerSelect.value === supplierSelect.value);
        panel.querySelector('[data-conversion-panel]').hidden = sameUnit || !customerSelect.value || !supplierSelect.value;
        panel.querySelector('[data-same-unit-message]').hidden = !sameUnit;
        if (sameUnit) factorInput.value = 1;

        panel.querySelector('[data-conversion-title]').textContent =
            supplierName ? `1 ${supplierName} Equals` : '1 Supplier Unit Equals';
        panel.querySelector('[data-conversion-customer-unit]').textContent = customerName || 'Customer Unit';
        panel.querySelector('[data-conversion-help]').textContent =
            supplierName && customerName
                ? `Enter how many ${customerName} are received in one ${supplierName}.`
                : 'Enter how many customer units are inside one supplier unit.';
        panel.querySelector('[data-purchase-price-label]').textContent =
            `Purchase Price${supplierName ? ` (${supplierName})` : ''}`;
        panel.querySelector('[data-selling-price-label]').textContent =
            `Selling Price${customerName ? ` (${customerName})` : ''}`;
        panel.querySelector('[data-opening-stock-label]').textContent =
            `Opening Stock${supplierName ? ` (${supplierName})` : ''}`;
        panel.querySelector('[data-opening-stock-help]').textContent =
            `Enter how many ${supplierName || 'supplier units'} are currently available.`;
        syncConvertedStock(panel);
        if (panel === variantBody.firstElementChild) updateBulkPriceLabels(customerName);
    }

    function syncConvertedStock(panel, initializeSupplierQuantity = false) {
        const supplierStock = panel.querySelector('[data-supplier-stock]');
        const customerStock = panel.querySelector('[data-field="stock_quantity"]');
        const factor = Number(panel.querySelector('[data-field="purchase_unit_factor"]')?.value || 1);
        const customerName = unitDisplayName(panel.querySelector('[data-customer-unit]')) || 'customer units';
        const supplierName = unitDisplayName(panel.querySelector('[data-supplier-unit]')) || 'supplier units';

        if (initializeSupplierQuantity) {
            const storedCustomerStock = Number(customerStock.value || 0);
            supplierStock.value = factor > 0 ? Number((storedCustomerStock / factor).toFixed(3)) : storedCustomerStock;
        } else if (supplierStock.value === '') {
            panel.querySelector('[data-stock-conversion-preview]').textContent =
                `Enter ${supplierName} stock to see the equivalent ${customerName}.`;
            return;
        }

        const supplierQuantity = Number(supplierStock.value || 0);
        const convertedQuantity = Number((supplierQuantity * factor).toFixed(3));
        customerStock.value = convertedQuantity;
        panel.querySelector('[data-stock-conversion-preview]').textContent =
            `${supplierQuantity.toLocaleString()} ${pluralUnit(supplierName, supplierQuantity)} = `
            + `${convertedQuantity.toLocaleString()} ${customerName} in stock`;
    }

    function pluralUnit(unit, quantity) {
        if (quantity === 1 || !unit) return unit;
        if (/^(kg|g|gram|kilogram|ml)$/i.test(unit)) return unit;
        if (/lit(er|re)$/i.test(unit)) return `${unit}s`;
        if (/(s|x|ch|sh)$/i.test(unit)) return `${unit}es`;
        return `${unit}s`;
    }

    function selectedAttributeIds() {
        return Array.from(variationAttributeRows.querySelectorAll('.variation-attribute-select'))
            .map((select) => select.value)
            .filter(Boolean);
    }

    function refreshAttributeChoices() {
        const selected = selectedAttributeIds();
        const rowCount = variationAttributeRows.children.length;
        const availableAttributeCount = catalogAttributes.filter((attribute) => attribute.is_active).length;

        variationAttributeRows.querySelectorAll('.variation-attribute-select').forEach((select) => {
            Array.from(select.options).forEach((option) => {
                option.disabled = option.value !== select.value && selected.includes(option.value);
            });
        });

        const allAttributesAdded = availableAttributeCount === 0
            || rowCount >= availableAttributeCount;
        addAttributeButton.disabled = allAttributesAdded;
        addAttributeButton.title = allAttributesAdded
            ? (availableAttributeCount ? 'All available attributes have been added.' : 'No active attributes are available.')
            : 'Add another product attribute';
        addAttributeButton.querySelector('[data-add-attribute-label]').textContent =
            allAttributesAdded && availableAttributeCount ? 'All Attributes Added' : 'Add Attribute';
    }

    function populateOptionChecklist(row, attributeId, selectedValueIds = []) {
        const checklist = row.querySelector('.variation-option-checklist');
        const attribute = catalogAttributes.find((item) => String(item.id) === String(attributeId));
        checklist.innerHTML = '';

        if (!attribute) {
            checklist.innerHTML = '<span class="variation-option-placeholder">Select an attribute first.</span>';
            return;
        }

        (attribute.values || []).forEach((value) => {
            const label = document.createElement('label');
            label.className = 'variation-option-choice';
            const option = document.createElement('input');
            option.type = 'checkbox';
            option.value = value.id;
            option.dataset.label = value.value;
            option.checked = selectedValueIds.map(Number).includes(Number(value.id));
            label.append(option, document.createTextNode(value.value));
            checklist.appendChild(label);
        });

    }

    function addVariationAttributeRow(attributeId = '', selectedValueIds = []) {
        const availableAttributeCount = catalogAttributes.filter((attribute) => attribute.is_active).length;
        if (variationAttributeRows.children.length >= availableAttributeCount) {
            refreshAttributeChoices();
            return;
        }

        const row = variationAttributeTemplate.content.firstElementChild.cloneNode(true);
        const attributeSelect = row.querySelector('.variation-attribute-select');

        populateAttributeSelect(attributeSelect, attributeId);
        populateOptionChecklist(row, attributeId, selectedValueIds);

        attributeSelect.addEventListener('change', () => {
            populateOptionChecklist(row, attributeSelect.value);
            removedVariantCombinations.clear();
            refreshAttributeChoices();
            generateVariantCombinations();
        });
        row.querySelector('.variation-option-checklist').addEventListener('change', (event) => {
            if (event.target.matches('input[type="checkbox"]')) {
                removedVariantCombinations.clear();
                generateVariantCombinations();
            }
        });
        row.querySelector('.remove-attribute-row').addEventListener('click', () => {
            row.remove();
            removedVariantCombinations.clear();
            refreshAttributeChoices();
            generateVariantCombinations();
        });

        variationAttributeRows.appendChild(row);
        refreshAttributeChoices();
        if (window.lucide) window.lucide.createIcons();
    }

    function cartesian(groups) {
        return groups.reduce(
            (combinations, group) => combinations.flatMap((combination) =>
                group.map((value) => [...combination, value])
            ),
            [[]]
        );
    }

    function generateVariantCombinations() {
        if (productMode !== 'variants') return;

        const configuredRows = Array.from(variationAttributeRows.children)
            .map((row) => {
                const attributeId = row.querySelector('.variation-attribute-select').value;
                const values = Array.from(row.querySelectorAll('.variation-option-checklist input:checked'))
                    .map((option) => ({
                        id: Number(option.value),
                        label: option.dataset.label,
                    }));

                return { attributeId, values };
            })
            .filter((row) => row.attributeId && row.values.length);

        const note = document.getElementById('variation-manager-note');
        if (!configuredRows.length) {
            note.hidden = false;
            variantBody.replaceChildren();
            updateVariantPresentation();
            syncLegacyFields();
            return;
        }

        note.hidden = true;
        const existing = new Map(
            Array.from(variantBody.children).map((panel) => {
                const data = panelData(panel);
                data.auto_generated_identity = panel.dataset.autoGeneratedIdentity === 'true';
                return [combinationKey(data.attribute_value_ids), data];
            })
        );
        const fallback = existing.get('') || existing.values().next().value || variantSeedData || {};
        const combinations = cartesian(configuredRows.map((row) => row.values));

        variantBody.innerHTML = '';
        combinations.forEach((combination, index) => {
            const ids = combination.map((value) => value.id);
            if (removedVariantCombinations.has(combinationKey(ids))) return;

            const optionLabels = combination.map((value) => value.label);
            const name = generatedVariantName(optionLabels);
            const saved = existing.get(combinationKey(ids));

            addVariant(saved || {
                name,
                sku: generatedVariantSku(optionLabels),
                barcode: '',
                purchase_price: fallback.purchase_price || 0,
                selling_price: fallback.selling_price || 0,
                compare_at_price: fallback.compare_at_price || null,
                stock_quantity: index === 0 ? (fallback.stock_quantity || 0) : 0,
                low_stock_alert: fallback.low_stock_alert || 5,
                unit_id: fallback.unit_id || '',
                purchase_unit_id: fallback.purchase_unit_id || fallback.unit_id || '',
                purchase_unit_factor: fallback.purchase_unit_factor || 1,
                is_active: true,
                attribute_value_ids: ids,
                auto_generated_identity: true,
            });
        });
    }

    function initializeVariationManager() {
        variationAttributeRows.innerHTML = '';
        const selectedByAttribute = new Map();

        variantRows.flatMap((variant) => variant.attribute_value_ids || []).forEach((valueId) => {
            const attribute = catalogAttributes.find((item) =>
                item.values.some((value) => Number(value.id) === Number(valueId))
            );
            if (!attribute) return;

            const values = selectedByAttribute.get(attribute.id) || [];
            if (!values.includes(Number(valueId))) values.push(Number(valueId));
            selectedByAttribute.set(attribute.id, values);
        });

        if (selectedByAttribute.size) {
            selectedByAttribute.forEach((valueIds, attributeId) => addVariationAttributeRow(attributeId, valueIds));
        } else {
            addVariationAttributeRow();
        }
    }

    function addVariant(data = {}) {
        const panel = variantTemplate.content.firstElementChild.cloneNode(true);
        variantBody.appendChild(panel);

        panel.querySelectorAll('[data-field]').forEach((input) => {
            const field = input.dataset.field;

            if (input.multiple) {
                const selected = (data[field] || []).map(String);
                Array.from(input.options).forEach((option) => option.selected = selected.includes(option.value));
            } else if (data[field] !== undefined && data[field] !== null) {
                input.value = ['track_stock', 'is_active'].includes(field)
                    ? ([true, 1, '1', 'true'].includes(data[field]) ? '1' : '0')
                    : data[field];
            }
        });

        if (data.id) {
            panel.id = `variant-${data.id}`;
        }
        if (data.auto_generated_identity) {
            panel.dataset.autoGeneratedIdentity = 'true';
        }

        updateUnitFlow(panel);
        syncConvertedStock(panel, true);
        panel.querySelector('[data-variant-collapse]').addEventListener('click', () => {
            const collapsed = panel.classList.toggle('is-collapsed');
            panel.querySelector('[data-variant-collapse]').setAttribute('aria-expanded', String(!collapsed));
            panel.querySelector('[data-variant-fields]').hidden = collapsed;
        });

        panel.querySelector('.remove-variant').addEventListener('click', () => {
            if (productMode === 'single' && variantBody.children.length === 1) return;
            const combination = combinationKey(panelData(panel).attribute_value_ids);
            if (combination) removedVariantCombinations.add(combination);
            panel.remove();
            reindexVariants();
            updateVariantPresentation();
            syncLegacyFields();
        });

        panel.querySelectorAll('input, select').forEach((input) => {
            input.addEventListener('input', () => {
                if (input.matches('[data-field="name"], [data-field="sku"]')) {
                    panel.dataset.autoGeneratedIdentity = 'false';
                }
                if (input.matches('[data-supplier-stock], [data-field="purchase_unit_factor"]')) {
                    syncConvertedStock(panel);
                }
                updateVariantPresentation();
                syncLegacyFields();
            });
            input.addEventListener('change', () => {
                if (input.matches('[data-customer-unit]')) updateUnitFlow(panel, true);
                if (input.matches('[data-supplier-unit]')) updateUnitFlow(panel);
                if (input.matches('[data-supplier-stock], [data-field="purchase_unit_factor"]')) {
                    syncConvertedStock(panel);
                }
                syncLegacyFields();
            });
        });

        reindexVariants();
        updateVariantPresentation();
        syncLegacyFields();
        if (window.lucide) window.lucide.createIcons();
    }

    function reindexVariants() {
        Array.from(variantBody.children).forEach((panel, index) => {
            panel.querySelectorAll('[data-field]').forEach((input) => {
                const field = input.dataset.field;
                input.name = field === 'attribute_value_ids'
                    ? `variants[${index}][${field}][]`
                    : `variants[${index}][${field}]`;
            });
        });
    }

    function updateVariantPresentation() {
        const isVariantMode = productMode === 'variants';
        const panels = Array.from(variantBody.children);

        hasVariantsInput.checked = isVariantMode;
        variationManager.hidden = !isVariantMode;
        variantTableHeader.hidden = true;
        variantBody.classList.toggle('variation-variant-list', isVariantMode);

        panels.forEach((panel, index) => {
            const name = panel.querySelector('[data-field="name"]').value.trim();
            panel.classList.toggle('single-item-editor', !isVariantMode);
            panel.querySelector('.variant-number').textContent = isVariantMode ? `Variant ${index + 1}` : 'Single Item';
            panel.querySelector('.variant-summary').textContent =
                name && name !== 'Default' ? name : 'Default item';
            panel.querySelector('.remove-variant').hidden = !isVariantMode;
            panel.querySelector('.remove-variant').disabled = !isVariantMode && panels.length === 1;
            panel.querySelector('.variant-name-field').hidden = !isVariantMode;
            panel.querySelector('.variant-sku-field').hidden = !isVariantMode;
            panel.querySelector('.variant-barcode-field').hidden = !isVariantMode;
            updateUnitFlow(panel);
        });

        singleSkuInput.closest('.product-field').hidden = isVariantMode;
        singleBarcodeInput.closest('.product-field').hidden = isVariantMode;
        singleSkuInput.disabled = isVariantMode;
        singleBarcodeInput.disabled = isVariantMode;
    }

    function setProductMode(mode) {
        if (mode === productMode) return;

        if (mode === 'single' && variantBody.children.length > 1) {
            const confirmed = window.confirm('Switching to a single item will remove the additional variant rows from this form. Continue?');
            if (!confirmed) return;

            while (variantBody.children.length > 1) {
                variantBody.lastElementChild.remove();
            }
            reindexVariants();
        }

        if (mode === 'variants') {
            const currentPanel = variantBody.firstElementChild;
            if (currentPanel) variantSeedData = panelData(currentPanel);
        }

        productMode = mode;
        if (mode === 'variants' && !variationAttributeRows.children.length) {
            initializeVariationManager();
        }
        if (mode === 'variants') {
            const hasConfiguredVariants = Array.from(variantBody.children).some((panel) =>
                panelData(panel).attribute_value_ids.length > 0
            );
            if (!hasConfiguredVariants) variantBody.replaceChildren();
        }
        if (mode === 'single' && !variantBody.children.length) {
            addVariant(variantSeedData || variantRows[0] || {});
        }
        updateVariantPresentation();
        syncLegacyFields();
    }

    function syncLegacyFields() {
        const first = variantBody.firstElementChild;
        if (!first) return;

        const value = (field) => first.querySelector(`[data-field="${field}"]`)?.value ?? '';
        document.getElementById('legacy-sku').value = value('sku');
        document.getElementById('legacy-barcode').value = value('barcode');
        document.getElementById('legacy-purchase-price').value = value('purchase_price');
        document.getElementById('legacy-selling-price').value = value('selling_price');
        document.getElementById('legacy-stock').value = value('stock_quantity');
        document.getElementById('legacy-alert').value = value('low_stock_alert');
        if (productMode === 'single') {
            singleSkuInput.value = value('sku');
            singleBarcodeInput.value = value('barcode');
        }
        updateProductSummary();
    }

    hasVariantsInput.addEventListener('change', () => {
        const requestedMode = hasVariantsInput.checked ? 'variants' : 'single';
        setProductMode(requestedMode);
        hasVariantsInput.checked = productMode === 'variants';
    });
    singleSkuInput.addEventListener('input', () => {
        const sku = variantBody.firstElementChild?.querySelector('[data-field="sku"]');
        if (sku) sku.value = singleSkuInput.value;
        syncLegacyFields();
    });
    singleBarcodeInput.addEventListener('input', () => {
        const barcode = variantBody.firstElementChild?.querySelector('[data-field="barcode"]');
        if (barcode) barcode.value = singleBarcodeInput.value;
        syncLegacyFields();
    });
    productNameInput.addEventListener('input', () => {
        if (productMode === 'variants') {
            variantBody.querySelectorAll('.variant-editor').forEach((panel) => {
                if (panel.dataset.autoGeneratedIdentity !== 'true') return;

                const valueIds = panelData(panel).attribute_value_ids.map(Number);
                const optionLabels = valueIds.map((valueId) => {
                    for (const attribute of catalogAttributes) {
                        const value = attribute.values.find((item) => Number(item.id) === valueId);
                        if (value) return value.value;
                    }
                    return '';
                });
                panel.querySelector('[data-field="name"]').value = generatedVariantName(optionLabels);
                panel.querySelector('[data-field="sku"]').value = generatedVariantSku(optionLabels);
            });
            updateVariantPresentation();
            syncLegacyFields();
        }
        updateProductSummary();
    });
    addAttributeButton.addEventListener('click', () => {
        if (!addAttributeButton.disabled) addVariationAttributeRow();
    });
    accordionSections.forEach((section) => {
        section.querySelector('[data-product-accordion-trigger]').addEventListener('click', () => {
            const isOpen = section.classList.toggle('is-open');
            section.querySelector('[data-product-accordion-trigger]').setAttribute('aria-expanded', String(isOpen));
            section.querySelector('[data-product-accordion-panel]').hidden = !isOpen;
        });
    });
    document.getElementById('product-form').addEventListener('submit', (event) => {
        const categoryValue = categorySelect?.querySelector('[data-category-value]');
        if (categorySelect && !categoryValue.value) {
            event.preventDefault();
            categorySelect.classList.add('is-invalid');
            categorySelect.querySelector('[data-category-trigger]').click();
            return;
        }

        if (productMode === 'variants' && !variantBody.children.length) {
            event.preventDefault();
            const note = document.getElementById('variation-manager-note');
            note.hidden = false;
            note.textContent = 'Select an attribute and at least one option to create variant combinations.';
            variationManager.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        syncLegacyFields();
    });
    document.getElementById('product-form').addEventListener('change', (event) => {
        if (!event.target.matches('[data-unit-select]') || event.target.value !== '__add_unit__') {
            return;
        }

        pendingUnitSelect = event.target;
        event.target.value = '';
        resetUnitForm();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('add-unit-modal')).show();
        requestAnimationFrame(() => document.getElementById('new-unit-name').focus());
    });

    const productImageInput = document.querySelector('[data-product-image]');
    const imageDropzone = document.querySelector('[data-image-dropzone]');
    const imagePreview = document.querySelector('[data-image-preview]');

    productImageInput.addEventListener('change', () => {
        const file = productImageInput.files[0];
        if (!file) return;

        const image = document.createElement('img');
        image.alt = 'Selected product preview';
        image.src = URL.createObjectURL(file);
        image.onload = () => URL.revokeObjectURL(image.src);
        imagePreview.replaceChildren(image);
        document.querySelector('[data-image-action]').textContent = 'Replace image';
    });
    ['dragenter', 'dragover'].forEach((eventName) => {
        imageDropzone.addEventListener(eventName, (event) => {
            event.preventDefault();
            imageDropzone.classList.add('is-dragging');
        });
    });
    ['dragleave', 'drop'].forEach((eventName) => {
        imageDropzone.addEventListener(eventName, (event) => {
            event.preventDefault();
            imageDropzone.classList.remove('is-dragging');
        });
    });
    imageDropzone.addEventListener('drop', (event) => {
        const file = event.dataTransfer.files[0];
        if (!file || !file.type.startsWith('image/')) return;
        const transfer = new DataTransfer();
        transfer.items.add(file);
        productImageInput.files = transfer.files;
        productImageInput.dispatchEvent(new Event('change', { bubbles: true }));
    });

    function updateBulkPriceLabels(customerName = '') {
        const label = customerName || 'Unit';
        document.querySelector('[data-tier-from-label]').textContent = `From ${label}`;
        document.querySelector('[data-tier-price-label]').textContent = `Price Per ${label}`;
    }

    function addPriceTier() {
        const row = document.createElement('div');
        row.className = 'bulk-price-row';
        row.innerHTML = `
            <input type="number" class="form-control" min="1" step="1" placeholder="Example: 10" aria-label="Bulk price starting quantity">
            <input type="number" class="form-control" min="0" step="0.01" placeholder="0.00" aria-label="Bulk price per customer unit">
            <button type="button" class="btn btn-icon btn-outline-danger" data-remove-row title="Remove price tier">
                <i data-lucide="trash-2"></i>
            </button>
        `;
        row.querySelector('[data-remove-row]').addEventListener('click', () => row.remove());
        document.querySelector('[data-price-tiers]').append(row);
        window.lucide?.createIcons();
    }

    document.querySelector('[data-add-price-tier]').addEventListener('click', addPriceTier);

    function resetUnitForm() {
        addUnitForm.reset();
        editingUnitId.value = '';
        document.querySelector('[data-unit-form-title]').textContent = 'Add New Unit';
        document.querySelector('[data-unit-submit]').textContent = 'Add Unit';
        document.getElementById('add-unit-error').hidden = true;
    }

    function showUnitManagerMessage(message, type = 'danger') {
        const notice = document.getElementById('unit-manager-message');
        notice.className = `alert alert-${type} py-2 px-3 small`;
        notice.textContent = message;
        notice.hidden = false;
    }

    function clearUnitManagerMessage() {
        document.getElementById('unit-manager-message').hidden = true;
    }

    unitManagerItems.addEventListener('click', (event) => {
        const editButton = event.target.closest('[data-edit-unit]');
        if (!editButton) return;
        const item = editButton.closest('[data-unit-row]');

        editingUnitId.value = item.dataset.unitId;
        document.getElementById('new-unit-name').value = item.dataset.unitName;
        document.getElementById('new-unit-symbol').value = item.dataset.unitSymbol;
        document.getElementById('new-unit-description').value = item.dataset.unitDescription;
        document.querySelector('[data-unit-form-title]').textContent = `Edit ${item.dataset.unitName}`;
        document.querySelector('[data-unit-submit]').textContent = 'Update Unit';
        document.getElementById('new-unit-name').focus();
    });

    document.querySelector('[data-new-unit]').addEventListener('click', resetUnitForm);
    document.querySelector('[data-reset-unit-form]').addEventListener('click', resetUnitForm);

    function renderUnitManagerItem(row, unit) {
        row.replaceChildren();
        row.dataset.unitRow = '';
        row.dataset.unitId = unit.id;
        row.dataset.unitName = unit.name;
        row.dataset.unitSymbol = unit.symbol;
        row.dataset.unitDescription = unit.description;

        const details = document.createElement('span');
        const name = document.createElement('strong');
        const description = document.createElement('small');
        name.textContent = unit.name;
        description.textContent = unit.description;
        details.append(name, description);

        const symbol = document.createElement('span');
        symbol.className = 'badge bg-light text-dark border';
        symbol.textContent = unit.symbol;

        const icon = document.createElement('i');
        icon.dataset.lucide = 'pencil';
        const editButton = document.createElement('button');
        editButton.type = 'button';
        editButton.className = 'unit-manager-edit';
        editButton.dataset.editUnit = '';
        editButton.append(details, symbol, icon);

        const deleteIcon = document.createElement('i');
        deleteIcon.dataset.lucide = 'trash-2';
        const deleteButton = document.createElement('button');
        deleteButton.type = 'button';
        deleteButton.className = 'btn btn-icon btn-outline-danger unit-manager-delete';
        deleteButton.dataset.deleteUnit = '';
        deleteButton.title = 'Delete unit';
        deleteButton.append(deleteIcon);
        row.append(editButton, deleteButton);
    }

    unitManagerItems.addEventListener('click', async (event) => {
        const deleteButton = event.target.closest('[data-delete-unit]');
        if (!deleteButton) return;

        const row = deleteButton.closest('[data-unit-row]');
        const unitName = row.dataset.unitName;
        if (!window.confirm(`Delete ${unitName}? This is only allowed when the unit is not in use.`)) {
            return;
        }

        clearUnitManagerMessage();
        deleteButton.disabled = true;
        const endpoint = @json(route('units.destroy', ['unit' => '__UNIT__'], false))
            .replace('__UNIT__', row.dataset.unitId);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!csrfToken) {
            showUnitManagerMessage('Your session token is unavailable. Refresh the page and try again.');
            deleteButton.disabled = false;
            return;
        }
        let response;

        try {
            response = await fetch(endpoint, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });
        } catch (error) {
            showUnitManagerMessage('The unit could not be deleted. Check your connection and try again.');
            deleteButton.disabled = false;
            return;
        }

        const responseText = await response.text();
        let result = {};
        try {
            result = responseText ? JSON.parse(responseText) : {};
        } catch (error) {
            // Status-based feedback below is more useful than exposing an HTML error response.
        }

        if (!response.ok) {
            const fallbackMessages = {
                403: 'You do not have permission to delete units.',
                419: 'Your session has expired. Refresh the page and try again.',
                422: 'This unit is currently in use and cannot be deleted.',
            };
            showUnitManagerMessage(
                result.message || fallbackMessages[response.status] || 'Unable to delete the unit.'
            );
            deleteButton.disabled = false;
            return;
        }

        document.querySelectorAll('[data-unit-select]').forEach((select) => {
            select.querySelector(`option[value="${row.dataset.unitId}"]`)?.remove();
        });
        variantTemplate.content.querySelectorAll('[data-unit-select]').forEach((select) => {
            select.querySelector(`option[value="${row.dataset.unitId}"]`)?.remove();
        });
        if (editingUnitId.value === row.dataset.unitId) {
            resetUnitForm();
        }
        row.remove();
        showUnitManagerMessage(result.message || 'Unit deleted successfully.', 'success');
    });

    addUnitForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const error = document.getElementById('add-unit-error');
        error.hidden = true;
        const unitId = editingUnitId.value;
        const endpoint = unitId
            ? @json(route('units.update', ['unit' => '__UNIT__'], false)).replace('__UNIT__', unitId)
            : @json(route('units.store', [], false));

        const response = await fetch(endpoint, {
            method: unitId ? 'PUT' : 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(Object.fromEntries(new FormData(addUnitForm))),
        });
        const result = await response.json();

        if (!response.ok) {
            error.textContent = result.message || Object.values(result.errors || {}).flat()[0] || 'Unable to add the unit.';
            error.hidden = false;
            return;
        }

        const unitSelects = [
            ...document.querySelectorAll('[data-unit-select]'),
            ...variantTemplate.content.querySelectorAll('[data-unit-select]'),
        ];
        unitSelects.forEach((select) => {
            let option = Array.from(select.options).find((item) => String(item.value) === String(result.id));
            if (!option) {
                const actionOption = Array.from(select.options).find((item) => item.value === '__add_unit__');
                option = new Option('', result.id);
                select.add(option, actionOption?.previousElementSibling || actionOption || null);
            }
            option.textContent = `${result.name} (${result.symbol})`;
            option.dataset.unitName = result.name;
            option.dataset.unitSymbol = result.symbol;
        });
        if (pendingUnitSelect && !unitId) {
            pendingUnitSelect.value = String(result.id);
            pendingUnitSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }

        let managerItem = unitManagerItems.querySelector(`[data-unit-id="${result.id}"]`);
        if (!managerItem) {
            managerItem = document.createElement('div');
            managerItem.className = 'unit-manager-item';
            unitManagerItems.appendChild(managerItem);
        }
        renderUnitManagerItem(managerItem, result);
        if (window.lucide) window.lucide.createIcons();

        pendingUnitSelect = null;
        resetUnitForm();
    });

    variantRows.forEach(addVariant);
    if (productMode === 'variants') initializeVariationManager();
    updateVariantPresentation();

    if (window.location.hash.startsWith('#variant-')) {
        requestAnimationFrame(() => {
            const itemSection = document.getElementById('item-details-panel').closest('[data-product-accordion]');
            itemSection.classList.add('is-open');
            itemSection.querySelector('[data-product-accordion-trigger]').setAttribute('aria-expanded', 'true');
            itemSection.querySelector('[data-product-accordion-panel]').hidden = false;
            const target = document.querySelector(window.location.hash);
            target?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            target?.querySelector('[data-field="name"]')?.focus();
        });
    }
</script>
