<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Models\Category;
use App\Models\Brand;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturnItem;
use App\Models\SalesReturnItem;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Services\StockLedgerService;
use App\Services\ProductCatalogService;
use App\Services\TenantUsageLimitService;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(['category', 'brand', 'variants.unit', 'variants.attributeValues.attribute'])
            ->latest()
            ->get();

        return view('products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::all();
        $brands = Brand::orderBy('name')->get();
        $attributes = ProductAttribute::with('values')->orderBy('name')->get();
        $units = Unit::where('is_active', true)->orderBy('name')->get();

        return view('products.create', compact('categories', 'brands', 'attributes', 'units'));
    }

    public function edit(Product $product)
    {
        $product->load(['variants.attributeValues', 'images']);
        $categories = Category::all();
        $brands = Brand::orderBy('name')->get();
        $attributes = ProductAttribute::with('values')->orderBy('name')->get();
        $units = Unit::where('is_active', true)->orderBy('name')->get();

        return view('products.edit', compact(
            'product',
            'categories',
            'brands',
            'attributes',
            'units'
        ));
    }

    public function stockLedger(Product $product)
    {
        $product->load(['category', 'variants.unit']);

        $movements = $product->stockMovements()
            ->with('variant.unit')
            ->latest('movement_date')
            ->latest()
            ->paginate(25);

        $movements->getCollection()->transform(function (StockMovement $movement) {
            $movement->display_type = $this->stockMovementLabel($movement->type);
            $movement->reference_url = $this->stockMovementReferenceUrl($movement);

            return $movement;
        });

        return view('products.stock-ledger', compact(
            'product',
            'movements'
        ));
    }

    private function stockMovementLabel(string $type): string
    {
        return match ($type) {
            'opening_stock' => 'Opening Stock',
            'purchase' => 'Purchased from Supplier',
            'purchase_reversal' => 'Purchase Update Reversal',
            'purchase_cancel' => 'Purchase Cancelled',
            'purchase_return' => 'Returned to Supplier',
            'sale' => 'Sold to Customer',
            'sale_cancel' => 'Sale Cancelled',
            'sales_return' => 'Customer Return',
            'stock_adjustment_in' => 'Stock Added Manually',
            'stock_adjustment_out' => 'Stock Reduced Manually',
            default => str($type)->replace('_', ' ')->title()->toString(),
        };
    }

    private function stockMovementReferenceUrl(StockMovement $movement): ?string
    {
        if (! $movement->source_type || ! $movement->source_id) {
            return null;
        }

        return match ($movement->source_type) {
            InvoiceItem::class => $this->invoiceUrl($movement->source_id),
            PurchaseItem::class => $this->purchaseUrl($movement->source_id),
            SalesReturnItem::class => $this->salesReturnInvoiceUrl($movement->source_id),
            PurchaseReturnItem::class => $this->supplierReturnPurchaseUrl($movement->source_id),
            default => null,
        };
    }

    private function invoiceUrl(int $invoiceItemId): ?string
    {
        $invoiceId = InvoiceItem::whereKey($invoiceItemId)->value('invoice_id');

        return $invoiceId ? route('invoices.show', $invoiceId) : null;
    }

    private function purchaseUrl(int $purchaseItemId): ?string
    {
        $purchaseId = PurchaseItem::whereKey($purchaseItemId)->value('purchase_id');

        return $purchaseId ? route('purchases.show', $purchaseId) : null;
    }

    private function salesReturnInvoiceUrl(int $salesReturnItemId): ?string
    {
        $returnItem = SalesReturnItem::with('salesReturn:id,invoice_id')
            ->find($salesReturnItemId);

        return $returnItem?->salesReturn?->invoice_id
            ? route('invoices.show', $returnItem->salesReturn->invoice_id)
            : null;
    }

    private function supplierReturnPurchaseUrl(int $purchaseReturnItemId): ?string
    {
        $returnItem = PurchaseReturnItem::with('purchaseReturn:id,purchase_id')
            ->find($purchaseReturnItemId);

        return $returnItem?->purchaseReturn?->purchase_id
            ? route('purchases.show', $returnItem->purchaseReturn->purchase_id)
            : null;
    }

    public function store(
        StoreProductRequest $request,
        TenantUsageLimitService $usageLimits
    )
    {
        $usageLimits->assertCanCreateProduct(auth()->user()->tenant);

        $data = $request->validated();

        app(ProductCatalogService::class)->create($data);

        return redirect()
            ->route('products.index')
            ->with(
                'success',
                'Product created successfully.'
            );
    }

    public function update(StoreProductRequest $request, Product $product)
    {
        $data = $request->validated();
        app(ProductCatalogService::class)->update($product, $data);

        return redirect()
            ->route('products.index')
            ->with(
                'success',
                'Product updated successfully.'
            );
    }
}
